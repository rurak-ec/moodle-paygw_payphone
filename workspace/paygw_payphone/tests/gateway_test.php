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

namespace paygw_payphone;

use core_payment\form\account_gateway;

/**
 * Unit tests for the PayPhone gateway class.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_payphone\gateway
 */
final class gateway_test extends \advanced_testcase {
    /**
     * PayPhone supports USD only (Ecuador).
     */
    public function test_get_supported_currencies(): void {
        $this->assertSame(['USD'], gateway::get_supported_currencies());
    }

    /**
     * Enabling the gateway requires the credential pair of the selected environment.
     */
    public function test_validate_gateway_form_requires_credentials(): void {
        $form = $this->createStub(account_gateway::class);

        // Enabled with no test credentials -> error.
        $errors = [];
        gateway::validate_gateway_form(
            $form,
            (object) ['enabled' => 1, 'environment' => 'test', 'testtoken' => '', 'teststoreid' => ''],
            [],
            $errors
        );
        $this->assertArrayHasKey('enabled', $errors);

        // Enabled with the test credentials present -> no error.
        $errors = [];
        gateway::validate_gateway_form(
            $form,
            (object) ['enabled' => 1, 'environment' => 'test', 'testtoken' => 'T', 'teststoreid' => 'S'],
            [],
            $errors
        );
        $this->assertArrayNotHasKey('enabled', $errors);

        // Live selected but only test credentials set -> error.
        $errors = [];
        gateway::validate_gateway_form(
            $form,
            (object) ['enabled' => 1, 'environment' => 'live', 'testtoken' => 'T', 'teststoreid' => 'S'],
            [],
            $errors
        );
        $this->assertArrayHasKey('enabled', $errors);

        // Disabled gateway is never blocked on missing credentials.
        $errors = [];
        gateway::validate_gateway_form($form, (object) ['enabled' => 0], [], $errors);
        $this->assertArrayNotHasKey('enabled', $errors);
    }
}
