<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Server\Runtime\HMR;

/**
 *
 */
final class LoadedFiles implements LoadedFilesInterface
{
    /**
     * @var string[]
     */
    private $files = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var int
     */
    private $index = -1;

    /**
     * @param string $file
     *
     * @return void
     */
    public function addFile(string $file): void
    {
        $this->files[] = $file;
        $this->count++;
    }

    /**
     * @return void
     */
    public function clear():void
    {
        $this->files = [];
        $this->count = 0;
        $this->index = -1;
    }

    /**
     * Return the current element
     *
     * @link  https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        if ($this->index > -1 && $this->index < $this->count) {
            return $this->files[$this->index];
        }

        return null;
    }

    /**
     * Move forward to next element
     *
     * @link  https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * Return the key of the current element
     *
     * @link  https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        if ($this->index > -1 && $this->index < $this->count) {
            return $this->index;
        }

        return null;
    }

    /**
     * Checks if current position is valid
     *
     * @link  https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return ($this->index > -1 && $this->index < $this->count);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        if ($this->count > 0) {
            $this->index = 0;

            return;
        }

        $this->index = -1;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->files;
    }
}
