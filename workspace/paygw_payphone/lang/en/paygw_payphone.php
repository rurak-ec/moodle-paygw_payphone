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
 * English strings for the PayPhone payment gateway.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PayPhone';
$string['pluginname_desc'] = 'The PayPhone plugin allows you to receive payments via PayPhone (Ecuador).';
$string['gatewayname'] = 'PayPhone';
$string['gatewaydescription'] = 'PayPhone is an Ecuadorian payment gateway provider for processing card and PayPhone app transactions.';

// Global settings page (informational only).
$string['setup_heading'] = 'How PayPhone works';
$string['setup_intro'] = '<p>There is nothing to configure on this page.</p>
<ul>
<li><strong>Fees:</strong> PayPhone charges the merchant 5% + VAT per transaction and deducts it automatically. You only set the course price; nothing about fees is configured in Moodle.</li>
<li><strong>You receive:</strong> the price minus PayPhone\'s commission, settled to your bank in about 24–48&nbsp;h.</li>
<li><strong>Currency:</strong> USD only (Ecuador).</li>
</ul>
<p><strong>To start charging, add your credentials (Token + Store ID) to a payment account:</strong></p>
<p><a class="btn btn-primary" href="{$a->accountsurl}">Go to Payment accounts</a></p>';

// Configuration fields (per payment account).
$string['config_instructions'] = '<div class="alert alert-info">
<p><strong>How to get your credentials</strong> — in the <a href="{$a->consoleurl}" target="_blank" rel="noopener">PayPhone developer console</a>:</p>
<ol>
<li>Sign in with your <strong>PayPhone Business</strong> account and add a user with the <em>Developer</em> role.</li>
<li>Go to <em>Create application</em> and fill it in:
<ul>
<li>Development platform: <code>PHP</code></li>
<li>Application type: <code>Web</code></li>
<li>Category: <code>Education</code> (or whichever fits)</li>
<li><strong>Web domain:</strong> <code>{$a->host}</code></li>
<li><strong>Response URL:</strong> <code>{$a->responseurl}</code></li>
</ul>
</li>
<li>Save, then open the <em>Credentials</em> tab and copy the <strong>Token</strong> and the <strong>Store ID</strong>.</li>
<li>Choose <strong>Environment: Test</strong> and paste them into the Test fields below (you can also fill the Production fields and switch later), then tick <em>Enable</em>.</li>
</ol>
<p class="mb-0"><strong>URLs for this site</strong> (copy into the console):<br>
Response URL: <code>{$a->responseurl}</code><br>
Cancellation URL: <code>{$a->cancelurl}</code></p>
</div>';
$string['enablegateway'] = 'Activate PayPhone (charge with this gateway)';
$string['enablegateway_help'] = 'Turn this on to make PayPhone usable as a payment option. While it is off, the payment account shows as "Not available" and PayPhone will not appear at checkout. Turn it on only after pasting valid credentials for the selected environment.';
$string['environment'] = 'Environment';
$string['environment_help'] = 'PayPhone uses the same URL for both environments; what changes is the token. This selector chooses which credential pair is used at runtime. Choose Test while integrating (all transactions are approved without contacting the bank) and Production for real payments. You can configure both pairs and switch any time.';
$string['test'] = 'Test (sandbox)';
$string['live'] = 'Production';
$string['testtoken'] = 'Test token';
$string['testtoken_help'] = 'The Bearer token of your Test application, from the PayPhone developer console (Credentials tab). Used when Environment is set to Test.';
$string['teststoreid'] = 'Test Store ID';
$string['teststoreid_help'] = 'The Store ID (storeId) of your Test application, from the PayPhone developer console.';
$string['livetoken'] = 'Production token';
$string['livetoken_help'] = 'The Bearer token of your Production application, from the PayPhone developer console (Credentials tab). Used when Environment is set to Production. Real charges apply.';
$string['livestoreid'] = 'Production Store ID';
$string['livestoreid_help'] = 'The Store ID (storeId) of your Production application, from the PayPhone developer console.';
// Legacy (pre-1.2.0) single-credential labels, kept for backward compatibility.
$string['token'] = 'Application token';
$string['token_help'] = 'The application Bearer token from the PayPhone developer console (Credentials tab).';
$string['storeid'] = 'Store ID';
$string['storeid_help'] = 'The store identifier (storeId) from the PayPhone developer console (Credentials tab).';

// Flow / UI.
$string['redirecting'] = 'Redirecting you to PayPhone...';
$string['paymentreference'] = 'Moodle course payment';
$string['paymentsuccessful'] = 'Your payment was successful.';
$string['paymentalreadyprocessed'] = 'This payment has already been processed.';
$string['paymentcancelled'] = 'Payment cancelled.';
$string['paymentnotcleared'] = 'Your payment was not approved by PayPhone. Your account has not been charged.';
$string['payment_received_pending'] = 'Your payment was received but enrolment could not be completed automatically. Please contact support; it will be applied shortly.';
$string['notyourpayment'] = 'This payment does not belong to your account.';

// Scheduled task.
$string['task_reconcile'] = 'Reconcile unfinished PayPhone transactions';

// Errors.
$string['error_prepare'] = 'PayPhone could not start the payment.';
$string['error_confirm'] = 'PayPhone could not confirm the payment.';
$string['error_api'] = 'PayPhone returned an error.';
$string['error_network'] = 'Could not reach PayPhone. Please try again.';
$string['error_badresponse'] = 'Unexpected response from PayPhone.';
$string['error_unknown'] = 'Unknown PayPhone error.';

// Privacy.
$string['privacy:metadata:paygw_payphone'] = 'Stores PayPhone transaction records linked to payments.';
$string['privacy:metadata:paygw_payphone:userid'] = 'The ID of the user who made the payment.';
$string['privacy:metadata:paygw_payphone:payphoneid'] = 'The PayPhone payment/transaction ID.';
$string['privacy:metadata:paygw_payphone:amount'] = 'The amount paid, in cents.';
$string['privacy:metadata:paygw_payphone:currency'] = 'The currency of the payment.';
$string['privacy:metadata:paygw_payphone:status'] = 'The status of the transaction (pending, approved, completed, canceled).';
$string['privacy:metadata:paygw_payphone:authorizationcode'] = 'The bank authorisation code of the transaction.';
$string['privacy:metadata:paygw_payphone:external'] = 'Payment data sent to PayPhone (Ecuador) to create and confirm the payment.';
$string['privacy:metadata:paygw_payphone:external:amount'] = 'The amount to charge, in cents.';
$string['privacy:metadata:paygw_payphone:external:currency'] = 'The currency of the payment.';
$string['privacy:metadata:paygw_payphone:external:clienttransactionid'] = 'The unique merchant transaction identifier.';
$string['privacy:metadata:paygw_payphone:external:reference'] = 'The payment reference/description.';
