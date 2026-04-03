package com.facturo.dto.response;

public record AuthResponse(
        String token,
        String email,
        String nombre,
        String apellidos
) {}
