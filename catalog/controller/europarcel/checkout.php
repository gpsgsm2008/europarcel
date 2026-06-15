<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Catalog\Controller\Extension\Europarcel\Europarcel;

class Checkout extends \Opencart\System\Engine\Controller
{
    public function lockerView(string &$route, array &$args, mixed &$output): void
    {
        $this->load->language('extension/europarcel/shipping/europarcel');

        $data['button_select_locker'] = $this->language->get('button_select_locker');
        $data['text_select_locker'] = $this->language->get('text_select_locker');

        // 1. Get selected lockers from admin settings
        $selected_lockers = $this->config->get('shipping_europarcel_locker_couriers');

        // If no settings exist, use defaults
        if (!is_array($selected_lockers) || empty($selected_lockers)) {
            $selected_lockers = ['fanbox']; // default
        }

        // 2. Map locker codes to carrier IDs
        $locker_to_carrier = [
            'easybox' => 6,
            'fanbox' => 3,
            'dpdbox' => 2,
            'carguslocker' => 1
        ];

        // 3. Get carrier IDs for selected lockers
        $carrier_ids = [];
        foreach ($selected_lockers as $locker_code) {
            if (isset($locker_to_carrier[$locker_code])) {
                $carrier_ids[] = $locker_to_carrier[$locker_code];
            }
        }

        $carrier_ids = array_unique($carrier_ids);

        // 4. Pass data to template
        $data['europarcel_config'] = [
            'selected_lockers' => $selected_lockers,
            'carrier_ids' => implode(',', $carrier_ids),
            'api_url' => 'https://maps.europarcel.com',
            'text_locker_selected' => $this->language->get('text_locker_selected'),
            'button_choose_locker' => $this->language->get('button_choose_locker'),
            'button_change_locker' => $this->language->get('button_change_locker'),
        ];

        $output .= $this->load->view('extension/europarcel/shipping/europarcel', $data);
    }

    public function saveLocker(): void
    {
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $locker_data = $this->request->post['locker_data'] ?? '';

            if ($locker_data && is_array($locker_data)) {
                // Save to session
                $this->session->data['europarcel_locker'] = $locker_data;
                $json['success'] = true;
                $json['message'] = 'Locker saved successfully';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveOrderEuroparcel(string &$route, array &$args, mixed &$output): void
    {
        if (!$this->config->get('shipping_europarcel_status')) {
            return;
        }

        $order_id = (int) $output;

        if (!$order_id) {
            return;
        }

        $shipping_code = '';

        if (isset($this->session->data['shipping_method']['code'])) {
            $shipping_code = $this->session->data['shipping_method']['code'];
        }

        if (strpos($shipping_code, 'europarcel.') !== 0) {
            unset($this->session->data['europarcel_locker']);
            return;
        }

        $service_name = str_replace('europarcel.', '', $shipping_code);

        $selected_locker = null;

        if ($service_name === 'locker' && isset($this->session->data['europarcel_locker'])) {
            $selected_locker = json_encode($this->session->data['europarcel_locker']);
        } else {
            unset($this->session->data['europarcel_locker']);
        }

        $this->db->query('INSERT INTO `' . DB_PREFIX . "europarcel_order` SET
            `order_id` = '" . (int) $order_id . "',
            `service_name` = '" . $this->db->escape($service_name) . "',
            `selected_locker` = " . ($selected_locker ? "'" . $this->db->escape($selected_locker) . "'" : 'NULL') . ',
            `date_added` = NOW()
        ');

        ///unset($this->session->data['europarcel_locker']);
    }

    public function getLocker(): void
    {
        $json = [];

        if (isset($this->session->data['europarcel_locker'])) {
            $json['locker'] = $this->session->data['europarcel_locker'];
            $json['success'] = true;
        } else {
            $json['success'] = false;
            $json['message'] = 'No locker selected';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addLockerInfo(string &$route, array &$args, mixed &$output): void
    {
        if (isset($this->session->data['europarcel_locker'])) {
            $locker_info = $this->session->data['europarcel_locker'];

            if (!empty($locker_info)) {
                $this->load->language('extension/europarcel/shipping/europarcel');

                $locker_info['text_locker_details'] = $this->language->get('text_locker_details');
                $locker_info['text_locker_name'] = $this->language->get('text_locker_name');
                $locker_info['text_locker_address'] = $this->language->get('text_locker_address');
                $locker_info['text_locker_locality'] = $this->language->get('text_locker_locality');
                $locker_info['text_view_on_map'] = $this->language->get('text_view_on_map');

                $locker_html = $this->load->view('extension/europarcel/europarcel/checkout_locker_info', $locker_info);

                $output .= $this->getCheckoutSuccessJSScript($locker_html);
            }
        }
    }

    private function getCheckoutSuccessJSScript(string $html): string
    {
        $escaped_html = addslashes(str_replace(["\n", "\r"], '', $html));

        return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait briefly to ensure the DOM is fully loaded
        setTimeout(function() {
            // Find the main container
            var contentDiv = document.getElementById('content');
            
            if (contentDiv) {
                // Create div for locker info
                var lockerDiv = document.createElement('div');
                lockerDiv.className = 'europarcel-checkout-locker';
                lockerDiv.innerHTML = '{$escaped_html}';
                
                // Append directly at the end of content
                contentDiv.insertBefore(lockerDiv,contentDiv.lastElementChild);
            }
        }, 100);
        
        // Enhanced styles
        var style = document.createElement('style');
        style.textContent = `
            .europarcel-checkout-locker {
                margin-top: 40px;
                margin-bottom: 20px;
                clear: both;
            }
            .europarcel-locker-alert {
                border: 1px solid #dee2e6;
                border-left: 4px solid #17a2b8;
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .europarcel-locker-alert h5 {
                color: #17a2b8;
                font-size: 1.3rem;
                margin-bottom: 15px;
                font-weight: 600;
            }
            .europarcel-locker-alert hr {
                margin: 15px 0;
                border-color: #ced4da;
                opacity: 0.5;
            }
            .europarcel-locker-alert .row {
                margin: 0 -10px;
            }
            .europarcel-locker-alert .col-md-6 {
                padding: 0 10px;
            }
            .europarcel-locker-alert p {
                margin-bottom: 10px;
                font-size: 0.95rem;
                line-height: 1.4;
            }
            .europarcel-locker-alert strong {
                color: #495057;
                font-weight: 600;
                display: inline-block;
                min-width: 100px;
            }
            @media (max-width: 768px) {
                .europarcel-locker-alert .row {
                    flex-direction: column;
                }
                .europarcel-locker-alert .col-md-6 {
                    margin-bottom: 15px;
                }
            }
        `;
        document.head.appendChild(style);
    });
    </script>
    HTML;
    }
}
