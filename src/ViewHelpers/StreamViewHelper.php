<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\ViewHelpers;

use StephanSchuler\FluidStreamBuffer\Stream\Container;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * The stream view helpers renders its child nodes but
 * returns a placeholder instead.
 * The Stream object created during this process is passed
 * through fluid variables so other view helpers can access
 * and fill it.
 */
class StreamViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('as', 'string', '', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $as = (string)$arguments['as'];

        $stream = Container::getCurrentContainer()->createNewStream();
        $variables = $renderingContext->getVariableProvider();

        $variables->add($as, $stream);
        $renderChildrenClosure();
        $variables->remove($as);

        return $stream->getName();
    }
}