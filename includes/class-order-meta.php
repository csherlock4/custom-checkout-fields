<?php

/**
 * Order Meta Class
 * 
 * Handles order processing and meta data storage for custom fields.
 */
class CCF_Order_Meta {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Order_Meta
     */
    private static $instance = null;
    
    /**
     * Field Manager instance
     * 
     * @var CCF_Field_Manager
     */
    private $field_manager;
    
    /**
     * Get instance of this class
     * 
     * @return CCF_Order_Meta
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->field_manager = CCF_Field_Manager::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Save field data when order is placed
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_order_meta']);
        
        // Save field data for block-based checkout
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'save_block_order_meta'], 10, 2);
    }
    
    /**
     * Save custom field data to order meta (classic checkout)
     * 
     * @param int $order_id Order ID
     */
    public function save_order_meta($order_id) {
        $fields = $this->field_manager->get_fields();
        
        // Handle legacy single field for backward compatibility
        if (!empty($_POST['ccf_field'])) {
            $field_value = sanitize_text_field($_POST['ccf_field']);
            update_post_meta($order_id, '_ccf_field', $field_value);
        }
        
        // Save all custom fields
        foreach ($fields as $field) {
            if (empty($field['enabled']) || empty($field['id'])) {
                continue;
            }
            
            $field_key = $field['id'];
            if (!empty($_POST[$field_key])) {
                $field_value = $this->sanitize_field_value($_POST[$field_key], $field['type']);
                $this->save_field_meta($order_id, $field_key, $field_value, $field);
            }
        }
    }
    
    /**
     * Save custom field data to order meta (block checkout)
     * 
     * @param WC_Order $order Order object
     * @param WP_REST_Request $request Request object
     */
    public function save_block_order_meta($order, $request) {
        $data = $request->get_json_params();
        
        // Handle legacy field
        if (!empty($data['ccf_field'])) {
            $field_value = sanitize_text_field($data['ccf_field']);
            $order->update_meta_data('_ccf_field', $field_value);
        }
        
        // Handle all configured fields
        $fields = $this->field_manager->get_fields();
        foreach ($fields as $field) {
            if (empty($field['enabled']) || empty($field['id'])) {
                continue;
            }
            
            $field_key = $field['id'];
            if (!empty($data[$field_key])) {
                $field_value = $this->sanitize_field_value($data[$field_key], $field['type']);
                $this->save_field_meta_to_order($order, $field_key, $field_value, $field);
            }
        }
        
        // Save the order to persist meta data
        $order->save();
    }
    
    /**
     * Save field meta data to order (post meta)
     * 
     * @param int $order_id Order ID
     * @param string $field_key Field key
     * @param mixed $field_value Field value
     * @param array $field_config Field configuration
     */
    private function save_field_meta($order_id, $field_key, $field_value, $field_config) {
        update_post_meta($order_id, '_' . $field_key, $field_value);
        
        // Also save field configuration for reference
        update_post_meta($order_id, '_' . $field_key . '_config', $field_config);
    }
    
    /**
     * Save field meta data to order object
     * 
     * @param WC_Order $order Order object
     * @param string $field_key Field key
     * @param mixed $field_value Field value
     * @param array $field_config Field configuration
     */
    private function save_field_meta_to_order($order, $field_key, $field_value, $field_config) {
        $order->update_meta_data('_' . $field_key, $field_value);
        
        // Also save field configuration for reference
        $order->update_meta_data('_' . $field_key . '_config', $field_config);
    }
    
    /**
     * Sanitize field value based on field type
     * 
     * @param mixed $value Field value
     * @param string $type Field type
     * @return mixed Sanitized value
     */
    private function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'number':
                return floatval($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get custom field data for an order
     * 
     * @param int $order_id Order ID
     * @return array Array of custom field data
     */
    public function get_order_custom_fields($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return [];
        }
        
        $custom_fields = [];
        $configured_fields = $this->field_manager->get_fields();
        
        // Check for legacy field first
        $legacy_value = $order->get_meta('_ccf_field', true);
        if ($legacy_value) {
            $legacy_label = get_option('ccf_label', 'Extra Information');
            $custom_fields[] = [
                'id' => 'ccf_field',
                'key' => '_ccf_field',
                'value' => $legacy_value,
                'label' => $legacy_label,
                'type' => 'text',
                'config' => null
            ];
        }
        
        // Get all multi-field values
        foreach ($configured_fields as $field) {
            if (empty($field['id'])) {
                continue;
            }
            
            $field_key = '_' . $field['id'];
            $field_value = $order->get_meta($field_key, true);
            
            if ($field_value !== '') { // Allow empty strings but not false/null
                $custom_fields[] = [
                    'id' => $field['id'],
                    'key' => $field_key,
                    'value' => $field_value,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'config' => $field
                ];
            }
        }
        
        return $custom_fields;
    }
    
    /**
     * Update order meta data
     * 
     * @param int $order_id Order ID
     * @param array $fields Array of field data to update
     * @return bool True on success, false on failure
     */
    public function update_order_meta($order_id, $fields) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        $updated_fields = [];
        
        // Update each field
        foreach ($fields as $field) {
            if (!isset($field['key']) || !isset($field['value'])) {
                continue;
            }
            
            $field_key = $field['key'];
            $field_value = sanitize_text_field($field['value']);
            
            // Update the meta
            update_post_meta($order_id, $field_key, $field_value);
            
            $updated_fields[] = [
                'key' => $field_key,
                'value' => $field_value
            ];
        }
        
        // Add order note about the update
        if (!empty($updated_fields)) {
            $note = 'Custom fields updated: ' . implode(', ', array_map(function($f) {
                return str_replace(['_ccf_', '_'], ['', ' '], $f['key']);
            }, $updated_fields));
            
            $order->add_order_note($note);
        }
        
        return true;
    }
    
    /**
     * Delete custom field data from order
     * 
     * @param int $order_id Order ID
     * @param string $field_key Field key
     * @return bool True on success, false on failure
     */
    public function delete_order_field($order_id, $field_key) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        delete_post_meta($order_id, $field_key);
        delete_post_meta($order_id, $field_key . '_config');
        
        // Add order note
        $field_name = str_replace(['_ccf_', '_'], ['', ' '], $field_key);
        $order->add_order_note("Custom field '$field_name' removed");
        
        return true;
    }
    
    /**
     * Get formatted custom field data for display
     * 
     * @param int $order_id Order ID
     * @return array Array of formatted field data
     */
    public function get_formatted_order_fields($order_id) {
        $custom_fields = $this->get_order_custom_fields($order_id);
        $formatted_fields = [];
        
        foreach ($custom_fields as $field) {
            $formatted_fields[] = [
                'label' => $field['label'],
                'value' => $field['value'],
                'type' => $field['type']
            ];
        }
        
        return $formatted_fields;
    }
    
    /**
     * Check if order has custom fields
     * 
     * @param int $order_id Order ID
     * @return bool True if order has custom fields, false otherwise
     */
    public function order_has_custom_fields($order_id) {
        $custom_fields = $this->get_order_custom_fields($order_id);
        return !empty($custom_fields);
    }
}
