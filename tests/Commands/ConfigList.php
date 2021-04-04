<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Commands;

use ArrayAccess;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;
use LaravelZero\Framework\Commands\Command;
use Tests\Helpers\ExceptionHelper;

class ConfigList extends Command
{
    protected $name = 'config:list';

    protected $signature = 'config:list
                             {key?  : config entry to display}
                             {--refresh= : recreates config using $_ENV["APP_ENV"]}                             ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'list config settings';

    /**
     * Create a new config cache command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \LogicException
     */
    public function handle(): void
    {
        /* $this->call('config:clear');

         $config = $this->getFreshConfiguration();

         $configPath = $this->laravel->getCachedConfigPath();

         $this->files->put(
             $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
         );*/
        $key = $this->argument('key') ?? 'database';

        $config = config($key);

        dump($config);
    }

    /**
     * @param array|ArrayAccess|\Illuminate\Support\Collection|Jsonable|JsonSerializable|string $content
     */
    public function infoJson(
        $content
    ): void {
        if (!\is_scalar($content)) {
            $content = \json_encode(
                $content,
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
            );
        }
        $this->info($content);
    }

    /**
     * @param array|ArrayAccess|\Illuminate\Support\Collection|Jsonable|JsonSerializable|string $content
     */
    public function warnJson(
        $content
    ): void {
        if (!\is_scalar($content)) {
            $content = \json_encode(
                $content,
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES
            );
        }
        $this->comment($content);
    }

    /**
     * Undocumented function.
     */
    public function printException(\Throwable $e): void
    {
        $this->warn(\json_encode(ExceptionHelper::normalizeException($e), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }

    public function getOutput()
    {
        return $this->output();
    }

    /**
     * Receives a generic path and transforms it to its analogue path for use as argument to \Storage facade.
     *
     * e.g. with testsOutput drive
     * - $this->pathToStoragePath('tests/_data/file.xml','testsOutput') => '_data/file.xml'
     * \Storage::disk('testsOutput')->( '_data/file.xml') points to storage/tests/_data/file.xml
     *
     * e.g. with siiXML drive
     * - $this->pathToStoragePath('/home/user/project_root/storage/siiXML/file.xml','siiXML') => 'file.xml'
     * \Storage::disk('siiXML')->( 'file.xml') points to storage/siiXML/file.xml
     *
     * @param string $originalPath The original path to parse
     * @param string $drive        The drive
     *
     * @return string relative path to file
     */
    public function pathToRelativeStoragePath(
        string $originalPath,
        string $drive = 'siiXML'
    ): string {
        if ('testsOutput' === $drive) {
            $drive = 'tests';
        }

        return \str_replace(
            [
                storage_path($drive),
                storage_path(),
            ],
            '',
            \str_replace('storage/storage', 'storage', storage_path($originalPath))
        );
    }

    /**
     * Undocumented function.
     */
    public function saveToStoragePath(
        string $originalPath,
        string $fileContent,
        string $drive = 'siiXML'
    ) {
        $normalizedOutputPath = \str_replace(['.input', '.xml'], ['', '.output.xml'], $this->pathToRelativeStoragePath($originalPath, $drive));
        $dirname = \dirname($normalizedOutputPath);

        if (Storage::disk($drive)->makeDirectory($dirname)) {
            Storage::disk($drive)->put(
                $normalizedOutputPath,
                $fileContent
            );

            $this->line(\sprintf('normalizedOutputPath: %s', $normalizedOutputPath));

            return null;
        }

        $this->error(\sprintf('Could not save to normalizedOutputPath: %s', $dirname));

        return null;
    }

    public function printSpecialCharsTable(): void
    {
        $tablechecks = [];

        foreach (\explode(' ', '✔ ✓ ✓ ✅ ✘ ❌ ✖ ✕ ❎ ☓ ✗ ó') as $char) {
            $chr_ord = \mb_ord($char);
            $hexchar = \dechex($chr_ord);

            $mb_strlen = \mb_strlen($char);

            $hexstr = \bin2hex($char);
            $unescapedPrint = \mb_chr($chr_ord);
            $twobytes = \bin2hex($unescapedPrint);
            $fixedPrint = \hex2bin($twobytes);
            $isUtf8 = \mb_check_encoding(
                $unescapedPrint,
                'UTF-8'
            );
            $tablechecks[] = [
                $chr_ord,
                $hexchar,
                $hexstr,
                $unescapedPrint,
                $fixedPrint,
                $mb_strlen,
            ];
        }
        $this->table(
            ['CHR', 'hex', 'bin2hex', 'latin1', 'utf8', 'mb_strlen'],
            $tablechecks
        );
    }
}
