<?php
/**
 * Plugin Name: Custom Checkout Fields
 * Description: Add admin-configurable custom fields to WooCommerce checkout.
 * Version: 1.0
 * Author: You
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
                    ]
                ]
            ]
        ],
        'default' => []
    ]);
});

// Simple test function to verify plugin is loading
add_action('wp_footer', function() {
    if (is_checkout()) {
        echo '<!-- CCF Plugin Loaded Successfully -->';
        error_log('[CCF] Plugin loaded on checkout page');
        
        // Debug the label value
        $label = get_option('ccf_label', 'Default Label');
        error_log('[CCF] Current label value: ' . $label);
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
    
    // Add some debugging info
    wp_add_inline_script('ccf-admin-js', '
        console.log("[CCF] Admin script loaded on page: " + "' . $hook_suffix . '");
        console.log("[CCF] Looking for element: #ccf-admin-root");
    ', 'before');
});

// Load field logic
require_once CCF_PATH . 'includes/functions.php';