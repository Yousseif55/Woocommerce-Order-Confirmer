<?php
/*
 * Plugin Name: Order Confirmer
 * Description: This plugin allows administrators to confirm WooCommerce orders and copy order details to the clipboard. It adds a settings page where a message template can be customized. The plugin also provides secure confirmation links and AJAX support for users to confirm order.
 * Author: Yousseif Ahmed
 * Version: 1.2.4
*/

// Add the Order Confirmer settings page to the admin menu
function order_confirmer_add_settings_page()
{
    add_menu_page(
        'Order Confirmer Settings',
        'Order Confirmer',
        'manage_options',
        'order-confirmer-settings',
        'order_confirmer_render_settings_page',
        'dashicons-clipboard'
    );
}

// Render the Order Confirmer settings page
function order_confirmer_render_settings_page()
{
    if (isset($_GET['settings-updated'])) {
        settings_errors(); // Display success notice
        
    }
    if (isset($_POST['clear_data_nonce']) && wp_verify_nonce($_POST['clear_data_nonce'], 'clear_data_action')) {
        order_confirmer_clear_order_data();
    }
?>
    <div class="wrap">
        <h1>Order Confirmer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('order_confirmer_settings_group');
            do_settings_sections('order-confirmer-settings');
            submit_button();
            ?>
        </form>
        <form method="post">
            <?php wp_nonce_field('clear_data_action', 'clear_data_nonce'); ?>
            <table class="form-table">
                <em>Use the following option to clear copy request logs<br>Choose order creation date to clear before</em>
                <tr valign="top">
                    <th scope="row">Cutoff Order Date</th>
                    <td><input type="date" name="order_confirmer_cutoff_date" value="<?php echo esc_attr(get_option('order_confirmer_cutoff_date', '')); ?>" /></td>
                </tr>
            </table>
            <button type="submit" class="button button-secondary">Clear Data</button>
        </form>
    </div>
<?php
}

// Register the settings for the Order Confirmer plugin
function order_confirmer_register_settings()
{
    register_setting('order_confirmer_settings_group', 'order_confirmer_template');
    register_setting('order_confirmer_settings_group', 'order_confirmer_bacs_template'); // New template


    add_settings_section(
        'order_confirmer_settings_section',
        'Message Template',
        'order_confirmer_settings_section_callback',
        'order-confirmer-settings'
    );

    add_settings_field(
        'order_confirmer_template',
        'COD Template',
        'order_confirmer_template_callback',
        'order-confirmer-settings',
        'order_confirmer_settings_section'
    );
    add_settings_field(
        'order_confirmer_bacs_template', // New template field
        'Direct Bank Template',
        'order_confirmer_bacs_template_callback',
        'order-confirmer-settings',
        'order_confirmer_settings_section'
    );
}

// Callback for the settings section description
function order_confirmer_settings_section_callback()
{
    echo '<p>Use the following placeholders in your message to add the following data:<br> 
     <b>{id}</b> -> Order ID<br>
     <b>{phone}</b> -> Billing Phone<br>
     <b>{address}</b> -> Billing Address<br>
     <b>{total}</b> -> Order Total<br>
     <b>{items}</b> -> Order Content<br>
     <b>{date}</b> -> Creation Date<br>
     <b>{link}</b> -> Confirmation Link.</p>';
}

// Clear metas according to special date
function order_confirmer_clear_order_data()
{
    global $wpdb;

    $cutoff_date = isset($_POST['order_confirmer_cutoff_date']) ? sanitize_text_field($_POST['order_confirmer_cutoff_date']) : '';
    if (!$cutoff_date) {
        add_settings_error('order_confirmer_settings', 'no_cutoff_date', 'Cutoff date not set.');
        return;
    }

    $cutoff_datetime = date('Y-m-d H:i:s', strtotime($cutoff_date));

    // Get order IDs with date_created_gmt before the cutoff date
    $order_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}wc_orders WHERE date_created_gmt < %s",
            $cutoff_datetime
        )
    );

    if (!empty($order_ids)) {
        // Convert array of IDs to a comma-separated string
        $order_ids_str = implode(',', array_map('intval', $order_ids));

        // Delete meta where order_id matches and meta_key is '_copy_request'
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE order_id IN ($order_ids_str) AND meta_key = '_copy_request' AND meta_value= 1"
        );

        add_settings_error('order_confirmer_settings', 'data_cleared', 'Data cleared successfully.', 'updated');
    } else {
        add_settings_error('order_confirmer_settings', 'no_data_to_clear', 'No data to clear.', 'info');
    }
}
// Callback to render the textarea for the message template
function order_confirmer_template_callback()
{
    $template = get_option('order_confirmer_template', '');
    echo '<textarea name="order_confirmer_template" rows="10" cols="50" class="large-text">' . esc_textarea($template) . '</textarea>';
}

// Callback to render the textarea for the bacs message template
function order_confirmer_bacs_template_callback()
{
    $template = get_option('order_confirmer_bacs_template', '');
    echo '<textarea name="order_confirmer_bacs_template" rows="10" cols="50" class="large-text">' . esc_textarea($template) . '</textarea>';
}
// Add custom columns to the WooCommerce orders page
function order_confirmer_add_custom_columns($columns)
{
    $columns['billing_phone'] = 'Phone';
    $columns['confirm_copy'] = '<a style="display: block; text-align: center;">Copy</a>';
    return $columns;
}
add_filter('manage_woocommerce_page_wc-orders_columns', 'order_confirmer_add_custom_columns', 20);

// Generate a secure link for order confirmation
function order_confirmer_generate_secure_link($order_id)
{
    $token = md5($order_id . NONCE_KEY); // Create a token based on order ID and NONCE_KEY
    $link = add_query_arg(
        array(
            'token' => $token
        ),
        site_url('/order-confirmation/' . $order_id)
    );
    return $link;
}

// Render content for the custom columns in the WooCommerce orders page
function order_confirmer_custom_columns_content($column, $order_id)
{
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone() ?: $order->get_shipping_phone();
    switch ($column) {
        case 'billing_phone':
            $phone = preg_replace('/^(\+?0?0?2|2)/', '', $phone);
            $phone = str_replace(' ', '', $phone);
            $western_to_standard = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];
            $translated_phone = strtr($phone, $western_to_standard);
            echo '<a href="#" class="billing-phone" title="Copy Number" data-order-phone="' . $translated_phone . '">' . $phone . '</a>';
            break;
        case 'confirm_copy':
            if ($order->get_payment_method() == 'paymob' || $order->get_payment_method() == 'bacs' && $order->get_date_paid() !== NULL || $order->get_status() == 'completed' || $order->get_status() == 'cancelled' || $order->get_status() == 'refunded') {
                return;
            } else {
                $country_code = $order->get_billing_country();
                $state_code   = $order->get_billing_state();
                $countries = new WC_Countries(); // Get an instance of the WC_Countries Object
                $country_states = $countries->get_states($country_code); // Get the states array from a country code
                $state_name     = $country_states[$state_code];
                error_log($state_name);
                $order_items = $order->get_items("shipping");
                $meta_data = '';
                if ($order_items) {
                    foreach ($order_items as $item_id => $item) {
                        $meta_data = $item->get_meta('Items', true);
                        break;
                    }
                }
                $copy_request =  $order->get_meta('_copy_request');;

                $icon_color = $copy_request ? 'red' : 'inherit';

                echo '<a href="#" class="copying-msg" title="Copy Message" 
                data-order-id="' . $order->get_id() . '" 
                data-order-total="' . $order->get_total() . '" 
                data-order-address="' . $state_name . ', ' . $order->get_billing_address_1() . '" 
                data-order-phone="' . $phone . '" 
                data-order-items="' . $meta_data . '" 
                data-order-payment="' . $order->get_payment_method() . '"                 
                data-order-date="' . $order->get_date_created() . '" 
                style="font-size: 19px; display: block; text-align: center; color: ' . $icon_color . ';">
                <span class="dashicons dashicons-clipboard"></span></a>';
                break;
            }
    }
}

// Handle AJAX request to get the order confirmation template
function order_confirmer_get_template($order_id)
{
    global $wpdb;

    check_ajax_referer('copy_to_clipboard_nonce', 'nonce');

    // Retrieve order details
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order_total = isset($_POST['order_total']) ? sanitize_text_field($_POST['order_total']) : '';
    $order_address = isset($_POST['order_address']) ? sanitize_text_field($_POST['order_address']) : '';
    $order_phone = isset($_POST['order_phone']) ? sanitize_text_field($_POST['order_phone']) : '';
    $order_items = isset($_POST['order_items']) ? sanitize_text_field($_POST['order_items']) : '';
    $order_date = isset($_POST['order_date']) ? sanitize_text_field($_POST['order_date']) : '';
    $order_date_formatted = date('d/m/Y', strtotime($order_date));
    $order_link = order_confirmer_generate_secure_link($order_id);
    $order_payment = isset($_POST['order_payment']) ? sanitize_text_field($_POST['order_payment']) : '';
    if ($order_payment == 'bacs') {
        $template = get_option('order_confirmer_bacs_template', '');
    } else {
        $template = get_option('order_confirmer_template', '');
    }
    // Replace placeholders with actual values
    $custom_message = str_replace(
        ['{id}', '{total}', '{address}', '{phone}', '{items}', '{date}', '{link}'],
        [$order_id, $order_total, $order_address, $order_phone, $order_items, $order_date_formatted, $order_link],
        $template
    );

    $wpdb->insert('wp_wc_orders_meta', ['order_id' => $order_id, 'meta_key' => '_copy_request', 'meta_value' => 1], ['%d', '%s', '%d']);

    wp_send_json_success($custom_message);
}


// Auto delete copy request meta if order (completed, refunded, cancelled)
add_action('woocommerce_order_status_cancelled', function ($order_id) {
    $order = wc_get_order($order_id);
    $order->delete_meta_data('_copy_request');
}, 10, 1);

add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    $order->delete_meta_data('_copy_request');
}, 10, 1);

add_action('woocommerce_order_status_refunded', function ($order_id) {
    $order = wc_get_order($order_id);
    $order->delete_meta_data('_copy_request');
}, 10, 1);



// Register endpoint for order confirmation
function order_confirmer_add_endpoint()
{
    add_rewrite_endpoint('order-confirmation', EP_ROOT | EP_PAGES);
}

// Flush rewrite rules on plugin activation
function order_confirmer_rewrite_flush()
{
    order_confirmer_add_endpoint();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'order_confirmer_rewrite_flush');

// Add custom query var
function order_confirmer_query_vars($vars)
{
    $vars[] = 'order-confirmation';
    return $vars;
}

// Handle endpoint requests
function order_confirmer_template_redirect()
{
    global $wp;
    if (isset($wp->query_vars['order-confirmation'])) {
        include plugin_dir_path(__FILE__) . 'templates/order-confirmation.php';
        exit;
    }
}
