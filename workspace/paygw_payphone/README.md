# Pasarela de pago PayPhone para Moodle (`paygw_payphone`)

Plugin de **portal de pago** (`paygw`) que permite cobrar en Moodle con **PayPhone**, el procesador
de pagos ecuatoriano. Usa el método **"Botón de Pago" (redirección)**: el estudiante es enviado a la
página segura de PayPhone, paga, y al volver el servidor confirma el pago y entrega lo comprado
(por ejemplo, la matrícula del curso).

Se integra con el subsistema de pagos del núcleo de Moodle (`\core_payment`), por lo que funciona con
cualquier componente que cobre a través de él, como **"Inscripción de pago"** (`enrol_fee`).

---

## Requisitos

- **Moodle 4.5** o superior (`$plugin->requires = 2024100700`).
- Una cuenta **PayPhone Business** (Ecuador) con acceso a la **consola de desarrollador**.
- Moneda: **USD** (única que maneja PayPhone).
- El sitio Moodle debe estar publicado por **HTTPS** (requerido por PayPhone en producción).

---

## Instalación

1. Copia la carpeta del plugin en tu Moodle:
   ```
   public/payment/gateway/payphone
   ```
   (en instalaciones antiguas sin el directorio `public/`, sería `payment/gateway/payphone`).
2. Entra como administrador → **Administración del sitio → Notificaciones**, o por consola:
   ```
   php admin/cli/upgrade.php
   ```
3. Moodle instalará el plugin y creará la tabla `paygw_payphone`.

---

## Modelo de cobro (importante)

- **Comisión:** PayPhone cobra al **comercio** aproximadamente **5% + IVA (≈ 5,75%)** por transacción
  y la descuenta automáticamente de su lado. **En Moodle no se configura nada de comisiones.**
- **Tú recibes:** el precio del curso menos la comisión de PayPhone, liquidado a tu banco en
  **~24–48 horas**.
- **Recargo al comprador:** la ley ecuatoriana de protección al consumidor **prohíbe** trasladar la
  comisión al pago con tarjeta. Por eso este plugin **no incluye** el campo "Cargo adicional"
  (surcharge); si necesitas cubrir el costo, inclúyelo en el precio del curso.
- **Monto:** lo defines libremente (mínimo aprox. **$1 USD**). Solo **USD**.
- **Datos de tarjeta:** nunca pasan por Moodle; el formulario lo aloja PayPhone (cumplimiento PCI a
  cargo de PayPhone).

---

## Paso 1 — Crear la aplicación en la consola de PayPhone

Entra a la **consola de desarrollador**: <https://appdeveloper.payphonetodoesposible.com/>

1. Inicia sesión con tu cuenta **PayPhone Business** y agrega un usuario con rol **Developer**.
2. Ve a **Crear Aplicación** y complétala con estos valores:

   | Campo | Valor |
   |---|---|
   | Plataforma Desarrollo | `PHP` |
   | Tipo de Aplicación | `Web` |
   | Categoría | `Educación` (o la que corresponda) |
   | **Dominio web** | el host de tu Moodle, p. ej. `mi-moodle.ejemplo.com` |
   | **Url de respuesta** | `https://<tu-moodle>/payment/gateway/payphone/process.php` |

   > La **Url de respuesta** y la **Url de cancelación** exactas de TU sitio aparecen también dentro
   > de Moodle, en la pantalla de configuración del portal PayPhone (listas para copiar). La de
   > cancelación es `https://<tu-moodle>/payment/gateway/payphone/cancelled.php`.

3. Guarda la aplicación.
4. Abre la pestaña **Credenciales** y copia el **Token** y el **Store ID**.

> **Ambiente de prueba:** usa la sección **Probadores** / token de **Prueba** para testear sin
> cobros reales (todas las transacciones se aprueban sin contactar al banco). Cuando todo funcione,
> repite con las credenciales de **Producción**.

---

## Paso 2 — Configurar la cuenta de pago en Moodle

1. **Administración del sitio → Servidor → Pagos → Cuentas de pago** → *Crear cuenta de pagos*.
   - Nombre: el que quieras (p. ej. "PayPhone"). El **Número ID es opcional** (déjalo vacío).
   - Marca **Habilitar** y guarda.
2. En la lista de cuentas, abre tu cuenta → en los portales de pago, abre **PayPhone**.
3. Verás las instrucciones con las URLs de tu sitio. Completa:
   - **Ambiente:** `Prueba` (sandbox) o `Producción`. Define **qué par de credenciales se usa**
     (PayPhone usa la misma URL en ambos; lo que cambia es el token).
   - **Token de Prueba / Store ID de Prueba** y/o **Token de Producción / Store ID de Producción**
     copiados de la consola. Solo se muestran los campos del ambiente seleccionado; puedes
     configurar ambos pares y alternar cuando quieras.
   - Marca **Habilitar** y guarda.

> El token y el Store ID los obtienes **por ambiente** en la consola developer (una app de Prueba y
> una de Producción dan credenciales distintas). El plugin envía las del ambiente seleccionado.

---

## Paso 3 — Cobrar en un curso (ejemplo con `enrol_fee`)

1. En el curso → **Participantes → Métodos de matriculación → Añadir método → "Inscripción de pago"**.
2. Elige la **cuenta de pago** creada, define el **costo** y la **moneda USD** (PayPhone solo aparece
   con USD), y guarda.
3. Un estudiante que entre al curso verá el botón de pago → elige **PayPhone** → es redirigido a
   PayPhone → paga → vuelve y queda **matriculado** automáticamente.

---

## Cómo funciona (flujo técnico)

```
Estudiante pulsa Pagar y elige PayPhone
        │
        ▼
pay.php   ── Prepare ──▶  PayPhone (devuelve URLs de pago)
   guarda fila "pending" en paygw_payphone (idempotencia: clientTransactionId único)
        │ redirige el navegador a la página de PayPhone
        ▼
   El estudiante paga en PayPhone
        │ PayPhone redirige a la Url de respuesta con ?id=..&clientTransactionId=..
        ▼
process.php  ── Confirm ──▶  PayPhone (estado final + monto)
   valida statusCode == 3 (Aprobado)  Y  monto == esperado  Y  moneda == USD
   marca "approved" (anti doble-entrega) → save_payment() → deliver_order()
        │
        ▼
   Redirige a la URL de éxito (p. ej. el curso, ya matriculado)
```

- `cancelled.php` maneja la cancelación y devuelve al curso con aviso.
- Toda la información de la transacción se guarda en la tabla **`paygw_payphone`**.

### Archivos principales
| Archivo | Rol |
|---|---|
| `classes/gateway.php` | Moneda (USD), formulario de configuración (Token/Store ID/Ambiente) e instrucciones. |
| `classes/payphone_helper.php` | Único código que habla con la API de PayPhone: `prepare()` y `confirm()`. |
| `pay.php` | Inicia el pago (Prepare) y redirige a PayPhone. |
| `process.php` | Url de respuesta: confirma, valida y entrega la orden. |
| `cancelled.php` | Url de cancelación. |
| `db/install.xml` | Tabla `paygw_payphone`. |

---

## Seguridad

- **Validación en el servidor** al volver: se exige `statusCode == 3`, que el **monto** coincida
  (en centavos) y que la **moneda** sea la esperada. Nunca se confía en los parámetros de la URL.
- **Idempotencia:** `clientTransactionId` único por intento; la transición `pending → approved` evita
  entregar dos veces si el usuario recarga la página de retorno.
- **Confirmación síncrona:** PayPhone revierte automáticamente la transacción si no se confirma en
  ~5 minutos; el plugin confirma de inmediato al volver (con reintentos ante fallos de red).
- **Dominio + HTTPS:** el formulario y el retorno solo funcionan en el **Dominio web** registrado en
  la consola; usa HTTPS en producción.
- **Sin datos de tarjeta** en Moodle: los captura PayPhone.

---

## Solución de problemas

- **"Este valor no es válido" en la página global del plugin:** esa página es solo informativa; las
  credenciales **no** van ahí, sino en la **cuenta de pago** (Paso 2).
- **PayPhone no aparece al matricular:** revisa que el **costo esté en USD** (PayPhone solo soporta
  USD) y que el portal esté **Habilitado** en la cuenta de pago.
- **El pago no vuelve / falla la confirmación:** verifica que el **Dominio web** y la **Url de
  respuesta** registrados en la consola coincidan **exactamente** con los de tu sitio (incluido
  `https://`).
- **Funciona en prueba pero no en producción:** asegúrate de usar el **Token de Producción** con el
  **Ambiente: Producción**.

---

## Licencia

GNU GPL v3 o posterior. Consulta el archivo [LICENSE](LICENSE).
