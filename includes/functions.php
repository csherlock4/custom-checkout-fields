<?php

/**
 * Utility Functions
 * 
 * This file contains utility functions and helpers for the Custom Checkout Fields plugin.
 * Most functionality has been moved to dedicated classes for better organization.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin version
 * 
 * @return string Plugin version
 */
function ccf_get_version() {
    return '1.0.0';
}

/**
 * Get plugin name
 * 
 * @return string Plugin name
 */
function ccf_get_plugin_name() {
    return __('Custom Checkout Fields', 'custom-checkout-fields');
}

/**
 * Check if WooCommerce is active
 * 
 * @return bool True if WooCommerce is active
 */
function ccf_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * Get supported field types
 * 
 * @return array Array of supported field types
 */
function ccf_get_supported_field_types() {
    return apply_filters('ccf_supported_field_types', [
        'text' => __('Text Input', 'custom-checkout-fields'),
        'textarea' => __('Textarea', 'custom-checkout-fields'),
        'select' => __('Select Dropdown', 'custom-checkout-fields'),
        'email' => __('Email', 'custom-checkout-fields'),
        'tel' => __('Phone', 'custom-checkout-fields'),
        'number' => __('Number', 'custom-checkout-fields'),
        'url' => __('URL', 'custom-checkout-fields'),
    ]);
}

/**
 * Get field positions
 * 
 * @return array Array of field positions
 */
function ccf_get_field_positions() {
    return apply_filters('ccf_field_positions', [
        'after_billing' => __('After Billing Fields', 'custom-checkout-fields'),
        'after_shipping' => __('After Shipping Fields', 'custom-checkout-fields'),
        'before_payment' => __('Before Payment Methods', 'custom-checkout-fields'),
    ]);
}

/**
 * Log debug message
 * 
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 */
function ccf_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[CCF] [{$level}] {$message}");
    }
}

/**
 * Check if current page is checkout
 * 
 * @return bool True if checkout page
 */
function ccf_is_checkout() {
    return function_exists('is_checkout') && is_checkout();
}

/**
 * Get field validation rules
 * 
 * @param string $field_type Field type
 * @return array Validation rules
 */
function ccf_get_field_validation_rules($field_type) {
    $rules = [
        'text' => ['max_length' => 255],
        'textarea' => ['max_length' => 1000],
        'email' => ['max_length' => 255, 'format' => 'email'],
        'tel' => ['max_length' => 20],
        'number' => ['type' => 'numeric'],
        'url' => ['max_length' => 255, 'format' => 'url'],
        'select' => ['options_required' => true],
    ];
    
    return apply_filters('ccf_field_validation_rules', $rules[$field_type] ?? [], $field_type);
}

/**
 * Format field value for display
 * 
 * @param mixed $value Field value
 * @param string $field_type Field type
 * @return string Formatted value
 */
function ccf_format_field_value($value, $field_type = 'text') {
    if (empty($value)) {
        return '';
    }
    
    switch ($field_type) {
        case 'email':
            return sprintf('<a href="mailto:%s">%s</a>', esc_attr($value), esc_html($value));
        case 'tel':
            return sprintf('<a href="tel:%s">%s</a>', esc_attr($value), esc_html($value));
        case 'url':
            return sprintf('<a href="%s" target="_blank">%s</a>', esc_url($value), esc_html($value));
        case 'textarea':
            return nl2br(esc_html($value));
        default:
            return esc_html($value);
    }
}

/**
 * Get default field configuration
 * 
 * @return array Default field configuration
 */
function ccf_get_default_field_config() {
    return [
        'id' => '',
        'label' => '',
        'type' => 'text',
        'required' => false,
        'enabled' => true,
        'placeholder' => '',
        'position' => 'after_billing',
        'options' => [],
    ];
}

/**
 * Generate CSS class for field type
 * 
 * @param string $field_type Field type
 * @return string CSS class
 */
function ccf_get_field_type_class($field_type) {
    $classes = [
        'text' => 'ccf-field-text',
        'textarea' => 'ccf-field-textarea',
        'select' => 'ccf-field-select',
        'email' => 'ccf-field-email',
        'tel' => 'ccf-field-tel',
        'number' => 'ccf-field-number',
        'url' => 'ccf-field-url',
    ];
    
    return $classes[$field_type] ?? 'ccf-field-text';
}

/**
 * Check if field is required
 * 
 * @param array $field Field configuration
 * @return bool True if required
 */
function ccf_is_field_required($field) {
    return !empty($field['required']);
}

/**
 * Check if field is enabled
 * 
 * @param array $field Field configuration
 * @return bool True if enabled
 */
function ccf_is_field_enabled($field) {
    return !empty($field['enabled']) && !empty($field['id']);
}

/**
 * Get admin page URL
 * 
 * @return string Admin page URL
 */
function ccf_get_admin_url() {
    return admin_url('admin.php?page=ccf-settings');
}

/**
 * Get plugin documentation URL
 * 
 * @return string Documentation URL
 */
function ccf_get_documentation_url() {
    return 'https://github.com/csherlock4/custom-checkout-fields';
}

/**
 * Get plugin support URL
 * 
 * @return string Support URL
 */
function ccf_get_support_url() {
    return 'https://github.com/csherlock4/custom-checkout-fields/issues';
}

/**
 * Check if user can manage fields
 * 
 * @return bool True if user can manage fields
 */
function ccf_user_can_manage_fields() {
    return current_user_can('manage_options');
}

/**
 * Check if user can edit orders
 * 
 * @return bool True if user can edit orders
 */
function ccf_user_can_edit_orders() {
    return current_user_can('edit_shop_orders') || current_user_can('manage_woocommerce');
}

/**
 * Get legacy field label (for backward compatibility)
 * 
 * @return string Legacy field label
 */
function ccf_get_legacy_field_label() {
    return get_option('ccf_label', __('Extra Information', 'custom-checkout-fields'));
}

/**
 * Clean field ID (remove special characters)
 * 
 * @param string $id Field ID
 * @return string Cleaned field ID
 */
function ccf_clean_field_id($id) {
    return sanitize_key($id);
}

/**
 * Get field icon for admin display
 * 
 * @param string $field_type Field type
 * @return string Icon HTML or dashicon class
 */
function ccf_get_field_icon($field_type) {
    $icons = [
        'text' => 'dashicons-edit',
        'textarea' => 'dashicons-text',
        'select' => 'dashicons-arrow-down-alt2',
        'email' => 'dashicons-email',
        'tel' => 'dashicons-phone',
        'number' => 'dashicons-calculator',
        'url' => 'dashicons-admin-links',
    ];
    
    return $icons[$field_type] ?? 'dashicons-edit';
}

/**
 * Initialize all plugin components
 * 
 * This function is called from the main plugin file to initialize all components.
 */
function ccf_init_components() {
    // Initialize singletons
    CCF_Field_Manager::get_instance();
    CCF_Checkout_Integration::get_instance();
    CCF_Order_Meta::get_instance();
    CCF_Admin_Integration::get_instance();
    CCF_Email_Integration::get_instance();
    CCF_REST_API::get_instance();
    
    // Initialize block integration (it will self-determine if it should activate)
    CCF_Block_Checkout::get_instance();
}