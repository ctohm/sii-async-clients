<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Commands;

use Illuminate\Console\Command;
use WsdlToPhp\PackageGenerator\ConfigurationReader\GeneratorOptions;
use WsdlToPhp\PackageGenerator\Generator\Generator;

final class GenerateFromWsdlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wsdl2php:generate
                            {origin : the WSDL\'s url}
                            {--namespace=?: namespace for the classes that will be generated. (default "App\\Wsdl")}
                            {--destination=? : physical path for generated classes. (default app/Wsdl)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates WSDLs clients, methods and struct types for a given WSDL service';

    /**
     * @var string[]
     *
     * @psalm-var array{*: string, QueryEstDte: string, QueryEstDteAvanzadoClient: string, WsDTECorreo: string, DTEUpload: string, WsRPETCConsulta: string, RTCAnotEnvio: string, RTCConsultaCesiones: string, WsRegistroReclamoDte: string}
     */
    private static $siiEndpoints = [
        '*' => 'https://palena.sii.cl/DTEWS/%servicio.jws?WSDL',
        'QueryEstDte' => 'https://palena.sii.cl/DTEWS/QueryEstDte.jws?WSDL',
        'QueryEstDteAvanzadoClient' => 'https://palena.sii.cl/DTEWS/services/QueryEstDteAvanzadoClient?WSDL',
        'WsDTECorreo' => 'https://palena.sii.cl/DTEWS/services/wsDTECorreo?WSDL',
        'DTEUpload' => 'https://palena.sii.cl/cgi_dte/UPL/DTEUpload',
        // consultar env?o AEC y estado Cesi?n
        'WsRPETCConsulta' => 'https://palena.sii.cl/DTEWS/services/wsRPETCConsulta?WSDL',
        // enviar AEC
        'RTCAnotEnvio' => 'https://palena.sii.cl/cgi_rtc/RTC/RTCAnotEnvio.cgi',
        'RTCConsultaCesiones' => 'https://palena.sii.cl/cgi_rtc/RTC/RTCConsultaCesiones.cgi',
        'WsRegistroReclamoDte' => 'https://ws1.sii.cl/WSREGISTRORECLAMODTE/registroreclamodteservice?wsdl',

        //listarEventosHistDoc,
    ];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $service = $this->argument('service');
        $slug = \end(\explode('/', \parse_url('https://efactura.dgi.gub.uy:6470/ePrueba/ws_personaGetActEmpresarialPrueba?wsdl', \PHP_URL_PATH)));

        $destination = \sprintf('App\\Wsdl\\%s', $service);
        $namespace = $this->argument('namespace') ?? \sprintf('App\\Wsdl\\%s', $service);
        $url = self::$siiEndpoints[$service] ?? $service;
        $destination = \sprintf('./app/Wsdl/%s', $this->argument('destination') ?? $slug);
        $namespace = \sprintf('App\\Wsdl\\%s', $slug);

        try {
            // Options definition: the configuration file parameter is optional
            $options = GeneratorOptions::instance(/* '/path/file.yml' */);
            $options
                ->setSrcDirname('')
                ->setOrigin($url)
                ->setCategory(GeneratorOptions::VALUE_NONE)
                ->setDestination($destination)
                ->setStandalone(false)
                ->setNamespace($namespace);
            // Generator instantiation
            $generator = new Generator($options);
            // Package generation
            $generator->generatePackage();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
