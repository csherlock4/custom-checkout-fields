<?php

/**
 * Block Checkout Integration
 * 
 * Handles specific integration with WooCommerce block-based checkout.
 * This file contains block-specific code that was previously mixed
 * with other checkout logic.
 */

// Block checkout specific functionality
if (!function_exists('ccf_is_block_checkout')) {
    /**
     * Check if we're dealing with block-based checkout
     * 
     * @return bool True if block checkout is active
     */
    function ccf_is_block_checkout() {
        // Check if we're on checkout page
        if (!is_checkout()) {
            return false;
        }
        
        // Check if block checkout is enabled
        $checkout_page_id = wc_get_page_id('checkout');
        if (!$checkout_page_id) {
            return false;
        }
        
        // Check if the checkout page has the checkout block
        if (has_block('woocommerce/checkout', $checkout_page_id)) {
            return true;
        }
        
        return false;
    }
}

/**
 * Block Checkout Field Renderer
 * 
 * Handles rendering of custom fields in block-based checkout
 */
class CCF_Block_Checkout {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Block_Checkout
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
     * @return CCF_Block_Checkout
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
        // Only initialize if we're dealing with block checkout
        if (ccf_is_block_checkout()) {
            add_action('wp_footer', [$this, 'render_block_fields']);
        }
    }
    
    /**
     * Render custom fields for block checkout
     */
    public function render_block_fields() {
        $enabled_fields = $this->field_manager->get_enabled_fields();
        
        if (empty($enabled_fields)) {
            return;
        }
        
        $this->output_block_script();
        $this->output_fields_data($enabled_fields);
    }
    
    /**
     * Output JavaScript for block checkout integration
     */
    private function output_block_script() {
        ?>
        <script type="text/javascript">
        (function() {
            'use strict';
            
            // Configuration
            const CCF_BLOCK_CONFIG = {
                containerClass: 'ccf-custom-fields-container',
                fieldWrapperClass: 'ccf-field-wrapper',
                retryAttempts: 3,
                retryDelays: [500, 1500, 3000]
            };
            
            /**
             * Check if current page is using block checkout
             */
            function isBlockCheckout() {
                return document.querySelector('.wc-block-checkout__form') !== null;
            }
            
            /**
             * Check if fields are already added
             */
            function fieldsAlreadyAdded() {
                return document.querySelector('.' + CCF_BLOCK_CONFIG.containerClass) !== null;
            }
            
            /**
             * Get fields data from global variable
             */
            function getFieldsData() {
                return window.ccfBlockFields || [];
            }
            
            /**
             * Create HTML for a single field
             */
            function createFieldHTML(field) {
                let html = '<div class="' + CCF_BLOCK_CONFIG.fieldWrapperClass + '" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
                
                // Label
                html += '<label for="' + field.id + '" class="wc-block-components-text-input__label" style="display: block; margin-bottom: 8px; font-weight: 600;">';
                html += field.label;
                if (field.required) {
                    html += ' <span style="color: red;">*</span>';
                }
                html += '</label>';
                
                // Input field
                const commonStyles = 'width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px;';
                const requiredAttr = field.required ? ' required' : '';
                const placeholder = field.placeholder || '';
                
                switch (field.type) {
                    case 'textarea':
                        html += '<textarea id="' + field.id + '" name="' + field.id + '" placeholder="' + placeholder + '" style="' + commonStyles + ' min-height: 80px;"' + requiredAttr + '></textarea>';
                        break;
                    case 'select':
                        html += '<select id="' + field.id + '" name="' + field.id + '" style="' + commonStyles + '"' + requiredAttr + '>';
                        html += '<option value="">Select...</option>';
                        if (field.options) {
                            field.options.forEach(function(option) {
                                html += '<option value="' + option + '">' + option + '</option>';
                            });
                        }
                        html += '</select>';
                        break;
                    default:
                        html += '<input type="' + field.type + '" id="' + field.id + '" name="' + field.id + '" placeholder="' + placeholder + '" style="' + commonStyles + '"' + requiredAttr + ' />';
                }
                
                html += '</div>';
                return html;
            }
            
            /**
             * Create HTML for all fields
             */
            function createFieldsHTML(fields) {
                let html = '<div class="' + CCF_BLOCK_CONFIG.containerClass + '" style="margin: 20px 0;">';
                
                fields.forEach(function(field) {
                    html += createFieldHTML(field);
                });
                
                html += '</div>';
                return html;
            }
            
            /**
             * Insert fields into the checkout form
             */
            function insertFields(fieldsHTML) {
                // Try to insert after billing fields first
                const billingFields = document.querySelector('.wc-block-checkout__billing-fields');
                if (billingFields) {
                    billingFields.insertAdjacentHTML('afterend', fieldsHTML);
                    return true;
                }
                
                // Fallback: insert at end of form
                const checkoutForm = document.querySelector('.wc-block-checkout__form');
                if (checkoutForm) {
                    checkoutForm.insertAdjacentHTML('beforeend', fieldsHTML);
                    return true;
                }
                
                return false;
            }
            
            /**
             * Setup checkout form interception
             */
            function setupCheckoutInterception() {
                if (window.ccfInterceptionSetup) {
                    return; // Already setup
                }
                
                const originalFetch = window.fetch;
                
                window.fetch = function(url, options) {
                    // Check if this is a checkout request
                    if (url && url.includes('/wp-json/wc/store/v1/checkout')) {
                        if (options && options.body) {
                            try {
                                const data = JSON.parse(options.body);
                                
                                // Add custom field values to the request data
                                const ccfFields = getFieldsData();
                                ccfFields.forEach(function(field) {
                                    const fieldElement = document.getElementById(field.id);
                                    if (fieldElement && fieldElement.value) {
                                        data[field.id] = fieldElement.value;
                                    }
                                });
                                
                                // Update the request body
                                options.body = JSON.stringify(data);
                            } catch (e) {
                                console.warn('CCF: Error processing checkout data:', e);
                            }
                        }
                    }
                    
                    return originalFetch.apply(this, arguments);
                };
                
                window.ccfInterceptionSetup = true;
            }
            
            /**
             * Add custom fields to block checkout
             */
            function addCustomFields() {
                // Check preconditions
                if (!isBlockCheckout()) {
                    return false;
                }
                
                if (fieldsAlreadyAdded()) {
                    return false;
                }
                
                const fields = getFieldsData();
                if (!fields.length) {
                    return false;
                }
                
                // Create and insert fields
                const fieldsHTML = createFieldsHTML(fields);
                const inserted = insertFields(fieldsHTML);
                
                if (inserted) {
                    setupCheckoutInterception();
                    return true;
                }
                
                return false;
            }
            
            /**
             * Initialize with retry logic
             */
            function initializeWithRetry() {
                let attempts = 0;
                
                function tryAddFields() {
                    if (addCustomFields()) {
                        return; // Success
                    }
                    
                    attempts++;
                    if (attempts < CCF_BLOCK_CONFIG.retryAttempts) {
                        setTimeout(tryAddFields, CCF_BLOCK_CONFIG.retryDelays[attempts - 1]);
                    }
                }
                
                tryAddFields();
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeWithRetry);
            } else {
                initializeWithRetry();
            }
            
        })();
        </script>
        <?php
    }
    
    /**
     * Output fields data as JavaScript variable
     * 
     * @param array $fields Array of field configurations
     */
    private function output_fields_data($fields) {
        $json_fields = json_encode(array_values($fields));
        
        if ($json_fields !== false) {
            echo '<script>window.ccfBlockFields = ' . $json_fields . ';</script>';
        }
    }
    
    /**
     * Validate field for block checkout
     * 
     * @param array $field Field configuration
     * @return bool True if valid for block checkout
     */
    public function validate_field_for_blocks($field) {
        // Check required properties
        if (empty($field['id']) || empty($field['label'])) {
            return false;
        }
        
        // Check if field type is supported in blocks
        $supported_types = ['text', 'textarea', 'email', 'tel', 'number', 'select'];
        if (!in_array($field['type'], $supported_types)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get block-compatible field configuration
     * 
     * @param array $field Original field configuration
     * @return array Block-compatible field configuration
     */
    public function get_block_field_config($field) {
        $block_field = [
            'id' => $field['id'],
            'label' => $field['label'],
            'type' => $field['type'],
            'required' => !empty($field['required']),
            'placeholder' => $field['placeholder'] ?? '',
        ];
        
        // Add field-specific properties
        if ($field['type'] === 'select' && !empty($field['options'])) {
            $block_field['options'] = $field['options'];
        }
        
        return $block_field;
    }
}
