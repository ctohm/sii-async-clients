<?php

declare(strict_types=1);

/**
 * DBThor Cesion 1.11.0
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
    public function testEmpty()
    {
        $stack = [];
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * @depends testEmpty
     */
    public function testPush(array $stack)
    {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack) - 1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }

    /**
     * @depends testPush
     */
    public function testPop(array $stack)
    {
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }
    /**
     * @dataProvider additionProvider
     */
    public function testRunsFirst(
        string $email,
        float $loaded_at
    ): array {
        $run_at = microtime(true);
        //  $this->writeWhenRun('Test 1', $email, $loaded_at, $run_at, '#090', '#AA1');
        $this->assertGreaterThan($loaded_at, $run_at);
        return [$email, $loaded_at];
    }
    /**
     * @depends testRunsFirst
     * @dataProvider anotherProvider
     */
    public function testRunsSecond(
        string $email,
        float $loaded_at,
        bool $exists
    ): void {
        $run_at = microtime(true);
        $this->assertFalse($exists);
        // $this->writeWhenRun('Test 2', $email, $loaded_at, $run_at, '#3A3', '#CC4');
        $this->assertGreaterThan($loaded_at, $run_at);;
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
        return [
            ['file/11111.xml', microtime(true), Storage::exists('file/11111.xml')],
            ['file/22222.xml', microtime(true), Storage::exists('file/22222.xml')],
            ['file/33333.xml', microtime(true), Storage::exists('file/33333.xml')],
            ['file/44444.xml', microtime(true), Storage::exists('file/44444.xml')],
            ['file/55555.xml', microtime(true), Storage::exists('file/55555.xml')],
            ['file/66666.xml', microtime(true), Storage::exists('file/66666.xml')],
        ];
    }
}
