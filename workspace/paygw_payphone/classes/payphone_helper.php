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
 * PayPhone API client.
 *
 * This is the ONLY place that talks HTTP to PayPhone. It implements the two calls of the
 * "Botón de Pago" (Payment Button) redirect flow:
 *   - prepare(): creates the transaction and returns the hosted payment URLs.
 *   - confirm(): server-to-server confirmation after the customer returns.
 *
 * @package    paygw_payphone
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payphone;

/**
 * PayPhone API client.
 *
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payphone_helper {
    /** @var string Base URL of the PayPhone API. */
    const API_BASE = 'https://pay.payphonetodoesposible.com';

    /** @var int PayPhone status code for an approved transaction. */
    const STATUS_APPROVED = 3;

    /** @var int PayPhone status code for a cancelled/rejected transaction. */
    const STATUS_CANCELED = 2;

    /** @var string The application Bearer token. */
    protected $token;

    /** @var string The store id. */
    protected $storeid;

    /**
     * Constructor.
     *
     * @param string $token PayPhone application token (Bearer).
     * @param string $storeid PayPhone store id.
     */
    public function __construct(string $token, string $storeid) {
        // Credentials are pasted by hand and frequently carry stray whitespace.
        $this->token = trim($token);
        $this->storeid = trim($storeid);
    }

    /**
     * Build a client using the credential pair for the gateway's selected environment.
     *
     * PayPhone uses ONE base URL for both test and production; only the token/Store ID differ.
     * This is the single place that resolves which pair to use.
     *
     * @param \stdClass $config Gateway configuration (from helper::get_gateway_configuration()).
     * @return self
     */
    public static function for_config(\stdClass $config): self {
        $live = (!empty($config->environment) && $config->environment === 'live');
        $token = $live ? ($config->livetoken ?? '') : ($config->testtoken ?? '');
        $storeid = $live ? ($config->livestoreid ?? '') : ($config->teststoreid ?? '');

        // Backward compatibility with the pre-1.2.0 single-credential fields.
        if ($token === '' && !empty($config->token)) {
            $token = $config->token;
        }
        if ($storeid === '' && !empty($config->storeid)) {
            $storeid = $config->storeid;
        }

        return new self((string) $token, (string) $storeid);
    }

    /**
     * Prepare a transaction (Botón de Pago).
     *
     * @param int $cents Total amount in integer cents.
     * @param string $currency ISO-4217 currency code (USD).
     * @param string $reference Human-readable payment reason.
     * @param string $clienttxid Unique merchant transaction id (<=50 chars).
     * @param string $responseurl URL PayPhone redirects the customer back to after paying.
     * @param string $cancelurl URL PayPhone redirects to if the customer cancels.
     * @return array Decoded response with at least: paymentId, payWithCard, payWithPayPhone.
     * @throws \moodle_exception On API/network/validation error.
     */
    public function prepare(
        int $cents,
        string $currency,
        string $reference,
        string $clienttxid,
        string $responseurl,
        string $cancelurl
    ): array {
        // Per PayPhone: amount == amountWithoutTax + amountWithTax + tax + service + tip.
        // We keep it simple (no separate tax breakdown). See plan notes re: IVA.
        $payload = [
            'amount' => $cents,
            'amountWithoutTax' => $cents,
            'amountWithTax' => 0,
            'tax' => 0,
            'service' => 0,
            'tip' => 0,
            'currency' => $currency,
            'clientTransactionId' => $clienttxid,
            'storeId' => $this->storeid,
            'reference' => \core_text::substr($reference, 0, 100),
            'responseUrl' => $responseurl,
            'cancellationUrl' => $cancelurl,
        ];

        $response = $this->request('/api/button/Prepare', $payload);

        if (empty($response['paymentId']) || empty($response['payWithCard'])) {
            throw new \moodle_exception(
                'error_prepare',
                'paygw_payphone',
                '',
                $this->extract_error($response)
            );
        }

        return $response;
    }

    /**
     * Confirm a transaction after the customer returns.
     *
     * @param int $id The PayPhone transaction id (from the return URL).
     * @param string $clienttxid The merchant transaction id (from the return URL).
     * @return array Decoded response (statusCode, transactionStatus, amount, currency, ...).
     * @throws \moodle_exception On API/network error.
     */
    public function confirm(int $id, string $clienttxid): array {
        $payload = [
            'id' => $id,
            'clientTxId' => $clienttxid,
        ];

        return $this->request('/api/button/V2/Confirm', $payload, 2);
    }

    /**
     * Finalise a transaction: confirm with PayPhone, validate, then atomically save + deliver.
     *
     * This is the single source of truth for completing a payment, shared by the interactive
     * return handler (process.php) and the reconciliation scheduled task. It is idempotent and
     * crash-safe: the pending -> approved transition is done under a row lock inside a DB
     * transaction (so two concurrent callers cannot both deliver), the payment is saved before
     * delivery, and delivery is re-runnable for an already-saved ('approved') row.
     *
     * Requires $record->transactionid (the numeric PayPhone transaction id from the return URL)
     * to be set before calling for a 'pending' row.
     *
     * @param \stdClass $record A row from {paygw_payphone}.
     * @return string One of: 'completed', 'rejected', 'pending', 'error', 'delivery_failed'.
     */
    public static function finalise_transaction(\stdClass $record): string {
        global $DB;

        if ($record->status === 'completed') {
            return 'completed';
        }
        if ($record->status === 'canceled') {
            return 'rejected';
        }

        // Step 1: a still-pending row must be confirmed server-to-server with PayPhone.
        if ($record->status === 'pending') {
            if (empty($record->transactionid)) {
                // No return received yet (user never came back) -> cannot confirm.
                return 'pending';
            }

            $config = (object) \core_payment\helper::get_gateway_configuration(
                $record->component,
                $record->paymentarea,
                $record->itemid,
                'payphone'
            );
            $client = self::for_config($config);

            try {
                $confirm = $client->confirm((int) $record->transactionid, $record->clienttransactionid);
            } catch (\moodle_exception $e) {
                // Transient/unreachable: leave pending so it can be retried later.
                return 'error';
            }

            // SECURITY: fail-closed validation of status, amount and currency.
            $statusok = isset($confirm['statusCode']) && (int) $confirm['statusCode'] === self::STATUS_APPROVED;
            $amountok = isset($confirm['amount']) && (int) $confirm['amount'] === (int) $record->amount;
            $currencyok = isset($confirm['currency']) && $confirm['currency'] === $record->currency;

            if (!$statusok || !$amountok || !$currencyok) {
                $DB->update_record('paygw_payphone', (object) [
                    'id' => $record->id,
                    'status' => 'canceled',
                    'timemodified' => time(),
                ]);
                debugging('paygw_payphone: confirmation rejected for ' . $record->clienttransactionid .
                    ' (statusok=' . (int) $statusok . ', amountok=' . (int) $amountok .
                    ', currencyok=' . (int) $currencyok . ')', DEBUG_DEVELOPER);
                return 'rejected';
            }

            $authcode = isset($confirm['authorizationCode']) ? (string) $confirm['authorizationCode'] : null;
            $ppid = isset($confirm['transactionId']) ? (string) $confirm['transactionId'] : $record->payphoneid;

            // Atomic claim: lock the row, re-check status, save payment. Only the winner proceeds.
            try {
                $transaction = $DB->start_delegated_transaction();
                $locked = $DB->get_record_sql(
                    'SELECT * FROM {paygw_payphone} WHERE id = ? FOR UPDATE',
                    [$record->id],
                    MUST_EXIST
                );

                if ($locked->status === 'pending') {
                    $paymentid = \core_payment\helper::save_payment(
                        $locked->accountid,
                        $locked->component,
                        $locked->paymentarea,
                        $locked->itemid,
                        $locked->userid,
                        $locked->amount / 100,
                        $locked->currency,
                        'payphone'
                    );
                    $locked->status = 'approved';
                    $locked->paymentid = $paymentid;
                    $locked->payphoneid = $ppid;
                    $locked->authorizationcode = $authcode;
                    $locked->timemodified = time();
                    $DB->update_record('paygw_payphone', $locked);
                }
                $record = $locked;
                $transaction->allow_commit();
            } catch (\Exception $e) {
                // Transaction auto-rolls back; payment not saved -> safe to retry later.
                debugging('paygw_payphone: save_payment failed for ' . $record->clienttransactionid .
                    ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                return 'error';
            }
        }

        // Step 2: payment is saved ('approved'); deliver the order (idempotent / re-runnable).
        if ($record->status === 'approved') {
            try {
                \core_payment\helper::deliver_order(
                    $record->component,
                    $record->paymentarea,
                    $record->itemid,
                    $record->paymentid,
                    $record->userid
                );
            } catch (\Exception $e) {
                // Money captured & saved but delivery failed: keep 'approved' so the
                // reconciliation task retries delivery. Surface to the user as "contact support".
                debugging('paygw_payphone: deliver_order failed for ' . $record->clienttransactionid .
                    ': ' . $e->getMessage(), DEBUG_NORMAL);
                return 'delivery_failed';
            }
            $DB->update_record('paygw_payphone', (object) [
                'id' => $record->id,
                'status' => 'completed',
                'timemodified' => time(),
            ]);
            return 'completed';
        }

        return $record->status === 'completed' ? 'completed' : 'pending';
    }

    /**
     * Perform a JSON POST against the PayPhone API with optional retries.
     *
     * @param string $path API path (starting with /).
     * @param array $payload Request body, JSON-encoded automatically.
     * @param int $maxattempts Number of attempts (for transient network/5xx errors).
     * @return array Decoded JSON response.
     * @throws \moodle_exception On unrecoverable error.
     */
    protected function request(string $path, array $payload, int $maxattempts = 1): array {
        global $CFG;
        // The \curl class lives in filelib.php, which is loaded in web requests but NOT in the
        // minimal CLI/cron bootstrap used by the reconciliation task. Load it explicitly.
        require_once($CFG->libdir . '/filelib.php');

        $url = self::API_BASE . $path;
        $body = json_encode($payload);

        $lasterror = '';
        for ($attempt = 1; $attempt <= $maxattempts; $attempt++) {
            $curl = new \curl();
            $curl->setHeader([
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            $options = [
                // Interactive return path: keep worker hold minimal to avoid starvation.
                'CURLOPT_TIMEOUT' => 10,
                'CURLOPT_CONNECTTIMEOUT' => 5,
                'CURLOPT_RETURNTRANSFER' => true,
                // SECURITY: Moodle's \curl defaults CURLOPT_SSL_VERIFYPEER to 0 (off). For a
                // payment channel whose Confirm response is the sole source of truth, we MUST
                // verify the TLS peer certificate and hostname, and pin a modern TLS floor.
                'CURLOPT_SSL_VERIFYPEER' => 1,
                'CURLOPT_SSL_VERIFYHOST' => 2,
                'CURLOPT_SSLVERSION' => CURL_SSLVERSION_TLSv1_2,
            ];

            $raw = $curl->post($url, $body, $options);
            $info = $curl->get_info();
            $httpcode = isset($info['http_code']) ? (int) $info['http_code'] : 0;
            $errno = $curl->get_errno();

            // Transient failure (network error or server 5xx): retry with backoff.
            if ($errno || $httpcode >= 500 || $httpcode === 0) {
                $lasterror = $curl->error ?: ('HTTP ' . $httpcode);
                if ($attempt < $maxattempts) {
                    // Exponential backoff: 1s, 2s, 4s ...
                    sleep((int) pow(2, $attempt - 1));
                    continue;
                }
                throw new \moodle_exception('error_network', 'paygw_payphone', '', $lasterror);
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new \moodle_exception(
                    'error_badresponse',
                    'paygw_payphone',
                    '',
                    'HTTP ' . $httpcode
                );
            }

            // PayPhone returns 4xx with an error body for business errors.
            if ($httpcode >= 400) {
                throw new \moodle_exception(
                    'error_api',
                    'paygw_payphone',
                    '',
                    $this->extract_error($decoded)
                );
            }

            return $decoded;
        }

        // Should be unreachable.
        throw new \moodle_exception('error_network', 'paygw_payphone', '', $lasterror);
    }

    /**
     * Build a readable error message from a PayPhone error response.
     *
     * @param array|null $response Decoded response.
     * @return string
     */
    protected function extract_error(?array $response): string {
        if (empty($response)) {
            return get_string('error_unknown', 'paygw_payphone');
        }
        // PayPhone groups specific failures under an "errors" array.
        if (!empty($response['errors']) && is_array($response['errors'])) {
            $messages = [];
            foreach ($response['errors'] as $err) {
                if (!empty($err['message'])) {
                    $messages[] = $err['message'];
                }
            }
            if ($messages) {
                return implode(' ', $messages);
            }
        }
        if (!empty($response['message'])) {
            return $response['message'];
        }
        return get_string('error_unknown', 'paygw_payphone');
    }
}
