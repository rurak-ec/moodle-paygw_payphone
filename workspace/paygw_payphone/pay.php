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
 * Starts a PayPhone payment: prepares the transaction and redirects to the hosted payment page.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payphone\payphone_helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();
// SECURITY (CSRF): payment initiation is state-changing (DB insert + Prepare API call).
// The modal JS includes the sesskey in the link.
require_sesskey();

$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = optional_param('description', '', PARAM_TEXT);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payphone');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('payphone');
$currency = $payable->get_currency();
$cost = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

// PayPhone works with integer cents.
$cents = (int) round($cost * 100);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/payment/gateway/payphone/pay.php');

// Unique merchant transaction id (idempotency key). High-entropy and opaque: it is also the
// lookup key on return, so it must not leak userid/itemid/time. <= 50 chars (PayPhone limit).
$clienttxid = 'mdl' . random_string(40);

// Persist the full context so process.php can reconstruct everything from just the
// id + clientTransactionId that PayPhone returns.
$now = time();
$record = new stdClass();
$record->clienttransactionid = $clienttxid;
$record->payphoneid = null;
$record->paymentid = null;
$record->component = $component;
$record->paymentarea = $paymentarea;
$record->itemid = $itemid;
$record->userid = (int) $USER->id;
$record->accountid = $payable->get_account_id();
$record->amount = $cents;
$record->currency = $currency;
$record->status = 'pending';
$record->authorizationcode = null;
$record->timecreated = $now;
$record->timemodified = $now;
$recordid = $DB->insert_record('paygw_payphone', $record);

$responseurl = (new moodle_url('/payment/gateway/payphone/process.php'))->out(false);
$cancelurl = (new moodle_url('/payment/gateway/payphone/cancelled.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'clientTransactionId' => $clienttxid,
]))->out(false);

$reference = ($description !== '') ? $description : get_string('paymentreference', 'paygw_payphone');

$payphone = payphone_helper::for_config($config);

try {
    $result = $payphone->prepare($cents, $currency, $reference, $clienttxid, $responseurl, $cancelurl);
} catch (\moodle_exception $e) {
    // Mark the pending row as cancelled and send the user back with the error.
    $DB->update_record('paygw_payphone', (object) [
        'id' => $recordid,
        'status' => 'canceled',
        'timemodified' => time(),
    ]);
    redirect(
        new moodle_url('/'),
        get_string('error_prepare', 'paygw_payphone') . ' ' . $e->getMessage(),
        0,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Store PayPhone's payment id for traceability.
$DB->set_field('paygw_payphone', 'payphoneid', $result['paymentId'], ['id' => $recordid]);

// SECURITY (open-redirect/SSRF defence-in-depth): the hosted payment URL comes from the API
// response. Only ever redirect to PayPhone's own HTTPS domain.
$paywith = $result['payWithCard'];
$scheme = strtolower((string) parse_url($paywith, PHP_URL_SCHEME));
$host = strtolower((string) parse_url($paywith, PHP_URL_HOST));
if ($scheme !== 'https' || !preg_match('/(^|\.)payphonetodoesposible\.com$/', $host)) {
    $DB->update_record('paygw_payphone', (object) [
        'id' => $recordid,
        'status' => 'canceled',
        'timemodified' => time(),
    ]);
    redirect(
        new moodle_url('/'),
        get_string('error_prepare', 'paygw_payphone'),
        0,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Redirect the customer to PayPhone's hosted payment page (card flow).
redirect(new moodle_url($paywith));
