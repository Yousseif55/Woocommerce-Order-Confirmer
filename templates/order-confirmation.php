<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function confirm_title_update($title, $id = null)
{
    $title = 'Order Confirmation';
    return $title;
}
add_filter('the_title', 'confirm_title_update', 10, 2);

function remove_title_confirm_nav_menu($nav_menu, $args)
{
    remove_filter('the_title', 'confirm_title_update', 10, 2);
    return $nav_menu;
}
add_filter('pre_wp_nav_menu', 'remove_title_confirm_nav_menu', 10, 2);

function add_title_confirm_none_menu($items, $args)
{
    add_filter('the_title', 'confirm_title_update', 10, 2);
    return $items;
}
add_filter('wp_nav_menu_items', 'add_title_confirm_none_menu', 10, 2);

get_header();
?>
<div id="content-wrap" class="container clr">
    <div id="primary" class="content-area clr">
        <div id="content" class="site-content clr" >
            <article class="single-page-article clr">
                <div class="entry clr" itemprop="text">
                    <div class="woocommerce" >
                        <div class="woocommerce-order">

                            <?php
                            global $wp;
                            $order_id = isset($wp->query_vars['order-confirmation']) ? intval($wp->query_vars['order-confirmation']) : 0;
                            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

                            // Validate token and order ID
                            $expected_token = md5($order_id . NONCE_KEY);
                            if ($token !== $expected_token) {
                                echo '<p style="text-align:center;" class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><strong>' . esc_html__("The confirmation link is invalid.", "woocommerce") . '</strong></p>';
                                echo '</div>';
                                get_footer();
                                return;
                            }

                            $order = wc_get_order($order_id);
                            if (!$order) {
                                echo '<p style="text-align:center;" class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><strong>' . esc_html__("The specified order could not be found.", "woocommerce") . '</strong></p>';
                                echo '</div>';
                                get_footer();
                                return;
                            }
                            $status = $order->get_status();
                            // Check if the order is already confirmed
                            if (!in_array($status, ['processing', 'on-hold']) ) {

                                echo '<p style="text-align:center;" class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><strong>' . __('Your order #', 'woocommerce') . $order_id . __(' has already been confirmed.', 'woocommerce') . '</strong></p>';
                            } else {
                                // Confirm the order
                                $order->add_order_note('Order Confirmed by Customer.');
                                $order->update_status('wc-confirmed');
                                $current_date = date('Y-m-d H:i:s');
                                $order->update_meta_data('_confirmation_date', $current_date);
                                $order->save();

                                echo '<p style="text-align:center;" class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><strong>' . __('Your order #', 'woocommerce') . $order_id . __(' has been confirmed successfully.', 'woocommerce') . '</strong></p>';
                            }
                            ?>


                            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

                                <li class="woocommerce-order-overview__order order">
                                    <?php esc_html_e('Order number:', 'woocommerce'); ?>
                                    <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                            ?></strong>
                                </li>

                                <li class="woocommerce-order-overview__date date">
                                    <?php esc_html_e('Date:', 'woocommerce'); ?>
                                    <strong><?php echo wc_format_datetime($order->get_date_created()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                            ?></strong>
                                </li>

                                <?php if (is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email()) : ?>
                                    <li class="woocommerce-order-overview__email email">
                                        <?php esc_html_e('Email:', 'woocommerce'); ?>
                                        <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                ?></strong>
                                    </li>
                                <?php endif; ?>

                                <li class="woocommerce-order-overview__total total">
                                    <?php esc_html_e('Total:', 'woocommerce'); ?>
                                    <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                            ?></strong>
                                </li>

                                <?php if ($order->get_payment_method_title()) : ?>
                                    <li class="woocommerce-order-overview__payment-method method">
                                        <?php esc_html_e('Payment method:', 'woocommerce'); ?>
                                        <strong><?php echo wp_kses_post($order->get_payment_method_title()); ?></strong>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <?php do_action('woocommerce_thankyou', $order->get_id()); ?>

                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
<?php
get_footer();
