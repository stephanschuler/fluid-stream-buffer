<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\Stream;

use function count;
use function end;
use function feof;
use function fopen;
use function fputs;
use function fseek;
use function ftell;
use function key;
use function rewind;
use function stream_copy_to_stream;
use function strlen;
use function strpos;
use function substr;

/**
 * A NestedResource works on an internal stack of PHP resource streams.
 * While creating a single target resource stream, the source resource
 * stream is run through until a replace pattern is found. When this
 * happens, the resource stream associated with the found pattern is
 * pushed to the stack and iterated over instead of the previous stream.
 */
class NestedResource
{
    const ONE_MB = 1024 * 1024;
    const MEMORY = 'php://temp/maxmemory:' . self::ONE_MB;
    const WINDOW_SIZE = 7168; // Trying to keep read size below 8192

    /**
     * Stack of open temp data streams. The current is always the one to
     * operate in, the last is always the most outer one.
     *
     * @var resource[]
     */
    private $resources = [];

    /**
     * The array key is the exact string to look for in every $this->resource
     * streams.
     * The corresponding value is the resource stream to use instead.
     *
     * @var array<string, resource>
     */
    private $replace = [];

    /**
     * Huge windows increase peak memory consumption.
     * Tiny windows increase iteration count, which is bad for performance.
     *
     * Since a new resource stream is created for every replace pattern, the
     * optimal window size greatly depends on the number of different items.
     *
     * Notice: Having a huge number of string fragments appended to the same
     * data stream does not hurt in terms of memory.
     *
     * @var int
     */
    private $windowSize = self::WINDOW_SIZE;

    /**
     * Best case scenario: Every replace pattern has exactly the same length
     * as the others. This is the max length of all of them.
     *
     * @var int
     */
    private $maximumLengthOfReplacePatterns = 1;

    /**
     * The read size is the window size plus the maximum pattern length.
     *
     * @var int
     */
    private $readSize = self::WINDOW_SIZE + 1;

    private function __construct($stream)
    {
        $this->resources[] = self::copyStream($stream);
    }

    /**
     * @param string $content
     * @return static
     */
    public static function createFromString(string $content): self
    {
        $stream = self::createNewMemoryStream();
        fputs($stream, $content);
        return new self($stream);
    }

    /**
     * @param resource $stream
     * @return static
     */
    public static function createFromStream($stream): self
    {
        return new self($stream);
    }

    /**
     * Register a new replace pattern.
     * The $pattern string is searched in every stream content as an exact
     * match.
     * The $by resource is used instead.
     *
     * @param string $pattern
     * @param resource $by
     */
    public function replace(string $pattern, $by)
    {
        $this->replace[$pattern] = $by;
        $this->maximumLengthOfReplacePatterns = max(strlen($pattern), $this->maximumLengthOfReplacePatterns);
        $this->readSize = $this->windowSize + $this->maximumLengthOfReplacePatterns;
    }

    /**
     * Reads through all resources iteratively according to replace patterns.
     *
     * @return resource
     */
    public function toResourceStream()
    {
        $result = self::createNewMemoryStream();
        while (count($this->resources)) {
            $source = end($this->resources);
            $tell = ftell($source);
            $window = fread($source, $this->readSize);
            $next = $this->getNextMatching($window);
            if ($next === null) {
                fputs($result, substr($window, 0, $this->windowSize));
                if (!feof($source)) {
                    fseek($source, $tell + $this->windowSize);
                } else {
                    $key = key($this->resources);
                    unset($this->resources[$key]);
                }
            } else {
                list($offset, $pattern, $nested) = $next;
                fputs($result, substr($window, 0, $offset));
                fseek($source, $tell + $offset + strlen($pattern));
                $this->resources[] = self::copyStream($nested);
            }
        }
        rewind($result);
        return $result;
    }

    /**
     * @return resource
     */
    public static function createNewMemoryStream()
    {
        return fopen(self::MEMORY, 'w');
    }

    /**
     * @param resource $source
     * @return resource
     */
    private static function copyStream($source)
    {
        $target = self::createNewMemoryStream();
        $tell = ftell($source);
        rewind($source);
        stream_copy_to_stream($source, $target);
        fseek($source, $tell);
        rewind($target);
        return $target;
    }

    /**
     * @param string $window
     * @return array|null
     */
    private function getNextMatching(string $window): ?array
    {
        foreach ($this->replace as $pattern => $source) {
            $offset = strpos($window, $pattern);
            if ($offset !== false) {
                return [$offset, $pattern, $source];
            }
        }
        return null;
    }
}