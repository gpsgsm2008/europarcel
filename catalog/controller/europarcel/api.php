<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Opencart\Catalog\Controller\Extension\Europarcel\Europarcel;

class Api extends \Opencart\System\Engine\Controller
{
    private function validateApiKey(): bool
    {
        $api_key = '';

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'x-api-key') {
                    $api_key = $value;
                    break;
                }
            }
        }

        if (empty($api_key) && isset($_SERVER['HTTP_X_API_KEY'])) {
            $api_key = $_SERVER['HTTP_X_API_KEY'];
        }

        $stored_key = $this->config->get('shipping_europarcel_api_key');

        if (empty($api_key) || empty($stored_key) || !hash_equals($stored_key, $api_key)) {
            $this->response->addHeader('HTTP/1.1 401 Unauthorized');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'error' => 'AUTHENTICATION_FAILED',
                'message' => 'Invalid or missing API key',
            ]));
            return false;
        }

        return true;
    }

    public function test(): void
    {
        if (!$this->validateApiKey()) {
            return;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'store_name' => $this->config->get('config_name') ?? '',
            'opencart_version' => VERSION,
            'extension' => 'europarcel',
        ]));
    }

    public function orders(): void
    {
        if (!$this->validateApiKey()) {
            return;
        }

        $this->load->model('extension/europarcel/europarcel/api');

        $filters = [];

        // Parse status filter (CSV of status names)
        if (!empty($this->request->get['status'])) {
            $status_names = array_map('trim', explode(',', $this->request->get['status']));
            $status_map = $this->model_extension_europarcel_europarcel_api->getStatusNameToIdMap();

            $status_ids = [];
            foreach ($status_names as $name) {
                $key = strtolower($name);
                if (isset($status_map[$key])) {
                    $status_ids[] = $status_map[$key];
                }
            }

            if (!empty($status_ids)) {
                $filters['status_ids'] = $status_ids;
            }
        }

        if (!empty($this->request->get['date_after'])) {
            $filters['date_after'] = $this->request->get['date_after'];
        }

        if (!empty($this->request->get['order_id_after'])) {
            $filters['order_id_after'] = (int) $this->request->get['order_id_after'];
        }

        $filters['page'] = !empty($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
        $filters['limit'] = !empty($this->request->get['limit']) ? min((int) $this->request->get['limit'], 100) : 100;

        $orders = $this->model_extension_europarcel_europarcel_api->getOrders($filters);
        $total = $this->model_extension_europarcel_europarcel_api->getTotalOrders($filters);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'orders' => $orders,
            'total' => $total,
            'page' => $filters['page'],
            'limit' => $filters['limit'],
        ]));
    }

    public function order(): void
    {
        if (!$this->validateApiKey()) {
            return;
        }

        $order_id = isset($this->request->get['order_id']) ? (int) $this->request->get['order_id'] : 0;

        if (!$order_id) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'error' => 'INVALID_REQUEST',
                'message' => 'Missing order_id parameter',
            ]));
            return;
        }

        $this->load->model('extension/europarcel/europarcel/api');

        $order = $this->model_extension_europarcel_europarcel_api->getOrder($order_id);

        if (!$order) {
            $this->response->addHeader('HTTP/1.1 404 Not Found');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'error' => 'ORDER_NOT_FOUND',
                'message' => 'Order not found',
            ]));
            return;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($order));
    }
}
