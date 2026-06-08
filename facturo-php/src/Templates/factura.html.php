<?php
/**
 * Variables inyectadas por PdfService mediante extract().
 *
 * @var \Facturo\Model\Factura        $factura
 * @var \Facturo\Model\Autonomo       $autonomo
 * @var \Facturo\Model\Cliente        $cliente
 * @var \Facturo\Model\LineaFactura[] $lineas
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body   { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #222; }
  table  { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th     { background: #4f46e5; color: #fff; padding: 8px; text-align: left; }
  td     { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
  .total-row td { font-weight: bold; background: #f3f4f6; }
  h1     { font-size: 22px; color: #4f46e5; }
  .meta  { display: flex; justify-content: space-between; margin-bottom: 24px; }
  .block { line-height: 1.6; }
</style>
</head>
<body>
<h1>FACTURA <?= htmlspecialchars($factura->numeroFactura) ?></h1>

<div class="meta">
  <div class="block">
    <strong>Emisor:</strong><br>
    <?= htmlspecialchars($autonomo->nombre . ' ' . $autonomo->apellidos) ?><br>
    NIF: <?= htmlspecialchars($autonomo->nif) ?><br>
    <?= htmlspecialchars($autonomo->direccion ?? '') ?><br>
    <?= htmlspecialchars($autonomo->ciudad ?? '') ?>
  </div>
  <div class="block">
    <strong>Fecha emisión:</strong> <?= htmlspecialchars($factura->fechaEmision) ?><br>
    <strong>Vencimiento:</strong> <?= htmlspecialchars($factura->fechaVencimiento ?? 'N/A') ?><br>
    <strong>Estado:</strong> <?= htmlspecialchars($factura->estado) ?>
  </div>
  <div class="block">
    <strong>Destinatario:</strong><br>
    <?= htmlspecialchars($cliente->nombre) ?><br>
    NIF: <?= htmlspecialchars($cliente->nif) ?><br>
    <?= htmlspecialchars($cliente->direccion ?? '') ?><br>
    <?= htmlspecialchars($cliente->ciudad ?? '') ?>
  </div>
</div>

<table>
  <thead>
    <tr><th>Concepto</th><th>Cantidad</th><th>Precio unit.</th><th>Importe</th></tr>
  </thead>
  <tbody>
    <?php foreach ($lineas as $linea): ?>
    <tr>
      <td><?= htmlspecialchars($linea->concepto) ?></td>
      <td><?= htmlspecialchars((string)$linea->cantidad) ?></td>
      <td><?= number_format((float)$linea->precioUnitario, 2, ',', '.') ?> €</td>
      <td><?= number_format((float)$linea->importe, 2, ',', '.') ?> €</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr class="total-row"><td colspan="3">Base imponible</td><td><?= number_format((float)$factura->baseImponible, 2, ',', '.') ?> €</td></tr>
    <tr><td colspan="3">IVA (<?= $factura->porcentajeIva ?>%)</td><td><?= number_format((float)$factura->cuotaIva, 2, ',', '.') ?> €</td></tr>
    <tr><td colspan="3">IRPF (<?= $factura->porcentajeIrpf ?>%)</td><td>-<?= number_format((float)$factura->cuotaIrpf, 2, ',', '.') ?> €</td></tr>
    <tr class="total-row"><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= number_format((float)$factura->total, 2, ',', '.') ?> €</strong></td></tr>
  </tfoot>
</table>

<?php if ($factura->notas): ?>
<p style="margin-top:20px"><strong>Notas:</strong> <?= htmlspecialchars($factura->notas) ?></p>
<?php endif; ?>

</body></html>