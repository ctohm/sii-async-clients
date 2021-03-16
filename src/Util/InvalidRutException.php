<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace CTOhm\SiiAsyncClients\Util;

use Exception;

class InvalidRutException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report(): void
    {
        kdump(['message' => $this->getMessage()]);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render(
        $request
    ) {
        return response(['message' => $this->getMessage()]);
    }
}
