# Contributing to the PayPhone payment gateway (paygw_payphone)

Thanks for your interest in improving the **PayPhone** payment gateway.

## Ground rules
- The plugin is licensed under the **GNU GPL v3 or later**. By contributing you agree your
  contribution is released under the same license.
- Follow the [Moodle coding style](https://moodledev.io/general/development/policies/codingstyle)
  and the [Moodle development policies](https://moodledev.io/general/development/policies).
- Keep all code comments and identifiers in **English**.
- Preserve existing copyright notices; add your own `@copyright` line rather than replacing one.
- **Never** log, print or commit PayPhone credentials (Token / Store ID) or transaction secrets.

## Development
- Plugin source lives in [`workspace/paygw_payphone`](workspace/paygw_payphone).
- The AMD module is **ES6** (`import`/`export`). After changing `amd/src/*.js`, rebuild with
  `grunt amd` (rollup) against a Moodle checkout; do **not** hand-edit `amd/build/*`.
- Bump `$plugin->version`/`$plugin->release` in `version.php` and add an upgrade step for any DB
  change, and update [`CHANGELOG.md`](workspace/paygw_payphone/CHANGELOG.md).
- The payment flow must stay fail-closed: the server-to-server **Confirm** response is the only
  source of truth (status + amount + currency are validated before delivering the order).

## Before opening a pull request
Run the same checks CI runs (via [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci)):

```bash
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci phpdoc --max-warnings 0
moodle-plugin-ci mustache
moodle-plugin-ci grunt --max-lint-warnings 0
moodle-plugin-ci phpunit
```

## Reporting issues
Please use the GitHub issue tracker:
<https://github.com/rurak-ec/moodle-paygw_payphone/issues>.
Include your Moodle version, PHP version, the environment (test/live) and steps to reproduce — but
**never** paste credentials or full transaction payloads.

---

## Español

Gracias por mejorar la pasarela **PayPhone**. Las contribuciones se publican bajo **GNU GPL v3 o
posterior**; sigue el estilo de código y las políticas de Moodle, y mantén comentarios e
identificadores en **inglés**. **Nunca** registres ni subas credenciales (Token / Store ID) ni
secretos de transacción. El código está en `workspace/paygw_payphone`; el módulo AMD es ES6 y se
compila con `grunt amd` (no edites `amd/build/` a mano). Antes de un pull request, ejecuta las
comprobaciones de `moodle-plugin-ci` indicadas arriba. Reporta incidencias en el issue tracker de
GitHub, sin pegar credenciales ni payloads completos.
