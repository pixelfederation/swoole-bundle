<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Bridge\Symfony\Bundle\Exception\CouldNotCreatePidFileException;
use K911\Swoole\Bridge\Symfony\Bundle\Exception\PidFileNotAccessibleException;

use function K911\Swoole\get_object_property;

use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServerStartCommand extends AbstractServerStartCommand
{
    private bool $openCloseOutput = false;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Run Swoole HTTP server in the background.')
            ->addOption('pid-file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->getProjectDirectory().'/var/swoole.pid')
            ->addOption('open-console', null, InputOption::VALUE_NONE, 'Whatever open console output, to sent logs from php to php://stdout, php://stderr, to docker /dev/stdout, /std/error.')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        /** @var null|string $pidFile */
        $pidFile = $input->getOption('pid-file');
        $serverConfiguration->daemonize($pidFile);

        $openConsole = $input->getOption('open-console');
        Assertion::boolean($openConsole);
        $this->openCloseOutput = $openConsole;

        parent::prepareServerConfiguration($serverConfiguration, $input);
    }

    /**
     * {@inheritdoc}
     */
    protected function startServer(HttpServerConfiguration $serverConfiguration, HttpServer $server, SymfonyStyle $io): void
    {
        $pidFile = $serverConfiguration->getPidFile();

        if (!touch($pidFile)) {
            throw PidFileNotAccessibleException::forFile($pidFile);
        }

        if (!is_writable($pidFile)) {
            throw CouldNotCreatePidFileException::forPath($pidFile);
        }

        if (false === $this->openCloseOutput) {
            $this->closeSymfonyStyle($io);
        }

        $server->start();
    }

    private function closeSymfonyStyle(SymfonyStyle $io): void
    {
        $output = get_object_property($io, 'output', OutputStyle::class);
        if ($output instanceof ConsoleOutput) {
            $this->closeConsoleOutput($output);
        } elseif ($output instanceof StreamOutput) {
            $this->closeStreamOutput($output);
        }
    }

    /**
     * Prevents usage of php://stdout or php://stderr while running in background.
     */
    private function closeConsoleOutput(ConsoleOutput $output): void
    {
        fclose($output->getStream());

        /** @var StreamOutput $streamOutput */
        $streamOutput = $output->getErrorOutput();

        $this->closeStreamOutput($streamOutput);
    }

    private function closeStreamOutput(StreamOutput $output): void
    {
        $output->setVerbosity(\PHP_INT_MIN);
        fclose($output->getStream());
    }
}
