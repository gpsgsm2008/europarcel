<?php
/**
 * @copyright Copyright (c) Europarcel (https://www.europarcel.com)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

$_['heading_title'] = 'Europarcel Shipping';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified Europarcel shipping!';
$_['text_edit'] = 'Edit Europarcel Shipping';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_general_settings'] = 'General Settings';
$_['text_home_settings'] = 'Home to Home Settings';
$_['text_locker_settings'] = 'Home to Locker Settings';

// Courier texts
$_['text_courier_standard'] = 'Standard Courier';
$_['text_courier_express'] = 'Express Courier';
$_['text_courier_economy'] = 'Economy Courier';
$_['text_courier_same_day'] = 'Same Day Courier';

$_['text_api_integration'] = 'API Integration';
$_['text_none'] = '--- None ---';
$_['text_all_zones'] = 'All Zones';

$_['entry_status'] = 'Status';
$_['entry_api_key'] = 'Europarcel API Key';
$_['entry_cod_payment_methods'] = 'Cash on Delivery Payment Methods';

// Entry Home to Home
$_['entry_home_status'] = 'Home Delivery Status';
$_['entry_home_title'] = 'Display Title';
$_['entry_home_cost'] = 'Shipping Cost';
$_['entry_home_default_courier'] = 'Default Courier';
$_['entry_home_tax_class'] = 'Tax Class';
$_['entry_home_geo_zone'] = 'Geo Zone';
$_['entry_home_sort_order'] = 'Sort Order';
$_['entry_home_free_shiping'] = 'Free shiping from';

// Entry Home to Locker
$_['entry_locker_status'] = 'Locker Delivery Status';
$_['entry_locker_title'] = 'Display Title';
$_['entry_locker_cost'] = 'Shipping Cost';
$_['entry_locker_couriers'] = 'Available Couriers';
$_['entry_locker_tax_class'] = 'Tax Class';
$_['entry_locker_geo_zone'] = 'Geo Zone';
$_['entry_locker_sort_order'] = 'Sort Order';
$_['entry_locker_free_shiping'] = 'Free shiping from';

// Help
$_['help_home_title'] = 'Title displayed during checkout for home delivery';
$_['help_home_cost'] = 'Fixed cost for home delivery';
$_['help_home_default_courier'] = 'Default courier for home delivery';
$_['help_locker_title'] = 'Title displayed during checkout for locker delivery';
$_['help_locker_cost'] = 'Fixed cost for locker delivery';
$_['help_locker_couriers'] = 'Select available couriers for locker delivery';
$_['help_api_key'] = 'Copy your API Key from your Europarcel account (Dashboard > API Keys) and paste it here. This key is used to sync orders with the Europarcel platform.';
$_['help_free_shipping'] = 'Minimum purchase value for free delivery, 0 is disabled';
$_['help_cod_payment_methods'] = 'Select which payment methods should be treated as Cash on Delivery (COD). Orders with these payment methods will have bank repayment amount set automatically.';

// Text
$_['text_no_payment_methods'] = 'No payment methods installed. Please install and enable payment methods first.';

// Locker info (admin order view)
$_['text_locker_details'] = 'Locker Details';
$_['text_locker_name'] = 'Locker name';
$_['text_locker_address'] = 'Address';
$_['text_locker_locality'] = 'Locality';
$_['text_locker_location'] = 'Location';
$_['text_view_on_map'] = 'View on Google Maps';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Europarcel shipping!';
$_['error_home_title'] = 'Home delivery title is required!';
$_['error_home_cost'] = 'Home delivery cost must be a valid number!';
$_['error_locker_title'] = 'Locker delivery title is required!';
$_['error_locker_cost'] = 'Locker delivery cost must be a valid number!';
$_['error_free_shipping'] = 'Must be a valid number!';
