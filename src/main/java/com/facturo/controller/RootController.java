package com.facturo.controller;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
public class RootController {

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
