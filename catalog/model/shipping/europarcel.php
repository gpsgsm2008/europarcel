<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Catalog\Model\Extension\Europarcel\Shipping;

class Europarcel extends \Opencart\System\Engine\Model
{
    public function getQuote(array $address): array
    {
        $this->load->language('extension/europarcel/shipping/europarcel');

        // Check global module status
        if (!$this->config->get('shipping_europarcel_status')) {
            return [];
        }

        $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('shipping_europarcel_home_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

        $status = false;
        $method_data = [];
        $sub_total = $this->cart->getSubTotal();
        // Home to Home
        if ($this->config->get('shipping_europarcel_home_status')) {
            if ($this->config->get('shipping_europarcel_home_geo_zone_id') == 0 || $query->num_rows) {
                $status = true;
            }

            if ($status) {
                $cost = (float) $this->config->get('shipping_europarcel_home_cost');
                $quote_data = [];
                $free_shiping_home = (float) $this->config->get('shipping_europarcel_free_shipping_home_total');
                if ($free_shiping_home != 0 && $free_shiping_home < $sub_total) {
                    $cost = 0;
                }
                $quote_data['home'] = [
                    'code' => 'europarcel.home',
                    'name' => $this->config->get('shipping_europarcel_home_title'),
                    'cost' => $cost,
                    'tax_class_id' => $this->config->get('shipping_europarcel_home_tax_class_id'),
                    'text' => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_europarcel_home_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
                ];

                $method_data = [
                    'code' => 'europarcel',
                    'name' => $this->language->get('text_title'),
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get('shipping_europarcel_home_sort_order'),
                    'error' => false
                ];
            }
        }

        // Home to Locker
        if ($this->config->get('shipping_europarcel_locker_status')) {
            $query = $this->db->query('SELECT * FROM ' . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('shipping_europarcel_locker_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

            $status = false;
            if ($this->config->get('shipping_europarcel_locker_geo_zone_id') == 0 || $query->num_rows) {
                $status = true;
            }

            if ($status) {
                $cost = (float) $this->config->get('shipping_europarcel_locker_cost');
                $free_shiping_locker = (float) $this->config->get('shipping_europarcel_free_shipping_locker_total');
                if ($free_shiping_locker != 0 && $free_shiping_locker < $sub_total) {
                    $cost = 0;
                }

                if (!isset($method_data['quote'])) {
                    $method_data['quote'] = [];
                }

                $method_data['quote']['locker'] = [
                    'code' => 'europarcel.locker',
                    'name' => $this->config->get('shipping_europarcel_locker_title'),
                    'cost' => $cost,
                    'tax_class_id' => $this->config->get('shipping_europarcel_locker_tax_class_id'),
                    'text' => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_europarcel_locker_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
                ];

                if (empty($method_data['title'])) {
                    $method_data['title'] = $this->language->get('text_title');
                }
                if (empty($method_data['code'])) {
                    $method_data['code'] = 'europarcel';
                }
                if (empty($method_data['sort_order'])) {
                    $method_data['sort_order'] = $this->config->get('shipping_europarcel_locker_sort_order');
                }
            }
        }

        return $method_data;
    }
}
