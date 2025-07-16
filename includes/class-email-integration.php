<?php

/**
 * Email Integration Class
 * 
 * Handles integration with WooCommerce emails to display custom fields.
 */
class CCF_Email_Integration {
    
    /**
     * Instance of this class
     * 
     * @var CCF_Email_Integration
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
     * @return CCF_Email_Integration
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
        // Add custom fields to order emails
        add_action('woocommerce_email_order_meta', [$this, 'add_fields_to_email'], 10, 3);
        
        // Add custom fields to order details in emails (alternative placement)
        add_action('woocommerce_email_order_details', [$this, 'add_fields_to_email_details'], 5, 4);
        
        // Add custom fields to order confirmation and account pages
        add_action('woocommerce_order_details_after_order_table', [$this, 'add_fields_to_order_details']);
    }
    
    /**
     * Add custom fields to order meta section in emails
     * 
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Whether email is sent to admin
     * @param bool $plain_text Whether email is plain text
     */
    public function add_fields_to_email($order, $sent_to_admin, $plain_text) {
        $custom_fields = $this->order_meta->get_formatted_order_fields($order->get_id());
        
        if (empty($custom_fields)) {
            return;
        }
        
        if ($plain_text) {
            $this->render_plain_text_fields($custom_fields);
        } else {
            $this->render_html_fields($custom_fields);
        }
        
        // Add spacing after custom fields
        if ($plain_text) {
            echo "\n";
        } else {
            echo '<br>';
        }
    }
    
    /**
     * Add custom fields to order details section in emails
     * 
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Whether email is sent to admin
     * @param bool $plain_text Whether email is plain text
     * @param WC_Email $email Email object
     */
    public function add_fields_to_email_details($order, $sent_to_admin, $plain_text, $email) {
        $custom_fields = $this->order_meta->get_formatted_order_fields($order->get_id());
        
        if (empty($custom_fields)) {
            return;
        }
        
        if ($plain_text) {
            $this->render_plain_text_table($custom_fields);
        } else {
            $this->render_html_table($custom_fields);
        }
    }
    
    /**
     * Add custom fields to order confirmation page and customer account
     * 
     * @param WC_Order $order Order object
     */
    public function add_fields_to_order_details($order) {
        $custom_fields = $this->order_meta->get_formatted_order_fields($order->get_id());
        
        if (empty($custom_fields)) {
            return;
        }
        
        echo '<h2 class="woocommerce-order-details__title">' . __('Custom Information', 'custom-checkout-fields') . '</h2>';
        echo '<table class="woocommerce-table woocommerce-table--custom-fields shop_table custom-fields" style="margin-bottom: 20px;">';
        echo '<tbody>';
        
        foreach ($custom_fields as $field) {
            echo '<tr>';
            echo '<th style="text-align: left; padding: 8px; background: #f7f7f7; border: 1px solid #ddd;">' . esc_html($field['label']) . '</th>';
            echo '<td style="text-align: left; padding: 8px; border: 1px solid #ddd;">' . esc_html($field['value']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Render custom fields in plain text format
     * 
     * @param array $custom_fields Array of field data
     */
    private function render_plain_text_fields($custom_fields) {
        foreach ($custom_fields as $field) {
            echo "\n" . $field['label'] . ": " . $field['value'] . "\n";
        }
    }
    
    /**
     * Render custom fields in HTML format
     * 
     * @param array $custom_fields Array of field data
     */
    private function render_html_fields($custom_fields) {
        echo '<h3>' . __('Custom Information', 'custom-checkout-fields') . '</h3>';
        
        foreach ($custom_fields as $field) {
            echo '<p><strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field['value']) . '</p>';
        }
    }
    
    /**
     * Render custom fields in plain text table format
     * 
     * @param array $custom_fields Array of field data
     */
    private function render_plain_text_table($custom_fields) {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo strtoupper(__('Custom Information', 'custom-checkout-fields')) . "\n";
        echo str_repeat('=', 50) . "\n";
        
        foreach ($custom_fields as $field) {
            echo $field['label'] . ": " . $field['value'] . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Render custom fields in HTML table format
     * 
     * @param array $custom_fields Array of field data
     */
    private function render_html_table($custom_fields) {
        echo '<h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">' . __('Custom Information', 'custom-checkout-fields') . '</h2>';
        echo '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">';
        
        foreach ($custom_fields as $field) {
            echo '<tr>';
            echo '<td style="text-align: left; border: 1px solid #eee; padding: 8px; background: #f7f7f7;"><strong>' . esc_html($field['label']) . '</strong></td>';
            echo '<td style="text-align: left; border: 1px solid #eee; padding: 8px;">' . esc_html($field['value']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Get custom fields for email template
     * 
     * @param WC_Order $order Order object
     * @return array Array of custom fields for template use
     */
    public function get_email_template_fields($order) {
        return $this->order_meta->get_formatted_order_fields($order->get_id());
    }
    
    /**
     * Check if custom fields should be displayed in email
     * 
     * @param WC_Order $order Order object
     * @param WC_Email $email Email object
     * @return bool True if fields should be displayed
     */
    public function should_display_fields_in_email($order, $email) {
        // Don't display in certain email types if needed
        $excluded_emails = apply_filters('ccf_excluded_email_types', []);
        
        if (in_array($email->id, $excluded_emails)) {
            return false;
        }
        
        // Check if order has custom fields
        return $this->order_meta->order_has_custom_fields($order->get_id());
    }
    
    /**
     * Format field value for email display
     * 
     * @param string $value Field value
     * @param string $type Field type
     * @return string Formatted value
     */
    public function format_field_value_for_email($value, $type) {
        switch ($type) {
            case 'email':
                return '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            case 'url':
                return '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
            case 'tel':
                return '<a href="tel:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            default:
                return esc_html($value);
        }
    }
    
    /**
     * Add custom fields to email subject line (if needed)
     * 
     * @param string $subject Email subject
     * @param WC_Order $order Order object
     * @return string Modified subject
     */
    public function modify_email_subject($subject, $order) {
        // Example: Add custom field value to subject
        $custom_fields = $this->order_meta->get_formatted_order_fields($order->get_id());
        
        foreach ($custom_fields as $field) {
            if ($field['label'] === 'Priority' && !empty($field['value'])) {
                $subject = '[' . $field['value'] . '] ' . $subject;
                break;
            }
        }
        
        return $subject;
    }
    
    /**
     * Add custom fields to email headers
     * 
     * @param string $headers Email headers
     * @param string $email_id Email ID
     * @param WC_Order $order Order object
     * @return string Modified headers
     */
    public function modify_email_headers($headers, $email_id, $order) {
        // Example: Add custom field as email header
        $custom_fields = $this->order_meta->get_formatted_order_fields($order->get_id());
        
        foreach ($custom_fields as $field) {
            if ($field['type'] === 'email' && !empty($field['value'])) {
                $headers .= "Reply-To: " . $field['value'] . "\r\n";
                break;
            }
        }
        
        return $headers;
    }
}
