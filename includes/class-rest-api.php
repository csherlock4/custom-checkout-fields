<?php

/**
 * REST API Class
 * 
 * Handles REST API endpoints for custom checkout fields.
 */
class CCF_REST_API {
    
    /**
     * Instance of this class
     * 
     * @var CCF_REST_API
     */
    private static $instance = null;
    
    /**
     * Field Manager instance
     * 
     * @var CCF_Field_Manager
     */
    private $field_manager;
    
    /**
     * Order Meta instance
     * 
     * @var CCF_Order_Meta
     */
    private $order_meta;
    
    /**
     * API namespace
     * 
     * @var string
     */
    private $namespace = 'ccf/v1';
    
    /**
     * Get instance of this class
     * 
     * @return CCF_REST_API
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
        $this->order_meta = CCF_Order_Meta::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Field management endpoints
        $this->register_field_routes();
        
        // Order meta endpoints
        $this->register_order_meta_routes();
        
        // Test endpoint
        $this->register_test_routes();
    }
    
    /**
     * Register field management routes
     */
    private function register_field_routes() {
        // Get all fields
        register_rest_route($this->namespace, '/fields', [
            'methods' => 'GET',
            'callback' => [$this, 'get_fields'],
            'permission_callback' => [$this, 'check_admin_permissions'],
        ]);
        
        // Create/update fields
        register_rest_route($this->namespace, '/fields', [
            'methods' => 'POST',
            'callback' => [$this, 'save_fields'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'fields' => [
                    'required' => true,
                    'validate_callback' => [$this, 'validate_fields_param'],
                    'sanitize_callback' => [$this, 'sanitize_fields_param'],
                ]
            ]
        ]);
        
        // Get single field
        register_rest_route($this->namespace, '/fields/(?P<field_id>[a-zA-Z0-9_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_field'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'field_id' => [
                    'validate_callback' => [$this, 'validate_field_id'],
                ]
            ]
        ]);
        
        // Delete field
        register_rest_route($this->namespace, '/fields/(?P<field_id>[a-zA-Z0-9_]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_field'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'field_id' => [
                    'validate_callback' => [$this, 'validate_field_id'],
                ]
            ]
        ]);
    }
    
    /**
     * Register order meta routes
     */
    private function register_order_meta_routes() {
        // Get order meta
        register_rest_route($this->namespace, '/order-meta/(?P<order_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_meta'],
            'permission_callback' => [$this, 'check_order_permissions'],
            'args' => [
                'order_id' => [
                    'validate_callback' => [$this, 'validate_order_id'],
                ]
            ]
        ]);
        
        // Update order meta
        register_rest_route($this->namespace, '/order-meta/(?P<order_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_order_meta'],
            'permission_callback' => [$this, 'check_order_permissions'],
            'args' => [
                'order_id' => [
                    'validate_callback' => [$this, 'validate_order_id'],
                ],
                'fields' => [
                    'required' => true,
                    'validate_callback' => [$this, 'validate_order_fields_param'],
                ]
            ]
        ]);
    }
    
    /**
     * Register test routes
     */
    private function register_test_routes() {
        // Test save endpoint
        register_rest_route($this->namespace, '/test-save', [
            'methods' => 'POST',
            'callback' => [$this, 'test_save'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'fields' => [
                    'required' => false,
                    'default' => [],
                    'validate_callback' => [$this, 'validate_test_fields'],
                    'sanitize_callback' => [$this, 'sanitize_test_fields'],
                ]
            ]
        ]);
    }
    
    /**
     * Get all fields
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_fields($request) {
        try {
            $fields = $this->field_manager->get_fields();
            
            return rest_ensure_response([
                'success' => true,
                'fields' => $fields,
                'count' => count($fields)
            ]);
        } catch (Exception $e) {
            return new WP_Error('get_fields_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Save fields
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function save_fields($request) {
        try {
            $fields = $request->get_param('fields');
            $result = $this->field_manager->save_fields($fields);
            
            if ($result) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Fields saved successfully',
                    'fields' => $this->field_manager->get_fields()
                ]);
            } else {
                return new WP_Error('save_failed', 'Failed to save fields', ['status' => 500]);
            }
        } catch (Exception $e) {
            return new WP_Error('save_fields_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get single field
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_field($request) {
        try {
            $field_id = $request->get_param('field_id');
            $field = $this->field_manager->get_field_by_id($field_id);
            
            if ($field) {
                return rest_ensure_response([
                    'success' => true,
                    'field' => $field
                ]);
            } else {
                return new WP_Error('field_not_found', 'Field not found', ['status' => 404]);
            }
        } catch (Exception $e) {
            return new WP_Error('get_field_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Delete field
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function delete_field($request) {
        try {
            $field_id = $request->get_param('field_id');
            $result = $this->field_manager->delete_field($field_id);
            
            if ($result) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Field deleted successfully'
                ]);
            } else {
                return new WP_Error('delete_failed', 'Field not found or could not be deleted', ['status' => 404]);
            }
        } catch (Exception $e) {
            return new WP_Error('delete_field_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get order meta
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_order_meta($request) {
        try {
            $order_id = $request->get_param('order_id');
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_Error('order_not_found', 'Order not found', ['status' => 404]);
            }
            
            $custom_fields = $this->order_meta->get_order_custom_fields($order_id);
            
            return rest_ensure_response([
                'success' => true,
                'order_id' => $order_id,
                'custom_fields' => $custom_fields
            ]);
        } catch (Exception $e) {
            return new WP_Error('get_order_meta_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Update order meta
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_order_meta($request) {
        try {
            $order_id = $request->get_param('order_id');
            $fields = $request->get_param('fields');
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_Error('order_not_found', 'Order not found', ['status' => 404]);
            }
            
            $result = $this->order_meta->update_order_meta($order_id, $fields);
            
            if ($result) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Order meta updated successfully',
                    'order_id' => $order_id
                ]);
            } else {
                return new WP_Error('update_failed', 'Failed to update order meta', ['status' => 500]);
            }
        } catch (Exception $e) {
            return new WP_Error('update_order_meta_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Test save endpoint
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function test_save($request) {
        try {
            $fields = $request->get_param('fields');
            
            // Ensure we have an array (even if empty)
            if (!is_array($fields)) {
                $fields = [];
            }
            
            $result = update_option('ccf_fields', $fields);
            $stored = get_option('ccf_fields');
            
            return rest_ensure_response([
                'success' => $result,
                'sent' => $fields,
                'stored' => $stored,
                'message' => 'Direct option update completed'
            ]);
        } catch (Exception $e) {
            return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Check admin permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool True if user has permission
     */
    public function check_admin_permissions($request) {
        return current_user_can('manage_options');
    }
    
    /**
     * Check order permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool True if user has permission
     */
    public function check_order_permissions($request) {
        return current_user_can('edit_shop_orders') || current_user_can('manage_woocommerce');
    }
    
    /**
     * Validate fields parameter
     * 
     * @param mixed $param Parameter value
     * @return bool True if valid
     */
    public function validate_fields_param($param) {
        return is_array($param);
    }
    
    /**
     * Sanitize fields parameter
     * 
     * @param mixed $param Parameter value
     * @return array Sanitized fields
     */
    public function sanitize_fields_param($param) {
        if (!is_array($param)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($param as $field) {
            if (is_array($field)) {
                $sanitized[] = $this->field_manager->sanitize_field($field);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate field ID parameter
     * 
     * @param mixed $param Parameter value
     * @return bool True if valid
     */
    public function validate_field_id($param) {
        return is_string($param) && !empty($param);
    }
    
    /**
     * Validate order ID parameter
     * 
     * @param mixed $param Parameter value
     * @return bool True if valid
     */
    public function validate_order_id($param) {
        return is_numeric($param) && $param > 0;
    }
    
    /**
     * Validate order fields parameter
     * 
     * @param mixed $param Parameter value
     * @return bool True if valid
     */
    public function validate_order_fields_param($param) {
        return is_array($param);
    }
    
    /**
     * Validate test fields parameter
     * 
     * @param mixed $param Parameter value
     * @return bool True if valid
     */
    public function validate_test_fields($param) {
        return is_array($param) || is_null($param);
    }
    
    /**
     * Sanitize test fields parameter
     * 
     * @param mixed $param Parameter value
     * @return array Sanitized fields
     */
    public function sanitize_test_fields($param) {
        return is_array($param) ? $param : [];
    }
}
