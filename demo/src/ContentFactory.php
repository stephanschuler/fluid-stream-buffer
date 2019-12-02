<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\Demo;

use TYPO3Fluid\Fluid\View\TemplateView;
use function md5;
use function sha1;
use function sprintf;

class ContentFactory
{
    public static function createTemplateViewAndFill(int $size = 10000)
    {
        $view = self::setupTemplate();
        $view->assign('items', self::accumulateItems($size));
        return $view;
    }

    public static function setupTemplate(): TemplateView
    {
        $view = new TemplateView();
        $path = $view->getTemplatePaths();
        $path->setTemplateRootPaths([
            __DIR__ . '/../Resources/Private/Templates'
        ]);
        $path->setLayoutRootPaths([
            __DIR__ . '/../Resources/Private/Layouts'
        ]);
        $path->setPartialRootPaths([
            __DIR__ . '/../Resources/Private/Partials'
        ]);
        $path->setFormat('xml');
        return $view;
    }

    protected static function accumulateItems(int $size)
    {
        for ($i = 0; $i < $size; $i++) {
            yield [
                'id' => $i,
                'name' => md5((string)$i),
                'attributes' => (function () use ($i) {
                    for ($j = 0; $j < 100; $j++) {
                        $key = sprintf('%s[%s]', $i, $j);
                        yield $key => sha1($key);
                    }
                })()
            ];
        }
    }
}