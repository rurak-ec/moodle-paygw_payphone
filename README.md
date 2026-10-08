# PayPhone payment gateway (paygw_payphone)

A Moodle **payment gateway** that lets a site charge with **PayPhone** (Ecuador) through Moodle's
core payment subsystem (`\core_payment`), usable by Fee enrolment (`enrol_fee`) and any payment area.

- **Component:** `paygw_payphone`
- **Supported Moodle:** 4.5 – 5.3 LTS (`$plugin->supported = [405, 503]`)
- **Currency:** USD (Ecuador)
- **License:** GNU GPL v3 or later
- **Issues:** <https://github.com/rurak-ec/moodle-paygw_payphone/issues>

> Development repository. The installable plugin lives in
> [`workspace/paygw_payphone`](workspace/paygw_payphone). See its
> [README](workspace/paygw_payphone/README.md) and
> [CHANGELOG](workspace/paygw_payphone/CHANGELOG.md) for details.

---

## English

### What it does
- Charges via PayPhone's **"Botón de Pago" (redirect)** flow: `pay.php` derives the amount
  server-side from core payment, creates the transaction and redirects to PayPhone's hosted page;
  `process.php` confirms it server-to-server on return and delivers the order.
- A **test/live environment selector** with per-environment credentials (Token + Store ID).

### Security highlights
- The amount is computed server-side (never trusted from the client).
- Server-to-server **Confirm** is the only source of truth: status, amount and currency are
  validated fail-closed before the order is delivered.
- TLS peer/host verification is forced; credentials are never logged.
- Delivery is atomic and idempotent (row-locked), with a **reconciliation task** that finishes
  transactions interrupted on return.
- Privacy provider exports/deletes the stored transaction data.

### Installation
1. Build the ZIP: `./scripts/package_workspace.sh`
2. Install via **Site administration → Plugins → Install plugins**, or copy
   `workspace/paygw_payphone` to `payment/gateway/payphone` and run the upgrade.
3. Add your Token + Store ID to a payment account (**Site administration → Payments → Payment
   accounts**).

### Languages
Ships **English only** (plugins-directory policy); the Spanish pack is kept under
[`/translations`](translations/) for lang.moodle.org (AMOS) after approval.

---

## Español

### Qué hace
- Cobra con PayPhone mediante el flujo **"Botón de Pago" (redirección)**: `pay.php` calcula el
  importe en el servidor desde el subsistema de pagos, crea la transacción y redirige a la página
  de PayPhone; `process.php` la confirma servidor-a-servidor al volver y entrega el pedido.
- **Selector de ambiente** (Prueba/Producción) con credenciales por ambiente (Token + Store ID).

### Seguridad
- El importe se calcula en el servidor (nunca se confía en el cliente).
- La **confirmación servidor-a-servidor** es la única fuente de verdad: estado, monto y moneda se
  validan *fail-closed* antes de entregar el pedido.
- Verificación TLS forzada; las credenciales nunca se registran.
- Entrega atómica e idempotente (con bloqueo de fila) y **tarea de reconciliación**.
- El proveedor de privacidad exporta/borra los datos de transacción almacenados.

### Instalación
1. Generar el ZIP: `./scripts/package_workspace.sh`
2. Instalar desde **Administración del sitio → Plugins → Instalar plugins**, o copiar
   `workspace/paygw_payphone` a `payment/gateway/payphone` y ejecutar la actualización.
3. Añadir Token + Store ID a una cuenta de pago.

### Idiomas
Se publica **solo en inglés** (política del directorio); el español se conserva en
[`/translations`](translations/) para lang.moodle.org (AMOS) tras la aprobación.

## License
GNU GPL v3 or later — see [LICENSE](LICENSE).
