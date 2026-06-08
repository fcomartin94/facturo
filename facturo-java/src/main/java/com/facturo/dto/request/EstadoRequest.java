package com.facturo.dto.request;

import com.facturo.entity.Factura.EstadoFactura;
import jakarta.validation.constraints.NotNull;

/**
 * Body del endpoint PATCH /api/facturas/{id}/estado.
 * Ejemplo: { "estado": "EMITIDA" }
 */
public record EstadoRequest(
        @NotNull EstadoFactura estado
) {}
