<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\Stream;

/**
 * A Stream object is meant to associate a PHP resource stream
 * with a unique placeholder string.
 * Streams should not leak memory, so there's no static component
 * to it.
 */
class Stream
{
    /**
     * The php resource stream holding the actual data.
     *
     * @var resource
     */
    private $stream;

    /**
     * A unique placeholder the data can by identified by.
     *
     * @var string
     */
    private $name;

    private function __construct()
    {
        $this->stream = NestedResource::createNewMemoryStream();
        $this->name = '[::' . uniqid() . ']';
    }

    public static function create()
    {
        return new self();
    }

    /**
     * Add some data to the resource stream
     *
     * @param string $content
     */
    public function push(string $content)
    {
        fputs($this->stream, $content);
    }

    /**
     * Every stream has a unique name within a PHP run
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Content is referred to as PHP resource stream, not as a string.
     * This allows this object to hold more data than the memory limit
     * is set to.
     *
     * @return resource
     */
    public function getContent()
    {
        rewind($this->stream);
        return $this->stream;
    }
}