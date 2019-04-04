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
 * Cancel Order
 */
add_action('woocommerce_order_status_pending_to_cancelled', 'woocommerce_tradesafe_cancel_order', 0, 1);
function woocommerce_tradesafe_cancel_order($order_id) {
    $order = wc_get_order( $order_id );

    if ($order->meta_exists('tradesafe_id')) {
        $data = array(
                'step' => 'DECLINED',
        );

        $response = woocommerce_tradesafe_api_request('contract/' . $order->get_meta('tradesafe_id'), array('body' => $data), 'PUT');
    }
}

/**
 * Send off API request.
 *
 * @since 1.4.0 introduced.
 *
 * @param $command
 * @param $token
 * @param $api_args
 * @param string $method GET | PUT | POST | DELETE.
 *
 * @return bool|WP_Error
 */
function woocommerce_tradesafe_api_request( $command, $api_args, $method = 'POST' ) {
    $settings = get_option('woocommerce_tradesafe_settings');
    $url = 'https://www.tradesafe.co.za/api';
    $token = $settings['json_web_token'];

    if ( 'yes' === $settings['testmode'] ) {
        $url = 'https://sandbox.tradesafe.co.za/api';
    }

    if ( empty( $token ) ) {
        error_log( "Error posting API request: No token supplied", true );
        return new WP_Error( '404', __( 'A token is required to submit a request to the TradeSafe API', 'woocommerce-gateway-tradesafe' ), null );
    }

    $api_endpoint  = sprintf('%s/%s', $url, $command);

    $api_args['timeout'] = 45;
    $api_args['headers'] = array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type'   => 'application/json',
    );

    if (isset($api_args['body'])) {
        $api_args['body'] = json_encode($api_args['body']);
    }
    $api_args['method'] = strtoupper( $method );

    $results = wp_remote_request( $api_endpoint, $api_args );

    if (isset($results['response']['code']) && 200 !== $results['response']['code'] && 201 !== $results['response']['code']) {
        error_log( "Error posting API request:\n" . print_r( $results['response'], true ) );
        return new WP_Error( $results['response']['code'], $results['response']['message'], $results );
    }

    $maybe_json = json_decode( $results['body'], true );

    if ( ! is_null( $maybe_json ) && isset($maybe_json['error']) ) {
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
function woocommerce_tradesafe_valid_transaction($available_gateways) {
    $user = wp_get_current_user();
    $user_id = get_user_meta($user->id, 'tradesafe_id', true);

    if (!$user_id && isset($available_gateways['tradesafe'])) {
	    if (get_user_meta($user->id, 'account_id_number', true) == '' ||
            get_user_meta($user->id, 'account_mobile_number', true) == '' ||
            get_user_meta($user->id, 'account_bank_name', true) == '' ||
            get_user_meta($user->id, 'account_bank_number', true) == '' ||
            get_user_meta($user->id, 'account_bank_type', true) == '') {
		    unset($available_gateways['tradesafe']);
		    if (isset($_REQUEST['wc-ajax'])) {
			    print "<div>The TradeSafe Escrow Payment Gateway is currently disabled. Have you updated your <a href='" . get_site_url(null, 'my-account/tradesafe/') . "'>account</a>?</div>";
            }
        }
    }

    return $available_gateways;
}

add_action( 'wp_loaded', function() {
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_USER_AGENT'] === 'api.tradesafe.co.za') {
	    $data = json_decode(file_get_contents('php://input'), true);

		$query = new WC_Order_Query();
		$query->set('customer', $data['counterparty_buyer_email']);
		$orders = $query->get_orders();

	    if (isset($data['step'])) {
	        switch ($data['step']) {
		        case "FUNDS_RECEIVED":
			        foreach ( $orders as $order ) {
				        $tradesafe_id = $order->get_meta('tradesafe_id');
				        if ($tradesafe_id == $data['id']) {
					        $order->update_status( 'processing', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );
					        $data = array(
						        'step' => 'SENT',
					        );

					        $response = woocommerce_tradesafe_api_request('contract/' . $tradesafe_id, array('body' => $data), 'PUT');
                        }
			        }
			        break;
                case "DECLINED":
	                foreach ( $orders as $order ) {
		                $tradesafe_id = $order->get_meta('tradesafe_id');
		                if ($tradesafe_id == $data['id']) {
			                $order->update_status( 'cancelled', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );
		                }
	                }
                    break;
	        }
        }
	}
});

add_filter( 'woocommerce_my_account_my_orders_actions', 'woocommerce_tradesafe_my_orders_actions', 100, 2 );
function woocommerce_tradesafe_my_orders_actions($actions, $order) {

	if ( $order->has_status( array( 'processing' ) ) ) {
		// Set the action button
		$actions['accept'] = array(
			'url'       => '/tradesafe/accept/' . $order->get_id(),
			'name'      => __( 'Accept', 'woocommerce-gateway-tradesafe' ),
			'action'    => 'accept',
		);

		// Set the action button
		$actions['extend'] = array(
			'url'       => '/tradesafe/extend/' . $order->get_id(),
			'name'      => __( 'Extend', 'woocommerce-gateway-tradesafe' ),
			'action'    => 'extend',
		);

		// Set the action button
		$actions['decline'] = array(
			'url'       => '/tradesafe/decline/' . $order->get_id(),
			'name'      => __( 'Decline', 'woocommerce-gateway-tradesafe' ),
			'action'    => 'decline',
		);
	}
	return $actions;
}

/*
 * Add Link (Tab) to My Account menu
 */
add_filter ( 'woocommerce_account_menu_items', 'woocommerce_tradesafe_account_tab', 40 );
function woocommerce_tradesafe_account_tab($menu_links){
    $menu_links = array_slice( $menu_links, 0, 5, true )
        + array( 'tradesafe' => 'TradeSafe Details' )
        + array_slice( $menu_links, 5, NULL, true );

    return $menu_links;
}

add_action( 'init', 'woocommerce_tradesafe_account' );
function woocommerce_tradesafe_account() {
    add_rewrite_endpoint( 'tradesafe', EP_PERMALINK | EP_PAGES | EP_ROOT);
}

add_action( 'pre_get_posts', 'woocommerce_tradesafe_order_actions' );
function woocommerce_tradesafe_order_actions( $query ) {
	if ( $query->is_main_query() ) {
	    $action = $query->query['pagename'];

		// this is for security!
		$allowed_actions = array('tradesafe/accept', 'tradesafe/extend', 'tradesafe/decline');

		if ( in_array($action, $allowed_actions) ) {
			$order_id = $query->query['page'];
			$order = wc_get_order($order_id);
			$tradesafe_id = $order->get_meta('tradesafe_id');

			switch ( $action ) {
				case 'tradesafe/accept':
					$data = array(
						'step' => 'GOODS_ACCEPTED',
					);

					$response = woocommerce_tradesafe_api_request('contract/' . $tradesafe_id, array('body' => $data), 'PUT');

					$order->update_status( 'completed', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-gateway-tradesafe' ) ) );
				    break;
				case 'tradesafe/extend':
					break;
				case 'tradesafe/decline':
					break;
			}

			wp_redirect('/my-account/orders/');
			exit;
		}
	}
}

add_action( 'woocommerce_account_tradesafe_endpoint', 'woocommerce_tradesafe_account_content' );
function woocommerce_tradesafe_account_content() {
	$gateway = new WC_Gateway_TradeSafe();

    $user = wp_get_current_user();
    $user_id = get_user_meta($user->id, 'tradesafe_id', true);

    if (!$user_id) {
        $api_endpoint  = 'user/' . $user->user_email;
    } else {
        $api_endpoint  = 'user/' . $user_id;
    }

	$response = $gateway->api_request($api_endpoint, array(), 'GET');
//    $response = '';

    if ( ! is_wp_error( $response ) ) {
        if ( isset($response['User']['username']) ) {
            add_user_meta($user->id, 'tradesafe_id', $response['User']['username'], true);

            print '<div class="row"><strong>First Name:</strong> ' . $response['Account']['first_name'] . '</div>';
            print '<div class="row"><strong>Last Name:</strong> ' . $response['Account']['last_name'] . '</div>';
	        print '<div class="row"><strong>Email:</strong> ' . $response['User']['email'] . '</div>';
            print '<div class="row"><strong>ID Number:</strong> ' . $response['Account']['id_number'] . '</div>';
            print '<div class="row"><strong>Mobile Number:</strong> ' . $response['Account']['mobile'] . '</div>';

            if ('yes' === $gateway->testmode) {
                $link = 'https://sandbox.tradesafe.co.za/login';
            } else {
	            $link = 'https://www.tradesafe.co.za/login';
            }

	        print '<a href="' . $link . '" target="_blank">Update TradeSafe Account</a>';
        } else {
	        woocommerce_tradesafe_account_form();
        }
    } else {
	    print 'Account incomplete';

	    if ('yes' === $gateway->debug) {
		    foreach ($response->errors as $code => $errors) {
			    print '<div class="row"><strong>Error Code:</strong> ' . $code . '</div>';
			    print '<div class="row"><strong>Error Messages:</strong><br/>' . implode('<br/>', $errors) . '</div>';
            }
	    }

	    woocommerce_tradesafe_account_form();
    }
}

function  woocommerce_tradesafe_account_form() {
	$user = wp_get_current_user();
    $banks = array(
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
    );
    $account_types = array(
            "CHEQUE" => "Cheque/Current Account",
            "SAVINGS" => "Savings Account",
            "TRANSMISSION" =>"Transmission Account",
            "BOND" => "Bond Account",
    );
	if ( ! empty( $_POST ) ) {
		$gateway = new WC_Gateway_TradeSafe();
		$data = array(
		        'first_name' => get_user_meta($user->id, 'first_name',true),
		        'last_name' => get_user_meta($user->id, 'last_name',true),
		        'email' => $user->user_email,
		        'mobile_country' => 'ZA',
		        'mobile' => $_POST['account_mobile_number'],
		        'id_number' => $_POST['account_id_number'],
		        'bank' => $banks[$_POST['account_bank_name']],
		        'number' => $_POST['account_bank_number'],
		        'branch_code' => $_POST['account_bank_name'],
		        'type' => $_POST['account_bank_type'],
        );

		$response = $gateway->api_request('verify/user', array('body' => $data));

		if (! is_wp_error($response)) {
			update_user_meta($user->id, 'account_id_number', $data['id_number']);
			update_user_meta($user->id, 'account_mobile_number', $data['mobile']);
			update_user_meta($user->id, 'account_bank_name', $data['branch_code']);
			update_user_meta($user->id, 'account_bank_number', $data['number']);
			update_user_meta($user->id, 'account_bank_type', $data['type']);
        } else {
			foreach ($response->errors as $code => $errors) {
				print '<div class="row"><strong>Error:</strong><br/>' . implode('<br/>', $errors) . '</div>';
			}
        }
	} else {
	    $_POST = array(
	            'account_id_number' => get_user_meta($user->id, 'account_id_number', true),
	            'account_mobile_number' => get_user_meta($user->id, 'account_mobile_number', true),
	            'account_bank_name' => get_user_meta($user->id, 'account_bank_name', true),
	            'account_bank_number' => get_user_meta($user->id, 'account_bank_number', true),
	            'account_bank_type' => get_user_meta($user->id, 'account_bank_type', true),
        );
    }
    ?>
    <form class="woocommerce-EditAccountForm edit-account" action="" method="post">
        <h3><?php esc_html_e( 'Personal Information', 'tradesafe' ); ?></h3>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_id_number"><?php esc_html_e( 'ID Number', 'tradesafe' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_id_number" id="account_id_number" value="<?php print $_POST['account_id_number']; ?>" required="required">
        </p>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_mobile_number"><?php esc_html_e( 'Mobile Number', 'tradesafe' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_mobile_number" id="account_mobile_number" value="<?php print $_POST['account_mobile_number']; ?>" required="required">
        </p>

        <h3><?php esc_html_e( 'Banking Details', 'tradesafe' ); ?></h3>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_bank_name"><?php esc_html_e( 'Bank', 'tradesafe' ); ?>&nbsp;<span class="required">*</span></label>
            <select class="woocommerce-Input woocommerce-Input--select input-select" name="account_bank_name" id="account_bank_name" required="required">
                <option value="">-- Select your Bank --</option>
                <?php
                    foreach ($banks as $branch_code => $bank_name) {
                        if ($_POST['account_bank_name'] == $branch_code) {
	                        print '<option value="' . $branch_code .  '" selected>' . $bank_name . '</option>';
                        } else {
	                        print '<option value="' . $branch_code .  '">' . $bank_name . '</option>';
                        }
                    }
                ?>
            </select>
        </p>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_bank_number"><?php esc_html_e( 'Account Number', 'tradesafe' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_bank_number" id="account_bank_number" value="<?php print $_POST['account_bank_number']; ?>" required="required">
        </p>

<!--        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">-->
<!--            <label for="account_bank_code">--><?php //esc_html_e( 'Branch Code', 'tradesafe' ); ?><!--&nbsp;<span class="required">*</span></label>-->
<!--            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_bank_code" id="account_bank_code" value="--><?php //print $_POST['account_bank_code']; ?><!--" required="required">-->
<!--        </p>-->

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_bank_type"><?php esc_html_e( 'Account Type', 'tradesafe' ); ?>&nbsp;<span class="required">*</span></label>
            <select class="woocommerce-Input woocommerce-Input--select input-select" name="account_bank_type" id="account_bank_type" required="required">
                <option value="">-- Select Account Type --</option>
	            <?php
	            foreach ($account_types as $account_code => $account_name) {
		            if ($_POST['account_bank_type'] == $account_code) {
			            print '<option value="' . $account_code .  '" selected>' . $account_name . '</option>';
		            } else {
			            print '<option value="' . $account_code .  '">' . $account_name . '</option>';
		            }
	            }
	            ?>
            </select>
        </p>

        <p>
            <button type="submit" class="woocommerce-Button button" name="save_account_details" value="Save changes">Save changes</button>
        </p>
    </form>

<?php }