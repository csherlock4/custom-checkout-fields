<?php
/**
 * Plugin Name: Custom Checkout Fields
 * Description: Adds admin-configurable custom fields to WooCommerce checkout.
 * Version: 1.0
 * Author: Thems in STEM
 */

if (!defined('ABSPATH')) exit;

define('CCF_PATH', plugin_dir_path(__FILE__));
define('CCF_URL', plugin_dir_url(__FILE__));

// Register settings early in WordPress lifecycle
add_action('init', function() {
    // Legacy single field setting (for backward compatibility)
    register_setting('general', 'ccf_label', [
        'type' => 'string',
        'description' => 'Label for custom checkout field',
        'show_in_rest' => true,
        'default' => 'Extra Information'
    ]);
    
    // New multi-field system
    register_setting('general', 'ccf_fields', [
        'type' => 'array',
        'description' => 'Array of custom checkout fields',
        'show_in_rest' => [
            'schema' => [
                'type' => 'array',
                'minItems' => 0,  // Allow empty arrays
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'label' => ['type' => 'string'],
                        'type' => ['type' => 'string'],
                        'required' => ['type' => 'boolean'],
                        'enabled' => ['type' => 'boolean'],
                        'placeholder' => ['type' => 'string'],
                        'position' => ['type' => 'string']
                    ],
                    'additionalProperties' => false
                ]
            ]
        ],
        'default' => [],
        'sanitize_callback' => function($value) {
            // Handle null or false values (convert to empty array)
            if ($value === null || $value === false) {
                $value = [];
            }
            
            // Ensure we have an array
            if (!is_array($value)) {
                $value = [];
            }
            
            // Clear any object cache to prevent stale data
            wp_cache_delete('ccf_fields', 'options');
            
            return $value;
        }
    ]);
});

// Simple test function to verify plugin is loading
add_action('wp_footer', function() {
    if (is_checkout()) {
        echo '<!-- CCF Plugin Loaded Successfully -->';
    }
});

// Enqueue Admin React App
add_action('admin_menu', function () {
    add_menu_page('Custom Checkout Fields', 'Checkout Fields', 'manage_options', 'ccf-settings', function () {
        echo '<div class="wrap">';
        echo '<div id="ccf-admin-root">Loading...</div>';
        echo '</div>';
    });
});

add_action('admin_enqueue_scripts', function ($hook_suffix) {
    // Only load on our plugin page
    if ($hook_suffix !== 'toplevel_page_ccf-settings') {
        return;
    }
    
    $asset_path = CCF_URL . 'admin/dist/';
    
    wp_enqueue_script(
        'ccf-admin-js',
        $asset_path . 'assets/main.js',
        ['wp-api-request'],
        filemtime(CCF_PATH . 'admin/dist/assets/main.js'), // Use file time for cache busting
        true
    );
    
    // Add module attribute to the script tag
    add_filter('script_loader_tag', function($tag, $handle, $src) {
        if ($handle === 'ccf-admin-js') {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }, 10, 3);
    
    wp_enqueue_style(
        'ccf-admin-css', 
        $asset_path . 'assets/main.css',
        [],
        filemtime(CCF_PATH . 'admin/dist/assets/main.css')
    );
});

// Add a custom REST endpoint to test direct option updates
add_action('rest_api_init', function() {
    register_rest_route('ccf/v1', '/test-save', [
        'methods' => 'POST',
        'callback' => function($request) {
            try {
                $fields = $request->get_param('fields');
                
                // Ensure we have an array (even if empty)
                if (!is_array($fields)) {
                    $fields = [];
                }
                
                $result = update_option('ccf_fields', $fields);
                $stored = get_option('ccf_fields');
                
                return [
                    'success' => $result,
                    'sent' => $fields,
                    'stored' => $stored,
                    'message' => 'Direct option update completed'
                ];
            } catch (Exception $e) {
                return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
            }
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'fields' => [
                'required' => false,  // Make it optional since empty arrays might not be sent
                'default' => [],
                'validate_callback' => function($param) {
                    return is_array($param) || is_null($param);
                },
                'sanitize_callback' => function($param) {
                    return is_array($param) ? $param : [];
                }
            ]
        ]
    ]);
});

// Load all class files
require_once CCF_PATH . 'includes/class-field-manager.php';
require_once CCF_PATH . 'includes/class-checkout-integration.php';
require_once CCF_PATH . 'includes/class-order-meta.php';
require_once CCF_PATH . 'includes/class-admin-integration.php';
require_once CCF_PATH . 'includes/class-email-integration.php';
require_once CCF_PATH . 'includes/class-rest-api.php';
require_once CCF_PATH . 'includes/blocks-integration.php';

// Load utility functions
require_once CCF_PATH . 'includes/functions.php';

// Initialize all components
add_action('plugins_loaded', 'ccf_init_components');