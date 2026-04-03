# Facturo — Invoicing for freelancers (EN)

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)
[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/fcomartin94/facturo)

Language (bilingual **equivalent** recruiter pair):

- EN (this file): `OVERVIEW_EN.md`
- ES: `OVERVIEW_ES.md`

Technical (bilingual **equivalent** pair):

- EN: `README_EN.md`
- ES: `README_ES.md`
- GitHub entry: `README.md`

> REST API for **freelancers**: clients, invoices with VAT/withholding, status workflow, PDF output, and **JWT-based multitenancy**. Each user only ever sees their own data. Modern stack: **Java 21**, **Spring Boot 3**, **PostgreSQL**, **Spring Security + JWT**, PDF generation with **iText**.

---

## What it is

**Facturo** is the backend for an invoicing platform aimed at self-employed professionals. It covers the usual path: sign up, manage clients, create line-based invoices, compute taxes, transition statuses, and download PDFs.

## Status

- Working API with JWT auth and isolation via `autonomo_id`.
- CI on GitHub Actions (`./mvnw -B verify` on push/PR).
- Reproducible environment on **GitHub Codespaces** (Java 21 + Docker + REST Client).

## Architecture (high level)

```text
Client (REST / browser / facturo-api.http)
        |
        v
Spring Boot API (8080)
        |
        v
PostgreSQL (clients, invoices, freelancers; scoped by JWT)
```

## Stack (summary)

| Area | Choice |
|------|--------|
| Runtime | Java 21 |
| Framework | Spring Boot 3 |
| Data | PostgreSQL 16, JPA |
| Security | JWT (jjwt) |
| PDF | iText 8 |

## Documentation

- Full technical guide (endpoints, startup, env, `facturo-api.http`): [`README_EN.md`](README_EN.md) / [`README_ES.md`](README_ES.md)
