<?php

/**
 * Plugin Name: WooCommerce eService
 * Description: WooCommerce eService gateway integration.
 * Version: 1.2.1
 */

/**
 * Abort if the file is called directly
 */
if (!defined('WPINC')) {
    exit;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mmb-gateway-woocommerce-activator.php
 */
function activate_mmb_gateway_woocommerce()
{
    require_once plugin_dir_path(__FILE__) . 'classes/class-mmb-gateway-woocommerce-activator.php';
    MMB_Gateway_Woocommerce_Activator::activate();
}

register_activation_hook(__FILE__, 'activate_mmb_gateway_woocommerce');


/**
 * Run the plugin after all plugins are loaded
 */
add_action('plugins_loaded', 'init_mmb_gateway', 0);
function init_mmb_gateway()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    /**
     * The core plugin class that is used to define internationalization and
     * admin-specific hooks
     */
    require plugin_dir_path(__FILE__) . 'classes/class-mmb-gateway-woocommerce.php';

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    function run_mmb_gateway_woocommerce()
    {
        $plugin = new MMB_Gateway_Woocommerce();
        $plugin->run();
    }

    run_mmb_gateway_woocommerce();
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mmb_gateway_action_links' );
function mmb_gateway_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=eservice' ) . '">' . __( 'Settings', 'eservice' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}

add_action('rest_api_init', function () {
    register_rest_route('mmb-gateway/v1', '/redirect-data', array(
        'methods' => 'POST',
        'callback' => 'get_mmb_gateway_redirect_data',
        'permission_callback' => '__return_true'
    ));
});
function get_mmb_gateway_redirect_data(WP_REST_Request $request) {
    // Validating
    $body = $request->get_json_params();
    if (!$body) {
        return new WP_Error('no_payload', 'No payload found', array('status' => 400));
    }
    $order_key = $body['order_key'];
    if (!$order_key) {
        return new WP_Error('no_order_key', 'No order key provided', array('status' => 404));
    }
    $order_id = wc_get_order_id_by_order_key($order_key);
    if (!$order_id) {
        return new WP_Error('no_order', 'No order found for provided key', array('status' => 404));
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('invalid_order', 'Order not found', array('status' => 404));
    }
    if ($order->is_paid()) {
        return new WP_Error('already_paid', 'Order already paid, nothing to do', array('status' => 204));
    }

    require plugin_dir_path(__FILE__) . 'admin/class-mmb-gateway-request.php';
    $gateway = new EService();
    $mmb_request = new MMB_Gateway_Request($gateway);
    // generate_mmb_gateway_form in headless mode will return data required to build form on my own on the custom frontend
    $form_data = $mmb_request->generate_mmb_gateway_form($order, $gateway->testmode, true);

    return rest_ensure_response($form_data);
}
