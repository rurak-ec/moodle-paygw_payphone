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
 * Contains the gateway class for the PayPhone payment gateway.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payphone;

use core_payment\form\account_gateway;

/**
 * The gateway class for the PayPhone payment gateway.
 *
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * The currencies supported by PayPhone. PayPhone operates in Ecuador (USD).
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['USD'];
    }

    /**
     * Configuration form for the gateway instance.
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        global $CFG;
        $mform = $form->get_mform();

        // Friendly onboarding instructions, including THIS site's exact domain and
        // response/cancellation URLs so the admin can copy them into the PayPhone console.
        $a = (object) [
            'consoleurl' => 'https://appdeveloper.payphonetodoesposible.com/',
            'host' => parse_url($CFG->wwwroot, PHP_URL_HOST),
            'responseurl' => $CFG->wwwroot . '/payment/gateway/payphone/process.php',
            'cancelurl' => $CFG->wwwroot . '/payment/gateway/payphone/cancelled.php',
        ];
        $mform->addElement(
            'static',
            'payphone_instructions',
            '',
            get_string('config_instructions', 'paygw_payphone', $a)
        );

        // Make core's generic "Enable" checkbox explicit and render it as a toggle switch.
        // The 'enabled' element is added by core_payment\form\account_gateway BEFORE this hook;
        // we only adjust it here, so it affects the PayPhone config page only.
        if ($mform->elementExists('enabled')) {
            $enabled = $mform->getElement('enabled');
            $enabled->setLabel(get_string('enablegateway', 'paygw_payphone'));
            $enabled->updateAttributes(['role' => 'switch']);
            $mform->addHelpButton('enabled', 'enablegateway', 'paygw_payphone');
            // Inline (scoped to this form render) toggle styling for the gateway "enabled" switch.
            $svgoff = '%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'-4 -4 8 8\'%3e'
                . '%3ccircle r=\'3\' fill=\'rgba(0,0,0,.25)\'/%3e%3c/svg%3e';
            $svgon = '%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'-4 -4 8 8\'%3e'
                . '%3ccircle r=\'3\' fill=\'%23fff\'/%3e%3c/svg%3e';
            $css = '<style>'
                . '#id_enabled.form-check-input{width:2.6em;height:1.35em;margin-top:.15em;'
                . 'border-radius:2em;cursor:pointer;background-position:left center;'
                . 'background-repeat:no-repeat;transition:background-position .15s ease-in-out;'
                . 'background-image:url("data:image/svg+xml,' . $svgoff . '");}'
                . '#id_enabled.form-check-input:checked{background-position:right center;'
                . 'background-image:url("data:image/svg+xml,' . $svgon . '");}'
                . '#id_enabled.form-check-input + label{cursor:pointer;font-weight:600;margin-left:.4em;}'
                . '</style>';
            $mform->addElement('html', $css);
        }

        // Environment selector. PayPhone uses ONE URL for both environments; what differs is the
        // token/Store ID. So this selector chooses which credential pair is sent at runtime.
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_payphone'), [
            'test' => get_string('test', 'paygw_payphone'),
            'live' => get_string('live', 'paygw_payphone'),
        ]);
        $mform->setDefault('environment', 'test');
        $mform->addHelpButton('environment', 'environment', 'paygw_payphone');

        // Test credentials (shown only when Environment = Test). Token is secret -> masked password input.
        $mform->addElement('password', 'testtoken', get_string('testtoken', 'paygw_payphone'), ['size' => 60]);
        $mform->setType('testtoken', PARAM_TEXT);
        $mform->addHelpButton('testtoken', 'testtoken', 'paygw_payphone');
        $mform->hideIf('testtoken', 'environment', 'neq', 'test');

        $mform->addElement('text', 'teststoreid', get_string('teststoreid', 'paygw_payphone'), ['size' => 40]);
        $mform->setType('teststoreid', PARAM_TEXT);
        $mform->addHelpButton('teststoreid', 'teststoreid', 'paygw_payphone');
        $mform->hideIf('teststoreid', 'environment', 'neq', 'test');

        // Production credentials (shown only when Environment = Production). Token is secret -> masked.
        $mform->addElement('password', 'livetoken', get_string('livetoken', 'paygw_payphone'), ['size' => 60]);
        $mform->setType('livetoken', PARAM_TEXT);
        $mform->addHelpButton('livetoken', 'livetoken', 'paygw_payphone');
        $mform->hideIf('livetoken', 'environment', 'neq', 'live');

        $mform->addElement('text', 'livestoreid', get_string('livestoreid', 'paygw_payphone'), ['size' => 40]);
        $mform->setType('livestoreid', PARAM_TEXT);
        $mform->addHelpButton('livestoreid', 'livestoreid', 'paygw_payphone');
        $mform->hideIf('livestoreid', 'environment', 'neq', 'live');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(
        account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if (!empty($data->enabled)) {
            // Require the credential pair for the SELECTED environment.
            $live = ((isset($data->environment) ? $data->environment : 'test') === 'live');
            $token = $live ? ($data->livetoken ?? '') : ($data->testtoken ?? '');
            $storeid = $live ? ($data->livestoreid ?? '') : ($data->teststoreid ?? '');
            if (empty($token) || empty($storeid)) {
                $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
            }
        }
    }
}
