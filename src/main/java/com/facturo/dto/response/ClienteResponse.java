package com.facturo.dto.response;

import java.time.LocalDateTime;

public record ClienteResponse(
        Long id,
        String nombre,
        String nif,
        String email,
        String telefono,
        String direccion,
        String codigoPostal,
        String ciudad,
        String provincia,
        String pais,
        LocalDateTime creadoEn
) {}
