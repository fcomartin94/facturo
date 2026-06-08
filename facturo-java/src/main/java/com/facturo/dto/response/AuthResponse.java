package com.facturo.dto.response;

import java.time.LocalDateTime;

/**
 * Respuesta de los endpoints de autenticación.
 * Formato: { "token": "...", "autonomo": { ... } }
 * Compatible con el frontend y con la versión PHP.
 */
public record AuthResponse(
        String token,
        AutonomoResponse autonomo
) {
    public record AutonomoResponse(
            Long id,
            String email,
            String nombre,
            String apellidos,
            String nif,
            String direccion,
            String codigoPostal,
            String ciudad,
            String provincia,
            String telefono,
            LocalDateTime creadoEn
    ) {}
}
