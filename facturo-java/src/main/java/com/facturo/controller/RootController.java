package com.facturo.controller;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

/**
 * Health-check endpoint for the Facturo Spring Boot API.
 * Returns a simple status payload at {@code GET /} that confirms the service
 * is running and points to the main entry endpoints.
 */
@RestController
public class RootController {

    /**
     * Returns API metadata: service name, status, auth endpoints, and docs reference.
     *
     * @return a map serialized to JSON by Spring MVC.
     */
    @GetMapping("/")
    public Map<String, Object> root() {
        return Map.of(
                "service", "Facturo API",
                "status", "ok",
                "auth", "/api/auth/register y /api/auth/login",
                "docs", "README.md y facturo-api.http"
        );
    }
}
