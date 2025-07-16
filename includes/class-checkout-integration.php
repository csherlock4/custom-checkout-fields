<?php

/**
 * Checkout Integration Class
 * 
 * Handles integration with WooCommerce checkout forms,
 * including both classic and block-based checkout.
 */
class CCF_Checkout_Integration {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Checkout_Integration
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
     * @return CCF_Checkout_Integration
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
        // Block-based checkout integration
        add_action('wp_footer', [$this, 'add_block_checkout_fields']);
        
        // Classic checkout integration
        add_action('woocommerce_after_checkout_billing_form', [$this, 'add_classic_checkout_fields'], 10, 1);
        add_action('woocommerce_after_order_notes', [$this, 'add_classic_checkout_fields'], 10, 1);
        add_action('woocommerce_checkout_after_customer_details', [$this, 'add_classic_checkout_fields'], 10, 1);
        add_action('woocommerce_review_order_before_submit', [$this, 'add_classic_checkout_fields'], 10, 1);
        
        // Add checkout validation
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout']);
        
        // Add CSS for field styling
        add_action('wp_head', [$this, 'add_checkout_styles']);
    }
    
    /**
     * Add custom fields to block-based checkout via JavaScript
     */
    public function add_block_checkout_fields() {
        if (!is_checkout()) {
            return;
        }
        
        $enabled_fields = $this->field_manager->get_enabled_fields();
        
        if (empty($enabled_fields)) {
            return;
        }
        
        $this->render_block_checkout_script();
        $this->output_fields_js_data($enabled_fields);
    }
    
    /**
     * Render JavaScript for block checkout integration
     */
    private function render_block_checkout_script() {
        ?>
        <script type="text/javascript">
        (function() {
          function isBlockCheckout() {
            return document.querySelector('.wc-block-checkout__form');
          }
          
          function addCCFBlockFields() {
            if (!isBlockCheckout()) return;
            if (document.querySelector('.ccf-custom-fields-container')) return;

            // These fields are output by PHP for JS to use
            var ccfFields = window.ccfBlockFields || [];
            if (!ccfFields.length) return;

            var fieldsHtml = '<div class="ccf-custom-fields-container" style="margin: 20px 0;">';
            ccfFields.forEach(function(field) {
              fieldsHtml += '<div class="ccf-field-wrapper" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
              fieldsHtml += '<label for="' + field.id + '" class="wc-block-components-text-input__label" style="display: block; margin-bottom: 8px; font-weight: 600;">' + field.label;
              if (field.required) fieldsHtml += ' <span style="color: red;">*</span>';
              fieldsHtml += '</label>';
              if (field.type === 'textarea') {
                fieldsHtml += '<textarea id="' + field.id + '" name="' + field.id + '" placeholder="' + (field.placeholder || '') + '" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px;"' + (field.required ? ' required' : '') + '></textarea>';
              } else {
                fieldsHtml += '<input type="' + field.type + '" id="' + field.id + '" name="' + field.id + '" placeholder="' + (field.placeholder || '') + '" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px;"' + (field.required ? ' required' : '') + ' />';
              }
              fieldsHtml += '</div>';
            });
            fieldsHtml += '</div>';

            // Insert after billing fields if possible
            var blockBillingFields = document.querySelector('.wc-block-checkout__billing-fields');
            if (blockBillingFields) {
              blockBillingFields.insertAdjacentHTML('afterend', fieldsHtml);
            } else {
              // Fallback: insert at end of form
              var blockForm = document.querySelector('.wc-block-checkout__form');
              if (blockForm) blockForm.insertAdjacentHTML('beforeend', fieldsHtml);
            }
            
            // Hook into the WooCommerce Block checkout process
            setupBlockCheckoutInterception();
          }
          
          function setupBlockCheckoutInterception() {
            // Override the fetch function to intercept checkout requests
            var originalFetch = window.fetch;
            window.fetch = function(url, options) {
              // Check if this is a checkout request
              if (url && url.includes('/wp-json/wc/store/v1/checkout')) {
                // Add custom field data to the request
                if (options && options.body) {
                  try {
                    var data = JSON.parse(options.body);
                    
                    // Add custom field values to the request data
                    var ccfFields = window.ccfBlockFields || [];
                    ccfFields.forEach(function(field) {
                      var fieldElement = document.getElementById(field.id);
                      if (fieldElement && fieldElement.value) {
                        data[field.id] = fieldElement.value;
                      }
                    });
                    
                    // Update the request body with custom field data
                    options.body = JSON.stringify(data);
                  } catch (e) {
                    // Silent error handling
                  }
                }
              }
              
              return originalFetch.apply(this, arguments);
            };
          }
          
          document.addEventListener('DOMContentLoaded', function() {
            setTimeout(addCCFBlockFields, 500);
            setTimeout(addCCFBlockFields, 1500);
            setTimeout(addCCFBlockFields, 3000);
          });
        })();
        </script>
        <?php
    }
    
    /**
     * Output fields data as JavaScript variable
     * 
     * @param array $fields Array of field configurations
     */
    private function output_fields_js_data($fields) {
        echo '<script>window.ccfBlockFields = ' . json_encode(array_values($fields)) . ';</script>';
    }
    
    /**
     * Add custom fields to classic checkout
     * 
     * @param WC_Checkout $checkout WooCommerce checkout object
     */
    public function add_classic_checkout_fields($checkout) {
        $enabled_fields = $this->field_manager->get_enabled_fields();
        
        if (empty($enabled_fields)) {
            return;
        }
        
        foreach ($enabled_fields as $field) {
            $this->render_classic_field($field, $checkout);
        }
    }
    
    /**
     * Render a single field for classic checkout
     * 
     * @param array $field Field configuration
     * @param WC_Checkout $checkout WooCommerce checkout object
     */
    private function render_classic_field($field, $checkout) {
        $args = [
            'type'        => $field['type'] ?? 'text',
            'class'       => ['form-row-wide', 'ccf-custom-field'],
            'label'       => esc_html($field['label']),
            'required'    => !empty($field['required']),
            'priority'    => 100,
            'placeholder' => isset($field['placeholder']) ? esc_attr($field['placeholder']) : '',
        ];
        
        // Add field-specific attributes
        if ($field['type'] === 'select' && !empty($field['options'])) {
            $args['options'] = $field['options'];
        }
        
        echo '<div class="ccf-field-wrapper">';
        woocommerce_form_field($field['id'], $args, $checkout->get_value($field['id']));
        echo '</div>';
    }
    
    /**
     * Add CSS styles for custom fields
     */
    public function add_checkout_styles() {
        if (!is_checkout()) {
            return;
        }
        
        echo '<style>
        .ccf-custom-field {
            display: block !important;
            clear: both !important;
            width: 100% !important;
            margin: 10px 0 !important;
        }
        .ccf-custom-field input,
        .ccf-custom-field textarea,
        .ccf-custom-field select {
            width: 100% !important;
            padding: 10px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
        }
        .ccf-custom-field textarea {
            min-height: 80px !important;
            resize: vertical !important;
        }
        .ccf-field-wrapper {
            margin-bottom: 15px !important;
        }
        </style>';
    }
    
    /**
     * Validate checkout fields
     */
    public function validate_checkout() {
        $this->validate_checkout_fields($_POST);
    }
    
    /**
     * Validate checkout fields
     * 
     * @param array $data Posted checkout data
     * @return bool True if valid, false otherwise
     */
    public function validate_checkout_fields($data) {
        $enabled_fields = $this->field_manager->get_enabled_fields();
        $errors = [];
        
        foreach ($enabled_fields as $field) {
            if (!empty($field['required']) && empty($data[$field['id']])) {
                $errors[] = sprintf(__('%s is a required field.', 'custom-checkout-fields'), $field['label']);
            }
            
            // Field-specific validation
            if (!empty($data[$field['id']])) {
                switch ($field['type']) {
                    case 'email':
                        if (!is_email($data[$field['id']])) {
                            $errors[] = sprintf(__('%s must be a valid email address.', 'custom-checkout-fields'), $field['label']);
                        }
                        break;
                    case 'url':
                        if (!filter_var($data[$field['id']], FILTER_VALIDATE_URL)) {
                            $errors[] = sprintf(__('%s must be a valid URL.', 'custom-checkout-fields'), $field['label']);
                        }
                        break;
                    case 'number':
                        if (!is_numeric($data[$field['id']])) {
                            $errors[] = sprintf(__('%s must be a valid number.', 'custom-checkout-fields'), $field['label']);
                        }
                        break;
                }
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                wc_add_notice($error, 'error');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Get field value from checkout data
     * 
     * @param string $field_id Field ID
     * @param array $data Checkout data
     * @return string Field value
     */
    public function get_field_value($field_id, $data) {
        $value = '';
        
        if (isset($data[$field_id])) {
            $value = $data[$field_id];
        } elseif (isset($_POST[$field_id])) {
            $value = $_POST[$field_id];
        }
        
        return sanitize_text_field($value);
    }
}
