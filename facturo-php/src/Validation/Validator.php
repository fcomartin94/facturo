<?php
declare(strict_types=1);

namespace Facturo\Validation;

use Facturo\Exception\ValidationException;

class Validator
{
    private array $errors = [];

    /**
     * Valida que el campo esté presente y no sea empty (null, '', []).
     *
     * @param mixed  $value Valor a validar.
     * @param string $field Nombre del campo (incluido en el mensaje de error).
     * @return self Para encadenamiento fluido.
     */
    public function required(mixed $value, string $field): self
    {
        if ($value === null || $value === '' || $value === []) {
            $this->errors[$field] = "El campo '{$field}' es obligatorio";
        }
        return $this;
    }

    /**
     * Valida formato de email con filter_var(FILTER_VALIDATE_EMAIL).
     * Ignora el campo si está vacío — combinar con required() para campos obligatorios.
     *
     * @param string $value Valor a validar.
     * @param string $field Nombre del campo.
     * @return self Para encadenamiento fluido.
     */
    public function email(string $value, string $field): self
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo '{$field}' debe ser un email válido";
        }
        return $this;
    }

    /**
     * Valida que el valor tenga al menos $min caracteres.
     *
     * @param string $value Valor a validar.
     * @param string $field Nombre del campo.
     * @param int    $min   Longitud mínima requerida.
     * @return self Para encadenamiento fluido.
     */
    public function minLength(string $value, string $field, int $min): self
    {
        if (strlen($value) < $min) {
            $this->errors[$field] = "'{$field}' debe tener al menos {$min} caracteres";
        }
        return $this;
    }

    /**
     * Valida que el valor esté en un conjunto de opciones permitidas (comparación estricta ===).
     * Ignora el campo si el valor es null.
     *
     * @param mixed         $value   Valor a validar.
     * @param string        $field   Nombre del campo.
     * @param array<mixed> $options Conjunto de valores válidos.
     * @return self Para encadenamiento fluido.
     */
    public function inList(mixed $value, string $field, array $options): self
    {
        if ($value !== null && !in_array($value, $options, true)) {
            $this->errors[$field] =
                "'{$field}' debe ser uno de: " . implode(', ', $options);
        }
        return $this;
    }

    /**
     * Valida que el valor sea numérico y estrictamente positivo (> 0).
     * Ignora el campo si el valor es null.
     *
     * @param mixed  $value Valor a validar.
     * @param string $field Nombre del campo.
     * @return self Para encadenamiento fluido.
     */
    public function positiveNumber(mixed $value, string $field): self
    {
        if ($value !== null && (!is_numeric($value) || (float)$value <= 0)) {
            $this->errors[$field] = "'{$field}' debe ser un número positivo";
        }
        return $this;
    }

    /**
     * Lanza ValidationException si hay errores acumulados.
     * Llama a este método al final de la cadena de validación.
     *
     * @return void
     * @throws ValidationException Con el mapa completo de todos los errores acumulados.
     */
    public function throwIfInvalid(): void
    {
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }
}