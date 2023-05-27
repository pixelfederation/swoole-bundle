<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Log;

class AccessLogFormatter implements AccessLogFormatterInterface
{
    /**
     * @see http://httpd.apache.org/docs/2.4/mod/mod_log_config.html#examples
     */
    public const FORMAT_COMMON = '%h %l %u %t "%r" %>s %b';
    public const FORMAT_COMMON_TIME = '%a %l %u %t "%r" %>s %O "%{Referer}i" "%{User-Agent}i" %T %D';
    public const FORMAT_COMMON_VHOST = '%v %h %l %u %t "%r" %>s %b';
    public const FORMAT_COMBINED = '%h %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"';
    public const FORMAT_REFERER = '%{Referer}i -> %U';
    public const FORMAT_AGENT = '%{User-Agent}i';

    /**
     * @see https://httpd.apache.org/docs/2.4/logs.html#virtualhost
     */
    public const FORMAT_VHOST = '%v %l %u %t "%r" %>s %b';

    /**
     * @see https://anonscm.debian.org/cgit/pkg-apache/apache2.git/tree/debian/config-dir/apache2.conf.in#n212
     */
    public const FORMAT_COMMON_DEBIAN = '%h %l %u %t “%r” %>s %O';
    public const FORMAT_COMBINED_DEBIAN = '%h %l %u %t “%r” %>s %O “%{Referer}i” “%{User-Agent}i”';
    public const FORMAT_VHOST_COMBINED_DEBIAN = '%v:%p %h %l %u %t “%r” %>s %O “%{Referer}i” “%{User-Agent}i"';

    public function __construct(private string $format = self::FORMAT_COMMON)
    {
    }

    /**
     * Transform a log format to the final string to log.
     */
    public function format(AccessLogDataMap $map): string
    {
        $message = $this->replaceConstantDirectives($this->format, $map);

        return $this->replaceVariableDirectives($message, $map);
    }

    private function replaceConstantDirectives(
        string $format,
        AccessLogDataMap $map
    ): string {
        return \preg_replace_callback(
            '/%(?:[<>])?([%aABbDfhHklLmpPqrRstTuUvVXIOS])/',
            /* @phpstan-ignore-next-line */
            static function (array $matches) use ($map) {
                return match ($matches[1]) {
                    '%' => '%',
                    'a' => $map->getClientIp(),
                    'A' => $map->getLocalIp(),
                    'B' => $map->getResponseBodySize('0'),
                    'b' => $map->getResponseBodySize('-'),
                    'D' => $map->getRequestDuration('ms'),
                    'f' => $map->getFilename(),
                    'h' => $map->getRemoteHostname(),
                    'H' => $map->getProtocol(),
                    'm' => $map->getMethod(),
                    'p' => $map->getPort('canonical'),
                    'q' => $map->getQuery(),
                    'r' => $map->getRequestLine(),
                    's' => $map->getStatus(),
                    't' => $map->getRequestTime('begin:%d/%b/%Y:%H:%M:%S %z'),
                    'T' => $map->getRequestDuration('s'),
                    'u' => $map->getRemoteUser(),
                    'U' => $map->getPath(),
                    'v' => $map->getHost(),
                    'V' => $map->getServerName(),
                    'I' => $map->getRequestMessageSize('-'),
                    'O' => $map->getResponseMessageSize('-'),
                    'S' => $map->getTransferredSize(),
                    default => '-',
                };
            },
            $format
        );
    }

    private function replaceVariableDirectives(
        string $format,
        AccessLogDataMap $map
    ): string {
        return \preg_replace_callback(
            '/%(?:[<>])?{([^}]+)}([aCeinopPtT])/',
            static function (array $matches) use ($map) {
                return match ($matches[2]) {
                    'a' => $map->getClientIp(),
                    'C' => $map->getCookie($matches[1]),
                    'e' => $map->getEnv($matches[1]),
                    'i' => $map->getRequestHeader($matches[1]),
                    'o' => $map->getResponseHeader($matches[1]),
                    'p' => $map->getPort($matches[1]),
                    't' => $map->getRequestTime($matches[1]),
                    'T' => $map->getRequestDuration($matches[1]),
                    default => '-',
                };
            },
            $format
        );
    }
}
