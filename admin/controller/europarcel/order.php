<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Admin\Controller\Extension\Europarcel\Europarcel;

/*
 * Controller to display locker details on the order page
 */

class Order extends \Opencart\System\Engine\Controller
{
    public function addLockerInfo(string &$route, array &$args, mixed &$output): void
    {
        // Get order ID from URL
        if (isset($this->request->get['order_id'])) {
            $order_id = (int) $this->request->get['order_id'];
        } else {
            return; // No order found
        }

        // Load Europarcel order model
        $this->load->model('extension/europarcel/europarcel/order');

        // Get locker details for this order
        $locker_info = $this->model_extension_europarcel_europarcel_order->getLockerInfo($order_id);

        // If locker info exists and shipping method is Europarcel
        if ($locker_info) {
            $this->load->language('extension/europarcel/shipping/europarcel');
            $logo_map = [
                'Easybox' => 'easybox',
                'Fanbox' => 'fanbox',
                'FANbox' => 'fanbox',
                'DPD Box' => 'dpd',
                'DPD' => 'dpd',
                'Cargus' => 'cargus',
            ];

            $carrier = $locker_info['carrier_name'] ?? '';
            $logo_file = $logo_map[$carrier] ?? 'easybox';

            $data = [
                'locker_name' => $locker_info['locker_name'] ?? '',
                'locker_address' => $locker_info['locker_address'] ?? '',
                'locker_city' => $locker_info['locker_city'] ?? '',
                'locker_county' => $locker_info['locker_county'] ?? '',
                'carrier_name' => $carrier,
                'carrier_logo' => '../extension/europarcel/catalog/view/image/europarcel/' . $logo_file . '.webp',
                'lat' => $locker_info['lat'] ?? '',
                'long' => $locker_info['long'] ?? '',
                'text_locker_details' => $this->language->get('text_locker_details'),
                'text_locker_name' => $this->language->get('text_locker_name'),
                'text_locker_address' => $this->language->get('text_locker_address'),
                'text_locker_locality' => $this->language->get('text_locker_locality'),
                'text_locker_location' => $this->language->get('text_locker_location'),
                'text_view_on_map' => $this->language->get('text_view_on_map'),
            ];

            // Append locker details HTML
            $locker_html = $this->load->view('extension/europarcel/europarcel/locker_info', $data);
            $output .= $this->getJSScript($locker_html);
        }
    }

    private function getJSScript(string $html): string
    {
        $escaped_html = addslashes(str_replace(["\n", "\r"], '', $html));

        return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function insertAfter(ref, node) {
            ref.parentNode.insertBefore(node, ref.nextSibling);
        }

        function inject() {
            var target = document.getElementById('output-shipping-method');
            if (target && !document.querySelector('.europarcel-locker-info')) {
                var div = document.createElement('div');
                div.className = 'europarcel-locker-info mt-3';
                div.innerHTML = '{$escaped_html}';
                insertAfter(target, div);
            }
        }

        inject();
        if (!document.querySelector('.europarcel-locker-info')) {
            setTimeout(inject, 100);
        }
    });
    </script>
    HTML;
    }
}
