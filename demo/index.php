<?php
declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use StephanSchuler\FluidStreamBuffer\Demo\Logger;
use StephanSchuler\FluidStreamBuffer\Demo\ContentFactory;
use StephanSchuler\FluidStreamBuffer\Stream\Container;
use TYPO3Fluid\Fluid\View\TemplateView;

set_time_limit(600);

$autoload = require __DIR__ . '/../vendor/autoload.php';
assert($autoload instanceof ClassLoader);
$autoload->addPsr4('StephanSchuler\\FluidStreamBuffer\\Demo\\', __DIR__ . '/src');

$view = ContentFactory::createTemplateViewAndFill(10000);
assert($view instanceof TemplateView);

Logger::log('Going to render');

$stream = Container::create()
    ->renderView($view)
    ->toResourceStream();

Logger::log('Rendered, passed to internal stream.');

$outputBuffer = fopen('php://temp/maxmemory:128', 'w');
stream_copy_to_stream($stream, $outputBuffer);

Logger::log('Internal stream passed to memory stream.');

Logger::log('Stream size: ' . Logger::bytesToHumanReadable(fstat($stream)['size']));

Logger::log('Start extracting');
$var = stream_get_contents($outputBuffer);
fclose($outputBuffer);

Logger::log('Done');