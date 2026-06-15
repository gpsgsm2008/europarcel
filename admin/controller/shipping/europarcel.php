<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Admin\Controller\Extension\Europarcel\Shipping;

class Europarcel extends \Opencart\System\Engine\Controller
{
    private $available_lockers = [
        'easybox' => 'Sameday EasyBox - Delivery to locker',
        'fanbox' => 'Fan Courier FANbox - Delivery to locker',
        'dpdbox' => 'DPD Box - Delivery to locker',
        'carguslocker' => 'Cargus - Delivery to locker',
    ];
    private $available_carriers = [
        'cargus_national' => 'Cargus - Delivery to address',
        'dpd_standard' => 'DPD - Delivery to address',
        'fan_courier' => 'Fan Courier - Delivery to address',
        'gls_national' => 'GLS - Delivery to address',
        'sameday' => 'SameDay - Delivery to address',
        'bookurier' => 'Bookurier - Delivery to address',
    ];
    private $carrier_settings = [
        'cargus_national' => [
            'carrier' => 'cargus_national',
            'carrier_id' => 1,
            'service_id' => 1,
            'logo' => 'cargus.webp',
        ],
        'dpd_standard' => [
            'carrier' => 'dpd_standard',
            'carrier_id' => 2,
            'service_id' => 1,
            'logo' => 'dpd.webp',
        ],
        'fan_courier' => [
            'carrier' => 'fan_courier',
            'carrier_id' => 3,
            'service_id' => 1,
            'logo' => 'fan-courier.webp',
        ],
        'gls_national' => [
            'carrier' => 'gls_national',
            'carrier_id' => 4,
            'service_id' => 1,
            'logo' => 'gls.webp',
        ],
        'sameday' => [
            'carrier' => 'sameday',
            'carrier_id' => 6,
            'service_id' => 1,
            'logo' => 'sameday.webp',
        ],
        'bookurier' => [
            'carrier' => 'bookurier',
            'carrier_id' => 5,
            'service_id' => 1,
            'logo' => 'bookurier.webp',
        ],
        'easybox' => [
            'carrier' => 'easybox',
            'carrier_id' => 6,
            'service_id' => 2,
            'logo' => 'easybox.webp',
        ],
        'fanbox' => [
            'carrier' => 'fanbox',
            'carrier_id' => 3,
            'service_id' => 2,
            'logo' => 'fanbox.webp',
        ],
        'dpdbox' => [
            'carrier' => 'dpdbox',
            'carrier_id' => 2,
            'service_id' => 2,
            'logo' => 'dpd.webp',
        ],
        'carguslocker' => [
            'carrier' => 'carguslocker',
            'carrier_id' => 1,
            'service_id' => 2,
            'logo' => 'cargus.webp',
        ],
    ];

    public function index(): void
    {
        $this->load->language('extension/europarcel/shipping/europarcel');

        if (!$this->user->hasPermission('access', 'extension/europarcel/shipping/europarcel')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
            return;
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/geo_zone');

        // Home delivery carrier list
        $data['home_couriers'] = $this->available_carriers;

        // Locker delivery carrier list
        $data['locker_couriers'] = $this->available_lockers;

        // Installed payment methods for COD configuration
        $data['payment_methods'] = $this->getInstalledPaymentMethods();

        // API Key
        $data['shipping_europarcel_api_key'] = $this->config->get('shipping_europarcel_api_key');

        // Global status
        $data['shipping_europarcel_status'] = $this->config->get('shipping_europarcel_status');

        // Home delivery settings
        $home_fields = [
            'home_status',
            'home_title',
            'home_cost',
            'free_shipping_home_total',
            'home_default_courier',
            'home_tax_class_id',
            'home_geo_zone_id',
            'home_sort_order'
        ];

        foreach ($home_fields as $field) {
            $key = 'shipping_europarcel_' . $field;
            $data[$key] = $this->config->get($key);
        }

        // Locker delivery settings
        $locker_fields = [
            'locker_status',
            'locker_title',
            'locker_cost',
            'free_shipping_locker_total',
            'locker_tax_class_id',
            'locker_geo_zone_id',
            'locker_sort_order'
        ];

        foreach ($locker_fields as $field) {
            $key = 'shipping_europarcel_' . $field;
            $data[$key] = $this->config->get($key);
        }

        // Get selected locker couriers
        $data['shipping_europarcel_locker_couriers'] = $this->config->get('shipping_europarcel_locker_couriers');
        if (!is_array($data['shipping_europarcel_locker_couriers'])) {
            $data['shipping_europarcel_locker_couriers'] = [];
        }

        // Get selected COD payment methods
        $data['shipping_europarcel_cod_payment_methods'] = $this->config->get('shipping_europarcel_cod_payment_methods');
        if (!is_array($data['shipping_europarcel_cod_payment_methods'])) {
            $data['shipping_europarcel_cod_payment_methods'] = [];
        }

        // Default settings
        $defaults = [
            'shipping_europarcel_status' => 1,
            'shipping_europarcel_home_title' => 'Livrare la adresă',
            'shipping_europarcel_home_cost' => '15.00',
            'shipping_europarcel_home_default_courier' => 'courier_standard',
            'shipping_europarcel_home_status' => 1,
            'shipping_europarcel_locker_title' => 'Livrare la locker',
            'shipping_europarcel_locker_cost' => '10.00',
            'shipping_europarcel_locker_status' => 1,
            'shipping_europarcel_free_shipping_home_total' => 0,
            'shipping_europarcel_free_shipping_locker_total' => 0
        ];

        foreach ($defaults as $key => $value) {
            if (empty($data[$key])) {
                $data[$key] = $value;
            }
        }

        // Common data
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/europarcel/shipping/europarcel', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = html_entity_decode($this->url->link('extension/europarcel/shipping/europarcel.save', 'user_token=' . $this->session->data['user_token']));
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/europarcel/shipping/europarcel', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/europarcel/shipping/europarcel');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/europarcel/shipping/europarcel')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['shipping_europarcel_home_title'])) {
            $json['error']['home_title'] = $this->language->get('error_home_title');
        }

        if (!isset($this->request->post['shipping_europarcel_home_cost']) || !is_numeric($this->request->post['shipping_europarcel_home_cost'])) {
            $json['error']['home_cost'] = $this->language->get('error_home_cost');
        }

        if (!isset($this->request->post['shipping_europarcel_free_shipping_home_total']) || !is_numeric($this->request->post['shipping_europarcel_free_shipping_home_total'])) {
            $json['error']['free_shipping_home'] = $this->language->get('error_free_shipping');
        }

        if (empty($this->request->post['shipping_europarcel_locker_title'])) {
            $json['error']['locker_title'] = $this->language->get('error_locker_title');
        }

        if (!isset($this->request->post['shipping_europarcel_locker_cost']) || !is_numeric($this->request->post['shipping_europarcel_locker_cost'])) {
            $json['error']['locker_cost'] = $this->language->get('error_locker_cost');
        }

        if (!isset($this->request->post['shipping_europarcel_free_shipping_locker_total']) || !is_numeric($this->request->post['shipping_europarcel_free_shipping_locker_total'])) {
            $json['error']['free_shipping_locker'] = $this->language->get('error_free_shipping');
        }

        if (!isset($json['error'])) {
            $this->load->model('setting/setting');

            if (!isset($this->request->post['shipping_europarcel_locker_couriers']) || !is_array($this->request->post['shipping_europarcel_locker_couriers'])) {
                $this->request->post['shipping_europarcel_locker_couriers'] = [];
            }

            if (!isset($this->request->post['shipping_europarcel_cod_payment_methods']) || !is_array($this->request->post['shipping_europarcel_cod_payment_methods'])) {
                $this->request->post['shipping_europarcel_cod_payment_methods'] = [];
            }

            $this->model_setting_setting->editSetting('shipping_europarcel', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'europarcel_order` (
            `europarcel_order_id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `service_name` varchar(50) NOT NULL,
            `selected_locker` text,
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`europarcel_order_id`),
            KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        // Default settings on install - includes global status
        $default_settings = [
            'shipping_europarcel_status' => 1, // Critical - enables the module globally
            'shipping_europarcel_api_key' => '', // API key from Europarcel dashboard - client pastes it here
            'shipping_europarcel_home_status' => 1,
            'shipping_europarcel_home_title' => 'Livrare la adresă',
            'shipping_europarcel_home_cost' => '15.00',
            'shipping_europarcel_home_default_courier' => 'courier_standard',
            'shipping_europarcel_home_tax_class_id' => 0,
            'shipping_europarcel_home_geo_zone_id' => 0,
            'shipping_europarcel_home_sort_order' => 1,
            'shipping_europarcel_locker_status' => 1,
            'shipping_europarcel_locker_title' => 'Livrare la locker',
            'shipping_europarcel_locker_cost' => '10.00',
            'shipping_europarcel_locker_couriers' => ['courier_standard'],
            'shipping_europarcel_locker_tax_class_id' => 0,
            'shipping_europarcel_locker_geo_zone_id' => 0,
            'shipping_europarcel_locker_sort_order' => 2,
            'shipping_europarcel_free_shipping_home_total' => 0,
            'shipping_europarcel_free_shipping_locker_total' => 0,
            'shipping_europarcel_cod_payment_methods' => ['cod'],
        ];

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('shipping_europarcel', $default_settings);
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code' => 'europarcel_shipping_locker',
            'description' => 'Europarcel add locker button/information',
            'trigger' => 'catalog/view/checkout/shipping_method/after',
            'action' => 'extension/europarcel/europarcel/checkout.lockerView',
            'status' => 1,
            'sort_order' => 0
        ]);

        $this->model_setting_event->addEvent([
            'code' => 'europarcel_order_add',
            'description' => 'Europarcel: Save order data when order is created',
            'trigger' => 'catalog/model/checkout/order.addOrder/after',
            'action' => 'extension/europarcel/europarcel/checkout.saveOrderEuroparcel',
            'status' => 1,
            'sort_order' => 0
        ]);
        $this->model_setting_event->addEvent([
            'code' => 'europarcel_order_info_card',
            'description' => 'Europarcel: Display locker info on order page',
            'trigger' => 'admin/view/sale/order_info/after',
            'action' => 'extension/europarcel/europarcel/order.addLockerInfo',
            'status' => 1,
            'sort_order' => 0
        ]);
        $this->model_setting_event->addEvent([
            'code' => 'europarcel_checkout_success',
            'description' => 'Europarcel: Display locker info on checkout success page',
            'trigger' => 'catalog/view/common/success/after',
            'action' => 'extension/europarcel/europarcel/checkout.addLockerInfo',
            'status' => 1,
            'sort_order' => 0
        ]);
    }

    public function uninstall(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `' . DB_PREFIX . 'europarcel_order`');

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('shipping_europarcel');
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('europarcel_shipping_locker');
        $this->model_setting_event->deleteEventByCode('europarcel_order_add');
        $this->model_setting_event->deleteEventByCode('europarcel_order_info_card');
        $this->model_setting_event->deleteEventByCode('europarcel_checkout_success');
    }

    /**
     * Get all installed and enabled payment methods
     */
    protected function getInstalledPaymentMethods(): array
    {
        $this->load->model('setting/extension');

        $payment_methods = [];

        $extensions = $this->model_setting_extension->getExtensionsByType('payment');

        foreach ($extensions as $extension) {
            $code = $extension['code'];

            $status = $this->config->get('payment_' . $code . '_status');

            if ($status) {
                $this->load->language('extension/' . $extension['extension'] . '/payment/' . $code);

                $title = $this->language->get('heading_title');

                if ($title === 'heading_title') {
                    $title = ucfirst(str_replace('_', ' ', $code));
                }

                $payment_methods[] = [
                    'code' => $code,
                    'extension' => $extension['extension'],
                    'title' => $title
                ];
            }
        }

        // Reload Europarcel language to restore overwritten keys (e.g. heading_title)
        $this->load->language('extension/europarcel/shipping/europarcel');

        return $payment_methods;
    }
}
