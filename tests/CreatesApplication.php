<?php

declare(strict_types=1);

/**
 * DBThor Cesion 1.11.0
 */

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Storage;
use Pest\Datasets;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

trait CreatesApplication
{
    protected static ?ConsoleOutput $consoleOutput = null;
    protected static int $counter = 0;

    public function getConsoleOutput()
    {
        if (self::$consoleOutput === null) {
            self::$consoleOutput = new ConsoleOutput();
        }
        return self::$consoleOutput;
    }
    /**
     * Format input to textual table.
     *
     * @param  array  $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  string  $tableStyle
     * @param  array  $columnStyles
     * @return void
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        $table = new Table($this->getConsoleOutput());

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }
    public function writeWhenRun($testName, $email, $loaded_at, $ran_at, $loaded_color, $ran_color)
    {
        $this
            ->getConsoleOutput()
            ->writeln(
                sprintf(
                    '%s Email <options=bold>%s</> loaded at <fg=%s>%s</>. Test ran at <fg=%s>%s</> (%fs)',
                    $testName,
                    $email,
                    $loaded_color,
                    $loaded_at,
                    $ran_color,
                    $ran_at,
                    floatval($ran_at) - floatval($loaded_at)
                )
            );
        return $this;
    }
}
