<?php
declare(strict_types=1);

namespace Facturo\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Facturo\Repository\FacturaRepository;
use Facturo\Repository\ClienteRepository;
use Facturo\Repository\AutonomoRepository;
use Facturo\Exception\NotFoundException;

class PdfService
{
    private FacturaRepository  $facturaRepo;
    private ClienteRepository  $clienteRepo;
    private AutonomoRepository $autonomoRepo;

    public function __construct()
    {
        $this->facturaRepo  = new FacturaRepository();
        $this->clienteRepo  = new ClienteRepository();
        $this->autonomoRepo = new AutonomoRepository();
    }

    /**
     * Genera y devuelve el PDF de la factura como string binario.
     * El controlador lo enviará con los headers Content-Type y Content-Disposition correctos.
     *
     * @param int $facturaId  ID de la factura a convertir en PDF.
     * @param int $autonomoId ID del autónomo autenticado (verifica propiedad de la factura).
     * @return string Contenido binario del PDF generado por dompdf.
     * @throws NotFoundException Si la factura no existe o no pertenece al autónomo.
     */
    public function generate(int $facturaId, int $autonomoId): string
    {
        $factura = $this->facturaRepo->findByIdAndAutonomo($facturaId, $autonomoId);
        if ($factura === null) throw new NotFoundException('Factura', $facturaId);

        $lineas   = $this->facturaRepo->findLineasByFactura($facturaId);
        $cliente  = $this->clienteRepo->findByIdAndAutonomo($factura->clienteId, $autonomoId);
        $autonomo = $this->autonomoRepo->findById($autonomoId);

        // Renderizar la plantilla PHP → HTML
        ob_start();
        extract([
            'factura'  => $factura,
            'lineas'   => $lineas,
            'cliente'  => $cliente,
            'autonomo' => $autonomo,
        ]);
        include __DIR__ . '/../Templates/factura.html.php';
        $html = ob_get_clean();

        // Convertir HTML a PDF con dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}