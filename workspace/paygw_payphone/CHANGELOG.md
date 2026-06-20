# Registro de cambios

Todas las versiones notables de `paygw_payphone` se documentan aquí.
El formato sigue, de forma aproximada, [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).

## [1.2.1] — 2026-06-20 — Preparación para el directorio de moodle.org

### Cambiado
- `version.php`: declarado `$plugin->supported = [501, 502]` (Moodle 5.1–5.2).
- La tarea de reconciliación ahora procesa como máximo 100 filas por ejecución (las más antiguas
  primero), para que un backlog grande no sobrecargue el cron.
- Estilo de código conforme a `phpcs --standard=moodle` (0/0); el CSS del interruptor de la pasarela
  se construye por concatenación para respetar el límite de longitud de línea.

### Añadido
- CI con GitHub Actions (`moodle-plugin-ci`) en PHP 8.2–8.4 × Moodle 5.1/5.2 (PostgreSQL + MariaDB),
  más un job `main` (5.3-dev) no bloqueante.
- Tests PHPUnit para `payphone_helper` (resolución de credenciales, parsing de errores, idempotencia
  de `finalise_transaction`) y para `gateway` (moneda y validación del formulario).
- Pack de idioma **solo inglés**; el español se trasladó a `/translations` para AMOS.
- `CONTRIBUTING.md` y `scripts/package_workspace.sh`.

## [1.2.0] — 2026-06-19 — Selector de ambiente funcional (patrón oficial)

Investigación de la documentación oficial de PayPhone: usa **una sola URL** para prueba y
producción; el ambiente lo define **el token**. Implementado el patrón oficial (estilo plugin
WooCommerce de PayPhone):

### Cambiado
- El campo **Ambiente** ahora es **funcional**: elige qué par de credenciales se envía en runtime
  (la URL `API_BASE` sigue siendo constante).
- Credenciales **por ambiente**: **Token/Store ID de Prueba** y **de Producción**. Con `hideIf` solo
  se muestran los 2 campos del ambiente seleccionado (funcional y simple). Tokens enmascarados.
- Selección centralizada en `payphone_helper::for_config()` (única fuente de verdad), usada por
  `pay.php` y la reconciliación; con **fallback a los campos legacy** `token`/`storeid` (sin pérdida
  de datos al actualizar).
- Validación al habilitar: exige el par del ambiente seleccionado.
- `version` → `2026061803`, `release` → `1.2.0`.

### Corregido
- `payphone_helper::request()` ahora carga `lib/filelib.php` explícitamente: la clase `\curl` no
  está disponible en el bootstrap mínimo de CLI/cron, por lo que la **tarea de reconciliación**
  habría fallado al contactar a PayPhone. Verificado además que la verificación TLS
  (`CURLOPT_SSL_VERIFYPEER=1` + TLS 1.2) es compatible con el certificado real de PayPhone.
- **UI:** el campo Token usa ahora un `password` (input enmascarado limpio) en vez de
  `passwordunmask`, que rompía el ancho del formulario. Repuebla el valor guardado y no lo borra al
  reguardar.

## [1.1.0] — 2026-06-18 — Blindaje de seguridad

Auditoría de seguridad exhaustiva (línea por línea, Moodle + PayPhone + OWASP) y endurecimiento
profesional de toda la pasarela. Cambios:

### Seguridad
- **TLS:** se fuerza `CURLOPT_SSL_VERIFYPEER=1`, `CURLOPT_SSL_VERIFYHOST=2` y piso `TLSv1.2` en
  todas las llamadas a PayPhone (Moodle deja la verificación de certificado **desactivada** por
  defecto). Cierra MITM/robo de token/`Confirm` falsificado.
- **Entrega atómica anti doble-cobro:** la transición `pending → approved` se hace con bloqueo de
  fila (`SELECT … FOR UPDATE` dentro de transacción); el pago se guarda antes de entregar y la
  entrega es idempotente/re-ejecutable. Estados: `pending → approved → completed`.
- **Autorización (IDOR/BOLA):** `process.php` y `cancelled.php` exigen que la transacción
  pertenezca al usuario en sesión.
- **Open-redirect/SSRF:** `pay.php` solo redirige a `https://*.payphonetodoesposible.com`.
- **CSRF:** `pay.php` exige `sesskey` (incluido por el JS del modal). `cancelled.php` se protege por
  ownership (no puede llevar sesskey al ser URL de retorno).
- **Inyección de parámetros (JS):** la URL se arma con `URLSearchParams` (codificación correcta).
- **Validación de moneda fail-closed** y tipos `PARAM_COMPONENT`/`PARAM_AREA`.
- **`clientTransactionId` de alta entropía** (sin filtrar userid/itemid/tiempo).
- **Privacidad:** se declara la transmisión externa a PayPhone (`add_external_location_link`) y el
  campo `status`. **Token enmascarado** en el formulario (`passwordunmask`).

### Añadido
- **Tarea de reconciliación** (`paygw_payphone\task\reconcile_pending`, cada 10 min): finaliza
  transacciones que quedaron sin resolver por una interrupción del retorno (caso "pagado pero no
  entregado") reusando la misma ruta atómica; cancela las abandonadas. Nuevo campo
  `transactionid` en la tabla.

### Cambiado
- `version` → `2026061802`, `release` → `1.1.0`.

## [1.0.0] — 2026-06-18

### Añadido
- Versión inicial de la pasarela de pago **PayPhone** para Moodle (`paygw_payphone`).
- Flujo **"Botón de Pago" (redirección)**: `pay.php` (Prepare → redirige a PayPhone) y
  `process.php` (Confirm al volver), más `cancelled.php` para cancelaciones.
- Cliente de API `classes/payphone_helper.php` (`prepare()` / `confirm()`) usando la clase `\curl`
  del núcleo, con reintentos y manejo de errores.
- Tabla `paygw_payphone` para rastrear transacciones (idempotencia por `clientTransactionId`).
- **Validación de seguridad en el servidor** al confirmar: estado aprobado (`statusCode == 3`),
  monto y moneda; guard anti doble-entrega (`pending → approved`).
- Configuración por cuenta de pago: **Token**, **Store ID** y **Ambiente** (Prueba/Producción),
  con limpieza automática de espacios en las credenciales.
- **Instrucciones in-product** que muestran el **Dominio web** y la **Url de respuesta/cancelación
  reales del sitio** (generadas con `$CFG->wwwroot`) para copiar en la consola de PayPhone.
- Página global de ajustes convertida en **guía** con enlace directo a *Cuentas de pago*
  (se eliminó el campo "Cargo adicional"/surcharge: ilegal trasladar la comisión en Ecuador).
- Idiomas **español** e **inglés**.
- Cumplimiento de privacidad (`privacy/provider.php`) sobre la tabla del plugin.
- Documentación: `README.md`, `CHANGELOG.md` y `LICENSE`.

### Notas
- Moneda soportada: **USD** (Ecuador).
- Probado en **Moodle 5.1.3** con **"Inscripción de pago"** (`enrol_fee`).
