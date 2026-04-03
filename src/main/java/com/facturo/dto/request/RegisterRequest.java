package com.facturo.dto.request;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public record RegisterRequest(
        @NotBlank @Email String email,
        @NotBlank @Size(min = 8, message = "La contraseña debe tener mínimo 8 caracteres") String password,
        @NotBlank String nombre,
        @NotBlank String apellidos,
        @NotBlank @Size(min = 9, max = 9, message = "El NIF debe tener 9 caracteres") String nif,
        String direccion,
        String codigoPostal,
        String ciudad,
        String provincia,
        String telefono
) {}
