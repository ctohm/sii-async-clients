<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace CTOhm\SiiAsyncClients\RequestClients\AbstractCrawlers;


use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use GuzzleHttp\Cookie\CookieJar;

/**
 * @template T
 */
interface RequestClientInterface
{
    /**
     * { function_description }.
     *
     * @param SiiSignatureInterface  $firmaElectronica  The firma electronica
     */
    public function recreate(SiiSignatureInterface $firmaElectronica): void;

    /**
     * Dumps guzzle history.
     */
    public static function dumpHistory();

    /**
     * Gets the cert files.
     *
     * @return array  array of paths to the cert files
     */
    public static function getCertFiles(): array;

    /**
     * Clears the client and its cookies.
     */
    public   function clear(): void;

    /**
     * Authenticates against the SII.
     *
     * @throws \Exception (description)
     *
     * @return object|null ( description_of_the_return_value )
     */
    public function authOnSii(array $options = ['debug' => false]): CookieJar;

    public function getToken();

    /**
     * { function_description }.
     *
     * @param string  $rut_empresa  The rut empresa
     *
     * @return object  ( description_of_the_return_value )
     */
    public   function selecionaEmpresa($rut_empresa);

    /**
     * Gets the cookie jar.
     *
     * @return \GuzzleHttp\Cookie\CookieJar the cookie jar
     */
    public function getCookieJar(): \GuzzleHttp\Cookie\CookieJar;

    /**
     * Gets the client.
     *
     * @return \GuzzleHttp\Client the client
     */
    public function getClient(): \GuzzleHttp\Client;

    /**
     * Gets the url.
     *
     * @param string $path   The path
     * @param string $prefix The prefix
     *
     * @return string the url
     */
    public static function getUrl(string $path = '', string $prefix = null): string;
}
