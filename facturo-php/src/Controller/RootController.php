<?php
declare(strict_types=1);

namespace Facturo\Controller;

use Facturo\Http\Response;

class RootController
{
    /** GET / — responde con 200 OK y metadatos de la API. Útil como health check. */
    public function index(): void
    {
        Response::json([
            'message' => 'Facturo PHP API funcionando',
            'version' => '1.0.0',
            'timestamp' => date('c'),
        ]);
    }
}