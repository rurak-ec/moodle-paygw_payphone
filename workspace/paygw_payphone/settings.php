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
 * Settings for the PayPhone payment gateway.
 *
 * This page is informational only. PayPhone has nothing global to configure:
 *  - Fees (5% + IVA) are charged by PayPhone to the merchant automatically.
 *  - Credentials (Token + Store ID) are set per payment account.
 * We deliberately do NOT add the common "surcharge" setting: Ecuadorian consumer law
 * forbids surcharging card payments, so it must stay at 0 (its default).
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $a = (object) [
        'accountsurl' => (new moodle_url('/payment/accounts.php'))->out(false),
    ];

    $settings->add(new admin_setting_heading(
        'paygw_payphone_intro',
        get_string('setup_heading', 'paygw_payphone'),
        get_string('setup_intro', 'paygw_payphone', $a)
    ));
}
