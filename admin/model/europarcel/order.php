<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Admin\Model\Extension\Europarcel\Europarcel;

class Order extends \Opencart\System\Engine\Model
{
    public function getLockerInfo(int $order_id): array
    {
        $query = $this->db->query('SELECT * FROM `' . DB_PREFIX . "europarcel_order` WHERE `order_id` = '" . (int) $order_id . "'");

        if ($query->num_rows && $query->row['service_name'] === 'locker') {
            // Decode the JSON from selected_locker
            $locker_data = json_decode($query->row['selected_locker'], true);

            return [
                'service_name' => $query->row['service_name'],
                'locker_name' => $locker_data['name'] ?? '',
                'locker_address' => $locker_data['address'] ?? '',
                'locker_city' => $locker_data['locality_name'] ?? '',
                'locker_county' => $locker_data['county_name'] ?? '',
                'carrier_name' => $locker_data['carrier_name'] ?? '',
                'lat' => $locker_data['coordinates']['lat'] ?? '',
                'long' => $locker_data['coordinates']['long'] ?? '',
            ];
        }

        return [];
    }
}
