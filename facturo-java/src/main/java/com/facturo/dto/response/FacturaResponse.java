package com.facturo.dto.response;

import com.facturo.entity.Factura.EstadoFactura;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

public record FacturaResponse(
        Long id,
        String numeroFactura,
        EstadoFactura estado,
        LocalDate fechaEmision,
        LocalDate fechaVencimiento,
        ClienteResumen cliente,
        BigDecimal porcentajeIva,
        BigDecimal porcentajeIrpf,
        BigDecimal baseImponible,
        BigDecimal cuotaIva,
        BigDecimal cuotaIrpf,
        BigDecimal total,
        String notas,
        List<LineaFacturaResponse> lineas,
        LocalDateTime creadaEn
) {
    public record ClienteResumen(Long id, String nombre, String nif) {}

    public record LineaFacturaResponse(
            Long id,
            String concepto,
            BigDecimal cantidad,
            BigDecimal precioUnitario,
            BigDecimal importe
    ) {}
}
