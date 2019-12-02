Render huge fluid views to a stream resource
============================================

This is an attempt to render huge fluid views with low memory footprint.

Fluid snippets are passed to PHP resource streams in between to have
them not appear in the result string of the overall view.
Instead, placeholder strings are added to the result string.
A worker called `Container` walks through the result template, replaces
the placeholder strings by the actual contents and passes the combined
result to another resource stream.

Tests show a single fluid result of over 100MB overall can be created
with only 1MB of PHP memory footprint.

```php
<?php

use StephanSchuler\FluidStreamBuffer\Stream\Container;

$view = new TYPO3Fluid\Fluid\View\TemplateView();

$stream = Container::create()
    ->renderView($view)
    ->toResourceStream();

assert(get_resource_type($stream) === 'stream');

print_r(
    fstat(
        $stream
    )
);

/**
 * Array
 * (
 *     [0] => 16777223
 *     [1] => 31874556
 *     [2] => 33152
 *     [3] => 1
 *     [4] => 942230658
 *     [5] => 635458030
 *     [6] => 0
 *     [7] => 116746983
 *     [8] => 1575327606
 *     [9] => 1575327607
 *     [10] => 1575327607
 *     [11] => 4096
 *     [12] => 228024
 *     [dev] => 16777223
 *     [ino] => 31874556
 *     [mode] => 33152
 *     [nlink] => 1
 *     [uid] => 942230658
 *     [gid] => 635458030
 *     [rdev] => 0
 *     [size] => 116746983
 *     [atime] => 1575327606
 *     [mtime] => 1575327607
 *     [ctime] => 1575327607
 *     [blksize] => 4096
 *     [blocks] => 228024
 * )
 */
```