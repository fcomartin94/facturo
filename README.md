# Facturo

Invoicing web application for Spanish freelancers (*autónomos*). The project ships **two independent REST API backends** — one in Java and one in PHP — plus a shared Vanilla JS frontend. Both backends implement identical endpoints so the frontend can switch between them at runtime.

```
facturo/
├── facturo-java/       Spring Boot 3.3 REST API  (port 8080)
├── facturo-php/        PHP 8.2 bare-metal REST API (port 8000)
└── facturo-frontend/   Vanilla JS SPA (no build step)
```

---

## Tech stack

| Layer | Java backend | PHP backend |
|---|---|---|
| Runtime | Java 21 | PHP 8.2 |
| Framework | Spring Boot 3.3 | Bare-metal (PSR-4 via Composer) |
| ORM / DB access | Spring Data JPA / Hibernate | PDO with prepared statements |
| Database | PostgreSQL 16 | PostgreSQL 16 (same instance) |
| Auth | JWT via jjwt 0.12 | JWT via lcobucci/jwt 5 |
| PDF generation | Flying Saucer + Thymeleaf | Dompdf 2 |
| Other | Lombok, MapStruct | vlucas/phpdotenv |

**Frontend:** Vanilla JS ES Modules — no framework, no build step.

---

## Prerequisites

- Docker (for PostgreSQL)
- Java 21 + Maven 3.9+
- PHP 8.2 + Composer 2
- A static file server or any browser that supports ES Modules from `file://` / `localhost`

---

## 1 — Start the database

Both backends share the same PostgreSQL instance. Start it once:

```bash
cd facturo-php
docker compose up -d
```

This creates the `facturo_db` database with user `facturo_user` / password `facturo_pass` on port `5432`.

Apply the schema (only the first time):

```bash
psql -h localhost -U facturo_user -d facturo_db -f facturo-php/schema.sql
```

---

## 2 — Run the Java backend

```bash
cd facturo-java
./mvnw spring-boot:run
```

The API starts on **http://localhost:8080**.  
Configuration is in `src/main/resources/application.yml`; all values can be overridden with environment variables:

| Variable | Default |
|---|---|
| `SPRING_DATASOURCE_URL` | `jdbc:postgresql://localhost:5432/facturo_db` |
| `SPRING_DATASOURCE_USERNAME` | `facturo_user` |
| `SPRING_DATASOURCE_PASSWORD` | `facturo_pass` |
| `FACTURO_JWT_SECRET` | base64-encoded default (change in production) |
| `FACTURO_JWT_EXPIRATION_MS` | `86400000` (24 h) |

---

## 3 — Run the PHP backend

```bash
cd facturo-php
composer install
cp .env .env.local   # edit JWT_SECRET with: openssl rand -base64 32
php -S localhost:8000 router.php
```

The API starts on **http://localhost:8000**.  
Environment variables (`.env`):

| Variable | Default |
|---|---|
| `DB_HOST` | `localhost` |
| `DB_PORT` | `5432` |
| `DB_NAME` | `facturo_db` |
| `DB_USER` | `facturo_user` |
| `DB_PASS` | `facturo_pass` |
| `JWT_SECRET` | ⚠️ **must be changed** |
| `JWT_EXPIRATION` | `86400` (seconds) |

---

## 4 — Open the frontend

Serve `facturo-frontend/` with any static server:

```bash
cd facturo-frontend
npx serve .
# or: python3 -m http.server 3000
```

Then open `http://localhost:3000` in a browser.

On the login screen you can choose which backend to use (Java or PHP). The selection is stored in `localStorage` and all subsequent API calls go to the chosen backend.

---

## API reference

All protected endpoints require the header:

```
Authorization: Bearer <token>
```

### Auth (public)

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register a new user. Returns `{ autonomo, token }`. |
| `POST` | `/api/auth/login` | Authenticate. Returns `{ autonomo, token }`. |

### Clients (protected)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/clientes` | List all clients for the authenticated user. |
| `POST` | `/api/clientes` | Create a new client. |
| `GET` | `/api/clientes/{id}` | Get a single client. |
| `PUT` | `/api/clientes/{id}` | Update a client. |
| `DELETE` | `/api/clientes/{id}` | Delete a client. |

### Invoices (protected)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/facturas` | List all invoices (newest first). |
| `POST` | `/api/facturas` | Create an invoice with line items. Totals are calculated server-side. |
| `GET` | `/api/facturas/{id}` | Get an invoice with its line items. |
| `PATCH` | `/api/facturas/{id}/estado` | Update invoice status (`BORRADOR` / `EMITIDA` / `PAGADA` / `VENCIDA` / `CANCELADA`). |
| `GET` | `/api/facturas/{id}/pdf` | Download the invoice as a PDF file. |

---

## Invoice totals calculation

Amounts are always computed server-side with exact decimal arithmetic (Java: `BigDecimal`; PHP: `bcmath`):

```
base_imponible  = sum(cantidad × precio_unitario)
cuota_iva       = base_imponible × (porcentaje_iva  / 100)   default 21%
cuota_irpf      = base_imponible × (porcentaje_irpf / 100)   default 15%
total           = base_imponible + cuota_iva − cuota_irpf
```

---

## Security notes

- Passwords are hashed with BCrypt (cost 12), compatible between both backends.
- JWT tokens are stateless; the `sub` claim stores the user identifier.
- All data queries filter by `autonomo_id` to prevent cross-user data access (IDOR protection).
- CORS is open (`*`) for local development — restrict the allowed origin in production.

---

## Project structure

### facturo-java

```
src/main/java/com/facturo/
├── config/         Spring Security and CORS configuration
├── controller/     REST controllers (Auth, Cliente, Factura, Root)
├── dto/            Request and response DTOs
├── entity/         JPA entities (Autonomo, Cliente, Factura, LineaFactura)
├── exception/      Domain exceptions + global exception handler
├── mapper/         MapStruct mappers (entity → DTO)
├── repository/     Spring Data JPA repositories
├── security/       JWT filter and utility
└── service/        Business logic (Auth, Cliente, Factura, PDF)
```

### facturo-php

```
src/
├── Controller/     HTTP controllers (Auth, Cliente, Factura, Root)
├── Database/       PDO singleton
├── Exception/      Domain exceptions (Business, NotFound, Validation)
├── Http/           Response helpers
├── Model/          Immutable value objects (Autonomo, Cliente, Factura, LineaFactura)
├── Repository/     PDO-based repositories
├── Security/       JWT middleware and utility
├── Service/        Business logic (Auth, Cliente, Factura, PDF)
├── Templates/      Dompdf HTML template for PDF generation
└── Validation/     Fluent validator
```

### facturo-frontend

```
├── index.html          Login / registration + backend selector
├── clientes.html       Client management
├── facturas.html       Invoice management and PDF download
├── css/style.css
└── js/
    ├── config.js       Backend URLs and selector logic
    ├── api.js          Fetch wrapper (reads selected backend dynamically)
    ├── auth.js         Session helpers (JWT storage, requireAuth)
    ├── clientes.js     Client CRUD logic
    └── facturas.js     Invoice CRUD + PDF download logic
```

---

## License

MIT
