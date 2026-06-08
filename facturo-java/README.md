# Facturo — Invoicing API for Freelancers

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)

> REST API for freelancers and sole traders: client management, line-item invoices with VAT and withholding tax, status workflow, PDF generation, and **JWT-based multitenancy** — every user sees only their own data. Built with **Java 21**, **Spring Boot 3**, **PostgreSQL**, and **iText 8**.

---

## Stack

| Layer | Technology |
|-------|------------|
| Runtime | Java 21 |
| Framework | Spring Boot 3.3 |
| Database | PostgreSQL 16 + JPA/Hibernate |
| Security | Spring Security + JWT (jjwt) |
| PDF | iText 8 |
| Boilerplate | Lombok + MapStruct |
| CI | GitHub Actions (`./mvnw -B verify` on push/PR) |

## Architecture

```text
REST Client  →  Spring Boot API :8080  →  PostgreSQL
                     |
              JWT multitenancy (autonomo_id scopes all queries)
```

Every `cliente` and `factura` row is tied to an `autonomo_id` extracted from the JWT — a user cannot access another user's data.

## Key features

- **Auth** — register, login, stateless JWT with `BCryptPasswordEncoder`
- **Client CRUD** — full lifecycle per authenticated freelancer
- **Invoice lifecycle** — create line-item invoices, auto-compute VAT (21%) and withholding (15%), transition through `BORRADOR → EMITIDA → PAGADA / VENCIDA / CANCELADA`
- **PDF export** — formatted iText invoice download at `GET /api/facturas/{id}/pdf`

## Quick start

**Prerequisites:** Java 21+ and Docker Desktop.

```bash
# 1. Start PostgreSQL
docker compose up -d

# 2. Run the API
./mvnw spring-boot:run
# API at http://localhost:8080
```

### Environment variables (optional)

Defaults from `application.yml` match `docker-compose.yml` out of the box. Override for different environments:

| Variable | Description |
|----------|-------------|
| `FACTURO_JWT_SECRET` | JWT secret in Base64 |
| `FACTURO_JWT_EXPIRATION_MS` | Token lifetime in ms (default 86400000) |
| `SPRING_DATASOURCE_URL` | PostgreSQL JDBC URL |
| `SPRING_DATASOURCE_USERNAME` | Database user |
| `SPRING_DATASOURCE_PASSWORD` | Database password |

See `.env.example` for reference values.

## Try it with `facturo-api.http`

Install the **REST Client** extension (Huachao Mao) in VS Code or Cursor, open `facturo-api.http`, and run requests in this order:

| Step | Request | Expected |
|------|---------|----------|
| A | "1. Registrar nuevo autónomo" | 201 |
| B | "2. Login" | 200 + `token` |
| C | Paste `token` into `@token = ...` | — |
| D | "4. Crear cliente" | 201 + `id` |
| E | "9. Crear factura (IVA 21%, IRPF 15%)" | 201 + totals |
| F | "14. Descargar PDF de la factura ⭐" | PDF binary |

## Endpoints

| Area | Endpoints |
|------|-----------|
| Auth | `POST /api/auth/register` · `POST /api/auth/login` |
| Clients | `GET/POST /api/clientes` · `GET/PUT/DELETE /api/clientes/{id}` |
| Invoices | `GET/POST /api/facturas` · `GET/DELETE /api/facturas/{id}` · `PATCH /api/facturas/{id}/estado` · `GET /api/facturas/{id}/pdf` |

## Business rules

Taxable base = sum of (quantity × unit price) per line. VAT = base × 21%, withholding (IRPF) = base × 15%, total = base + VAT − withholding. Rates are configurable per invoice.

Invoice status flow:

```text
BORRADOR → EMITIDA → PAGADA
                   → VENCIDA
         → CANCELADA
```

Terminal states (PAGADA, VENCIDA, CANCELADA) cannot transition further. Drafts can be deleted; issued/paid invoices cannot.

## Roadmap

- [ ] Yearly dashboard (revenue, VAT due, withholding)
- [ ] Email invoice to client
- [ ] Overdue reminders
- [ ] Model 130 export
- [ ] React/Vue frontend
