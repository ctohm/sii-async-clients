<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Util;

class Misc
{
    /**
     * Comprueba si el rut ingresado es válido.
     *
     * @param string  $rut
     * @param string  $dv
     * @param bool $throw  pass true to throw and exception on invalid rut instead of returning false
     * @psalm-return list<string>
     * @return string[]  the resulting array means [numero: string, dv:string, rut_recibido: string]|bool
     * @throws InvalidRutException if RUT is invalid
     */
    public static function validaRut(string $rut, string $dv = '', $throw = true)
    {
        $rut = \preg_replace(
            '/[^k0-9]/i',
            '',
            \mb_strtolower($rut)
        );
        $numero = $rut;

        if ('' === $dv) {
            $dv = \mb_substr(
                $rut,
                -1
            );
            $numero = \mb_substr($rut, 0, \mb_strlen($rut) - 1);
        }
        $i = 2;
        $suma = 0;
        $dv = \mb_strtoupper((string) $dv);
        foreach (\array_reverse(\mb_str_split($numero)) as $v) {
            if (8 === $i) {
                $i = 2;
            }
            $suma += $v * $i;
            $i++;
        }
        $dvr = 11 - ($suma % 11);

        if (11 === $dvr) {
            $dvr = 0;
        }

        if (10 === $dvr) {
            $dvr = 'K';
        }
        $rut_recibido = \implode('-', [
            $numero, \mb_strtoupper($dv),
        ]);
        $rut_esperado = \implode('-', [
            $numero, (string) $dvr,
        ]);
        if ((string) $rut_recibido !== $rut_esperado) {
            debuglog()->info([
                'msg' => 'RUT inválido',
                'input' => [
                    $numero, $dv,
                ], 'esperado' => [
                    $numero, $dvr,
                ],
            ]);
            throw new InvalidRutException('RUT inválido: ' . $rut_recibido . ' DV esperado: ' . $dvr, 1);
        }

        return [
            $numero,  $dv, $rut_recibido,
        ];
    }
}
