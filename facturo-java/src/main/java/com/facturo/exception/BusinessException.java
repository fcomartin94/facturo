package com.facturo.exception;

/**
 * Signals a business rule violation (e.g. duplicate invoice number, email already in use).
 * Mapped to HTTP 400 Bad Request by {@link GlobalExceptionHandler}.
 */
public class BusinessException extends RuntimeException {

    /**
     * @param message Human-readable description of the violated rule.
     */
    public BusinessException(String message) {
        super(message);
    }
}
