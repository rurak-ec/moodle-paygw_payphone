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
 * Reconciliation task: finishes PayPhone transactions left unresolved by an interrupted return.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payphone\task;

use paygw_payphone\payphone_helper;

/**
 * Scheduled task that reconciles unfinished PayPhone transactions.
 *
 * Closes the "paid but not delivered" window: if the interactive return handler (process.php)
 * dies between confirming/saving the payment and delivering the order, this task retries using
 * the same atomic, idempotent finalise routine.
 *
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reconcile_pending extends \core\task\scheduled_task {

    /** @var int Seconds: don't touch rows younger than this (give the live flow time to finish). */
    const MIN_AGE = 300;

    /** @var int Seconds: a pending row that never received a return is abandoned after this. */
    const ABANDON_AGE = 1800;

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('task_reconcile', 'paygw_payphone');
    }

    /**
     * Run the reconciliation.
     */
    public function execute(): void {
        global $DB;

        $now = time();
        // Candidates: still pending, or approved-but-not-delivered, and not touched recently.
        $rows = $DB->get_records_select('paygw_payphone',
            "status IN ('pending', 'approved') AND timemodified < :cutoff",
            ['cutoff' => $now - self::MIN_AGE]);

        foreach ($rows as $row) {
            // Pending with no return ever received -> abandoned (PayPhone auto-reverses at ~5 min).
            if ($row->status === 'pending' && empty($row->transactionid)) {
                if ($row->timecreated < $now - self::ABANDON_AGE) {
                    $DB->set_field('paygw_payphone', 'status', 'canceled', ['id' => $row->id]);
                    $DB->set_field('paygw_payphone', 'timemodified', time(), ['id' => $row->id]);
                    mtrace("paygw_payphone: cancelled abandoned transaction {$row->clienttransactionid}");
                }
                continue;
            }

            // Otherwise finish it via the shared, atomic, idempotent routine.
            try {
                $result = payphone_helper::finalise_transaction($row);
                mtrace("paygw_payphone: reconciled {$row->clienttransactionid} -> {$result}");
            } catch (\Throwable $e) {
                mtrace("paygw_payphone: reconcile error for {$row->clienttransactionid}: " . $e->getMessage());
            }
        }
    }
}
