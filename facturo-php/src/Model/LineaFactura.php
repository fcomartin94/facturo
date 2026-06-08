<?php
declare(strict_types=1);

namespace Facturo\Model;

/**
 * Modelo inmutable que representa una línea de detalle de una factura.
 * Los campos numéricos (cantidad, precioUnitario, importe) son strings
 * para mantener la precisión exacta de NUMERIC en PostgreSQL.
 */
class LineaFactura
{
    public function __construct(
        public readonly ?int   $id,
        public readonly ?int   $facturaId,
        public readonly string $concepto,
        public readonly string $cantidad,        // NUMERIC — string para bcmath
        public readonly string $precioUnitario,  // NUMERIC — string para bcmath
        public readonly string $importe,         // cantidad × precioUnitario
    ) {}

    /**
     * Crea una instancia desde el array asociativo que devuelve PDO::FETCH_ASSOC.
     *
     * @param array<string, mixed> $row Fila de la tabla lineas_factura.
     * @return self Nueva instancia hidratada.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int)$row['id'],
            facturaId:      (int)$row['factura_id'],
            concepto:       $row['concepto'],
            cantidad:       $row['cantidad'],
            precioUnitario: $row['precio_unitario'],
            importe:        $row['importe'],
        );
    }

    /**
     * Exporta la línea como array para la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'facturaId'      => $this->facturaId,
            'concepto'       => $this->concepto,
            'cantidad'       => $this->cantidad,
            'precioUnitario' => $this->precioUnitario,
            'importe'        => $this->importe,
        ];
    }
}