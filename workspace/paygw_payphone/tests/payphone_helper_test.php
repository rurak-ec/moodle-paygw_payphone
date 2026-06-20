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

/**
 * Unit tests for the PayPhone API client helper.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_payphone\payphone_helper
 */
final class payphone_helper_test extends \advanced_testcase {
    /**
     * Read a protected property off a helper instance.
     *
     * @param payphone_helper $client
     * @param string $name
     * @return mixed
     */
    private function read(payphone_helper $client, string $name) {
        $prop = new \ReflectionProperty(payphone_helper::class, $name);
        $prop->setAccessible(true);
        return $prop->getValue($client);
    }

    /**
     * for_config() picks the credential pair for the selected environment, and trims whitespace.
     */
    public function test_for_config_selects_environment(): void {
        $config = (object) [
            'environment' => 'live',
            'livetoken' => '  LIVETOKEN  ',
            'livestoreid' => 'LIVESTORE',
            'testtoken' => 'TESTTOKEN',
            'teststoreid' => 'TESTSTORE',
        ];
        $live = payphone_helper::for_config($config);
        $this->assertSame('LIVETOKEN', $this->read($live, 'token'));
        $this->assertSame('LIVESTORE', $this->read($live, 'storeid'));

        $config->environment = 'test';
        $test = payphone_helper::for_config($config);
        $this->assertSame('TESTTOKEN', $this->read($test, 'token'));
        $this->assertSame('TESTSTORE', $this->read($test, 'storeid'));
    }

    /**
     * for_config() falls back to the pre-1.2.0 single-credential fields.
     */
    public function test_for_config_backward_compatible(): void {
        $config = (object) ['environment' => 'test', 'token' => 'OLDTOKEN', 'storeid' => 'OLDSTORE'];
        $client = payphone_helper::for_config($config);
        $this->assertSame('OLDTOKEN', $this->read($client, 'token'));
        $this->assertSame('OLDSTORE', $this->read($client, 'storeid'));
    }

    /**
     * extract_error() prefers the PayPhone "errors" array, then "message".
     */
    public function test_extract_error(): void {
        $client = payphone_helper::for_config((object) ['environment' => 'test', 'testtoken' => 'x', 'teststoreid' => 'y']);
        $method = new \ReflectionMethod(payphone_helper::class, 'extract_error');
        $method->setAccessible(true);

        $this->assertSame('A B', $method->invoke($client, ['errors' => [['message' => 'A'], ['message' => 'B']]]));
        $this->assertSame('Single', $method->invoke($client, ['message' => 'Single']));
        $this->assertSame(get_string('error_unknown', 'paygw_payphone'), $method->invoke($client, []));
        $this->assertSame(get_string('error_unknown', 'paygw_payphone'), $method->invoke($client, null));
    }

    /**
     * finalise_transaction() short-circuits already-resolved rows without contacting the API.
     */
    public function test_finalise_transaction_is_idempotent(): void {
        $this->resetAfterTest();

        $this->assertSame('completed', payphone_helper::finalise_transaction((object) ['status' => 'completed']));
        $this->assertSame('rejected', payphone_helper::finalise_transaction((object) ['status' => 'canceled']));
        // Pending with no return id yet cannot be confirmed -> stays pending (no API call).
        $this->assertSame(
            'pending',
            payphone_helper::finalise_transaction((object) ['status' => 'pending', 'transactionid' => null])
        );
    }
}
