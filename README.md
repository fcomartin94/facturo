# Facturo 🧾

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)

Plataforma de facturación para autónomos. API REST construida con Java 21 + Spring Boot 3.

Repo: [github.com/fcomartin94/facturo](https://github.com/fcomartin94/facturo). En cada push a `main`/`master` o en pull requests, el workflow **CI** ejecuta `./mvnw -B verify` (compila, tests y empaquetado). El estado del build se ve en el badge de arriba y en **Actions**.

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Java 21 + Spring Boot 3.3 |
| Base de datos | PostgreSQL 16 |
| ORM | Spring Data JPA / Hibernate |
| Seguridad | Spring Security + JWT (jjwt) |
| PDF | iText 8 |
| Código limpio | Lombok + MapStruct |

## Arquitectura: Multitenencia

Todas las tablas `clientes` y `facturas` tienen columna `autonomo_id`. Cada query filtra automáticamente por el autónomo extraído del token JWT. Un usuario **nunca puede ver datos de otro**.

## Requisitos previos

- Java 21+ (o usar solo el Maven Wrapper `./mvnw`, que descarga Maven 3.9.x)
- Docker (Desktop en local, o el entorno de Codespaces)

## Arrancar en local

```bash
# 1. Levantar PostgreSQL
docker compose up -d

# 2. Arrancar la API (compila si hace falta)
./mvnw spring-boot:run
```

La API estará disponible en `http://localhost:8080`.

### Variables de entorno (opcional)

Por defecto valen los valores de desarrollo del `application.yml` y del `docker-compose.yml`. Para producción o secretos distintos, define por ejemplo:

| Variable | Descripción |
|----------|-------------|
| `FACTURO_JWT_SECRET` | Secreto JWT en **Base64** (sustituye el valor por defecto). |
| `FACTURO_JWT_EXPIRATION_MS` | Caducidad del token en milisegundos (por defecto 86400000). |
| `SPRING_DATASOURCE_URL` | JDBC de PostgreSQL |
| `SPRING_DATASOURCE_USERNAME` | Usuario de la base de datos |
| `SPRING_DATASOURCE_PASSWORD` | Contraseña |

Hay un ejemplo comentado en `.env.example` (Spring Boot lee estas variables del entorno; no hace falta archivo `.env` salvo que uses una herramienta que lo cargue).

## Probar en GitHub Codespaces

1. En el repo de GitHub: **Code → Create codespace on main** (abre el Dev Container con Java 21 y Docker).
2. Al crearse el entorno, se ejecuta `docker compose up -d` y un `compile` inicial. Si Postgres no estuviera listo, en la terminal: `docker compose up -d` y espera unos segundos.
3. En la terminal del codespace: `./mvnw spring-boot:run`
4. Abre el puerto **8080** (Codespaces lo enlaza automáticamente; si no, **Ports** → visibilidad *Public* para probar desde fuera).
5. Usa `facturo-api.http` con la extensión REST Client o los mismos `curl` del archivo.

## Testear la API

Abre `facturo-api.http` en Cursor (o IntelliJ) y ejecuta los requests en orden:

1. **Registro** → `POST /api/auth/register`
2. **Login** → `POST /api/auth/login` — copia el `token` de la respuesta
3. Pega el token en la variable `@token` del archivo `.http`
4. Crea un cliente → `POST /api/clientes`
5. Crea una factura → `POST /api/facturas`
6. Descarga el PDF → `GET /api/facturas/1/pdf` ⭐

## Endpoints

### Auth
| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/auth/register` | Registro de autónomo |
| POST | `/api/auth/login` | Login, devuelve JWT |

### Clientes
| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/clientes` | Listar mis clientes |
| GET | `/api/clientes/{id}` | Obtener cliente |
| POST | `/api/clientes` | Crear cliente |
| PUT | `/api/clientes/{id}` | Actualizar cliente |
| DELETE | `/api/clientes/{id}` | Eliminar cliente |

### Facturas
| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/facturas` | Listar mis facturas |
| GET | `/api/facturas/{id}` | Obtener factura |
| POST | `/api/facturas` | Crear factura (calcula IVA/IRPF automáticamente) |
| PATCH | `/api/facturas/{id}/estado` | Cambiar estado |
| DELETE | `/api/facturas/{id}` | Eliminar factura (solo borradores) |
| GET | `/api/facturas/{id}/pdf` | **Descargar PDF** |

## Reglas de negocio

### Cálculo de importes
- `Base imponible` = suma de (cantidad × precio unitario) de cada línea
- `Cuota IVA` = base × 21% (o el % indicado)
- `Cuota IRPF` = base × 15% (o 7% para nuevos autónomos)
- `Total` = base + IVA − IRPF

### Estados de factura
```
BORRADOR → EMITIDA → PAGADA
                   → VENCIDA
         → CANCELADA
```

## Próximos pasos (fuera del MVP)

- [ ] Dashboard con resumen anual (ingresos, IVA pendiente, IRPF retenido)
- [ ] Envío de factura por email al cliente
- [ ] Recordatorios de facturas vencidas
- [ ] Exportación del modelo 130 (pago fraccionado IRPF)
- [ ] Frontend React/Vue
