<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cadenas en español para la pasarela de pago PayPhone.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PayPhone';
$string['pluginname_desc'] = 'El plugin PayPhone permite recibir pagos a través de PayPhone (Ecuador).';
$string['gatewayname'] = 'PayPhone';
$string['gatewaydescription'] = 'PayPhone es una pasarela de pago ecuatoriana para procesar transacciones con tarjeta y con la app PayPhone.';

// Página global de ajustes (solo informativa).
$string['setup_heading'] = 'Cómo funciona PayPhone';
$string['setup_intro'] = '<p>En esta página no hay nada que configurar.</p>
<ul>
<li><strong>Comisión:</strong> PayPhone cobra al comercio 5% + IVA por transacción y lo descuenta automáticamente. Tú solo defines el precio del curso; en Moodle no se configura nada de comisiones.</li>
<li><strong>Recibes:</strong> el precio menos la comisión de PayPhone, depositado a tu banco en unas 24–48&nbsp;h.</li>
<li><strong>Moneda:</strong> solo USD (Ecuador).</li>
</ul>
<p><strong>Para empezar a cobrar, agrega tus credenciales (Token + Store ID) a una cuenta de pago:</strong></p>
<p><a class="btn btn-primary" href="{$a->accountsurl}">Ir a Cuentas de pago</a></p>';

// Campos de configuración (por cuenta de pago).
$string['config_instructions'] = '<div class="alert alert-info">
<p><strong>Cómo obtener tus credenciales</strong> — en la <a href="{$a->consoleurl}" target="_blank" rel="noopener">consola de desarrollador de PayPhone</a>:</p>
<ol>
<li>Inicia sesión con tu cuenta <strong>PayPhone Business</strong> y agrega un usuario con rol <em>Developer</em>.</li>
<li>Entra a <em>Crear Aplicación</em> y complétala:
<ul>
<li>Plataforma Desarrollo: <code>PHP</code></li>
<li>Tipo de Aplicación: <code>Web</code></li>
<li>Categoría: <code>Educación</code> (o la que corresponda)</li>
<li><strong>Dominio web:</strong> <code>{$a->host}</code></li>
<li><strong>Url de respuesta:</strong> <code>{$a->responseurl}</code></li>
</ul>
</li>
<li>Guarda, luego abre la pestaña <em>Credenciales</em> y copia el <strong>Token</strong> y el <strong>Store ID</strong>.</li>
<li>Elige <strong>Ambiente: Prueba</strong> y pégalos en los campos de Prueba de abajo (también puedes llenar los de Producción y alternar luego), y marca <em>Habilitar</em>.</li>
</ol>
<p class="mb-0"><strong>URLs de este sitio</strong> (cópialas en la consola):<br>
Url de respuesta: <code>{$a->responseurl}</code><br>
Url de cancelación: <code>{$a->cancelurl}</code></p>
</div>';
$string['enablegateway'] = 'Activar PayPhone (cobrar con este portal)';
$string['enablegateway_help'] = 'Actívalo para que PayPhone se pueda usar como medio de pago. Mientras esté apagado, la cuenta de pago aparece como "No disponible" y PayPhone no se mostrará al pagar. Actívalo solo después de pegar credenciales válidas del ambiente seleccionado.';
$string['environment'] = 'Ambiente';
$string['environment_help'] = 'PayPhone usa la misma URL para ambos ambientes; lo que cambia es el token. Este selector elige qué par de credenciales se usa. Elige Prueba mientras integras (todas las transacciones se aprueban sin contactar al banco) y Producción para pagos reales. Puedes configurar ambos pares y alternar cuando quieras.';
$string['test'] = 'Prueba (sandbox)';
$string['live'] = 'Producción';
$string['testtoken'] = 'Token de Prueba';
$string['testtoken_help'] = 'El token Bearer de tu aplicación de Prueba, de la consola de desarrollador de PayPhone (pestaña Credenciales). Se usa cuando el Ambiente es Prueba.';
$string['teststoreid'] = 'Store ID de Prueba';
$string['teststoreid_help'] = 'El Store ID (storeId) de tu aplicación de Prueba, de la consola de desarrollador de PayPhone.';
$string['livetoken'] = 'Token de Producción';
$string['livetoken_help'] = 'El token Bearer de tu aplicación de Producción, de la consola de desarrollador de PayPhone (pestaña Credenciales). Se usa cuando el Ambiente es Producción. Aplica cobros reales.';
$string['livestoreid'] = 'Store ID de Producción';
$string['livestoreid_help'] = 'El Store ID (storeId) de tu aplicación de Producción, de la consola de desarrollador de PayPhone.';
// Legacy (anterior a 1.2.0), se mantienen por compatibilidad.
$string['token'] = 'Token de la aplicación';
$string['token_help'] = 'El token Bearer de la aplicación, de la consola de desarrollador de PayPhone (pestaña Credenciales).';
$string['storeid'] = 'Store ID';
$string['storeid_help'] = 'El identificador del local (storeId), de la consola de desarrollador de PayPhone (pestaña Credenciales).';

// Flujo / UI.
$string['redirecting'] = 'Redirigiéndote a PayPhone...';
$string['paymentreference'] = 'Pago de curso en Moodle';
$string['paymentsuccessful'] = 'Tu pago se realizó con éxito.';
$string['paymentalreadyprocessed'] = 'Este pago ya fue procesado.';
$string['paymentcancelled'] = 'Pago cancelado.';
$string['paymentnotcleared'] = 'PayPhone no aprobó tu pago. No se realizó ningún cargo a tu cuenta.';
$string['payment_received_pending'] = 'Tu pago se recibió, pero la matrícula no pudo completarse automáticamente. Contacta a soporte; se aplicará en breve.';
$string['notyourpayment'] = 'Este pago no pertenece a tu cuenta.';

// Tarea programada.
$string['task_reconcile'] = 'Reconciliar transacciones PayPhone sin finalizar';

// Errores.
$string['error_prepare'] = 'PayPhone no pudo iniciar el pago.';
$string['error_confirm'] = 'PayPhone no pudo confirmar el pago.';
$string['error_api'] = 'PayPhone devolvió un error.';
$string['error_network'] = 'No se pudo contactar con PayPhone. Inténtalo de nuevo.';
$string['error_badresponse'] = 'Respuesta inesperada de PayPhone.';
$string['error_unknown'] = 'Error desconocido de PayPhone.';

// Privacidad.
$string['privacy:metadata:paygw_payphone'] = 'Almacena los registros de transacciones de PayPhone vinculados a los pagos.';
$string['privacy:metadata:paygw_payphone:userid'] = 'El ID del usuario que realizó el pago.';
$string['privacy:metadata:paygw_payphone:payphoneid'] = 'El ID de pago/transacción de PayPhone.';
$string['privacy:metadata:paygw_payphone:amount'] = 'El monto pagado, en centavos.';
$string['privacy:metadata:paygw_payphone:currency'] = 'La moneda del pago.';
$string['privacy:metadata:paygw_payphone:status'] = 'El estado de la transacción (pending, approved, completed, canceled).';
$string['privacy:metadata:paygw_payphone:authorizationcode'] = 'El código de autorización bancaria de la transacción.';
$string['privacy:metadata:paygw_payphone:external'] = 'Datos de pago enviados a PayPhone (Ecuador) para crear y confirmar el pago.';
$string['privacy:metadata:paygw_payphone:external:amount'] = 'El monto a cobrar, en centavos.';
$string['privacy:metadata:paygw_payphone:external:currency'] = 'La moneda del pago.';
$string['privacy:metadata:paygw_payphone:external:clienttransactionid'] = 'El identificador único de transacción del comercio.';
$string['privacy:metadata:paygw_payphone:external:reference'] = 'La referencia/descripción del pago.';
