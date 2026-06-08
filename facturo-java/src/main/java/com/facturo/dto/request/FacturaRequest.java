package com.facturo.dto.request;

import jakarta.validation.Valid;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotEmpty;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Positive;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.List;

public record FacturaRequest(
        @NotNull Long clienteId,
        @NotNull LocalDate fechaEmision,
        LocalDate fechaVencimiento,
        BigDecimal porcentajeIva,      // null = usa 21% por defecto
        BigDecimal porcentajeIrpf,     // null = usa 15% por defecto
        String notas,
        @NotEmpty @Valid List<LineaFacturaRequest> lineas
) {
    public record LineaFacturaRequest(
            @NotBlank String concepto,
            @NotNull @Positive BigDecimal cantidad,
            @NotNull @Positive BigDecimal precioUnitario
    ) {}
}
