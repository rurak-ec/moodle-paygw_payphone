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
 * This module is responsible for PayPhone content in the gateways modal.
 *
 * PayPhone uses a full-page redirect ("Botón de Pago"): we just navigate the browser to pay.php,
 * which prepares the transaction server-side and redirects to PayPhone's hosted payment page.
 *
 * @module     paygw_payphone/gateways_modal
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Modal from 'core/modal';

const showModalWithPlaceholder = async() => {
    const modal = await Modal.create({
        body: await Templates.render('paygw_payphone/payphone_button_placeholder', {})
    });
    modal.show();
};

export const process = (component, paymentArea, itemId, description) => {
    return showModalWithPlaceholder()
        .then(() => {
            // Build the query string with proper encoding (avoids HTTP parameter injection from
            // attacker-influenced values such as a course name) and include the sesskey (CSRF).
            const params = new URLSearchParams({
                component: component,
                paymentarea: paymentArea,
                itemid: itemId,
                description: description,
                sesskey: M.cfg.sesskey
            });
            location.href = M.cfg.wwwroot + '/payment/gateway/payphone/pay.php?' + params.toString();
            return new Promise(() => null);
        });
};
