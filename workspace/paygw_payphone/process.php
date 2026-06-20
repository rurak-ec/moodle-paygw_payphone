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
 * PayPhone return handler (responseUrl): confirms the payment server-to-server and delivers the order.
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

// PayPhone appends these to the responseUrl. (No sesskey is possible on a third-party return;
// the security anchor here is the server-to-server Confirm plus the ownership check below.)
$transactionid = required_param('id', PARAM_INT);
$clienttxid = required_param('clientTransactionId', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/payment/gateway/payphone/process.php');

$record = $DB->get_record('paygw_payphone', ['clienttransactionid' => $clienttxid], '*', MUST_EXIST);

// SECURITY (IDOR/BOLA): only the user who started this payment may drive it. The legitimate
// payer always returns in their own session; anyone else is rejected.
if ((int) $record->userid !== (int) $USER->id) {
    redirect(
        new moodle_url('/'),
        get_string('notyourpayment', 'paygw_payphone'),
        0,
        \core\output\notification::NOTIFY_ERROR
    );
}

$component = $record->component;
$paymentarea = $record->paymentarea;
$itemid = $record->itemid;
$successurl = helper::get_success_url($component, $paymentarea, $itemid);

// Already finished: do not re-process (idempotent).
if ($record->status === 'completed') {
    redirect(
        $successurl,
        get_string('paymentalreadyprocessed', 'paygw_payphone'),
        0,
        \core\output\notification::NOTIFY_INFO
    );
}
if ($record->status === 'canceled') {
    redirect(
        new moodle_url('/'),
        get_string('paymentnotcleared', 'paygw_payphone'),
        0,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Persist the PayPhone transaction id BEFORE confirming, so the reconciliation task can finish
// this transaction even if this request dies during Confirm/deliver.
if (empty($record->transactionid)) {
    $DB->set_field('paygw_payphone', 'transactionid', $transactionid, ['id' => $record->id]);
    $record->transactionid = $transactionid;
}

// Confirm + validate + atomically save & deliver (shared with the reconciliation task).
$result = payphone_helper::finalise_transaction($record);

switch ($result) {
    case 'completed':
        redirect(
            $successurl,
            get_string('paymentsuccessful', 'paygw_payphone'),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );
        break;
    case 'rejected':
        redirect(
            new moodle_url('/'),
            get_string('paymentnotcleared', 'paygw_payphone'),
            0,
            \core\output\notification::NOTIFY_ERROR
        );
        break;
    case 'delivery_failed':
        // Money captured but enrolment failed; the reconciliation task will retry delivery.
        redirect(
            new moodle_url('/'),
            get_string('payment_received_pending', 'paygw_payphone'),
            0,
            \core\output\notification::NOTIFY_WARNING
        );
        break;
    default:
        // Transient confirm failure ('error' or 'pending') — let the user retry the return.
        redirect(
            new moodle_url('/'),
            get_string('error_confirm', 'paygw_payphone'),
            0,
            \core\output\notification::NOTIFY_ERROR
        );
        break;
}
