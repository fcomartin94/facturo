# Facturo — Facturación para autónomos (ES)

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)
[![Abrir en GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/fcomartin94/facturo)

Idioma / Language (par recruiter **equivalente**):

- ES (este archivo): `OVERVIEW_ES.md`
- EN: `OVERVIEW_EN.md`

Técnico (par **equivalente** bilingüe):

- EN: `README_EN.md`
- ES: `README_ES.md`
- Entrada GitHub: `README.md`

> API REST para **autónomos**: clientes, facturas con IVA/IRPF, estados, PDF y **multitenencia por JWT**. Cada usuario solo ve su propio universo de datos. Stack moderno: **Java 21**, **Spring Boot 3**, **PostgreSQL**, **Spring Security + JWT**, generación de PDF con **iText**.

---

## Qué es

**Facturo** es el backend de una plataforma de facturación orientada a trabajadores por cuenta propia. Cubre el flujo típico: registrarse, gestionar clientes, crear facturas con líneas, calcular impuestos, cambiar estados y descargar el PDF.

## Estado

- API operativa con auth JWT y aislamiento por `autonomo_id`.
- CI en GitHub Actions (`./mvnw -B verify` en push/PR).
- Entorno reproducible en **GitHub Codespaces** (Java 21 + Docker + REST Client).

## Arquitectura (alto nivel)

```text
Cliente (REST / navegador / facturo-api.http)
        |
        v
Spring Boot API (8080)
        |
        v
PostgreSQL (clientes, facturas, autónomos; filtrado por JWT)
```

## Stack (resumen)

| Área | Elección |
|------|----------|
| Runtime | Java 21 |
| Framework | Spring Boot 3 |
| Datos | PostgreSQL 16, JPA |
| Seguridad | JWT (jjwt) |
| PDF | iText 8 |

## Documentación

- Guía técnica completa (endpoints, arranque, variables, `facturo-api.http`): [`README_ES.md`](README_ES.md) / [`README_EN.md`](README_EN.md)
