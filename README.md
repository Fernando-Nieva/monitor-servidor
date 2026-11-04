# Monitor del Servidor

Panel web que muestra el estado del equipo donde corre la aplicación: CPU, memoria, disco, tiempo encendido y temperatura.

Proyecto de **Programación IV**, UTN FRRe, cohorte 2026.

Este README es igual en todas las ramas, así que podés compararlas desde cualquiera de ellas.

## Ramas

Las ramas son acumulativas: cada una parte de la anterior y le agrega algo.

```text
main  ──►  develop  ──►  WebSockets
base       + GraphQL     + tiempo real
           + rediseño    + temperatura real
```

| | `main` | `develop` | `WebSockets` |
|---|---|---|---|
| **Objetivo** | Monitor básico | Sumar GraphQL y mejorar la interfaz | Datos en tiempo real |
| **Cómo se actualiza la página** | Consulta HTTP cada 5 s | Consulta HTTP cada 5 s | WebSockets, con consulta HTTP de respaldo |
| **API REST** (`/api/metrics`) | Sí | Sí | Sí, y además emite las métricas por WebSocket |
| **API GraphQL** (`/graphql`) | No | Sí | Sí, con el campo extra `board_temp_c` |
| **Interfaz** | Vista simple | Rediseño con 3 medidores | 4 medidores, estados e historial de la sesión |
| **Temperatura del CPU** | Vía WMI: requiere permisos de administrador | Igual que `main` | Sensor real con LibreHardwareMonitor |
| **Procesos necesarios** | `serve` | `serve` | `serve`, `reverb:start` y `monitor:stream` |
| **Herramientas de IA** | No | No | Laravel Boost (guías, skills y MCP) |

### `main`: la base

Una vista Blade que usa JavaScript para pedir `/api/metrics` cada 5 segundos y actualizar los valores. Incluye lo mínimo para entender el flujo completo del proyecto.

### `develop`: GraphQL y rediseño

Las métricas se leen igual que en `main`. Cambian dos cosas:

- **API GraphQL:** con Lighthouse, para pedir solo los campos necesarios. El esquema está en `graphql/schema.graphql` y el resolver en `app/GraphQL/Queries/Metrics.php`.
- **Interfaz:** nuevo diseño con medidores circulares para CPU, memoria y disco.

### `WebSockets`: tiempo real

El servidor envía los datos en lugar de esperar a que la página los pida:

```text
monitor:stream ──► evento ServerMetricsUpdated ──► Reverb ──► Laravel Echo ──► página
                   (canal público server-metrics)
```

- **Respaldo:** si pasan 10 segundos sin datos, la página consulta `/api/metrics` y muestra "Sondeo cada 5 s".
- **Temperatura:** `cpu_temp_c` se lee de LibreHardwareMonitor. El dato de la placa va aparte, en `board_temp_c`, y la página lo marca como "Referencial" cuando no hay sensor, porque no refleja el calor del procesador.
- **Tests:** agrega `tests/Feature/ServerMetricsTemperatureTest.php`.

## Tecnologías

| Tecnología | Para qué se usa | Ramas |
|---|---|---|
| PHP 8.3 o superior | Lenguaje del servidor | Todas |
| Laravel 13 | Framework: rutas, controladores y vistas | Todas |
| Blade, CSS y JavaScript | Interfaz, sin frameworks de frontend | Todas |
| PowerShell y CIM/WMI | Lectura de CPU, memoria y tiempo encendido en Windows | Todas |
| PHPUnit | Tests | Todas |
| Lighthouse | API GraphQL | `develop`, `WebSockets` |
| Laravel Reverb | Servidor de WebSockets | `WebSockets` |
| Laravel Echo y Pusher JS | Cliente WebSocket, cargado desde un CDN | `WebSockets` |
| LibreHardwareMonitor | Temperatura real del procesador | `WebSockets` |
| Laravel Boost | Guías y herramientas para asistentes de IA | `WebSockets` |

Vite y Tailwind vienen con la plantilla de Laravel, pero la vista del monitor no los usa: tiene su propio CSS.

## Cómo funciona

En todas las ramas, el centro es `app/Services/ServerMetrics.php`. Su método `snapshot()` arma un array con las métricas:

- **Disco:** funciones nativas de PHP, sobre la unidad donde está el proyecto.
- **CPU, memoria y tiempo encendido:** un script de PowerShell que PHP ejecuta y cuyo resultado JSON interpreta.

| Ruta | Devuelve | Ramas |
|---|---|---|
| `GET /` | Redirige a `/servidor` | Todas |
| `GET /servidor` | La página del monitor | Todas |
| `GET /api/metrics` | Las métricas en JSON (máximo 60 pedidos por minuto) | Todas |
| `POST /graphql` | Los campos pedidos en la consulta | `develop`, `WebSockets` |

Ejemplo de consulta GraphQL:

```graphql
{
  metrics {
    hostname
    cpu_pct
    memory { used_pct }
    disk { used_pct }
  }
}
```

## Instalación

Requiere PHP 8.3 o superior, Composer y Node.js.

```sh
git clone https://github.com/Fernando-Nieva/monitor-servidor.git
cd monitor-servidor
git switch main   # o develop, o WebSockets
composer run setup
php artisan serve
```

Abrí <http://localhost:8000/servidor>.

Las dependencias cambian según la rama, así que después de cada `git switch` ejecutá `composer install`.

### Pasos adicionales en `WebSockets`

1. Agregá esto al `.env`, reemplazando los valores de ejemplo:

   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=monitor
   REVERB_APP_KEY=una-clave-local
   REVERB_APP_SECRET=un-secreto-local
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

2. Ejecutá `php artisan config:clear` y dejá corriendo cada comando en su propia terminal:

   ```sh
   php artisan serve
   php artisan reverb:start
   php artisan monitor:stream
   ```

3. Para ver la temperatura real del CPU, instalá LibreHardwareMonitor:

   ```sh
   winget install LibreHardwareMonitor.LibreHardwareMonitor
   ```

   Abrilo como administrador y activá `Options → Remote Web Server → Run` (puerto 8085). La aplicación lo lee en `http://127.0.0.1:8085/data.json`; esa dirección se cambia con `LIBRE_HARDWARE_MONITOR_URL`.

> **Atención:** el servidor web de LibreHardwareMonitor escucha en todas las interfaces y sin contraseña. No le abras el puerto 8085 en el firewall.

## Limitaciones conocidas

- **Solo Windows:** en otros sistemas, CPU, memoria y tiempo encendido aparecen como no disponibles.
- **Temperatura en `main` y `develop`:** sin permisos de administrador aparece "No disponible". Esa fuente es una zona térmica ACPI de la placa: en el equipo de pruebas no varió con la CPU al 100%, así que no sirve como temperatura del procesador.
- **LibreHardwareMonitor no arranca solo:** si está cerrado, la rama `WebSockets` muestra el dato "Referencial" de la placa.
- **Canal público:** cualquiera que pueda conectarse a Reverb puede recibir las métricas. No está pensado para exponerse a internet.
- **`ExampleTest` falla:** espera un 200 en `/`, pero esa ruta redirige a `/servidor`.

## Tests

```sh
php artisan test
```
