<?php
/**
 * Plugin Name: WooCommerce TradeSafe Gateway
 * Plugin URI: https://github.com/tradesafe-plugins/woocommerce-tradesafe-gateway
 * Description: Receive payments using the TradeSafe API.
 * Author: TradeSafe
 * Author URI: http://www.tradesafe.co.za/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 5.0
 * WC tested up to: 3.5
 * WC requires at least: 3.5
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the gateway.
 * @since 1.0.0
 */
function woocommerce_tradesafe_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	define( 'WC_GATEWAY_TRADESAFE_VERSION', '1.0.0' );

	require_once( plugin_basename( 'includes/class-wc-gateway-tradesafe.php' ) );
	require_once( plugin_basename( 'includes/class-wc-gateway-tradesafe-privacy.php' ) );
	load_plugin_textdomain( 'woocommerce-gateway-tradesafe', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_tradesafe_add_gateway' );
}
add_action( 'plugins_loaded', 'woocommerce_tradesafe_init', 0 );

function woocommerce_tradesafe_plugin_links( $links ) {
	$settings_url = add_query_arg(
		array(
			'page' => 'wc-settings',
			'tab' => 'checkout',
			'section' => 'wc_gateway_tradesafe',
		),
		admin_url( 'admin.php' )
	);

	$plugin_links = array(
		'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-tradesafe' ) . '</a>',
		'<a href="https://www.tradesafe.co.za/page/contact">' . __( 'Support', 'woocommerce-gateway-tradesafe' ) . '</a>',
		'<a href="https://www.tradesafe.co.za/page/API">' . __( 'Docs', 'woocommerce-gateway-tradesafe' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_tradesafe_plugin_links' );

add_action( 'woocommerce_cancelled_order', 'order_status_cancelled');
function order_status_cancelled($order) {
//    print_r(get_defined_vars());
//    die();
}

add_action( 'show_user_profile', 'edit_tradesafe_profile' );
add_action( 'edit_user_profile', 'edit_tradesafe_profile' );
function edit_tradesafe_profile($user) {
    $id_number = get_the_author_meta( 'id_number', $user->ID );
    $bank = get_the_author_meta( 'bank', $user->ID );
    $account_number = get_the_author_meta( 'account_number', $user->ID );
    $branch_code = get_the_author_meta( 'branch_code', $user->ID );
    $account_type = get_the_author_meta( 'account_type', $user->ID );
    ?>
    <h3><?php esc_html_e( 'Personal Information', 'tradesafe' ); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="id_number"><?php esc_html_e( 'ID Number', 'tradesafe' ); ?></label></th>
            <td>
                <input type="text"
                       id="id_number"
                       name="id_number"
                       value="<?php echo esc_attr( $id_number ); ?>"
                       class="regular-text"
                />
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'Banking Details', 'tradesafe' ); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="bank"><?php esc_html_e( 'Bank Name', 'tradesafe' ); ?></label></th>
            <td>
                <input type="text"
                       id="bank"
                       name="bank"
                       value="<?php echo esc_attr( $bank ); ?>"
                       class="regular-text"
                />
            </td>
        </tr>

        <tr>
            <th><label for="account_number"><?php esc_html_e( 'Account Number', 'tradesafe' ); ?></label></th>
            <td>
                <input type="text"
                       id="account_number"
                       name="account_number"
                       value="<?php echo esc_attr( $account_number ); ?>"
                       class="regular-text"
                />
            </td>
        </tr>

        <tr>
            <th><label for="branch_code"><?php esc_html_e( 'Branch Code', 'tradesafe' ); ?></label></th>
            <td>
                <input type="text"
                       id="branch_code"
                       name="branch_code"
                       value="<?php echo esc_attr( $branch_code ); ?>"
                       class="regular-text"
                />
            </td>
        </tr>

        <tr>
            <th><label for="account_type"><?php esc_html_e( 'Account Type', 'tradesafe' ); ?></label></th>
            <td>
                <input type="text"
                       id="account_type"
                       name="account_type"
                       value="<?php echo esc_attr( $account_type ); ?>"
                       class="regular-text"
                />
            </td>
        </tr>
    </table>

    <?php
}

add_action( 'personal_options_update', 'update_tradesafe_profile' );
add_action( 'edit_user_profile_update', 'update_tradesafe_profile' );
function update_tradesafe_profile( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    if ( ! empty( $_POST['id_number'] ) ) {
        update_user_meta( $user_id, 'id_number', intval( $_POST['id_number'] ) );
    }

    if ( ! empty( $_POST['bank'] ) ) {
        update_user_meta( $user_id, 'bank', intval( $_POST['bank'] ) );
    }

    if ( ! empty( $_POST['account_number'] ) ) {
        update_user_meta( $user_id, 'account_number', intval( $_POST['account_number'] ) );
    }

    if ( ! empty( $_POST['branch_code'] ) ) {
        update_user_meta( $user_id, 'branch_code', intval( $_POST['branch_code'] ) );
    }

    if ( ! empty( $_POST['account_type'] ) ) {
        update_user_meta( $user_id, 'account_type', intval( $_POST['account_type'] ) );
    }
}

/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function woocommerce_tradesafe_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_TradeSafe';
	return $methods;
}
