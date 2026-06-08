<?php
declare(strict_types=1);

namespace Facturo\Exception;

/**
 * Excepción base de dominio. Lleva el código HTTP que corresponde al error.
 * El router la captura y delega en Response::json para enviar la respuesta adecuada.
 */
class BusinessException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatusCode = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}