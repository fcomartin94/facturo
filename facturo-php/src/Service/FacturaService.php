<?php
declare(strict_types=1);

namespace Facturo\Service;

use Facturo\Model\Factura;
use Facturo\Model\LineaFactura;
use Facturo\Repository\FacturaRepository;
use Facturo\Repository\ClienteRepository;
use Facturo\Validation\Validator;
use Facturo\Exception\BusinessException;
use Facturo\Exception\NotFoundException;
use Facturo\Exception\ValidationException;

class FacturaService
{
    private FacturaRepository $facturaRepo;
    private ClienteRepository $clienteRepo;
    private const SCALE = 2;   // decimales en todos los bcmath

    public function __construct()
    {
        $this->facturaRepo = new FacturaRepository();
        $this->clienteRepo = new ClienteRepository();
    }

    /**
     * Lista todas las facturas del autónomo como arrays serializables.
     *
     * @param int $autonomoId ID del autónomo autenticado.
     * @return array<int, array<string, mixed>>
     */
    public function getAll(int $autonomoId): array
    {
        return array_map(
            fn(Factura $f) => $f->toArray(),
            $this->facturaRepo->findAllByAutonomo($autonomoId)
        );
    }

    /**
     * Obtiene una factura con sus líneas de detalle incluidas en la clave 'lineas'.
     *
     * @param int $id         ID de la factura.
     * @param int $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed>
     * @throws NotFoundException Si la factura no existe o no pertenece al autónomo.
     */
    public function getById(int $id, int $autonomoId): array
    {
        $factura = $this->facturaRepo->findByIdAndAutonomo($id, $autonomoId);
        if ($factura === null) throw new NotFoundException('Factura', $id);

        $lineas = $this->facturaRepo->findLineasByFactura($id);
        $result = $factura->toArray();
        $result['lineas'] = array_map(fn(LineaFactura $l) => $l->toArray(), $lineas);
        return $result;
    }

    /**
     * Crea una factura completa (cabecera + líneas) con cálculo automático de totales.
     *
     * @param array<string, mixed> $data       Payload con clienteId, fechaEmision y lineas.
     * @param int                   $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed> La factura creada con totales calculados y clave 'lineas'.
     * @throws ValidationException Si faltan campos obligatorios o las líneas son inválidas.
     * @throws NotFoundException   Si el cliente no existe o no pertenece al autónomo.
     * @throws BusinessException   Si el número de factura ya existe (HTTP 409).
     */
    public function create(array $data, int $autonomoId): array
    {
        // Validación básica
        (new Validator())
            ->required($data['clienteId']   ?? null, 'clienteId')
            ->required($data['fechaEmision']?? null, 'fechaEmision')
            ->required($data['lineas']      ?? null, 'lineas')
            ->throwIfInvalid();

        if (empty($data['lineas']) || !is_array($data['lineas'])) {
            throw new BusinessException('La factura debe tener al menos una línea', 422);
        }

        // Verificar que el cliente pertenece al autónomo
        $cliente = $this->clienteRepo->findByIdAndAutonomo(
            (int)$data['clienteId'], $autonomoId
        );
        if ($cliente === null) {
            throw new NotFoundException('Cliente', $data['clienteId']);
        }

        // Calcular totales con bcmath (precisión exacta, sin coma flotante)
        $baseImponible = '0.00';
        $lineasModelo  = [];

        foreach ($data['lineas'] as $linea) {
            $cantidad  = (string)($linea['cantidad']       ?? 0);
            $precio    = (string)($linea['precioUnitario'] ?? 0);
            $importe   = bcmul($cantidad, $precio, self::SCALE);
            $baseImponible = bcadd($baseImponible, $importe, self::SCALE);

            $lineasModelo[] = new LineaFactura(
                id:             null,
                facturaId:      0,   // se asigna tras el INSERT
                concepto:       $linea['concepto']      ?? '',
                cantidad:       $cantidad,
                precioUnitario: $precio,
                importe:        $importe,
            );
        }

        $pctIva  = (string)($data['porcentajeIva']  ?? '21.00');
        $pctIrpf = (string)($data['porcentajeIrpf'] ?? '15.00');

        $cuotaIva  = bcmul($baseImponible, bcdiv($pctIva,  '100', 6), self::SCALE);
        $cuotaIrpf = bcmul($baseImponible, bcdiv($pctIrpf, '100', 6), self::SCALE);
        $total     = bcsub(bcadd($baseImponible, $cuotaIva, self::SCALE), $cuotaIrpf, self::SCALE);

        $numeroFactura = $data['numeroFactura']
            ?? $this->facturaRepo->getNextNumeroFactura($autonomoId);

        if ($this->facturaRepo->existsNumeroFactura($autonomoId, $numeroFactura)) {
            throw new BusinessException(
                "El número de factura '{$numeroFactura}' ya existe para este autónomo", 409
            );
        }

        $factura = new Factura(
            id:                null,
            autonomoId:        $autonomoId,
            clienteId:         (int)$data['clienteId'],
            numeroFactura:     $numeroFactura,
            fechaEmision:      $data['fechaEmision'],
            fechaVencimiento:  $data['fechaVencimiento'] ?? null,
            porcentajeIva:     $pctIva,
            porcentajeIrpf:    $pctIrpf,
            baseImponible:     $baseImponible,
            cuotaIva:          $cuotaIva,
            cuotaIrpf:         $cuotaIrpf,
            total:             $total,
            estado:            'BORRADOR',
            notas:             $data['notas'] ?? null,
        );

        $created = $this->facturaRepo->create($factura, $lineasModelo);
        $result  = $created->toArray();
        $result['lineas'] = array_map(fn(LineaFactura $l) => $l->toArray(), $lineasModelo);
        return $result;
    }

    /**
     * Actualiza el estado de una factura validando que sea un valor permitido.
     *
     * @param int    $id         ID de la factura.
     * @param string $estado     Nuevo estado (BORRADOR, EMITIDA, PAGADA, VENCIDA, CANCELADA).
     * @param int    $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed> La factura actualizada.
     * @throws ValidationException Si el estado no es un valor permitido.
     * @throws NotFoundException   Si la factura no existe o no pertenece al autónomo.
     */
    public function updateEstado(int $id, string $estado, int $autonomoId): array
    {
        $validos = ['BORRADOR','EMITIDA','PAGADA','VENCIDA','CANCELADA'];
        (new Validator())->inList($estado, 'estado', $validos)->throwIfInvalid();

        $factura = $this->facturaRepo->updateEstado($id, $autonomoId, $estado);
        if ($factura === null) throw new NotFoundException('Factura', $id);
        return $factura->toArray();
    }
}