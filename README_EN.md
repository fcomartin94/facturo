# Facturo — REST API (EN)

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)
[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/fcomartin94/facturo)

Language (bilingual **equivalent** pair):

- EN (this file): `README_EN.md`
- ES: `README_ES.md`

Recruiter (bilingual **equivalent** pair):

- EN: `OVERVIEW_EN.md`
- ES: `OVERVIEW_ES.md`
- GitHub entry: `README.md`

Repository: [github.com/fcomartin94/facturo](https://github.com/fcomartin94/facturo). On every push to `main`/`master` or on pull requests, the **CI** workflow runs `./mvnw -B verify` (compile, tests, package). Build status appears in the badge above and under **Actions**.

Invoicing platform for freelancers and sole traders. REST API built with Java 21 + Spring Boot 3.

## Stack

| Layer | Technology |
|---|---|
| Framework | Java 21 + Spring Boot 3.3 |
| Database | PostgreSQL 16 |
| ORM | Spring Data JPA / Hibernate |
| Security | Spring Security + JWT (jjwt) |
| PDF | iText 8 |
| Boilerplate | Lombok + MapStruct |

## Architecture: multitenancy

`clientes` and `facturas` tables include an `autonomo_id` column. Every query is scoped to the freelancer from the JWT. A user **cannot** see another user’s data.

## Prerequisites

- Java 21+ (or Maven Wrapper `./mvnw` only, which pulls Maven 3.9.x)
- Docker (Desktop locally, or the Codespaces environment)

## Run locally

```bash
# 1. Start PostgreSQL
docker compose up -d

# 2. Run the API (builds if needed)
./mvnw spring-boot:run
```

The API listens on `http://localhost:8080`.

### Environment variables (optional)

Defaults come from `application.yml` and `docker-compose.yml`. For production or different secrets:

| Variable | Description |
|----------|-------------|
| `FACTURO_JWT_SECRET` | JWT secret in **Base64** (replaces the default). |
| `FACTURO_JWT_EXPIRATION_MS` | Token lifetime in milliseconds (default 86400000). |
| `SPRING_DATASOURCE_URL` | PostgreSQL JDBC URL |
| `SPRING_DATASOURCE_USERNAME` | Database user |
| `SPRING_DATASOURCE_PASSWORD` | Database password |

See commented examples in `.env.example`. Spring Boot reads these from the environment; a `.env` file is only needed if a tool loads it for you.

## Try on GitHub Codespaces

[**Open in GitHub Codespaces**](https://codespaces.new/fcomartin94/facturo) (badge above): Java 21 + Docker. If the container enters *recovery mode* after `.devcontainer` changes, use **Rebuild Container** after `git pull`.

1. After the codespace opens: `docker compose up -d` (wait a few seconds). Optional first run: `./mvnw -DskipTests compile`.
2. Start the API: `./mvnw spring-boot:run`
3. Port **8080**: **Ports** tab → **8080** → **Port visibility** → **Public**. Open the forwarded URL. Root **`/`** returns JSON (200). Without a token: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/api/clientes` should return **401**.
4. Follow the **`facturo-api.http`** guide below.

## Guide: exercise the API with `facturo-api.http`

With the API running and Postgres up (`docker compose up -d`).

### 1. REST Client extension

- In **Cursor** or **VS Code**: Extensions → **REST Client** (Huachao Mao) → **Install**.
- The workspace may recommend extensions via `.vscode/extensions.json`.

**GitHub Codespaces** includes the extension in the devcontainer.

### 2. Open the file

Open **`facturo-api.http`**. Use **Send Request** above each request.

### 3. Suggested order

Request titles in `facturo-api.http` are in Spanish; match the numbered blocks below.

| Step | Action | Expected |
|------|--------|----------|
| A | **Send Request** on **“1. Registrar nuevo autónomo”** | **201** (or error if email exists). |
| B | **“2. Login”** | **200** with JSON including **`token`**. |
| C | Paste **`token`** into the `@token = ...` line in the file. | — |
| D | **“4. Crear cliente”** | **201**; note **`id`** (e.g. `1`). |
| E | **“9. Crear factura (IVA 21%, IRPF 15% por defecto)”** | **`clienteId`** matches the client. **201** with totals. |
| F | **“14. Descargar PDF de la factura ⭐”** | PDF or binary body in the response. |

If you get **401**, fix `@token` or log in again.

### 4. Browser (optional)

`http://localhost:8080/` (or the public port URL in Codespaces): welcome JSON **200**.

## Endpoints

### Auth

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/register` | Register freelancer account |
| POST | `/api/auth/login` | Login, returns JWT |

### Clients

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/clientes` | List my clients |
| GET | `/api/clientes/{id}` | Get client |
| POST | `/api/clientes` | Create client |
| PUT | `/api/clientes/{id}` | Update client |
| DELETE | `/api/clientes/{id}` | Delete client |

### Invoices

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/facturas` | List my invoices |
| GET | `/api/facturas/{id}` | Get invoice |
| POST | `/api/facturas` | Create invoice (auto VAT / withholding) |
| PATCH | `/api/facturas/{id}/estado` | Change status |
| DELETE | `/api/facturas/{id}` | Delete invoice (drafts only) |
| GET | `/api/facturas/{id}/pdf` | Download PDF |

## Business rules

### Amounts

- Taxable base = sum of (quantity × unit price) per line
- VAT = base × 21% (or configured rate)
- Withholding (IRPF) = base × 15% (or 7% for new freelancers)
- Total = base + VAT − withholding

### Invoice status

```text
BORRADOR → EMITIDA → PAGADA
                   → VENCIDA
         → CANCELADA
```

## Roadmap (beyond MVP)

- [ ] Yearly dashboard (revenue, VAT due, withholding)
- [ ] Email invoice to client
- [ ] Overdue reminders
- [ ] Model 130 export (fractional IRPF)
- [ ] React/Vue frontend
