# Facturo — API REST (ES)

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)
[![Abrir en GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/fcomartin94/facturo)

Idioma / Language (par recruiter **equivalente**):

- ES (este archivo): `README_ES.md`
- EN: `README_EN.md`

Recruiter (par **equivalente**):

- ES: `OVERVIEW_ES.md`
- EN: `OVERVIEW_EN.md`
- Entrada GitHub: `README.md`

Repositorio: [github.com/fcomartin94/facturo](https://github.com/fcomartin94/facturo). En cada push a `main`/`master` o en pull requests, el workflow **CI** ejecuta `./mvnw -B verify` (compila, tests y empaquetado). El estado del build se ve en el badge de arriba y en **Actions**.

Plataforma de facturación para autónomos. API REST con Java 21 + Spring Boot 3.

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Java 21 + Spring Boot 3.3 |
| Base de datos | PostgreSQL 16 |
| ORM | Spring Data JPA / Hibernate |
| Seguridad | Spring Security + JWT (jjwt) |
| PDF | iText 8 |
| Código limpio | Lombok + MapStruct |

## Arquitectura: multitenencia

Las tablas de `clientes` y `facturas` incluyen columna `autonomo_id`. Cada consulta filtra por el autónomo obtenido del JWT. Un usuario **no puede ver datos de otro**.

## Requisitos previos

- Java 21+ (o solo el Maven Wrapper `./mvnw`, que descarga Maven 3.9.x)
- Docker (Desktop en local, o el entorno de Codespaces)

## Arrancar en local

```bash
# 1. Levantar PostgreSQL
docker compose up -d

# 2. Arrancar la API (compila si hace falta)
./mvnw spring-boot:run
```

La API queda en `http://localhost:8080`.

### Variables de entorno (opcional)

Por defecto aplican los valores de desarrollo de `application.yml` y `docker-compose.yml`. Para producción u otros secretos:

| Variable | Descripción |
|----------|-------------|
| `FACTURO_JWT_SECRET` | Secreto JWT en **Base64** (sustituye el valor por defecto). |
| `FACTURO_JWT_EXPIRATION_MS` | Caducidad del token en milisegundos (por defecto 86400000). |
| `SPRING_DATASOURCE_URL` | JDBC de PostgreSQL |
| `SPRING_DATASOURCE_USERNAME` | Usuario de la base de datos |
| `SPRING_DATASOURCE_PASSWORD` | Contraseña |

Hay un ejemplo comentado en `.env.example`. Spring Boot lee estas variables del entorno; no hace falta archivo `.env` salvo que una herramienta lo cargue.

## Probar en GitHub Codespaces

[**Abrir en GitHub Codespaces**](https://codespaces.new/fcomartin94/facturo) (también el botón arriba): entorno con Java 21 y Docker. Si el contenedor entra en *recovery mode* tras cambios en `.devcontainer`, usa **Rebuild Container** tras `git pull`.

1. Tras abrir el codespace: `docker compose up -d` (espera unos segundos). Opcional: `./mvnw -DskipTests compile` la primera vez.
2. Arranca la API: `./mvnw spring-boot:run`
3. Puerto **8080**: pestaña **Ports** → **8080** → **Port visibility** → **Public**. Abre el enlace del puerto. La raíz **`/`** devuelve JSON (200). Sin token: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/api/clientes` debe ser **401**.
4. Sigue la guía de **`facturo-api.http`** (abajo).

## Guía: probar la API con `facturo-api.http`

Con la API en marcha y Postgres arriba (`docker compose up -d`).

### 1. Extensión REST Client

- En **Cursor** o **VS Code**: extensiones → **REST Client** (Huachao Mao) → **Install**.
- El workspace puede recomendar extensiones vía `.vscode/extensions.json`.

En **GitHub Codespaces** la devcontainer ya incluye la extensión.

### 2. Abrir el archivo

Abre **`facturo-api.http`**. Sobre cada petición aparece **Send Request**.

### 3. Orden sugerido

| Paso | Acción | Resultado esperado |
|------|--------|-------------------|
| A | **Send Request** en «1. Registrar nuevo autónomo» | **201** (o error si el email existe). |
| B | «2. Login» | **200** con JSON que incluye **`token`**. |
| C | Pega el **`token`** en la línea `@token = ...` del archivo. | — |
| D | «4. Crear cliente» | **201**; anota el **`id`** (p. ej. `1`). |
| E | «9. Crear factura…» | **`clienteId`** coherente con el cliente. **201** con totales. |
| F | «14. Descargar PDF» | PDF o cuerpo binario en la respuesta. |

Si hay **401**, revisa `@token` o repite login.

### 4. Navegador (opcional)

`http://localhost:8080/` (o URL pública del puerto en Codespaces): JSON de bienvenida **200**.

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
| POST | `/api/facturas` | Crear factura (IVA/IRPF automáticos) |
| PATCH | `/api/facturas/{id}/estado` | Cambiar estado |
| DELETE | `/api/facturas/{id}` | Eliminar factura (solo borradores) |
| GET | `/api/facturas/{id}/pdf` | Descargar PDF |

## Reglas de negocio

### Cálculo de importes

- Base imponible = suma de (cantidad × precio unitario) por línea
- Cuota IVA = base × 21% (u otro % indicado)
- Cuota IRPF = base × 15% (o 7% para nuevos autónomos)
- Total = base + IVA − IRPF

### Estados de factura

```text
BORRADOR → EMITIDA → PAGADA
                   → VENCIDA
         → CANCELADA
```

## Próximos pasos (fuera del MVP)

- [ ] Dashboard con resumen anual (ingresos, IVA pendiente, IRPF retenido)
- [ ] Envío de factura por email al cliente
- [ ] Recordatorios de facturas vencidas
- [ ] Exportación del modelo 130 (IRPF fraccionado)
- [ ] Frontend React/Vue
