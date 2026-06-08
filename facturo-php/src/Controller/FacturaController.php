<?php
declare(strict_types=1);

namespace Facturo\Controller;

use Facturo\Http\Response;
use Facturo\Service\FacturaService;
use Facturo\Service\PdfService;

/**
 * Controlador REST para la gestión de facturas del autónomo autenticado.
 * Incluye el endpoint de generación de PDF que envía bytes binarios en lugar de JSON.
 */
class FacturaController
{
    private FacturaService $facturaService;
    private PdfService     $pdfService;

    public function __construct()
    {
        $this->facturaService = new FacturaService();
        $this->pdfService     = new PdfService();
    }

    /** GET /api/facturas — lista todas las facturas del autónomo autenticado. */
    public function index(int $autonomoId): void
    {
        Response::json($this->facturaService->getAll($autonomoId));
    }

    /** GET /api/facturas/{id} — obtiene una factura con sus líneas de detalle. */
    public function show(int $id, int $autonomoId): void
    {
        Response::json($this->facturaService->getById($id, $autonomoId));
    }

    /** POST /api/facturas — crea una factura completa (cabecera + líneas) y devuelve 201. */
    public function store(int $autonomoId): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->facturaService->create($data, $autonomoId);
        Response::json($result, 201);
    }

    /** PATCH /api/facturas/{id}/estado — actualiza solo el estado de la factura. */
    public function updateEstado(int $id, int $autonomoId): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $estado = $data['estado'] ?? '';
        $result = $this->facturaService->updateEstado($id, $estado, $autonomoId);
        Response::json($result);
    }

    /**
     * GET /api/facturas/{id}/pdf — genera y devuelve el PDF de la factura como descarga.
     * Envía cabeceras Content-Type y Content-Disposition en lugar de JSON.
     *
     * @param int $id         ID de la factura.
     * @param int $autonomoId ID del autónomo autenticado.
     * @return void
     */
    public function pdf(int $id, int $autonomoId): void
    {
        $pdfContent = $this->pdfService->generate($id, $autonomoId);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"factura-{$id}.pdf\"");
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit;
    }
}