<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests;

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

trait CreatesApplication
{
    protected static ?ConsoleOutput $consoleOutput = null;

    protected static int $counter = 0;

    public function getConsoleOutput()
    {
        if (null === self::$consoleOutput) {
            self::$consoleOutput = new ConsoleOutput();
        }

        return self::$consoleOutput;
    }

    /**
     * Format input to textual table.
     *
     * @param array                                         $headers
     * @param array|\Illuminate\Contracts\Support\Arrayable $rows
     * @param string                                        $tableStyle
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = []): void
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
                \sprintf(
                    '%s Email <options=bold>%s</> loaded at <fg=%s>%s</>. Test ran at <fg=%s>%s</> (%fs)',
                    $testName,
                    $email,
                    $loaded_color,
                    $loaded_at,
                    $ran_color,
                    $ran_at,
                    (float) $ran_at - (float) $loaded_at
                )
            );

        return $this;
    }
}
