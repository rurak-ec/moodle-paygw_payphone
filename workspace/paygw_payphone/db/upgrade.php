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
 * Upgrade steps for paygw_payphone.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the PayPhone payment gateway.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_paygw_payphone_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026061802) {
        // Add the numeric PayPhone transaction id (from the return URL), needed to Confirm
        // a transaction during reconciliation after an interrupted return.
        $table = new xmldb_table('paygw_payphone');
        $field = new xmldb_field('transactionid', XMLDB_TYPE_INTEGER, '19', null, null, null, null, 'payphoneid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026061802, 'paygw', 'payphone');
    }

    if ($oldversion < 2026100801) {
        $table = new xmldb_table('paygw_payphone');

        // Add index on status and timemodified for scheduled task reconciliation efficiency.
        $index = new xmldb_index('status_timemodified', XMLDB_INDEX_NOTUNIQUE, ['status', 'timemodified']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add index on userid for user return checks and privacy lookups.
        $userindex = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $userindex)) {
            $dbman->add_index($table, $userindex);
        }

        upgrade_plugin_savepoint(true, 2026100801, 'paygw', 'payphone');
    }

    return true;
}
