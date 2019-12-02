<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\Stream;

use TYPO3Fluid\Fluid\View\ViewInterface;
use function assert;

/**
 * The container renders a single view.
 * While doing so, the very container instance can be retrieved by
 * a static method call, so streams can be attached to it from
 * within e.g. a view helper.
 */
class Container
{
    /**
     * Depending on the current rendering state, this is either
     * the Container instance currently in render mode or null.
     *
     * @var self
     */
    private static $instance;

    /**
     * All streams attached to this very container during the
     * current rendering run.
     *
     * @var Stream[]
     */
    private $streams = [];

    private function __construct()
    {
    }

    public static function create(): Container
    {
        return new self();
    }

    /**
     * While rendering the given view object, the current container
     * is made accessible. Global state is cleared when this run
     * is finished.
     *
     * @param ViewInterface $view
     * @return NestedResource
     */
    public function renderView(ViewInterface $view): NestedResource
    {
        try {
            $previousInstance = self::$instance;
            self::$instance = $this;
            return $this->renderViewToNestedResource($view);
        } finally {
            $this->streams = [];
            self::$instance = $previousInstance;
        }
    }

    /**
     * This container is accessible only while in rendering mode
     *
     * @return Container
     */
    public static function getCurrentContainer(): Container
    {
        return self::$instance;
    }

    /**
     * Append an other stream to the current container
     *
     * @return Stream
     */
    public function createNewStream(): Stream
    {
        $stream = Stream::create();
        $this->streams[] = $stream;
        return $stream;
    }

    /**
     * While rendering, all output is passed to a Nested Resource object,
     * as well as all stream data appearing during that time.
     *
     * @param ViewInterface $view
     * @return NestedResource
     */
    private function renderViewToNestedResource(ViewInterface $view): NestedResource
    {
        $resource = NestedResource::createFromString(
            $view->render()
        );
        foreach ($this->streams as $stream) {
            assert($stream instanceof Stream);
            $resource->replace(
                $stream->getName(),
                $stream->getContent()
            );
        }
        return $resource;
    }
}