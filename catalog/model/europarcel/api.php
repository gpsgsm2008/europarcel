<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Catalog\Model\Extension\Europarcel\Europarcel;

class Api extends \Opencart\System\Engine\Model
{
    private array $carrier_settings = [
        'cargus_national' => ['carrier_id' => 1, 'service_id' => 1],
        'dpd_standard' => ['carrier_id' => 2, 'service_id' => 1],
        'fan_courier' => ['carrier_id' => 3, 'service_id' => 1],
        'gls_national' => ['carrier_id' => 4, 'service_id' => 1],
        'sameday' => ['carrier_id' => 6, 'service_id' => 1],
        'bookurier' => ['carrier_id' => 5, 'service_id' => 1],
        'easybox' => ['carrier_id' => 6, 'service_id' => 2],
        'fanbox' => ['carrier_id' => 3, 'service_id' => 2],
        'dpdbox' => ['carrier_id' => 2, 'service_id' => 2],
        'carguslocker' => ['carrier_id' => 1, 'service_id' => 2],
    ];

    public function getOrders(array $filters = []): array
    {
        $sql = 'SELECT o.*, eo.service_name, eo.selected_locker,
                       os.name as order_status_name,
                       c.iso_code_2 as shipping_country_code,
                       z.code as shipping_zone_code
                FROM `' . DB_PREFIX . 'order` o
                LEFT JOIN `' . DB_PREFIX . 'europarcel_order` eo ON o.order_id = eo.order_id
                LEFT JOIN `' . DB_PREFIX . "order_status` os ON o.order_status_id = os.order_status_id AND os.language_id = '" . (int) $this->config->get('config_language_id') . "'
                LEFT JOIN `" . DB_PREFIX . 'country` c ON o.shipping_country_id = c.country_id
                LEFT JOIN `' . DB_PREFIX . 'zone` z ON o.shipping_zone_id = z.zone_id
                WHERE o.order_status_id > 0';

        if (!empty($filters['status_ids'])) {
            $status_ids = array_map('intval', $filters['status_ids']);
            $sql .= ' AND o.order_status_id IN (' . implode(',', $status_ids) . ')';
        }

        if (!empty($filters['date_after'])) {
            $sql .= " AND o.date_added > '" . $this->db->escape($filters['date_after']) . "'";
        }

        if (!empty($filters['order_id_after'])) {
            $sql .= " AND o.order_id > '" . (int) $filters['order_id_after'] . "'";
        }

        $sql .= ' ORDER BY o.order_id ASC';

        $limit = isset($filters['limit']) ? min((int) $filters['limit'], 100) : 100;
        $page = isset($filters['page']) ? max((int) $filters['page'], 1) : 1;
        $offset = ($page - 1) * $limit;

        $sql .= ' LIMIT ' . (int) $offset . ', ' . (int) $limit;

        $query = $this->db->query($sql);

        $orders = [];

        foreach ($query->rows as $row) {
            $orders[] = $this->formatOrder($row);
        }

        return $orders;
    }

    public function getTotalOrders(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) as total
                FROM `' . DB_PREFIX . 'order` o
                LEFT JOIN `' . DB_PREFIX . 'europarcel_order` eo ON o.order_id = eo.order_id
                WHERE o.order_status_id > 0';

        if (!empty($filters['status_ids'])) {
            $status_ids = array_map('intval', $filters['status_ids']);
            $sql .= ' AND o.order_status_id IN (' . implode(',', $status_ids) . ')';
        }

        if (!empty($filters['date_after'])) {
            $sql .= " AND o.date_added > '" . $this->db->escape($filters['date_after']) . "'";
        }

        if (!empty($filters['order_id_after'])) {
            $sql .= " AND o.order_id > '" . (int) $filters['order_id_after'] . "'";
        }

        $query = $this->db->query($sql);

        return (int) $query->row['total'];
    }

    public function getOrder(int $order_id): ?array
    {
        $sql = 'SELECT o.*, eo.service_name, eo.selected_locker,
                       os.name as order_status_name,
                       c.iso_code_2 as shipping_country_code,
                       z.code as shipping_zone_code
                FROM `' . DB_PREFIX . 'order` o
                LEFT JOIN `' . DB_PREFIX . 'europarcel_order` eo ON o.order_id = eo.order_id
                LEFT JOIN `' . DB_PREFIX . "order_status` os ON o.order_status_id = os.order_status_id AND os.language_id = '" . (int) $this->config->get('config_language_id') . "'
                LEFT JOIN `" . DB_PREFIX . 'country` c ON o.shipping_country_id = c.country_id
                LEFT JOIN `' . DB_PREFIX . "zone` z ON o.shipping_zone_id = z.zone_id
                WHERE o.order_id = '" . (int) $order_id . "'
                AND o.order_status_id > 0";

        $query = $this->db->query($sql);

        if (!$query->num_rows) {
            return null;
        }

        return $this->formatOrder($query->row);
    }

    public function getOrderProducts(int $order_id): array
    {
        $sql = 'SELECT op.*, p.weight, p.length, p.width, p.height,
                       p.weight_class_id, p.length_class_id,
                       wcd.unit as weight_unit,
                       lcd.unit as length_unit
                FROM `' . DB_PREFIX . 'order_product` op
                LEFT JOIN `' . DB_PREFIX . 'product` p ON op.product_id = p.product_id
                LEFT JOIN `' . DB_PREFIX . "weight_class_description` wcd ON p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int) $this->config->get('config_language_id') . "'
                LEFT JOIN `" . DB_PREFIX . "length_class_description` lcd ON p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int) $this->config->get('config_language_id') . "'
                WHERE op.order_id = '" . (int) $order_id . "'";

        $query = $this->db->query($sql);

        $items = [];

        foreach ($query->rows as $row) {
            $items[] = [
                'name' => $row['name'],
                'quantity' => (int) $row['quantity'],
                'price' => (float) $row['price'],
                'total' => (float) $row['total'],
                'weight' => $this->convertWeightToKg((float) ($row['weight'] ?? 0), $row['weight_unit'] ?? 'kg'),
                'length' => $this->convertLengthToCm((float) ($row['length'] ?? 0), $row['length_unit'] ?? 'cm'),
                'width' => $this->convertLengthToCm((float) ($row['width'] ?? 0), $row['length_unit'] ?? 'cm'),
                'height' => $this->convertLengthToCm((float) ($row['height'] ?? 0), $row['length_unit'] ?? 'cm'),
            ];
        }

        return $items;
    }

    public function getOrderTotals(int $order_id): array
    {
        $sql = 'SELECT * FROM `' . DB_PREFIX . "order_total`
                WHERE order_id = '" . (int) $order_id . "'
                ORDER BY sort_order ASC";

        $query = $this->db->query($sql);

        $totals = [
            'subtotal' => 0.00,
            'shipping_total' => 0.00,
            'discount_total' => 0.00,
            'total' => 0.00,
        ];

        foreach ($query->rows as $row) {
            $code = $row['code'];
            $value = (float) $row['value'];

            switch ($code) {
                case 'sub_total':
                    $totals['subtotal'] = $value;
                    break;
                case 'shipping':
                    $totals['shipping_total'] = $value;
                    break;
                case 'total':
                    $totals['total'] = $value;
                    break;
                case 'coupon':
                case 'voucher':
                    $totals['discount_total'] += abs($value);
                    break;
            }
        }

        return $totals;
    }

    public function getStatusNameToIdMap(): array
    {
        $sql = 'SELECT order_status_id, name FROM `' . DB_PREFIX . "order_status`
                WHERE language_id = '" . (int) $this->config->get('config_language_id') . "'";

        $query = $this->db->query($sql);

        $map = [];

        foreach ($query->rows as $row) {
            $map[strtolower($row['name'])] = (int) $row['order_status_id'];
        }

        return $map;
    }

    private function formatOrder(array $row): array
    {
        $order_id = (int) $row['order_id'];
        $products = $this->getOrderProducts($order_id);
        $totals = $this->getOrderTotals($order_id);

        $service_name = $row['service_name'] ?? null;
        $selected_locker = null;
        $carrier_id = null;
        $service_id = null;

        if (!empty($row['selected_locker'])) {
            $selected_locker = json_decode($row['selected_locker'], true);
        }

        if ($service_name) {
            $carrier_info = $this->resolveCarrierInfo($service_name, $selected_locker);
            $carrier_id = $carrier_info['carrier_id'];
            $service_id = $carrier_info['service_id'];
        }

        // OpenCart 4.x stores payment_method and shipping_method as JSON strings
        $payment_method_raw = $row['payment_method'] ?? '';
        $payment_method_data = json_decode($payment_method_raw, true);
        $payment_method_name = is_array($payment_method_data) ? ($payment_method_data['name'] ?? '') : $payment_method_raw;
        $payment_code = $row['payment_code'] ?? '';
        if (empty($payment_code) && is_array($payment_method_data)) {
            $payment_code = $payment_method_data['code'] ?? '';
        }

        // Determine if this is a Cash on Delivery order based on configured COD payment methods
        $is_cod = $this->isCashOnDeliveryPayment($payment_code);

        $shipping_method_raw = $row['shipping_method'] ?? '';
        $shipping_method_data = json_decode($shipping_method_raw, true);
        $shipping_method_name = is_array($shipping_method_data) ? ($shipping_method_data['name'] ?? '') : $shipping_method_raw;
        $shipping_code = $row['shipping_code'] ?? '';
        if (empty($shipping_code) && is_array($shipping_method_data)) {
            $shipping_code = $shipping_method_data['code'] ?? '';
        }

        return [
            'id' => $order_id,
            'status' => $row['order_status_name'] ?? '',
            'status_id' => (int) $row['order_status_id'],
            'currency' => $row['currency_code'] ?? '',
            'total' => $totals['total'],
            'subtotal' => $totals['subtotal'],
            'shipping_total' => $totals['shipping_total'],
            'discount_total' => $totals['discount_total'],
            'date_created' => $row['date_added'] ?? '',
            'payment_method' => $payment_method_name,
            'payment_code' => $payment_code,
            'is_cod' => $is_cod,
            'customer' => [
                'email' => $row['email'] ?? '',
                'firstname' => $row['firstname'] ?? '',
                'lastname' => $row['lastname'] ?? '',
                'telephone' => $row['telephone'] ?? '',
            ],
            'shipping' => [
                'firstname' => $row['shipping_firstname'] ?? '',
                'lastname' => $row['shipping_lastname'] ?? '',
                'company' => $row['shipping_company'] ?? '',
                'address_1' => $row['shipping_address_1'] ?? '',
                'address_2' => $row['shipping_address_2'] ?? '',
                'city' => $row['shipping_city'] ?? '',
                'zone' => $row['shipping_zone'] ?? '',
                'zone_code' => $row['shipping_zone_code'] ?? '',
                'postcode' => $row['shipping_postcode'] ?? '',
                'country' => $row['shipping_country'] ?? '',
                'country_code' => $row['shipping_country_code'] ?? '',
            ],
            'shipping_method' => [
                'code' => $shipping_code,
                'name' => $shipping_method_name,
            ],
            'europarcel' => [
                'service_name' => $service_name,
                'carrier_id' => $carrier_id,
                'service_id' => $service_id,
                'selected_locker' => $selected_locker,
            ],
            'line_items' => $products,
        ];
    }

    private function resolveCarrierInfo(?string $service_name, ?array $selected_locker): array
    {
        if ($service_name === 'home') {
            $default_courier = $this->config->get('shipping_europarcel_home_default_courier');
            if ($default_courier && isset($this->carrier_settings[$default_courier])) {
                return $this->carrier_settings[$default_courier];
            }
            return ['carrier_id' => null, 'service_id' => 1];
        }

        if ($service_name === 'locker' && $selected_locker) {
            if (!empty($selected_locker['carrier_id'])) {
                return [
                    'carrier_id' => (int) $selected_locker['carrier_id'],
                    'service_id' => 2,
                ];
            }

            $locker_type = $selected_locker['type'] ?? $selected_locker['locker_type'] ?? null;
            if ($locker_type && isset($this->carrier_settings[$locker_type])) {
                return $this->carrier_settings[$locker_type];
            }

            return ['carrier_id' => null, 'service_id' => 2];
        }

        return ['carrier_id' => null, 'service_id' => null];
    }

    /**
     * Check if the payment code is configured as Cash on Delivery
     */
    private function isCashOnDeliveryPayment(string $payment_code): bool
    {
        if (empty($payment_code)) {
            return false;
        }

        // Get configured COD payment methods from settings
        $cod_payment_methods = $this->config->get('shipping_europarcel_cod_payment_methods');

        if (!is_array($cod_payment_methods) || empty($cod_payment_methods)) {
            // Fallback: check if payment code contains 'cod' (legacy behavior)
            return str_contains(strtolower($payment_code), 'cod');
        }

        // Extract the base payment method code (e.g., 'cod' from 'cod.cod')
        $base_code = $payment_code;
        if (str_contains($payment_code, '.')) {
            $parts = explode('.', $payment_code);
            $base_code = $parts[0];
        }

        // Check if the payment code or base code matches any configured COD method
        foreach ($cod_payment_methods as $cod_method) {
            if ($payment_code === $cod_method || $base_code === $cod_method) {
                return true;
            }
        }

        return false;
    }

    private function convertWeightToKg(float $weight, string $unit): float
    {
        return match (strtolower($unit)) {
            'g' => $weight / 1000,
            'lb' => $weight * 0.453592,
            'oz' => $weight * 0.0283495,
            default => $weight, // kg
        };
    }

    private function convertLengthToCm(float $length, string $unit): float
    {
        return match (strtolower($unit)) {
            'mm' => $length / 10,
            'in' => $length * 2.54,
            'm' => $length * 100,
            default => $length, // cm
        };
    }
}
