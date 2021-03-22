<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use Symfony\Component\DomCrawler\Crawler;

class DteWsClient extends SiiAuthClient
{
    public static $debug = true;
    /**
     * Gets the url.
     *
     * @param string $path   The path
     * @param null|string $prefix The prefix
     *
     * @return string the url
     */
    public static function getUrl(string $path = '', string $prefix = null): string
    {
        //return sprintf('%s/%s', self::BASE_URL, $path);
        return \sprintf('%s/%s', $prefix ?? self::BASE_URL, $path);
    }
    /**
     * { function_description }.
     *
     * @param string $rut_empresa  The rut empresa
     * @param string  $FEC_DESDE    The fec desde
     * @param string  $FEC_HASTA    The fec hasta
     *
     * @return Crawler|array<array-key, mixed>  ( description_of_the_return_value )
     */
    public   function listaDocumentosEmitidos(
        $rut_empresa,
        $FEC_DESDE = '',
        $FEC_HASTA = ''
    ) {
        $docsEmitidosPath = \sprintf(
            '%s/%s.html',
            self::$tempFolder,
            'docsEmitidos'
        );
        $dte_url = self::getUrl('mipeAdminDocsEmi.cgi');

        $this->authOnSii();
        $this->selecionaEmpresa($rut_empresa);

        $response = $this->sendSiiRequest(
            'GET',
            $dte_url,
            [
                'headers' => [],
                //'debug'   => true,
                'query'   => [
                    'RUT_RECP'  => '',
                    'FOLIO'     => '',
                    'RZN_SOC'   => '',
                    'FEC_DESDE' => $FEC_DESDE ?? '',
                    'FEC_HASTA' => $FEC_HASTA ?? '',
                    'TPO_DOC'   => '',
                    'ESTADO'    => '',
                    'ORDEN'     => '',
                    'NUM_PAG'   => 1,
                ],
            ]
        );

        $docsEmitidos = tap($response->getBody()->getContents(), fn ($resp) => dd($resp));

        //$user_id = self::$firmaElectronica->user_id;
        $crawler = new Crawler($docsEmitidos);

        try {
            // Emisor o Receptor
            $tablaHead = $crawler->filter('#tablaDatos > thead > tr > th')
                ->eq(1)->text();
            $parseTablaDatos = static function (
                $node,
                $index
            ) use ($tablaHead) {
                return self::parseTablaDatos($node, $index, $tablaHead);
            };

            $tablaDatos = $crawler->filter('#tablaDatos > tbody > tr')
                ->each($parseTablaDatos);

            return $tablaDatos;
        } catch (\Exception $e) {
            debuglog()->info($e);

            return [];
        }
    }

    /**
     * { function_description }.
     *
     * @param string $rut_empresa  The rut empresa
     * @param string  $FEC_DESDE    The fec desde
     * @param string  $FEC_HASTA    The fec hasta
     *
     * @return Crawler|array<array-key, mixed>  ( description_of_the_return_value )
     */
    public static function listaDocumentosRecibidos(
        $rut_empresa,
        $FEC_DESDE = '',
        $FEC_HASTA = ''
    ) {
        $docsEmitidosPath = \sprintf(
            '%s/%s.html',
            self::$tempFolder,
            'mipeAdminDocsRcp'
        );
        $dte_url = self::getUrl('mipeAdminDocsRcp.cgi');

        self::authOnSii();
        self::selecionaEmpresa($rut_empresa);

        $response = self::sendSiiRequest(
            'GET',
            $dte_url,
            [
                'headers' => [],
                //'debug'   => true,
                'query'   => [
                    'RUT_EMI'   => '',
                    'FOLIO'     => '',
                    'RZN_SOC'   => '',
                    'FEC_DESDE' => $FEC_DESDE ?? '',
                    'FEC_HASTA' => $FEC_HASTA ?? '',
                    'TPO_DOC'   => '',
                    'ESTADO'    => '',
                    'ORDEN'     => '',
                    'NUM_PAG'   => 1,
                ],
            ]
        );

        $docsEmitidos = $response->contents;

        $user_id = self::$firmaElectronica->user_id;
        $crawler = new Crawler($docsEmitidos);

        try {
            // Emisor o Receptor
            $tablaHead = $crawler->filter('#tablaDatos > thead > tr > th')
                ->eq(1)->text();
            $parseTablaDatos = static function (
                $node,
                $index
            ) use ($tablaHead) {
                return self::parseTablaDatos($node, $index, $tablaHead);
            };

            $tablaDatos = $crawler->filter('#tablaDatos > tbody > tr')
                ->each($parseTablaDatos);

            return $tablaDatos;
        } catch (\Exception $e) {
            debuglog()->info($e->getMessage());

            return [];
        }
    }

    /**
     * { function_description }.
     *
     * @param Crawler  $node       The node
     * @param int                                $index      The index
     * @param string                         $tablaHead  The tabla head
     *
     * @return array<array-key, mixed>  ( description_of_the_return_value )
     */
    private static function parseTablaDatos(
        Crawler $node,
        int $index,
        string $tablaHead = 'Receptor'
    ) {
        //kdump(['parseTablaDatos' => $index]);
        $rut_empresa = self::$rut_empresa;
        $user_id = self::$firmaElectronica->user_id;
        $tds = $node->filter('td');
        $tipo_documento_glosa = $tds->eq(3)->text();
        $tipo_doc = '00';

        if (\array_key_exists($tipo_documento_glosa, self::$tipos_doc_reverso)) {
            $tipo_doc = self::$tipos_doc_reverso[$tipo_documento_glosa];
        }

        $folio = $tds->eq(4)->text();
        $rut_contraparte = \App\Models\Empresa::checkAndInsertEmpresa(
            /*$rut_empresa */
            $tds->eq(1)->text(),
            '',
            /*$razon_social*/
            $tds->eq(2)->text()
        );
        //kdump($tablaHead, $rut_contraparte);

        $dte = [
            'rut_emisor'     => 'Receptor' === $tablaHead ? $rut_empresa :
                $rut_contraparte,
            'tipo_documento' => (int) $tipo_doc,
            'folio'          => $folio,
        ];
        $id_doc = \implode('_', $dte);
        $dte = \array_merge($dte, [
            'id'           => $id_doc,
            'user_id'      => $user_id,
            'rut_receptor' => 'Receptor' === $tablaHead ? $rut_contraparte :
                $rut_empresa,
        ]);
        debuglog()->info($dte);
        $dte['fechaEmision'] = $tds->eq(5)->text();
        $dte['mntTotal'] = $tds->eq(6)->text();
        $dte['estado'] = $tds->eq(7)->text();

        if (34 === intval($dte['tipo_documento'])) {
            $dte['mntNeto'] = 0;
            $dte['mntExento'] = $dte['mntTotal'];
            $dte['mntIva'] = 0;
        } elseif (33 === intval($dte['tipo_documento'])) {
            $dte['mntNeto'] = (int) ($dte['mntTotal'] / 1.19);
            $dte['mntIva'] = $dte['mntTotal'] - $dte['mntNeto'];
            $dte['mntExento'] = 0;
        }

        return $dte;
    }
}
