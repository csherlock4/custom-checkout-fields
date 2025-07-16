<?php

/**
 * Admin Integration Class
 * 
 * Handles admin screens and meta boxes for custom fields.
 */
class CCF_Admin_Integration {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Admin_Integration
     */
    private static $instance = null;
    
    /**
     * Order Meta instance
     * 
     * @var CCF_Order_Meta
     */
    private $order_meta;
    
    /**
     * Get instance of this class
     * 
     * @return CCF_Admin_Integration
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
        $this->order_meta = CCF_Order_Meta::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add meta box to order edit screens
        add_action('add_meta_boxes', [$this, 'add_order_meta_box']);
        
        // Enqueue scripts for order edit pages
        add_action('admin_enqueue_scripts', [$this, 'enqueue_order_meta_scripts']);
        
        // Show custom fields in order details
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_order_custom_fields']);
    }
    
    /**
     * Add meta box to WooCommerce order edit screen
     */
    public function add_order_meta_box() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'])) {
            return;
        }
        
        add_meta_box(
            'ccf-order-fields',
            'Custom Checkout Fields',
            [$this, 'render_order_meta_box'],
            ['shop_order', 'woocommerce_page_wc-orders'],
            'normal',
            'default'
        );
    }
    
    /**
     * Render order meta box
     * 
     * @param WP_Post|WC_Order $post_or_order Post or order object
     */
    public function render_order_meta_box($post_or_order) {
        $order_id = $this->get_order_id($post_or_order);
        
        if (!$order_id) {
            echo '<div class="p-4 text-red-600">Error: Could not determine order ID</div>';
            return;
        }
        
        // Create container for React component
        echo '<div id="ccf-order-meta-root" data-order-id="' . esc_attr($order_id) . '">Loading custom fields...</div>';
    }
    
    /**
     * Get order ID from post or order object
     * 
     * @param WP_Post|WC_Order $post_or_order Post or order object
     * @return int|null Order ID or null if not found
     */
    private function get_order_id($post_or_order) {
        if (is_object($post_or_order)) {
            if (method_exists($post_or_order, 'get_id')) {
                // WC_Order object
                return $post_or_order->get_id();
            } elseif (isset($post_or_order->ID)) {
                // Post object
                return $post_or_order->ID;
            }
        }
        
        // Fallback - try to get from $_GET or $_POST
        if (isset($_GET['id'])) {
            return intval($_GET['id']);
        }
        
        if (isset($_POST['order_id'])) {
            return intval($_POST['order_id']);
        }
        
        return null;
    }
    
    /**
     * Enqueue scripts for order edit pages
     * 
     * @param string $hook_suffix Current admin page hook suffix
     */
    public function enqueue_order_meta_scripts($hook_suffix) {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'])) {
            return;
        }
        
        // Check if we're on an order edit page (not order list)
        if (!isset($_GET['id']) && !isset($_GET['post'])) {
            return;
        }
        
        $asset_path = CCF_URL . 'admin/dist/';
        
        // Enqueue the order meta box React script
        wp_enqueue_script(
            'ccf-order-meta-js',
            $asset_path . 'assets/order-meta.js',
            ['wp-api-request'],
            filemtime(CCF_PATH . 'admin/dist/assets/order-meta.js'),
            true
        );
        
        // Add module attribute to the script tag
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ($handle === 'ccf-order-meta-js') {
                $tag = str_replace('<script ', '<script type="module" ', $tag);
            }
            return $tag;
        }, 10, 3);
        
        // Enqueue the shared CSS
        wp_enqueue_style(
            'ccf-order-meta-css',
            $asset_path . 'assets/main.css',
            [],
            filemtime(CCF_PATH . 'admin/dist/assets/main.css')
        );
    }
    
    /**
     * Display custom fields in WooCommerce admin order details
     * 
     * @param WC_Order $order Order object
     */
    public function display_order_custom_fields($order) {
        $order_id = $order->get_id();
        $custom_fields = $this->order_meta->get_formatted_order_fields($order_id);
        
        if (empty($custom_fields)) {
            return;
        }
        
        echo '<div class="ccf-admin-order-fields" style="margin-top: 20px;">';
        echo '<h3>' . __('Custom Fields', 'custom-checkout-fields') . '</h3>';
        
        foreach ($custom_fields as $field) {
            echo '<div class="address">';
            echo '<p><strong>' . esc_html($field['label']) . ':</strong><br>' . esc_html($field['value']) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add custom column to orders list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_order_list_column($columns) {
        $columns['ccf_custom_fields'] = __('Custom Fields', 'custom-checkout-fields');
        return $columns;
    }
    
    /**
     * Populate custom column in orders list
     * 
     * @param string $column Column name
     * @param int $order_id Order ID
     */
    public function populate_order_list_column($column, $order_id) {
        if ($column === 'ccf_custom_fields') {
            if ($this->order_meta->order_has_custom_fields($order_id)) {
                echo '<span class="dashicons dashicons-yes" title="' . __('Has custom fields', 'custom-checkout-fields') . '"></span>';
            } else {
                echo '<span class="dashicons dashicons-minus" title="' . __('No custom fields', 'custom-checkout-fields') . '"></span>';
            }
        }
    }
    
    /**
     * Add settings link to plugin actions
     * 
     * @param array $actions Plugin actions
     * @return array Modified actions
     */
    public function add_settings_link($actions) {
        $settings_link = '<a href="' . admin_url('admin.php?page=ccf-settings') . '">' . __('Settings', 'custom-checkout-fields') . '</a>';
        array_unshift($actions, $settings_link);
        return $actions;
    }
    
    /**
     * Add admin notices
     */
    public function add_admin_notices() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>';
            echo __('Custom Checkout Fields requires WooCommerce to be installed and active.', 'custom-checkout-fields');
            echo '</p></div>';
        }
    }
    
    /**
     * Register admin styles
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style(
            'ccf-admin-styles',
            CCF_URL . 'admin/css/admin.css',
            [],
            '1.0.0'
        );
    }
    
    /**
     * Add help tab to admin pages
     */
    public function add_help_tab() {
        $screen = get_current_screen();
        
        if ($screen && $screen->id === 'toplevel_page_ccf-settings') {
            $screen->add_help_tab([
                'id' => 'ccf-help',
                'title' => __('Custom Checkout Fields Help', 'custom-checkout-fields'),
                'content' => $this->get_help_content()
            ]);
        }
    }
    
    /**
     * Get help content
     * 
     * @return string Help content HTML
     */
    private function get_help_content() {
        return '
        <h3>' . __('Getting Started', 'custom-checkout-fields') . '</h3>
        <p>' . __('Use this plugin to add custom fields to your WooCommerce checkout page.', 'custom-checkout-fields') . '</p>
        
        <h3>' . __('Field Types', 'custom-checkout-fields') . '</h3>
        <ul>
            <li><strong>Text:</strong> Single line text input</li>
            <li><strong>Textarea:</strong> Multi-line text input</li>
            <li><strong>Email:</strong> Email address input with validation</li>
            <li><strong>Phone:</strong> Phone number input</li>
            <li><strong>Select:</strong> Dropdown selection</li>
        </ul>
        
        <h3>' . __('Field Positions', 'custom-checkout-fields') . '</h3>
        <ul>
            <li><strong>After Billing:</strong> After billing address fields</li>
            <li><strong>After Shipping:</strong> After shipping address fields</li>
            <li><strong>Before Payment:</strong> Before payment method selection</li>
        </ul>
        ';
    }
}
