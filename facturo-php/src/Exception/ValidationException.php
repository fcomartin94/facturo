<?php
declare(strict_types=1);

namespace Facturo\Exception;

/** Errores de validación del payload - mapea a HTTP 422. */
class ValidationException extends BusinessException
{
    /** @param array<string, string> $errors Mapa campo -> mensaje de error */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Errores de validación', 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}