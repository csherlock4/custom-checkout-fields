<?php

/**
 * Field Manager Class
 * 
 * Handles core field management logic including field validation,
 * sanitization, and field configuration management.
 */
class CCF_Field_Manager {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Field_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     * 
     * @return CCF_Field_Manager
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
        // Private constructor to prevent direct instantiation
    }
    
    /**
     * Get all configured fields
     * 
     * @return array Array of field configurations
     */
    public function get_fields() {
        $fields = get_option('ccf_fields', []);
        
        // Ensure we have an array
        if (!is_array($fields)) {
            $fields = [];
        }
        
        return $fields;
    }
    
    /**
     * Get all enabled fields
     * 
     * @return array Array of enabled field configurations
     */
    public function get_enabled_fields() {
        $fields = $this->get_fields();
        
        $enabled_fields = array_filter($fields, function($field) {
            return !empty($field['enabled']) && !empty($field['id']);
        });
        
        // Fallback to legacy single field for backward compatibility
        if (empty($enabled_fields)) {
            $label = get_option('ccf_label', 'Extra Information');
            if (!empty($label)) {
                $enabled_fields = [[
                    'id' => 'ccf_field',
                    'label' => $label,
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Enter ' . $label,
                    'position' => 'after_billing'
                ]];
            }
        }
        
        return $enabled_fields;
    }
    
    /**
     * Validate field configuration
     * 
     * @param array $field Field configuration
     * @return array|WP_Error Valid field configuration or error
     */
    public function validate_field($field) {
        $errors = [];
        
        // Required fields
        if (empty($field['id'])) {
            $errors[] = 'Field ID is required';
        }
        
        if (empty($field['label'])) {
            $errors[] = 'Field label is required';
        }
        
        // Validate field type
        $valid_types = ['text', 'textarea', 'select', 'email', 'tel', 'number', 'url'];
        if (!in_array($field['type'], $valid_types)) {
            $errors[] = 'Invalid field type';
        }
        
        // Validate position
        $valid_positions = ['after_billing', 'after_shipping', 'before_payment'];
        if (!empty($field['position']) && !in_array($field['position'], $valid_positions)) {
            $errors[] = 'Invalid field position';
        }
        
        if (!empty($errors)) {
            return new WP_Error('invalid_field', implode(', ', $errors));
        }
        
        return $field;
    }
    
    /**
     * Sanitize field configuration
     * 
     * @param array $field Field configuration
     * @return array Sanitized field configuration
     */
    public function sanitize_field($field) {
        $sanitized = [
            'id' => sanitize_key($field['id']),
            'label' => sanitize_text_field($field['label']),
            'type' => sanitize_text_field($field['type']),
            'required' => !empty($field['required']),
            'enabled' => !empty($field['enabled']),
            'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
            'position' => sanitize_text_field($field['position'] ?? 'after_billing')
        ];
        
        // Add field-specific sanitization
        if ($field['type'] === 'select' && !empty($field['options'])) {
            $sanitized['options'] = array_map('sanitize_text_field', $field['options']);
        }
        
        return $sanitized;
    }
    
    /**
     * Save field configuration
     * 
     * @param array $fields Array of field configurations
     * @return bool True on success, false on failure
     */
    public function save_fields($fields) {
        // Ensure we have an array
        if (!is_array($fields)) {
            $fields = [];
        }
        
        // Validate and sanitize each field
        $sanitized_fields = [];
        foreach ($fields as $field) {
            $validated = $this->validate_field($field);
            if (is_wp_error($validated)) {
                continue; // Skip invalid fields
            }
            
            $sanitized_fields[] = $this->sanitize_field($field);
        }
        
        // Clear any object cache to prevent stale data
        wp_cache_delete('ccf_fields', 'options');
        
        return update_option('ccf_fields', $sanitized_fields);
    }
    
    /**
     * Get field by ID
     * 
     * @param string $field_id Field ID
     * @return array|null Field configuration or null if not found
     */
    public function get_field_by_id($field_id) {
        $fields = $this->get_fields();
        
        foreach ($fields as $field) {
            if ($field['id'] === $field_id) {
                return $field;
            }
        }
        
        return null;
    }
    
    /**
     * Delete field by ID
     * 
     * @param string $field_id Field ID
     * @return bool True on success, false on failure
     */
    public function delete_field($field_id) {
        $fields = $this->get_fields();
        
        $filtered_fields = array_filter($fields, function($field) use ($field_id) {
            return $field['id'] !== $field_id;
        });
        
        if (count($filtered_fields) === count($fields)) {
            return false; // Field not found
        }
        
        return $this->save_fields($filtered_fields);
    }
    
    /**
     * Generate unique field ID
     * 
     * @param string $base_id Base ID to use
     * @return string Unique field ID
     */
    public function generate_unique_id($base_id = 'ccf_field') {
        $fields = $this->get_fields();
        $existing_ids = array_column($fields, 'id');
        
        $counter = 1;
        $new_id = $base_id;
        
        while (in_array($new_id, $existing_ids)) {
            $new_id = $base_id . '_' . $counter;
            $counter++;
        }
        
        return $new_id;
    }
    
    /**
     * Get field types with labels
     * 
     * @return array Array of field types
     */
    public function get_field_types() {
        return [
            'text' => 'Text Input',
            'textarea' => 'Textarea',
            'select' => 'Select Dropdown',
            'email' => 'Email',
            'tel' => 'Phone',
            'number' => 'Number',
            'url' => 'URL'
        ];
    }
    
    /**
     * Get field positions with labels
     * 
     * @return array Array of field positions
     */
    public function get_field_positions() {
        return [
            'after_billing' => 'After Billing Fields',
            'after_shipping' => 'After Shipping Fields',
            'before_payment' => 'Before Payment Methods'
        ];
    }
}
