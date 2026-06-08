package com.facturo.exception;

/**
 * Thrown when a requested entity does not exist or does not belong to the
 * authenticated user (prevents IDOR by treating both cases identically).
 * Mapped to HTTP 404 Not Found by {@link GlobalExceptionHandler}.
 */
public class ResourceNotFoundException extends RuntimeException {

    /**
     * @param message Human-readable description of the missing resource.
     */
    public ResourceNotFoundException(String message) {
        super(message);
    }
}
