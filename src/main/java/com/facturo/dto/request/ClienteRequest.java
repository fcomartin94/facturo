package com.facturo.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public record ClienteRequest(
        @NotBlank String nombre,
        @NotBlank @Size(min = 9, max = 15) String nif,
        String email,
        String telefono,
        String direccion,
        String codigoPostal,
        String ciudad,
        String provincia,
        String pais
) {}
