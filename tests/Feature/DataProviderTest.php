<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\BaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class DataProviderTest extends BaseTestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function xtestRunsFirst(
        string $filename,
        float $loaded_at,
        bool $exists
    ): void {
        $run_at = \microtime(true);
        $this->writeWhenRun('Test 1', $filename, $loaded_at, $run_at, '#3A3', '#CC4');
        self::assertFalse($exists);
    }

    /**
     * @dataProvider generatorProvider
     */
    public function testRunsSecond(
        string $filename,
        float $loaded_at,
        callable $existsFn
    ): void {
        $exists = $existsFn();
        $run_at = \microtime(true);
        self::assertFalse($exists);
        $this->writeWhenRun('Test 2', $filename, $loaded_at, $run_at, '#6C6', '#AA1');
        //   self::assertGreaterThan($loaded_at, $run_at);
    }

    public function generatorProvider()
    {
        $files = [
            'file/11111.xml',
            'file/22222.xml',
            'file/33333.xml',
            'file/44444.xml',
            'file/55555.xml',
            'file/66666.xml',
        ];

        foreach ($files as $filename) {
            yield [$filename, \microtime(true), static fn () => Storage::exists($filename)];
        }
    }

    public function additionProvider()
    {
        $files = [
            'file/11111.xml',
            'file/22222.xml',
            'file/33333.xml',
            'file/44444.xml',
            'file/55555.xml',
            'file/66666.xml',
        ];

        return get_files($files);
    }

    public function anotherProvider()
    {
        return static function () {
            foreach ([
                ['file/11111.xml', \microtime(true), Storage::exists('file/11111.xml')],
                ['file/22222.xml', \microtime(true), Storage::exists('file/22222.xml')],
                ['file/33333.xml', \microtime(true), Storage::exists('file/33333.xml')],
                ['file/44444.xml', \microtime(true), Storage::exists('file/44444.xml')],
                ['file/55555.xml', \microtime(true), Storage::exists('file/55555.xml')],
                ['file/66666.xml', \microtime(true), Storage::exists('file/66666.xml')],
            ] as $file) {
                yield $file;
            }
        };
    }
}
