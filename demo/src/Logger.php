<?php
declare(strict_types=1);

namespace StephanSchuler\FluidStreamBuffer\Demo;

use function explode;
use function is_null;
use function join;
use function microtime;
use function round;
use function str_pad;

final class Logger
{
    const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    const DOTTED_LINE = '................................................................................................................................';

    private function __construct()
    {
    }

    public static function log(string ...$datas)
    {
        echo self::DOTTED_LINE . PHP_EOL;
        foreach ($datas as $i => $data) {
            if ($i === 0) {
                echo self::time();
                echo ': ';
                echo str_pad($data, 80, ' ', STR_PAD_RIGHT);
                echo self::memory() . PHP_EOL;
            } else {
                echo self::DOTTED_LINE . PHP_EOL;
                echo $data . PHP_EOL;
            }
        }
        echo self::DOTTED_LINE . PHP_EOL;
    }

    public static function bytesToHumanReadable(float $mem): string
    {
        $units = self::UNITS;
        while ($mem >= 1024) {
            $mem /= 1024;
            array_shift($units);
        }
        return self::padFloat($mem, 2) . current($units);
    }

    private static function time(): string
    {
        static $time;
        if (is_null($time)) {
            $time = microtime(true);
        }
        $now = microtime(true);
        $diff = self::padFloat($now - $time, 3);
        $time = $now;
        return $diff . 's';
    }

    private static function memory(): string
    {
        $peak = self::bytesToHumanReadable(memory_get_peak_usage());
        $current = self::bytesToHumanReadable(memory_get_usage());

        return sprintf('%s peak (%s cur)', $peak, $current);
    }

    private static function padFloat(float $value, int $precission): string
    {
        $values = explode('.', (string)round($value, $precission));
        $values[0] = str_pad($values[0] ?? '0', $precission, ' ', STR_PAD_LEFT);
        $values[1] = str_pad($values[1] ?? '0', $precission, '0', STR_PAD_RIGHT);
        return join('.', $values);
    }
}