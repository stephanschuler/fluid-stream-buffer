<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\ViewHelpers\Stream;

use StephanSchuler\FluidStreamBuffer\Stream\Stream;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use function assert;

/**
 * The content view helper renders its child nodes passes its
 * result to a given Stream object and doesn't return a string
 * at all.
 */
class ContentViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('into', Stream::class, '', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $container = $arguments['into'];
        assert($container instanceof Stream);

        $container->push(
            $renderChildrenClosure()
        );

        return '';
    }
}