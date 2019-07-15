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
			'page'    => 'wc-settings',
			'tab'     => 'checkout',
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

add_action( 'woocommerce_cancelled_order', 'order_status_cancelled' );
function order_status_cancelled( $order ) {
//    print_r(get_defined_vars());
//    wp_die();
}

// Adding the id var so that WP recognizes it
function woocommerce_tradesafe_plugin_callback_query_vars( $vars ) {
	$vars[] = 'tradesafe';
	$vars[] = 'action';
	$vars[] = 'action_id';

	return $vars;
}

add_filter( 'query_vars', 'woocommerce_tradesafe_plugin_callback_query_vars' );

function woocommerce_tradesafe_plugin_callback_parse_request( $wp ) {
	// only process requests with "mypluginname=param1"
	if ( array_key_exists( 'tradesafe', $wp->query_vars )
	     && $wp->query_vars['tradesafe'] == '1' ) {
		switch ( $wp->query_vars['action'] ) {
			case 'callback':
				wp_die();
				// run callback query
				break;
			case "auth":
				// run auth check
				woocommerce_tradesafe_plugin_callback_auth();
				break;
			case "unlink":
				if ( is_user_logged_in() ) {
					$user = wp_get_current_user();
					delete_user_meta( $user->ID, 'tradesafe_user_id' );
					$edit_account_url = wc_get_endpoint_url( 'tradesafe', '', wc_get_page_permalink( 'myaccount' ) );
					wp_redirect( $edit_account_url );
				}
				break;
			case "accept":
				$order        = wc_get_order( $wp->query_vars['action_id'] );
				$tradesafe_id = $order->get_meta( 'tradesafe_id' );
				$data         = array(
					'step' => 'GOODS_ACCEPTED',
				);

				$response = woocommerce_tradesafe_api_request( 'contract/' . $tradesafe_id, array( 'body' => $data ), 'PUT' );
				$order->update_status( 'completed', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );

				wp_redirect( '/my-account/orders/' );
				break;
			case "extend":
				$order        = wc_get_order( $wp->query_vars['action_id'] );
				$tradesafe_id = $order->get_meta( 'tradesafe_id' );
				$response     = woocommerce_tradesafe_api_request( 'contract/' . $tradesafe_id, array(), 'GET' );

				if ( is_wp_error( $response ) ) {
					wp_redirect( '/my-account/orders/' );
				}

				if ('' !== $response['Contract']['completion_days_renegotiated'] ) {
					$days = $response['Contract']['completion_days_renegotiated'] + 3;
                } else {
					$days = $response['Contract']['completion_total_days'] + 3;
                }

				$data = array(
					'step'  => 'SENT',
					'amend' => array(
						'completion_days' => $days,
					),
				);

				$response = woocommerce_tradesafe_api_request( 'contract/' . $tradesafe_id, array( 'body' => $data ), 'PUT' );
				$order->update_status( 'processing', sprintf( __( 'Extended Delivery Time.', 'woocommerce-gateway-tradesafe' ) ) );

				wp_redirect( '/my-account/orders/' );
				break;
			case "decline":
				wp_die();
				$order        = wc_get_order( $wp->query_vars['action_id'] );
				$tradesafe_id = $order->get_meta( 'tradesafe_id' );
				$data         = array(
					'step' => 'DISPUTED',
				);

				$response = woocommerce_tradesafe_api_request( 'contract/' . $tradesafe_id, array( 'body' => $data ), 'PUT' );
				$order->update_status( 'completed', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );

				wp_redirect( '/my-account/orders/' );
				break;
			default:
				status_header( 404 );
				wp_die();
		}
	}
}

add_action( 'parse_request', 'woocommerce_tradesafe_plugin_callback_parse_request' );

function woocommerce_tradesafe_plugin_callback_auth() {
//    print_r($_SERVER);
	$json = file_get_contents( 'php://input' );
	$data = json_decode( $json, true );

	if ( isset( $data['user_id'] ) && isset( $data['parameters']['user_id'] ) ) {
		update_user_meta( $data['parameters']['user_id'], 'tradesafe_user_id', $data['user_id'] );
		status_header( 200 );
	} else {
		status_header( 404 );
		wp_die();
	}
	wp_die();
}

add_action( 'show_user_profile', 'edit_tradesafe_profile' );
add_action( 'edit_user_profile', 'edit_tradesafe_profile' );
function edit_tradesafe_profile( $user ) {
	$tradesafe_id        = get_the_author_meta( 'tradesafe_user_id', $user->ID );
	$tradesafe_user_data = array();
	if ( $tradesafe_id != '' ) {
		$gateway                     = new WC_Gateway_TradeSafe();
		$tradesafe_user_data_request = $gateway->api_request( 'user/' . $tradesafe_id, array(), 'GET' );

		if ( ! is_wp_error( $tradesafe_user_data_request ) ) {
			$tradesafe_user_data['name']['title'] = 'Name';
			$tradesafe_user_data['name']['value'] = $tradesafe_user_data_request['first_name'] . ' ' . $tradesafe_user_data_request['last_name'];

			$tradesafe_user_data['id_number']['title'] = 'ID Number';
			$tradesafe_user_data['id_number']['value'] = $tradesafe_user_data_request['id_number'];

			$tradesafe_user_data['email']['title'] = 'Email';
			$tradesafe_user_data['email']['value'] = $tradesafe_user_data_request['email'];

			$tradesafe_user_data['mobile']['title'] = 'Mobile';
			$tradesafe_user_data['mobile']['value'] = $tradesafe_user_data_request['mobile'];
		}
	}

	?>
    <h3><?php esc_html_e( 'TradeSafe Account Details', 'tradesafe' ); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="id_number"><?php esc_html_e( 'TradeSafe ID', 'tradesafe' ); ?></label></th>
            <td>
				<?php echo esc_attr( $tradesafe_id ); ?>
            </td>
        </tr>
    </table>

	<?php if ( ! empty( $tradesafe_user_data ) ): ?>
        <table class="form-table">
			<?php foreach ( $tradesafe_user_data as $tradesafe_user_data_key => $tradesafe_user_data_row ): ?>
                <tr>
                    <th><label for="bank"><?php esc_html_e( $tradesafe_user_data_row['title'], 'tradesafe' ); ?></label>
                    </th>
                    <td>
						<?php echo esc_attr( $tradesafe_user_data_row['value'] ); ?>
                    </td>
                </tr>
			<?php endforeach; ?>
        </table>
	<?php endif; ?>

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
 * Cancel Order
 */
add_action( 'woocommerce_order_status_pending_to_cancelled', 'woocommerce_tradesafe_cancel_order', 0, 1 );
function woocommerce_tradesafe_cancel_order( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( $order->meta_exists( 'tradesafe_id' ) ) {
		$data = array(
			'step' => 'DECLINED',
		);

		$response = woocommerce_tradesafe_api_request( 'contract/' . $order->get_meta( 'tradesafe_id' ), array( 'body' => $data ), 'PUT' );
	}
}

/**
 * Send off API request.
 *
 * @param $command
 * @param $token
 * @param $api_args
 * @param string $method GET | PUT | POST | DELETE.
 *
 * @return bool|WP_Error
 * @since 1.4.0 introduced.
 *
 */
function woocommerce_tradesafe_api_request( $command, $api_args, $method = 'POST' ) {
	$settings = get_option( 'woocommerce_tradesafe_settings' );
	$token    = $settings['json_web_token'];

	if ( 'www.tradesafe.co.za' === $settings['api_domain'] ) {
		$url = 'https://www.tradesafe.co.za/api';
	} else {
		$url = 'https://' . $settings['api_domain'] . '/api';
		if ( '' === $settings['api_domain'] ) {
			$url = 'https://sandbox.tradesafe.co.za/api';
		}
	}

	if ( empty( $token ) ) {
		error_log( "Error posting API request: No token supplied", true );

		return new WP_Error( '404', __( 'A token is required to submit a request to the TradeSafe API', 'woocommerce-gateway-tradesafe' ), null );
	}

	$api_endpoint = sprintf( '%s/%s', $url, $command );

	$api_args['timeout'] = 45;
	$api_args['headers'] = array(
		'Authorization' => 'Bearer ' . $token,
		'Content-Type'  => 'application/json',
	);

	if ( isset( $api_args['body'] ) ) {
		$api_args['body'] = json_encode( $api_args['body'] );
	}
	$api_args['method'] = strtoupper( $method );

	if ( '' !== $settings['ca_certificate'] ) {
		$api_args['sslcertificates'] = $settings['ca_certificate'];
	}

	$results = wp_remote_request( $api_endpoint, $api_args );

	if ( isset( $results['response']['code'] ) && 200 !== $results['response']['code'] && 201 !== $results['response']['code'] ) {
		error_log( "Error posting API request:\n" . print_r( $results['response'], true ) );

		return new WP_Error( $results['response']['code'], $results['response']['message'], $results );
	}

	$maybe_json = json_decode( $results['body'], true );

	if ( ! is_null( $maybe_json ) && isset( $maybe_json['error'] ) ) {
		error_log( "Error posting API request:\n" . print_r( $results['body'], true ) );

		// Use trim here to display it properly e.g. on an order note, since TradeSafe can include CRLF in a message.
		return new WP_Error( 422, trim( $maybe_json['error'] ), $results['body'] );
	}

	return $maybe_json;
}

/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function woocommerce_tradesafe_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_TradeSafe';

	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'woocommerce_tradesafe_valid_transaction' );
function woocommerce_tradesafe_valid_transaction( $available_gateways ) {
	$user    = wp_get_current_user();
	$user_id = get_user_meta( $user->ID, 'tradesafe_user_id', true );

	if ( ! $user_id && isset( $available_gateways['tradesafe'] ) ) {
		unset( $available_gateways['tradesafe'] );
		if ( isset( $_REQUEST['wc-ajax'] ) ) {
			print "<div style='border: 1px solid #CA170F; padding: 10px; background-color: #f9e7e7'>To complete this action your must first complete <a style='font-weight: bold;' href='" . get_site_url( null, 'my-account/tradesafe/' ) . "'>your account</a></div>";
		}
	}

	return $available_gateways;
}

add_action( 'wp_loaded', function () {
	if ( is_user_logged_in() && ! current_user_can( 'administrator' ) ) {

	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_USER_AGENT'] === 'api.tradesafe.co.za' ) {
		$data = json_decode( file_get_contents( 'php://input' ), true );

		$query = new WC_Order_Query();
		$query->set( 'customer', $data['counterparty_buyer_email'] );
		$orders = $query->get_orders();

		if ( isset( $data['step'] ) ) {
			switch ( $data['step'] ) {
				case "FUNDS_RECEIVED":
					foreach ( $orders as $order ) {
						$tradesafe_id = $order->get_meta( 'tradesafe_id' );
						if ( '' !== $tradesafe_id && $tradesafe_id == $data['id'] ) {
							$order->update_status( 'processing', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );
							$data = array(
								'step' => 'SENT',
							);

							$response = woocommerce_tradesafe_api_request( 'contract/' . $tradesafe_id, array( 'body' => $data ), 'PUT' );
						}
					}
					break;
				case "DECLINED":
					foreach ( $orders as $order ) {
						$tradesafe_id = $order->get_meta( 'tradesafe_id' );
						if ( $tradesafe_id == $data['id'] ) {
							$order->update_status( 'cancelled', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );
						}
					}
					break;
			}
		}
	}
} );

add_filter( 'woocommerce_my_account_my_orders_actions', 'woocommerce_tradesafe_my_orders_actions', 100, 2 );
function woocommerce_tradesafe_my_orders_actions( $actions, $order ) {

	if ( $order->has_status( array( 'processing' ) ) ) {
		// Set the action button
		$actions['accept'] = array(
			'url'    => '/tradesafe/accept/' . $order->get_id() . '/',
			'name'   => __( 'Accept', 'woocommerce-gateway-tradesafe' ),
			'action' => 'accept',
		);

		// Set the action button
		$actions['extend'] = array(
			'url'    => '/tradesafe/extend/' . $order->get_id() . '/',
			'name'   => __( 'Extend', 'woocommerce-gateway-tradesafe' ),
			'action' => 'extend',
		);

		// Set the action button
//		$actions['decline'] = array(
//			'url'    => '/tradesafe/decline/' . $order->get_id() . '/',
//			'name'   => __( 'Decline', 'woocommerce-gateway-tradesafe' ),
//			'action' => 'decline',
//		);
	}

	return $actions;
}

/*
 * Add Link (Tab) to My Account menu
 */
add_filter( 'woocommerce_account_menu_items', 'woocommerce_tradesafe_account_tab', 40 );
function woocommerce_tradesafe_account_tab( $menu_links ) {
	$menu_links = array_slice( $menu_links, 0, 5, true )
	              + array( 'tradesafe' => 'TradeSafe Details' )
	              + array_slice( $menu_links, 5, null, true );

	return $menu_links;
}

add_action( 'init', 'woocommerce_tradesafe_account' );
function woocommerce_tradesafe_account() {
	add_rewrite_rule( '^tradesafe/(.*)/(.*)/?$', 'index.php?tradesafe=1&action=$matches[1]&action_id=$matches[2]', 'top' );
	add_rewrite_rule( '^tradesafe/(.*)/?$', 'index.php?tradesafe=1&action=$matches[1]', 'top' );
	add_rewrite_endpoint( 'tradesafe', EP_PERMALINK | EP_PAGES | EP_ROOT );
}

add_action( 'woocommerce_account_tradesafe_endpoint', 'woocommerce_tradesafe_account_content' );
function woocommerce_tradesafe_account_content() {
	$user         = wp_get_current_user();
	$gateway      = new WC_Gateway_TradeSafe();
	$tradesafe_id = get_user_meta( $user->ID, 'tradesafe_user_id', true );
	$settings     = get_option( 'woocommerce_tradesafe_settings' );

	if ( 'www.tradesafe.co.za' === $settings['api_domain'] ) {
		$url = 'https://www.tradesafe.co.za/api';
	} else {
		$url = 'https://' . $settings['api_domain'] . '/api';
		if ( '' === $settings['api_domain'] ) {
			$url = 'https://sandbox.tradesafe.co.za/api';
		}
	}

	if ( $tradesafe_id != '' ) {
		$tradesafe_user_data = array();

		$tradesafe_user_data_request = $gateway->api_request( 'user/' . $tradesafe_id, array(), 'GET' );

		if ( ! is_wp_error( $tradesafe_user_data_request ) ) {
			$tradesafe_user_data['user']['name']['title'] = 'Name';
			$tradesafe_user_data['user']['name']['value'] = $tradesafe_user_data_request['first_name'] . ' ' . $tradesafe_user_data_request['last_name'];

			$tradesafe_user_data['user']['id_number']['title'] = 'ID Number';
			$tradesafe_user_data['user']['id_number']['value'] = $tradesafe_user_data_request['id_number'];

			$tradesafe_user_data['user']['email']['title'] = 'Email';
			$tradesafe_user_data['user']['email']['value'] = $tradesafe_user_data_request['email'];

			$tradesafe_user_data['user']['mobile']['title'] = 'Mobile';
			$tradesafe_user_data['user']['mobile']['value'] = $tradesafe_user_data_request['mobile'];

			if ( $tradesafe_user_data_request['company'] ) {
				$tradesafe_user_data['company']['name']['title'] = 'Name';
				$tradesafe_user_data['company']['name']['value'] = $tradesafe_user_data_request['company']['name'] . ' ' . $tradesafe_user_data_request['company']['type'];

				$tradesafe_user_data['company']['reg']['title'] = 'Registration Number';
				$tradesafe_user_data['company']['reg']['value'] = $tradesafe_user_data_request['company']['reg_number'];
			}

			if ( $tradesafe_user_data_request['bank'] ) {
				$tradesafe_user_data['bank']['name']['title'] = 'Bank';
				$tradesafe_user_data['bank']['name']['value'] = $tradesafe_user_data_request['bank']['name'];

				$tradesafe_user_data['bank']['number']['title'] = 'Account Number';
				$tradesafe_user_data['bank']['number']['value'] = $tradesafe_user_data_request['bank']['account'];

				$tradesafe_user_data['bank']['type']['title'] = 'Account Type';
				$tradesafe_user_data['bank']['type']['value'] = $tradesafe_user_data_request['bank']['type'];
			}
		}

		printf( '<div style="border: 1px solid #FFD700; background-color: #fffbe5; padding: 10px;"><strong>Please Note:</strong> This following information is not stored on <strong>%s</strong> and is provided for confirmation purposes only. If you would like your information please <a href="%s/login" target="_blank">login to your account</a> on the TradeSafe Website.</div>', get_bloginfo( 'name' ), $url );

		if ( ! empty( $tradesafe_user_data['user'] ) ) {
			print "<h3>Personal Details</h3>";
			foreach ( $tradesafe_user_data['user'] as $tradesafe_user_data_key => $tradesafe_user_data_row ) {
				echo "<div class=\"tradesafe-user-$tradesafe_user_data_key\">";
				printf( "<strong>%s :</strong> %s", esc_attr( $tradesafe_user_data_row['title'] ), esc_attr( $tradesafe_user_data_row['value'] ) );
				echo "</div>";
			}
		}

		if ( ! empty( $tradesafe_user_data['company'] ) ) {
			print "<h3>Company Details</h3>";
			foreach ( $tradesafe_user_data['company'] as $tradesafe_user_data_key => $tradesafe_user_data_row ) {
				echo "<div class=\"tradesafe-company-$tradesafe_user_data_key\">";
				printf( "<strong>%s :</strong> %s", esc_attr( $tradesafe_user_data_row['title'] ), esc_attr( $tradesafe_user_data_row['value'] ) );
				echo "</div>";
			}
		}

		if ( ! empty( $tradesafe_user_data['bank'] ) ) {
			print "<h3>Banking Details</h3>";
			foreach ( $tradesafe_user_data['bank'] as $tradesafe_user_data_key => $tradesafe_user_data_row ) {
				echo "<div class=\"tradesafe-bank-$tradesafe_user_data_key\">";
				printf( "<strong>%s :</strong> %s", esc_attr( $tradesafe_user_data_row['title'] ), esc_attr( $tradesafe_user_data_row['value'] ) );
				echo "</div>";
			}
		}

		echo "<br/>";

		printf( '<a href="%s" class="button">%s</a>', '/tradesafe/unlink', __( 'Unlink Account', 'woocommerce-gateway-tradesafe' ) );
	} else {
		$token_cache_id = 'tradesafe-token-' . $user->ID;
		$token          = get_transient( $token_cache_id );

		if ( false === $token ) {
			$tradesafe_auth_token_request = $gateway->api_request( 'authorize/token', array(), 'GET' );
			if ( ! is_wp_error( $tradesafe_auth_token_request ) ) {
				$token_lifetime = $tradesafe_auth_token_request['expire'] - $tradesafe_auth_token_request['created'];
				$token          = $tradesafe_auth_token_request['token'];
				set_transient( $token_cache_id, $token, $token_lifetime );
			}
		}

		if ( isset( $token ) ) {
			$edit_account_url = wc_get_endpoint_url( 'tradesafe', '', wc_get_page_permalink( 'myaccount' ) );
			?>
            <form action="<?php print $url; ?>/api/register" method="post" target="_blank" style="display: inline">
                <input type="hidden" name="auth_key" value="a68f4d96-f94e-4a4c-8335-54f41d87b9a5">
                <input type="hidden" name="auth_token" value="<?php print $token; ?>">
                <input type="hidden" name="success_url" value="<?php print $edit_account_url; ?>">
                <input type="hidden" name="failure_url" value="<?php print get_site_url(); ?>">
                <input type="hidden" name="parameters[user_id]" value="<?php print $user->ID; ?>">
                <input type="submit" value="Create a TradeSafe Account">
            </form>
            <br/>
            <br/>
            <form action="<?php print $url; ?>/api/authorize" method="post" target="_blank" style="display: inline">
                <input type="hidden" name="auth_key" value="a68f4d96-f94e-4a4c-8335-54f41d87b9a5">
                <input type="hidden" name="auth_token" value="<?php print $token; ?>">
                <input type="hidden" name="success_url" value="<?php print $edit_account_url; ?>">
                <input type="hidden" name="failure_url" value="<?php print get_site_url(); ?>">
                <input type="hidden" name="parameters[user_id]" value="<?php print $user->ID; ?>">
                <input type="submit" value="Link Your TradeSafe Account">
            </form>
			<?php
		}
	}
}

/**
 * Front end registration
 */

add_action( 'register_form', 'woocommerce_tradesafe_registration_form' );
add_action( 'woocommerce_register_form_start', 'woocommerce_tradesafe_registration_form' );
function woocommerce_tradesafe_registration_form() {
	$settings = get_option( 'woocommerce_tradesafe_settings' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'woocommerce-tradesafe-register-js', plugins_url( '/assets/js/register.js', __FILE__ ) );

	if ( $settings['api_domain'] == '' ) {
		$settings['api_domain'] = 'sandbox.tradesafe.co.za';
	}

	wp_register_script( 'tradesafe-settings', false );
	wp_localize_script( 'tradesafe-settings', 'tradesafe_params', array( 'api_url' => $settings['api_domain'] ) );
	wp_enqueue_script( 'tradesafe-settings' );

	$message = "<a href='#why-tradesafe' class='show-more' id='why-tradesafe'>Why do we require your bank account details if you are the one buying?</a>"
	           . "<div class='more-text'>"
	           . "<p>Your funds are paid into an independent escrow (trust) account managed by our escrow partners TradeSafe Escrow. If the goods or services are not what you ordered, then TradeSafe will refund you. This information is also required for regulatory reporting purposes (TradeSafe is accountable to both the South African Reserve Bank and the Financial Intelligence Centre). Your bank account details are secured and encrypted with industry leading technology standards which can be found in most banks.</p>"
	           . "<p>Please ensure you enter your banking account details correctly as neither us nor TradeSafe will be held responsible should the funds be paid into another account if you provide incorrect bank account details.</p>"
	           . "<p><a href='#why-tradesafe' class='show-more'>Hide</a></p>"
	           . "</div>";

	$bank_account_types = array(
		'CHEQUE'       => 'Cheque/Current Account',
		'SAVINGS'      => 'Savings Account',
		'TRANSMISSION' => 'Transmission Account',
		'BOND'         => 'Bond Account',
	);

	$cbc_list = array(
		"632005" => "Absa Bank",
		"430000" => "African Bank",
		"470010" => "Capitec Bank",
		"250655" => "First National Bank / Rand Merchant Bank",
		"580105" => "Investec Bank",
		"450105" => "Mercantile Bank",
		"490991" => "MTN Banking",
		"198765" => "Nedbank (South Africa)",
		"460005" => "Postbank",
		"051001" => "Standard Bank (South Africa)",
//		"other"  => "Other Bank",
	);

	$fields = array(
		'first_name'    => array( 'First Name', 'text' ),
		'last_name'     => array( 'Last Name', 'text' ),
		'mobile_number' => array( 'Mobile Number', 'text' ),
		'id_number'     => array( 'ID Number', 'text' ),
		'message'       => array( '', 'html', $message ),
		'bank_name'     => array( 'Bank', 'select', $cbc_list ),
		'bank_account'  => array( 'Account Number', 'text' ),
//		'bank_branch'   => array( 'Branch', 'text' ),
		'bank_type'     => array( 'Account Type', 'select', $bank_account_types ),
	);

	if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
		$gateway = new WC_Gateway_TradeSafe();
		$request = $gateway->api_request( 'user/' . esc_html( $_GET['auth_key'] ), array(), 'GET' );

		if ( ! is_wp_error( $request ) ) {
			?>
            <div><strong><?php esc_html_e( 'Name' ); ?>
                    : </strong><br/><?php esc_html_e( $request['first_name'] ); ?> <?php esc_html_e( $request['last_name'] ); ?>
            </div>
            <div><strong><?php esc_html_e( 'Mobile' ); ?>: </strong><br/><?php esc_html_e( $request['mobile'] ); ?>
            </div>
            <div><strong><?php esc_html_e( 'ID Number' ); ?>
                    : </strong><br/><?php esc_html_e( $request['id_number'] ); ?></div>
			<?php

			$_POST['email'] = esc_html( $_GET['email'] );
			print '<input type="hidden" name="auth_key" value="' . esc_html( $_GET['auth_key'] ) . '">'
			      . '<input type="hidden" name="verify" value="' . esc_html( $_GET['verify'] ) . '">';
		}
	} else {
		$count = 1;
		foreach ( $fields as $field_name => $field_info ) {
			$field_value = ! empty( $_POST[ $field_name ] ) ? $_POST[ $field_name ] : '';
			?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="<?php print $field_name; ?>"><?php esc_html_e( $field_info[0], 'woocommerce-gateway-tradesafe' ) ?>
					<?php if ( 'html' != $field_info[1] ): ?>
                        <span class="required">*</span>
					<?php endif; ?>
					<?php if ( 'select' == $field_info[1] ): ?>
                        <select step="<?php print $count; ?>"
                                id="<?php print $field_name; ?>"
                                name="<?php print $field_name; ?>"
                                class="input">
							<?php
							foreach ( $field_info[2] as $option_value => $option_name ) {
								if ( $option_value == $field_value ) {
									print '<option selected="selected" value="' . $option_value . '">' . $option_name . '</option>';
								} else {
									print '<option value="' . $option_value . '">' . $option_name . '</option>';
								}
							}
							?>
                        </select>
					<?php elseif ( 'html' == $field_info[1] ): ?>
						<?php print $field_info[2]; ?>
					<?php else: ?>
                        <input type="<?php print $field_info[1]; ?>"
                               step="<?php print $count; ?>"
                               id="<?php print $field_name; ?>"
                               name="<?php print $field_name; ?>"
                               value="<?php echo esc_attr( $field_value ); ?>"
                               class="input input-text"
                        />
					<?php endif; ?>
                </label>
            </p>
			<?php
			$count ++;
		}
	}

	print "<div class=\"clear\"></div>";
}

add_filter( 'registration_errors', 'woocommerce_tradesafe_registration_errors', 10, 3 );
function woocommerce_tradesafe_registration_errors( $errors, $sanitized_user_login, $user_email ) {
	return _validate_registration_form( $errors, $user_email );
}

add_action( 'woocommerce_register_post', 'woocommerce_tradesafe_my_account_registration_errors', 10, 3 );
function woocommerce_tradesafe_my_account_registration_errors( $username, $email, $validation_errors ) {
	return _validate_registration_form( $validation_errors, $email );
}

function _validate_registration_form( $errors, $user_email ) {

	if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
		$settings           = get_option( 'woocommerce_tradesafe_settings' );
		$token              = $settings['json_web_token'];
		$verification_token = hash( 'sha256', $token . $_GET['auth_key'] . $_GET['email'] );

		if ( $_GET['verify'] !== $verification_token ) {
			$errors->add( 'error', __( 'Invalid verification token', 'woocommerce-gateway-tradesafe' ) );
		}
	} else {
		$user = array(
			'first_name'     => sanitize_text_field( $_POST['first_name'] ),
			'last_name'      => sanitize_text_field( $_POST['last_name'] ),
			'email'          => $user_email,
			'mobile_country' => 'ZA',
			'mobile'         => str_replace( ' ', '', sanitize_text_field( $_POST['mobile_number'] ) ),
			'id_number'      => str_replace( ' ', '', sanitize_text_field( $_POST['id_number'] ) ),
			'bank_account'   => [
				'bank'        => sanitize_text_field( $_POST['bank_name'] ),
				'number'      => sanitize_text_field( $_POST['bank_account'] ),
				'branch_code' => sanitize_text_field( $_POST['bank_name'] ),
				'type'        => sanitize_text_field( $_POST['bank_type'] ),
			],
		);

		$gateway = new WC_Gateway_TradeSafe();
		$request = $gateway->api_request( 'verify/user', array( 'body' => $user ), 'POST' );
		$logger  = new WC_Logger();

		if ( is_wp_error( $request ) ) {
			$message = __( 'Account Creation Failed. If you already have a TradeSafe account or have used TradeSafe in the past, please link your account instead.', 'woocommerce-gateway-tradesafe' );
			$errors->add( 'error', $message );
			$logger->add( 'tradesafe', 'Verified Failed: ' . __( $request->get_error_message(), 'woocommerce-gateway-tradesafe' ) );
		}

		$logger->add( 'tradesafe', 'Verified User' );
	}

	return $errors;
}

add_action( 'user_register', 'woocommerce_tradesafe_user_register' );
add_action( 'woocommerce_created_customer', 'woocommerce_tradesafe_user_register' );
function woocommerce_tradesafe_user_register( $user_id ) {
	static $request;
	$user = get_user_by( 'ID', $user_id );

	if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
		$settings           = get_option( 'woocommerce_tradesafe_settings' );
		$token              = $settings['json_web_token'];
		$verification_token = hash( 'sha256', $token . $_GET['auth_key'] . $_GET['email'] );

		if ( $_GET['verify'] === $verification_token ) {
			update_user_meta( $user_id, 'tradesafe_user_id', $_GET['auth_key'] );
		}
	} else {
		$user = array(
			'first_name'     => sanitize_text_field( $_POST['first_name'] ),
			'last_name'      => sanitize_text_field( $_POST['last_name'] ),
			'email'          => $user->user_email,
			'mobile_country' => 'ZA',
			'mobile'         => str_replace( ' ', '', sanitize_text_field( $_POST['mobile_number'] ) ),
			'id_number'      => str_replace( ' ', '', sanitize_text_field( $_POST['id_number'] ) ),
			'bank_account'   => [
				'bank'        => sanitize_text_field( $_POST['bank_name'] ),
				'number'      => sanitize_text_field( $_POST['bank_account'] ),
				'branch_code' => sanitize_text_field( $_POST['bank_name'] ),
				'type'        => sanitize_text_field( $_POST['bank_type'] ),
			],
		);

		if ( ! isset( $request ) ) {
			$gateway = new WC_Gateway_TradeSafe();
			$request = $gateway->api_request( 'user', array( 'body' => $user ), 'POST' );
		}

		$logger = new WC_Logger();
		if ( ! is_wp_error( $request ) ) {
			update_user_meta( $user_id, 'tradesafe_user_id', $request['user_id'] );
			$logger->add( 'tradesafe', 'Created / Linked User Account ' . $user_id . '-' . $request['user_id'] );
		} else {
			$logger->add( 'tradesafe', 'Account Creation Failed ' . __( $request->get_error_message(), 'woocommerce-gateway-tradesafe' ) );
		}

		if ( isset( $_POST['first_name'] ) ) {
			update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $_POST['first_name'] ) );
			update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
		}
		if ( isset( $_POST['last_name'] ) ) {
			update_user_meta( $user_id, 'billing_last_name', sanitize_text_field( $_POST['last_name'] ) );
			update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
		}
	}

}

add_action( 'wp_ajax_nopriv_woocommerce_tradesafe_ajax_login', 'woocommerce_tradesafe_ajax_login' );
function woocommerce_tradesafe_ajax_login() {
	$data = array(
		'success_url' => $_POST['page_url'],
		'failure_url' => $_POST['page_url']
	);

	$gateway       = new WC_Gateway_TradeSafe();
	$request_token = $gateway->api_request( 'authorize/token', array(), 'GET' );
	$request_owner = $gateway->api_request( 'verify/owner', array(), 'GET' );

	if ( ! is_wp_error( $request_token ) && ! is_wp_error( $request_owner ) ) {
		$data['auth_key']   = $request_owner['id'];
		$data['auth_token'] = $request_token['token'];

		print json_encode( $data );
	}

	wp_die();
}