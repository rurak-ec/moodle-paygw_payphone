# moodle-payphone

Pasarela de pago **PayPhone** (Ecuador) para Moodle — plugin de tipo `paygw`
(`paygw_payphone`).

El plugin vive en [`workspace/paygw_payphone/`](workspace/paygw_payphone/). Consulta su
[README](workspace/paygw_payphone/README.md) para instalación, configuración y uso, y el
[CHANGELOG](workspace/paygw_payphone/CHANGELOG.md) para el historial de versiones.

## Resumen

- Cobro con PayPhone mediante el flujo **"Botón de Pago" (redirección)** del subsistema de pagos
  de Moodle (`\core_payment`); consumible por *Inscripción de pago* (`enrol_fee`) y cualquier
  componente de pago.
- Moneda **USD** (Ecuador). Selector de **ambiente** (Prueba/Producción) con credenciales por
  ambiente.
- **Seguridad** auditada y endurecida: verificación TLS, validación server-side de monto/estado,
  entrega atómica idempotente, control de acceso, y tarea de reconciliación.

## Instalación (resumen)

Copia `workspace/paygw_payphone/` en `public/payment/gateway/payphone` de tu Moodle (5.1+) y visita
**Administración del sitio → Notificaciones** (o `php admin/cli/upgrade.php`).

## Licencia

GNU GPL v3 o posterior — ver [LICENSE](LICENSE).
