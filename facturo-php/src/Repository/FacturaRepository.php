<?php
declare(strict_types=1);

namespace Facturo\Repository;

use PDO;
use Exception;
use Facturo\Database\Database;
use Facturo\Model\Factura;
use Facturo\Model\LineaFactura;

/**
 * Repositorio de acceso a datos para Factura y LineaFactura.
 * La creación de facturas se ejecuta dentro de una transacción para garantizar
 * la integridad entre la cabecera y sus líneas de detalle.
 */
class FacturaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Lista todas las facturas del autónomo ordenadas por fecha de emisión descendente.
     *
     * @param int $autonomoId ID del autónomo propietario.
     * @return Factura[]
     */
    public function findAllByAutonomo(int $autonomoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM facturas WHERE autonomo_id = :aid ORDER BY fecha_emision DESC'
        );
        $stmt->execute(['aid' => $autonomoId]);
        return array_map([Factura::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Busca una factura por id verificando que pertenece al autónomo (evita IDOR).
     *
     * @param int $id         ID de la factura.
     * @param int $autonomoId ID del autónomo propietario.
     * @return Factura|null Factura encontrada, o null si no existe o no pertenece al autónomo.
     */
    public function findByIdAndAutonomo(int $id, int $autonomoId): ?Factura
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM facturas WHERE id = :id AND autonomo_id = :aid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'aid' => $autonomoId]);
        $row = $stmt->fetch();
        return $row ? Factura::fromRow($row) : null;
    }

    /**
     * Crea la factura y sus líneas en una sola transacción.
     * Si falla la inserción de alguna línea, se hace rollback completo.
     *
     * @param Factura        $factura Cabecera de la factura (id = null).
     * @param LineaFactura[] $lineas  Líneas de detalle de la factura.
     * @return Factura La factura persistida con id y creado_en asignados por la DB.
     * @throws Exception Si cualquier INSERT falla (rollback ya ejecutado antes de relanzar).
     */
    public function create(Factura $factura, array $lineas): Factura
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Insertar cabecera de factura
            $stmt = $this->pdo->prepare(
                'INSERT INTO facturas
                 (autonomo_id, cliente_id, numero_factura, fecha_emision, fecha_vencimiento,
                  porcentaje_iva, porcentaje_irpf, base_imponible, cuota_iva, cuota_irpf,
                  total, estado, notas)
                 VALUES (:aid, :cid, :num, :emision, :vencimiento,
                         :iva, :irpf, :base, :cuotaIva, :cuotaIrpf,
                         :total, :estado, :notas)
                 RETURNING *'
            );
            $stmt->execute([
                'aid'        => $factura->autonomoId,
                'cid'        => $factura->clienteId,
                'num'        => $factura->numeroFactura,
                'emision'    => $factura->fechaEmision,
                'vencimiento'=> $factura->fechaVencimiento,
                'iva'        => $factura->porcentajeIva,
                'irpf'       => $factura->porcentajeIrpf,
                'base'       => $factura->baseImponible,
                'cuotaIva'   => $factura->cuotaIva,
                'cuotaIrpf'  => $factura->cuotaIrpf,
                'total'      => $factura->total,
                'estado'     => $factura->estado,
                'notas'      => $factura->notas,
            ]);
            $facturaCreada = Factura::fromRow($stmt->fetch());

            // 2. Insertar líneas
            foreach ($lineas as $linea) {
                $this->insertLinea($facturaCreada->id, $linea);
            }

            $this->pdo->commit();
            return $facturaCreada;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Inserta una línea de detalle asociada a la cabecera de la factura.
     *
     * @param int          $facturaId ID de la factura ya persistida.
     * @param LineaFactura $linea     Línea a insertar.
     * @return void
     */
    private function insertLinea(int $facturaId, LineaFactura $linea): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lineas_factura (factura_id, concepto, cantidad, precio_unitario, importe)
             VALUES (:fid, :concepto, :cantidad, :precio, :importe)'
        );
        $stmt->execute([
            'fid'      => $facturaId,
            'concepto' => $linea->concepto,
            'cantidad' => $linea->cantidad,
            'precio'   => $linea->precioUnitario,
            'importe'  => $linea->importe,
        ]);
    }

    /**
     * Obtiene las líneas de una factura ordenadas por id ascendente.
     *
     * @param int $facturaId ID de la factura.
     * @return LineaFactura[]
     */
    public function findLineasByFactura(int $facturaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM lineas_factura WHERE factura_id = :fid ORDER BY id ASC'
        );
        $stmt->execute(['fid' => $facturaId]);
        return array_map([LineaFactura::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Actualiza solo el estado de la factura sin modificar ningún otro campo.
     *
     * @param int    $id         ID de la factura.
     * @param int    $autonomoId ID del autónomo propietario.
     * @param string $estado     Nuevo estado a persistir.
     * @return Factura|null La factura actualizada, o null si no existe o no pertenece al autónomo.
     */
    public function updateEstado(int $id, int $autonomoId, string $estado): ?Factura
    {
        $stmt = $this->pdo->prepare(
            'UPDATE facturas SET estado = :estado
             WHERE id = :id AND autonomo_id = :aid
             RETURNING *'
        );
        $stmt->execute(['estado' => $estado, 'id' => $id, 'aid' => $autonomoId]);
        $row = $stmt->fetch();
        return $row ? Factura::fromRow($row) : null;
    }

    /**
     * Comprueba si el número de factura ya existe para este autónomo.
     *
     * @param int    $autonomoId ID del autónomo.
     * @param string $numero     Número de factura a comprobar.
     * @return bool true si el número ya está en uso por este autónomo.
     */
    public function existsNumeroFactura(int $autonomoId, string $numero): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM facturas WHERE autonomo_id = :aid AND numero_factura = :num LIMIT 1'
        );
        $stmt->execute(['aid' => $autonomoId, 'num' => $numero]);
        return (bool)$stmt->fetch();
    }

    /**
     * Genera el siguiente número de factura para el autónomo en formato YYYY-NNN.
     * NNN es el total de facturas del año en curso + 1.
     *
     * @param int $autonomoId ID del autónomo.
     * @return string Número de factura sugerido (ej: '2025-005').
     */
    public function getNextNumeroFactura(int $autonomoId): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM facturas
             WHERE autonomo_id = :aid AND numero_factura LIKE :prefix"
        );
        $stmt->execute(['aid' => $autonomoId, 'prefix' => "{$year}-%"]);
        $count = (int)$stmt->fetch()['total'];
        return sprintf('%s-%03d', $year, $count + 1);
    }
}