<?php
declare(strict_types=1);

namespace Facturo\Exception;

/** Recurso no encontrado - mapea a HTTP 404. */
class NotFoundException extends BusinessException
{
    public function __construct(string $resource, int|string $id)
    {
        parent::__construct(
            "{$resource} con id '{$id}' no encontrado/a",
            404
        );
    }
}