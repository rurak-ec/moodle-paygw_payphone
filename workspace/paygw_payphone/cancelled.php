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
 * Handles cancelled PayPhone payments: marks the transaction cancelled and returns to the course.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// This is PayPhone's cancellationUrl return target: no sesskey is possible here, so the guard
// is the ownership check below (and we only ever flip a still-pending row to canceled).
require_login();

$component = optional_param('component', null, PARAM_COMPONENT);
$paymentarea = optional_param('paymentarea', null, PARAM_AREA);
$itemid = optional_param('itemid', null, PARAM_INT);
$clienttxid = optional_param('clientTransactionId', null, PARAM_ALPHANUMEXT);

// Mark the transaction cancelled, but only if it is still pending AND belongs to this user.
// A single guarded UPDATE avoids a race with process.php advancing the same row.
if (!empty($clienttxid)) {
    $record = $DB->get_record('paygw_payphone', ['clienttransactionid' => $clienttxid]);
    if ($record && (int) $record->userid === (int) $USER->id) {
        $now = time();
        $DB->execute(
            "UPDATE {paygw_payphone} SET status = 'canceled', timemodified = ?
              WHERE id = ? AND status = 'pending' AND userid = ?",
            [$now, $record->id, $USER->id]);
    }
}

$url = new moodle_url('/');
if ($component === 'enrol_fee' && $paymentarea === 'fee' && !empty($itemid)) {
    $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
    if (!empty($courseid)) {
        $url = course_get_url($courseid);
    }
}

redirect($url, get_string('paymentcancelled', 'paygw_payphone'), 0,
    \core\output\notification::NOTIFY_WARNING);
