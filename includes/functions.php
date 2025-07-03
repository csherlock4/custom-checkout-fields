<?php
// Inject custom fields for WooCommerce block-based checkout via JavaScript
add_action('wp_footer', function() {
    if (!is_checkout()) return;

    // Detect block-based checkout by looking for a block checkout form
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
      }
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(addCCFBlockFields, 500);
        setTimeout(addCCFBlockFields, 1500);
        setTimeout(addCCFBlockFields, 3000);
      });
    })();
    </script>
    <?php
    // Output enabled fields as JS variable for block checkout
    $fields = get_option('ccf_fields', []);
    $enabled_fields = array_filter($fields, function($field) {
        return !empty($field['enabled']) && !empty($field['id']);
    });
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
    echo '<script>window.ccfBlockFields = ' . json_encode(array_values($enabled_fields)) . ';</script>';
});

// Debug WooCommerce availability and page detection
add_action('wp_footer', function() {
    if (!is_checkout()) return;
    
    error_log('[CCF] wp_footer hook fired on checkout page');
    echo "<script>\n    console.log('[CCF] wp_footer hook fired on checkout page');\n    if (typeof wc_checkout_params !== 'undefined') {\n        console.log('[CCF] WooCommerce checkout parameters found');\n    } else {\n        console.log('[CCF] WooCommerce checkout parameters NOT found');\n    }\n    </script>";
});

// Try to detect if we're on a checkout page at all
add_action('wp', function() {
    if (is_checkout()) {
        error_log('[CCF] WordPress detected checkout page via wp hook');
    }
});

// Only inject fields via JS for block-based checkout if needed (not classic)
// (You can add a block checkout JS injection here if you want, but classic should be PHP only)

// Use multiple hooks to ensure field appears somewhere (for classic checkout)
add_action('woocommerce_after_checkout_billing_form', 'ccf_add_custom_field', 10, 1);
add_action('woocommerce_after_order_notes', 'ccf_add_custom_field', 10, 1);
add_action('woocommerce_checkout_after_customer_details', 'ccf_add_custom_field', 10, 1);
add_action('woocommerce_review_order_before_submit', 'ccf_add_custom_field', 10, 1);

// Centralized function to add the field (for classic checkout)
function ccf_add_custom_field($checkout) {
    // Get all enabled fields
    $fields = get_option('ccf_fields', []);
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

    if (empty($enabled_fields)) return;

    foreach ($enabled_fields as $field) {
        $args = [
            'type'        => $field['type'] ?? 'text',
            'class'       => ['form-row-wide', 'ccf-custom-field'],
            'label'       => esc_html($field['label']),
            'required'    => !empty($field['required']),
            'priority'    => 100,
            'placeholder' => isset($field['placeholder']) ? esc_attr($field['placeholder']) : '',
        ];
        echo '<div class="ccf-field-wrapper">';
        woocommerce_form_field($field['id'], $args, $checkout->get_value($field['id']));
        echo '</div>';
    }
}

// Add CSS to ensure field is visible
add_action('wp_head', function() {
    if (!is_checkout()) return;
    
    echo '<style>
    .ccf-custom-field {
        display: block !important;
        clear: both !important;
        width: 100% !important;
        margin: 10px 0 !important;
    }
    .ccf-custom-field input {
        width: 100% !important;
        padding: 10px !important;
        border: 1px solid #ccc !important;
    }
    </style>';
});

// Save field data when order is placed (works for both classic and block checkout)
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    // Get all configured fields
    $fields = get_option('ccf_fields', []);
    
    // Also check for legacy single field
    if (!empty($_POST['ccf_field'])) {
        $field_value = sanitize_text_field($_POST['ccf_field']);
        error_log('[CCF] Saving legacy field value for order ' . $order_id . ': ' . $field_value);
        update_post_meta($order_id, '_ccf_field', $field_value);
    }
    
    // Save all custom fields
    foreach ($fields as $field) {
        if (empty($field['enabled']) || empty($field['id'])) continue;
        
        $field_key = $field['id'];
        if (!empty($_POST[$field_key])) {
            $field_value = sanitize_text_field($_POST[$field_key]);
            error_log('[CCF] Saving field "' . $field['label'] . '" for order ' . $order_id . ': ' . $field_value);
            update_post_meta($order_id, '_' . $field_key, $field_value);
            
            // Also save field configuration for reference
            update_post_meta($order_id, '_' . $field_key . '_config', $field);
        }
    }
});

// Additional save hook for block-based checkout
add_action('woocommerce_store_api_checkout_update_order_from_request', function($order, $request) {
    $data = $request->get_json_params();
    if (!empty($data['ccf_field'])) {
        $field_value = sanitize_text_field($data['ccf_field']);
        error_log('[CCF] Saving field value via Store API for order ' . $order->get_id() . ': ' . $field_value);
        $order->update_meta_data('_ccf_field', $field_value);
    }
}, 10, 2);

// Validate field (optional - remove if not needed)
add_action('woocommerce_checkout_process', function() {
    // Add validation here if the field should be required
    // For now, we'll just log that validation ran
    error_log('[CCF] Checkout validation process ran');
});

// Block checkout validation
add_action('woocommerce_store_api_checkout_update_order_from_request', function($order, $request) {
    error_log('[CCF] Block checkout validation/processing ran');
}, 5, 2);

// Show field values in WooCommerce admin order details
add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    $order_id = $order->get_id();
    $fields = get_option('ccf_fields', []);
    $has_custom_fields = false;
    
    // Check for legacy single field first
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
    if ($legacy_value) {
        $legacy_label = get_option('ccf_label', 'Extra Information');
        echo '<div class="address">';
        echo '<p><strong>' . esc_html($legacy_label) . ':</strong><br>' . esc_html($legacy_value) . '</p>';
        echo '</div>';
        $has_custom_fields = true;
    }
    
    // Show all multi-field values
    foreach ($fields as $field) {
        if (empty($field['id'])) continue;
        
        $field_value = get_post_meta($order_id, '_' . $field['id'], true);
        if ($field_value) {
            if (!$has_custom_fields) {
                echo '<h3>Custom Fields</h3>';
            }
            echo '<div class="address">';
            echo '<p><strong>' . esc_html($field['label']) . ':</strong><br>' . esc_html($field_value) . '</p>';
            echo '</div>';
            $has_custom_fields = true;
        }
    }
});

// Show field values in customer order emails and invoices
add_action('woocommerce_email_order_meta', function($order, $sent_to_admin, $plain_text) {
    $order_id = $order->get_id();
    $fields = get_option('ccf_fields', []);
    $has_custom_fields = false;
    
    // Check for legacy single field first
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
    if ($legacy_value) {
        $legacy_label = get_option('ccf_label', 'Extra Information');
        if ($plain_text) {
            echo "\n" . $legacy_label . ": " . $legacy_value . "\n";
        } else {
            echo '<h3>Custom Information</h3>';
            echo '<p><strong>' . esc_html($legacy_label) . ':</strong> ' . esc_html($legacy_value) . '</p>';
        }
        $has_custom_fields = true;
    }
    
    // Show all multi-field values
    foreach ($fields as $field) {
        if (empty($field['id'])) continue;
        
        $field_value = get_post_meta($order_id, '_' . $field['id'], true);
        if ($field_value) {
            if (!$has_custom_fields && !$plain_text) {
                echo '<h3>Custom Information</h3>';
            }
            
            if ($plain_text) {
                echo "\n" . $field['label'] . ": " . $field_value . "\n";
            } else {
                echo '<p><strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field_value) . '</p>';
            }
            $has_custom_fields = true;
        }
    }
    
    // Add some spacing after custom fields
    if ($has_custom_fields) {
        if ($plain_text) {
            echo "\n";
        } else {
            echo '<br>';
        }
    }
}, 10, 3);

// Show custom fields in WooCommerce PDF invoices and order documents
add_action('woocommerce_order_item_meta_end', function($item_id, $item, $order, $plain_text) {
    // Only show once per order (on the first item)
    static $shown_for_order = [];
    $order_id = $order->get_id();
    
    if (isset($shown_for_order[$order_id])) {
        return; // Already shown for this order
    }
    $shown_for_order[$order_id] = true;
    
    $fields = get_option('ccf_fields', []);
    $has_custom_fields = false;
    
    // Check for legacy single field first
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
    if ($legacy_value) {
        $legacy_label = get_option('ccf_label', 'Extra Information');
        if ($plain_text) {
            echo "\n" . $legacy_label . ": " . $legacy_value;
        } else {
            echo '<br><strong>' . esc_html($legacy_label) . ':</strong> ' . esc_html($legacy_value);
        }
        $has_custom_fields = true;
    }
    
    // Show all multi-field values
    foreach ($fields as $field) {
        if (empty($field['id'])) continue;
        
        $field_value = get_post_meta($order_id, '_' . $field['id'], true);
        if ($field_value) {
            if ($plain_text) {
                echo "\n" . $field['label'] . ": " . $field_value;
            } else {
                echo '<br><strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field_value);
            }
            $has_custom_fields = true;
        }
    }
}, 10, 4);

// Also add custom fields to order details in emails (alternative placement)
add_action('woocommerce_email_order_details', function($order, $sent_to_admin, $plain_text, $email) {
    $order_id = $order->get_id();
    $fields = get_option('ccf_fields', []);
    $has_custom_fields = false;
    
    // Collect all custom field values
    $custom_field_data = [];
    
    // Check for legacy single field
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
    if ($legacy_value) {
        $legacy_label = get_option('ccf_label', 'Extra Information');
        $custom_field_data[] = [
            'label' => $legacy_label,
            'value' => $legacy_value
        ];
    }
    
    // Get all multi-field values
    foreach ($fields as $field) {
        if (empty($field['id'])) continue;
        
        $field_value = get_post_meta($order_id, '_' . $field['id'], true);
        if ($field_value) {
            $custom_field_data[] = [
                'label' => $field['label'],
                'value' => $field_value
            ];
        }
    }
    
    // Display custom fields if any exist
    if (!empty($custom_field_data)) {
        if ($plain_text) {
            echo "\n" . str_repeat('=', 50) . "\n";
            echo "CUSTOM INFORMATION\n";
            echo str_repeat('=', 50) . "\n";
            foreach ($custom_field_data as $field_data) {
                echo $field_data['label'] . ": " . $field_data['value'] . "\n";
            }
            echo "\n";
        } else {
            echo '<h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Custom Information</h2>';
            echo '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">';
            foreach ($custom_field_data as $field_data) {
                echo '<tr>';
                echo '<td style="text-align: left; border: 1px solid #eee; padding: 8px; background: #f7f7f7;"><strong>' . esc_html($field_data['label']) . '</strong></td>';
                echo '<td style="text-align: left; border: 1px solid #eee; padding: 8px;">' . esc_html($field_data['value']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}, 5, 4);

// Show custom fields on order confirmation page and customer account order view
add_action('woocommerce_order_details_after_order_table', function($order) {
    $order_id = $order->get_id();
    $fields = get_option('ccf_fields', []);
    $custom_field_data = [];
    
    // Check for legacy single field
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
    if ($legacy_value) {
        $legacy_label = get_option('ccf_label', 'Extra Information');
        $custom_field_data[] = [
            'label' => $legacy_label,
            'value' => $legacy_value
        ];
    }
    
    // Get all multi-field values
    foreach ($fields as $field) {
        if (empty($field['id'])) continue;
        
        $field_value = get_post_meta($order_id, '_' . $field['id'], true);
        if ($field_value) {
            $custom_field_data[] = [
                'label' => $field['label'],
                'value' => $field_value
            ];
        }
    }
    
    // Display custom fields if any exist
    if (!empty($custom_field_data)) {
        echo '<h2 class="woocommerce-order-details__title">Custom Information</h2>';
        echo '<table class="woocommerce-table woocommerce-table--custom-fields shop_table custom-fields" style="margin-bottom: 20px;">';
        echo '<tbody>';
        foreach ($custom_field_data as $field_data) {
            echo '<tr>';
            echo '<th style="text-align: left; padding: 8px; background: #f7f7f7; border: 1px solid #ddd;">' . esc_html($field_data['label']) . '</th>';
            echo '<td style="text-align: left; padding: 8px; border: 1px solid #ddd;">' . esc_html($field_data['value']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
}, 10, 1);

// Add React-powered meta box to WooCommerce order edit screen
add_action('add_meta_boxes', 'ccf_add_order_meta_box');

function ccf_add_order_meta_box() {
    // Only add meta box on WooCommerce order edit screens
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'])) {
        return;
    }
    
    add_meta_box(
        'ccf-order-fields',
        'Custom Checkout Fields',
        'ccf_render_order_meta_box',
        ['shop_order', 'woocommerce_page_wc-orders'],
        'normal',
        'default'
    );
}

function ccf_render_order_meta_box($post_or_order) {
    // Get order ID - handle both post object and WC_Order object
    $order_id = null;
    if (is_object($post_or_order)) {
        if (method_exists($post_or_order, 'get_id')) {
            // WC_Order object
            $order_id = $post_or_order->get_id();
        } elseif (isset($post_or_order->ID)) {
            // Post object
            $order_id = $post_or_order->ID;
        }
    } else {
        // Fallback - try to get from $_GET or $_POST
        $order_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['order_id']) ? intval($_POST['order_id']) : null);
    }
    
    if (!$order_id) {
        echo '<div class="p-4 text-red-600">Error: Could not determine order ID</div>';
        return;
    }
    
    // Create container for React component
    echo '<div id="ccf-order-meta-root" data-order-id="' . esc_attr($order_id) . '">Loading custom fields...</div>';
}

// Enqueue React script on order edit pages
add_action('admin_enqueue_scripts', 'ccf_enqueue_order_meta_scripts');

function ccf_enqueue_order_meta_scripts($hook_suffix) {
    // Only load on WooCommerce order edit pages
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
    
    // Add debugging info
    wp_add_inline_script('ccf-order-meta-js', '
        console.log("[CCF Order Meta] Script loaded on page: " + "' . $hook_suffix . '");
        console.log("[CCF Order Meta] Screen ID: " + "' . $screen->id . '");
        console.log("[CCF Order Meta] Looking for element: #ccf-order-meta-root");
    ', 'before');
}

// Register custom REST API endpoints for order meta
add_action('rest_api_init', 'ccf_register_order_meta_endpoints');

function ccf_register_order_meta_endpoints() {
    // GET endpoint to retrieve order meta
    register_rest_route('ccf/v1', '/order-meta/(?P<order_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'ccf_get_order_meta',
        'permission_callback' => 'ccf_check_order_meta_permissions',
        'args' => [
            'order_id' => [
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);
    
    // POST endpoint to update order meta
    register_rest_route('ccf/v1', '/order-meta/(?P<order_id>\d+)', [
        'methods' => 'POST',
        'callback' => 'ccf_update_order_meta',
        'permission_callback' => 'ccf_check_order_meta_permissions',
        'args' => [
            'order_id' => [
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ],
            'fields' => [
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_array($param);
                }
            ]
        ]
    ]);
}

function ccf_check_order_meta_permissions($request) {
    // Check if user can edit shop orders
    return current_user_can('edit_shop_orders') || current_user_can('manage_woocommerce');
}

function ccf_get_order_meta($request) {
    $order_id = $request->get_param('order_id');
    
    error_log('[CCF API] Getting order meta for order: ' . $order_id);
    
    // Get order to make sure it exists
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('order_not_found', 'Order not found', ['status' => 404]);
    }
    
    $custom_fields = [];
    
    // Get all configured fields for reference
    $configured_fields = get_option('ccf_fields', []);
    
    // Look for legacy field first
    $legacy_value = get_post_meta($order_id, '_ccf_field', true);
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
        if (empty($field['id'])) continue;
        
        $field_key = '_' . $field['id'];
        $field_value = get_post_meta($order_id, $field_key, true);
        
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
    
    error_log('[CCF API] Found ' . count($custom_fields) . ' custom fields for order ' . $order_id);
    
    return [
        'order_id' => $order_id,
        'custom_fields' => $custom_fields
    ];
}

function ccf_update_order_meta($request) {
    $order_id = $request->get_param('order_id');
    $fields = $request->get_param('fields');
    
    error_log('[CCF API] Updating order meta for order: ' . $order_id);
    error_log('[CCF API] Fields to update: ' . print_r($fields, true));
    
    // Get order to make sure it exists
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('order_not_found', 'Order not found', ['status' => 404]);
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
        
        error_log('[CCF API] Updated field ' . $field_key . ' = ' . $field_value);
    }
    
    // Add order note about the update
    if (!empty($updated_fields)) {
        $note = 'Custom fields updated: ' . implode(', ', array_map(function($f) {
            return str_replace(['_ccf_', '_'], ['', ' '], $f['key']);
        }, $updated_fields));
        
        $order->add_order_note($note);
    }
    
    return [
        'success' => true,
        'order_id' => $order_id,
        'updated_fields' => $updated_fields
    ];
}