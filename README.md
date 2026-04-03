# Facturo 🧾

[![CI](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml/badge.svg)](https://github.com/fcomartin94/facturo/actions/workflows/ci.yml)
[![Abrir en GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/fcomartin94/facturo)

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

[**Abrir en GitHub Codespaces**](https://codespaces.new/fcomartin94/facturo) (botón también arriba) — crea un entorno en el navegador con Java 21 y Docker; no hace falta instalar nada en local. Si el contenedor entrara en *recovery mode* tras un cambio en `.devcontainer`, usa **Rebuild Container** tras hacer `git pull`.

1. Tras abrir el codespace, en la terminal levanta Postgres: `docker compose up -d` (espera unos segundos). La primera vez puedes compilar con `./mvnw -DskipTests compile` si quieres comprobar el build.
2. Arranca la API: `./mvnw spring-boot:run`
3. Puertos **8080**: pestaña **Ports** → puerto **8080** → **Port visibility** → **Public**. Abre el enlace del puerto (icono de globo / “Open in browser”). La raíz **`/`** devuelve un JSON de bienvenida (200). Para comprobar seguridad: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/api/clientes` debe dar **401** sin token. Si el navegador sigue sin cargar, comprueba que la app está en marcha y que usas el **enlace actual** de Ports (cambia al reiniciar el codespace).
4. Sigue la **guía de prueba con `facturo-api.http`** (abajo).

## Guía: probar la API con `facturo-api.http`

Con la API en marcha (`./mvnw spring-boot:run`) y Postgres arriba (`docker compose up -d`).

### 1. Instalar la extensión REST Client

- En **Cursor** o **VS Code**: `Cmd+Shift+X` (extensiones) → busca **REST Client** → autor **Huachao Mao** → **Install**.
- Si abriste esta carpeta como proyecto, puede salir un aviso *“Workspace recommends…”* por `.vscode/extensions.json`; acepta instalar.

En **GitHub Codespaces** la devcontainer ya incluye esta extensión; no hace falta instalarla a mano.

### 2. Abrir el archivo de peticiones

Abre **`facturo-api.http`** en el editor. Encima de cada petición verás el enlace **`Send Request`** (a veces al pasar el ratón por la primera línea del bloque).

### 3. Ejecutar en este orden

| Paso | Qué hacer | Qué deberías ver |
|------|-----------|------------------|
| A | Pulsa **Send Request** en **“1. Registrar nuevo autónomo”** | Respuesta **201** con datos del usuario (o error si el email ya existe). |
| B | **“2. Login”** | Respuesta **200** con un JSON que incluye **`token`**. |
| C | Copia el valor de **`token`** (sin comillas). En la **línea 3** del archivo, deja `@token = eyJ...` (pega tu token). | — |
| D | **“4. Crear cliente”** | **201** y el cliente creado (anota el **`id`**, suele ser `1`). |
| E | **“9. Crear factura (IVA 21%…)”** | Asegúrate de que `"clienteId"` coincide con el cliente (p. ej. `1`). Respuesta **201** con totales. |
| F | **“14. Descargar PDF”** | Se abre/descarga el PDF o ves datos binarios en el panel de respuesta. |

Si algo falla con **401**, el token no está bien en `@token` o caducó: vuelve al paso **Login** (B) y actualiza `@token`.

### 4. Comprobar en el navegador (opcional)

Abre `http://localhost:8080/` (o el enlace **Public** del puerto 8080 en Codespaces): JSON de bienvenida **200**.

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
