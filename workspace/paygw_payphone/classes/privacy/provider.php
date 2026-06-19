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
 * Privacy Subsystem implementation for paygw_payphone.
 *
 * @package    paygw_payphone
 * @category   privacy
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payphone\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for paygw_payphone.
 *
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, paygw_provider {

    /**
     * Returns metadata about this plugin's data storage.
     *
     * @param \core_privacy\local\metadata\collection $collection The initialised collection to add items to.
     * @return \core_privacy\local\metadata\collection A listing of user data stored in this plugin.
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('paygw_payphone', [
            'userid' => 'privacy:metadata:paygw_payphone:userid',
            'payphoneid' => 'privacy:metadata:paygw_payphone:payphoneid',
            'amount' => 'privacy:metadata:paygw_payphone:amount',
            'currency' => 'privacy:metadata:paygw_payphone:currency',
            'status' => 'privacy:metadata:paygw_payphone:status',
            'authorizationcode' => 'privacy:metadata:paygw_payphone:authorizationcode',
        ], 'privacy:metadata:paygw_payphone');

        // Data is transmitted to PayPhone (Ecuador) to create and confirm the payment.
        $collection->add_external_location_link('payphonetodoesposible.com', [
            'amount' => 'privacy:metadata:paygw_payphone:external:amount',
            'currency' => 'privacy:metadata:paygw_payphone:external:currency',
            'clienttransactionid' => 'privacy:metadata:paygw_payphone:external:clienttransactionid',
            'reference' => 'privacy:metadata:paygw_payphone:external:reference',
        ], 'privacy:metadata:paygw_payphone:external');

        return $collection;
    }

    /**
     * Export all user data for the specified payment record, and the given context.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context that the payment data belongs
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $subcontext[] = get_string('gatewayname', 'paygw_payphone');
        $record = $DB->get_record('paygw_payphone', ['paymentid' => $payment->id]);
        if (!$record) {
            return;
        }

        $data = (object) [
            'payphoneid' => $record->payphoneid,
            'amount' => $record->amount,
            'currency' => $record->currency,
            'authorizationcode' => $record->authorizationcode,
            'status' => $record->status,
        ];
        writer::with_context($context)->export_data($subcontext, $data);
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;

        $DB->delete_records_select('paygw_payphone', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
