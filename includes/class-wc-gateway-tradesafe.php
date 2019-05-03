<?php

/**
 * TradeSafe Payment Gateway
 *
 * Provides a TradeSafe Payment Gateway.
 *
 * @class  woocommerce_tradesafe
 * @package WooCommerce
 * @category Payment Gateways
 * @author WooCommerce
 */
class WC_Gateway_TradeSafe extends WC_Payment_Gateway {

	/**
	 * Version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * @access protected
	 * @var array $data_to_send
	 */
	protected $data_to_send = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->version      = WC_GATEWAY_TRADESAFE_VERSION;
		$this->id           = 'tradesafe';
		$this->method_title = __( 'TradeSafe Escrow', 'woocommerce-gateway-tradesafe' );
		/* translators: 1: a href link 2: closing href */
		$this->method_description   = sprintf( __( 'TradeSafe works by sending the user to %1$sTradeSafe%2$s to enter their payment information.', 'woocommerce-gateway-tradesafe' ), '<a href="https://www.tradesafe.co.za/">', '</a>' );
		$this->icon                 = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/icon.png';
		$this->debug_email          = get_option( 'admin_email' );
		$this->available_countries  = array( 'ZA' );
		$this->available_currencies = array( 'ZAR' );

		// Supported functionality
		$this->supports = array(
			'products',
		);

		$this->init_form_fields();
		$this->init_settings();

		if ( ! is_admin() ) {
			$this->setup_constants();
		}

		// Setup default merchant data.
		$this->api_key          = $this->get_option( 'json_web_token' );
		$this->url              = 'https://www.tradesafe.co.za/api';
		$this->title            = $this->get_option( 'title' );
		$this->response_url     = add_query_arg( 'wc-api', 'WC_Gateway_TradeSafe', home_url( '/' ) );
		$this->send_debug_email = 'yes' === $this->get_option( 'send_debug_email' );
		$this->description      = $this->get_option( 'description' );
		$this->enabled          = $this->is_valid_for_use() ? 'yes' : 'no'; // Check if the base currency supports this gateway.
		$this->enable_logging   = 'yes' === $this->get_option( 'enable_logging' ) ? 'yes' : 'no';
		$this->debug            = 'yes' === $this->get_option( 'enable_debugging' ) ? 'yes' : 'no';

		// Setup the test data, if in test mode.
		if ( 'yes' === $this->get_option( 'testmode' ) ) {
			$this->testmode = 'yes';
			$this->url      = 'https://sandbox.tradesafe.co.za/api';
			$this->url      = 'http://local.tradesafe.co.za/api';
			$this->add_testmode_admin_settings_notice();
		} else {
			$this->testmode         = 'no';
			$this->send_debug_email = false;
		}

		add_action( 'woocommerce_api_wc_gateway_tradesafe', array( $this, 'check_itn_response' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_receipt_tradesafe', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_cancelled_order', array( $this, 'order_status_cancelled' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_option( 'tradesafe_verify_last_check', '64', '', true );

	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->get_countries();
		$this->update_option( 'tradesafe_verify_last_check', 0 );
		$token = $this->get_option( 'json_web_token' );

		$this->form_fields = array(
			'enabled'          => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-tradesafe' ),
				'label'       => __( 'Enable TradeSafe', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'checkbox',
				'description' => __( 'This controls whether or not this gateway is enabled within WooCommerce.', 'woocommerce-gateway-tradesafe' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'title'            => array(
				'title'       => __( 'Title', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-tradesafe' ),
				'default'     => __( 'TradeSafe Escrow', 'woocommerce-gateway-tradesafe' ),
				'desc_tip'    => true,
			),
			'description'      => array(
				'title'       => __( 'Description', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-tradesafe' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'testmode'         => array(
				'title'       => __( 'TradeSafe Sandbox', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in development mode.', 'woocommerce-gateway-tradesafe' ),
				'default'     => 'yes',
			),
			'json_web_token'   => array(
				'title'       => __( 'JSON Web Token', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'text',
				'placeholder' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
				'default'     => '',
			),
			'service_role'     => array(
				'title'       => __( 'Role', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'select',
				'description' => __( 'Please select that you are a Seller or an Agent', 'woocommerce-gateway-tradesafe' ),
				'options'     => array(
					'seller'      => __( 'Seller', 'woocommerce-gateway-tradesafe' ),
					'marketplace' => __( 'Agent or Broker', 'woocommerce-gateway-tradesafe' )
				),
				'default'     => 'seller',
			),
			'industry'         => array(
				'class'   => 'tradesafe-setting-heading',
				'title'   => __( 'Industry Classification', 'woocommerce-gateway-tradesafe' ),
				'type'    => 'select',
				'options' => array(
					'GENERAL_GOODS_SERVICES'     => __( 'General Goods & Services', 'woocommerce-gateway-tradesafe' ),
					'AGRICULTURE_LIVESTOCK_GAME' => __( 'Agriculture, Livestock & Game', 'woocommerce-gateway-tradesafe' ),
					'ART_ANTIQUES_COLLECTIBLES'  => __( 'Art, Antiques & Collectibles', 'woocommerce-gateway-tradesafe' ),
					'BUSINESS_SALE_BROKING'      => __( 'Business Sale & Broking', 'woocommerce-gateway-tradesafe' ),
					'VEHICLES_WATERCRAFT'        => __( 'Cars, Bikes & Watercraft', 'woocommerce-gateway-tradesafe' ),
					'CONSTRUCTION'               => __( 'Construction', 'woocommerce-gateway-tradesafe' ),
					'CONTRACT_WORK_FREELANCING'  => __( 'Contract Work & Freelancing', 'woocommerce-gateway-tradesafe' ),
					'FUEL'                       => __( 'Diesel, Petroleum & Biofuel (Local)', 'woocommerce-gateway-tradesafe' ),
					'FUEL_INTERNATIONAL'         => __( 'Diesel, Petroleum & Biofuel (Cross-Border)', 'woocommerce-gateway-tradesafe' ),
					'DONATIONS_TRUSTS'           => __( 'Donations & Trusts', 'woocommerce-gateway-tradesafe' ),
					'FILMS_PRODUCTION'           => __( 'Films & Production', 'woocommerce-gateway-tradesafe' ),
					'HOLIDAY_LETS_DEPOSITS'      => __( 'Holiday Lets & Deposits', 'woocommerce-gateway-tradesafe' ),
					'INVESTMENTS_EXITS'          => __( 'Investments & Exits', 'woocommerce-gateway-tradesafe' ),
					'MINING'                     => __( 'Mining, Metals & Minerals', 'woocommerce-gateway-tradesafe' ),
					'LEASES_RENTAL_DEPOSITS'     => __( 'Rental Deposits', 'woocommerce-gateway-tradesafe' ),
					'USED_PARTS'                 => __( 'Used Parts', 'woocommerce-gateway-tradesafe' ),
					'SOFTWARE_DEV_WEB_DOMAINS'   => __( 'Web Domain Purchases & Transfers', 'woocommerce-gateway-tradesafe' ),
					'WEDDINGS_FUNCTIONS'         => __( 'Weddings & Functions', 'woocommerce-gateway-tradesafe' )
				),
				'default' => 'GENERAL_GOODS_SERVICES',
			),
			'agent_fee'        => array(
				'title'       => __( 'Agent Fee', 'woocommerce-gateway-tradesafe' ),
				'description' => __( 'This fee only applies if the agent role above is selected', 'woocommerce-gateway-tradesafe' ),
				'type'        => 'text',
				'placeholder' => 'Example: 10.00',
				'default'     => '0.00',
			),
			'enable_logging'   => array(
				'title'   => __( 'Enable Logging', 'woocommerce-gateway-tradesafe' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable transaction logging for the gateway.', 'woocommerce-gateway-tradesafe' ),
				'default' => 'no',
			),
			'enable_debugging' => array(
				'title'   => __( 'Enable API Debugging', 'woocommerce-gateway-tradesafe' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable API debugging for the gateway.', 'woocommerce-gateway-tradesafe' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * add_testmode_admin_settings_notice()
	 * Add a notice to the merchant_key and merchant_id fields when in test mode.
	 *
	 * @since 1.0.0
	 */
	public function add_testmode_admin_settings_notice() {
		$this->form_fields['json_web_token']['description'] .= ' <strong>' . __( 'Sandbox Merchant API Key currently in use', 'woocommerce-gateway-tradesafe' ) . '.</strong>';
	}

	/**
	 * is_valid_for_use()
	 *
	 * Check if this gateway is enabled and available in the base currency being traded with.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_valid_for_use() {
		$is_available          = false;
		$is_available_currency = in_array( get_woocommerce_currency(), $this->available_currencies );

		if ( $is_available_currency && $this->api_key ) {
			$is_available = true;
		}

        $response = $this->api_request( 'verify/owner', array(), 'GET');

        if ( is_wp_error( $response ) ) {
            $is_available = false;
            foreach ( $response->errors as $code => $message ) {
                add_action( 'admin_notices', function () use ( $code, $message ) {
                    echo '<div class="error tradesafe-' . $code . '"><p>'
                         . '<strong>'
                         . __( 'TradeSafe Escrow: ', 'woocommerce-gateway-tradesafe' )
                         . '</strong><br />'
                         . __( $message[0], 'woocommerce-gateway-tradesafe' )
                         . '</p></div>';
                } );
            }
        } else {
	        $this->form_fields['json_web_token']['description'] .= '<strong>' . __( 'Account Details:', 'woocommerce-gateway-tradesafe' ) . '</strong><br/>'
	                                                               . "<div><strong>Name: </strong> " . $response['first_name'] . " " . $response['last_name'] . "</div>"
	                                                               . "<div><strong>Email: </strong>" . $response['email'] . "</div>"
	                                                               . "<div><strong>Mobile: </strong>" . $response['mobile'] . "</div>"
	                                                               . "<div><strong>ID Number: </strong>" . $response['id_number'] . "</div>"
	                                                               . "<div><strong>Bank Details: </strong><br/>" . $response['bank']['name'] . "<br/>" . $response['bank']['account'] . "<br/>" . $response['bank']['type'] . "</div>";
        }

		return $is_available;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( in_array( get_woocommerce_currency(), $this->available_currencies ) ) {
			parent::admin_options();
		} else {
			?>
            <h3><?php _e( 'TradeSafe', 'woocommerce-gateway-tradesafe' ); ?></h3>
            <div class="inline error"><p>
                    <strong><?php _e( 'Gateway Disabled', 'woocommerce-gateway-tradesafe' ); ?></strong> <?php /* translators: 1: a href link 2: closing href */
					echo sprintf( __( 'Choose South African Rands as your store currency in %1$sGeneral Settings%2$s to enable the TradeSafe Gateway.', 'woocommerce-gateway-tradesafe' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">', '</a>' ); ?>
                </p></div>
			<?php
		}
	}

	/**
	 * Generate the TradeSafe button link.
	 *
	 * @since 1.0.0
	 */
	public function generate_tradesafe_form( $order_id ) {
		$order = wc_get_order( $order_id );

		$pay_url   = $order->get_checkout_payment_url();
		$order_url = $order->get_view_order_url();

		if ( $order->meta_exists( 'tradesafe_id' ) ) {
			$response = $this->api_request( 'contract/' . $order->get_meta( 'tradesafe_id' ), array(), 'GET' );
			$response = $response['Contract'];
		} else {
			$owner_details = $this->api_request( 'verify/owner', array(), 'GET' );

			if (!is_wp_error($owner_details)) {
			    $owner = array(
			            'first_name' => $owner_details['first_name'],
			            'email' => $owner_details['email'],
			            'id_number' => $owner_details['id_number'],
			            'mobile' => $owner_details['mobile'],
			            'mobile_country' => 'ZA',
                );
			}

			$user = wp_get_current_user();
			$user_id = get_user_meta($user->ID, 'tradesafe_user_id', true);
			$buyer_details = $this->api_request( 'user/' . $user_id, array(), 'GET' );

			if (!is_wp_error($buyer_details)) {
				$buyer = array(
					'first_name' => $buyer_details['first_name'],
					'email' => $buyer_details['email'],
					'id_number' => $buyer_details['id_number'],
					'mobile' => $buyer_details['mobile'],
					'mobile_country' => 'ZA',
				);
			}

			$data = array(
				"name"              => get_bloginfo( 'name' ) . ' - Order ' . $order->get_order_number(),
				"reference"         => get_site_url( null, null, null ) . '|' . self::get_order_prop( $order, 'order_key' ),
				"success_url"       => $order_url,
				"failure_url"       => $pay_url,
				"industry"          => $this->get_option( 'industry' ),
				"description"       => sprintf( __( 'New order from %s. Order ID: %s', 'woocommerce-gateway-tradesafe' ), get_bloginfo( 'name' ), $order->get_order_number() ),
				"value"             => (float) $order->get_total(),
				"completion_days"   => 30,
				"completion_months" => 0,
				"completion_years"  => 0,
				"inspection_days"   => 7,
				"delivery_required" => false,
			);

			$data["fee_allocation"] = 1;

			$data["buyer"] = $buyer;
			$data["seller"] = $owner;

			if ( 'agent' === $this->get_option( 'service_role' ) ) {
				$data["seller"] = array();
				$data["agent"] = $owner;

				$data["fee_allocation"]       = 3;
				$data["agent_fee"]            = $this->get_option( 'agent_fee' );
				$data["agent_fee_allocation"] = 1;
			}

			$verify_response = $this->api_request( 'validate/contract', array( 'body' => $data ) );

			if ( ! is_wp_error( $verify_response ) && isset( $verify_response['errors'] ) ) {
				$output = '<p><strong>ERROR:</strong> ' . $verify_response['errors'][0] . '</p>';
				$output .= '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-tradesafe' ) . '</a>';

				return $output;
			} elseif ( is_wp_error( $verify_response ) ) {
				$output = '<p>An error occured.</p>';
				$output .= '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-tradesafe' ) . '</a>';

				if ( 'yes' === $this->debug ) {
					foreach ( $verify_response->errors as $code => $errors ) {
						$output .= '<div class="row"><strong>Error Code:</strong> ' . $code . '</div>';
						$output .= '<div class="row"><strong>Error Messages:</strong><br/>' . implode( '<br/>', $errors ) . '</div>';
					}
				}

				return $output;
			}

			$response = $this->api_request( 'contract', array( 'body' => $data ) );
			$response = $response['Contract'];

			$order->update_meta_data( 'tradesafe_id', $response['id'] );
			$order->save();
		}

		$ecentric_request = wp_remote_request( $response['ecentric_payment_url'], array( 'method' => 'GET' ) );
		$ecentric_json    = json_decode( $ecentric_request['body'], true );

		$payments = array(
			'eftsecure' => array(
				'title' => 'Pay via Instant EFT',
				'data'  => $response['eftsecure_payment_url'],
			),
			'ecentric'  => array(
				'title' => 'Ecentric (Credit / Debit Card)',
				'data'  => $ecentric_json['ecentric_payment_button'],
			),
			'manual'    => array(
				'title' => 'Pay via Manual EFT',
				'data'  => $response['payment_url'],
			)
		);

		// Construct variables for post
		$this->data_to_send = array(
			// Merchant details
			'return_url'       => $this->get_return_url( $order ),
			'cancel_url'       => $order->get_cancel_order_url(),
			'notify_url'       => $this->response_url,

			// Billing details
			'name_first'       => self::get_order_prop( $order, 'billing_first_name' ),
			'name_last'        => self::get_order_prop( $order, 'billing_last_name' ),
			'email_address'    => self::get_order_prop( $order, 'billing_email' ),

			// Item details
			'm_payment_id'     => ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce-gateway-tradesafe' ) ),
			'amount'           => $order->get_total(),
			'item_name'        => get_bloginfo( 'name' ) . ' - ' . $order->get_order_number(),
			/* translators: 1: blog info name */
			'item_description' => sprintf( __( 'New order from %s', 'woocommerce-gateway-tradesafe' ), get_bloginfo( 'name' ) ),

			// Custom strings
			'custom_str1'      => self::get_order_prop( $order, 'order_key' ),
			'custom_str2'      => 'WooCommerce/' . WC_VERSION . '; ' . get_site_url(),
			'custom_str3'      => self::get_order_prop( $order, 'id' ),
			'source'           => 'WooCommerce-Free-Plugin',
		);

		$output = $payments['ecentric']['data']
		          . '<a class="button" href="' . $payments['eftsecure']['data'] . '">' . __( $payments['eftsecure']['title'], 'woocommerce-gateway-tradesafe' ) . '</a>'
		          . '<a class="button" target="_blank" href="' . $payments['manual']['data'] . '">' . __( $payments['manual']['title'], 'woocommerce-gateway-tradesafe' ) . '</a>'
		          . '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-tradesafe' ) . '</a>';

		return $output;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {

		if ( $this->order_contains_pre_order( $order_id )
		     && $this->order_requires_payment_tokenization( $order_id )
		     && ! $this->cart_contains_pre_order_fee() ) {
			throw new Exception( 'TradeSafe does not support transactions without any upfront costs or fees. Please select another gateway' );
		}

		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to TradeSafe.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order. Please select one of the payment methods to pay to the TradeSafe escrow account who will keep your funds in trust and will only release your funds to the seller once you are happy with the product or service received.', 'woocommerce-gateway-tradesafe' ) . '</p>';
		echo $this->generate_tradesafe_form( $order );
	}

	/**
	 * Check TradeSafe ITN response.
	 *
	 * @since 1.0.0
	 */
	public function check_itn_response() {
		$this->handle_itn_request( stripslashes_deep( $_POST ) );

		// Notify TradeSafe that information has been received
		header( 'HTTP/1.0 200 OK' );
		flush();
	}

	/**
	 * Check TradeSafe ITN validity.
	 *
	 * @param array $data
	 *
	 * @since 1.0.0
	 */
	public function handle_itn_request( $data ) {
		$this->log( PHP_EOL
		            . '----------'
		            . PHP_EOL . 'TradeSafe ITN call received'
		            . PHP_EOL . '----------'
		);
		$this->log( 'Get posted data' );
		$this->log( 'TradeSafe Data: ' . print_r( $data, true ) );

		$tradesafe_error = false;
		$tradesafe_done  = false;
		$debug_email     = $this->get_option( 'debug_email', get_option( 'admin_email' ) );
		$session_id      = $data['custom_str1'];
		$vendor_name     = get_bloginfo( 'name', 'display' );
		$vendor_url      = home_url( '/' );
		$order_id        = absint( $data['custom_str3'] );
		$order_key       = wc_clean( $session_id );
		$order           = wc_get_order( $order_id );
		$original_order  = $order;

		if ( false === $data ) {
			$tradesafe_error         = true;
			$tradesafe_error_message = PF_ERR_BAD_ACCESS;
		}

		// Verify security signature
		if ( ! $tradesafe_error && ! $tradesafe_done ) {
			$this->log( 'Verify security signature' );
			$signature = md5( $this->_generate_parameter_string( $data, false, false ) ); // false not to sort data
			// If signature different, log for debugging
			if ( ! $this->validate_signature( $data, $signature ) ) {
				$tradesafe_error         = true;
				$tradesafe_error_message = PF_ERR_INVALID_SIGNATURE;
			}
		}

		// Verify source IP (If not in debug mode)
		if ( ! $tradesafe_error && ! $tradesafe_done
		     && $this->get_option( 'testmode' ) != 'yes' ) {
			$this->log( 'Verify source IP' );

			if ( ! $this->is_valid_ip( $_SERVER['REMOTE_ADDR'] ) ) {
				$tradesafe_error         = true;
				$tradesafe_error_message = PF_ERR_BAD_SOURCE_IP;
			}
		}

		// Verify data received
		if ( ! $tradesafe_error ) {
			$this->log( 'Verify data received' );
			$validation_data = $data;
			unset( $validation_data['signature'] );
			$has_valid_response_data = $this->validate_response_data( $validation_data );

			if ( ! $has_valid_response_data ) {
				$tradesafe_error         = true;
				$tradesafe_error_message = PF_ERR_BAD_ACCESS;
			}
		}

		// Check data against internal order
		if ( ! $tradesafe_error && ! $tradesafe_done ) {
			$this->log( 'Check data against internal order' );

			// Check order amount
			if ( ! $this->amounts_equal( $data['amount_gross'], self::get_order_prop( $order, 'order_total' ) )
			     && ! $this->order_contains_pre_order( $order_id )
			     && ! $this->order_contains_subscription( $order_id ) ) {
				$tradesafe_error         = true;
				$tradesafe_error_message = PF_ERR_AMOUNT_MISMATCH;
			} elseif ( strcasecmp( $data['custom_str1'], self::get_order_prop( $order, 'order_key' ) ) != 0 ) {
				// Check session ID
				$tradesafe_error         = true;
				$tradesafe_error_message = PF_ERR_SESSIONID_MISMATCH;
			}
		}

		// alter order object to be the renewal order if
		// the ITN request comes as a result of a renewal submission request
		$description = json_decode( $data['item_description'] );

		if ( ! empty( $description->renewal_order_id ) ) {
			$order = wc_get_order( $description->renewal_order_id );
		}

		// Get internal order and verify it hasn't already been processed
		if ( ! $tradesafe_error && ! $tradesafe_done ) {
			$this->log_order_details( $order );

			// Check if order has already been processed
			if ( 'completed' === self::get_order_prop( $order, 'status' ) ) {
				$this->log( 'Order has already been processed' );
				$tradesafe_done = true;
			}
		}

		// If an error occurred
		if ( $tradesafe_error ) {
			$this->log( 'Error occurred: ' . $tradesafe_error_message );

			if ( $this->send_debug_email ) {
				$this->log( 'Sending email notification' );

				// Send an email
				$subject = 'TradeSafe ITN error: ' . $tradesafe_error_message;
				$body    =
					"Hi,\n\n" .
					"An invalid TradeSafe transaction on your website requires attention\n" .
					"------------------------------------------------------------\n" .
					'Site: ' . esc_html( $vendor_name ) . ' (' . esc_url( $vendor_url ) . ")\n" .
					'Remote IP Address: ' . $_SERVER['REMOTE_ADDR'] . "\n" .
					'Remote host name: ' . gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) . "\n" .
					'Purchase ID: ' . self::get_order_prop( $order, 'id' ) . "\n" .
					'User ID: ' . self::get_order_prop( $order, 'user_id' ) . "\n";
				if ( isset( $data['pf_payment_id'] ) ) {
					$body .= 'TradeSafe Transaction ID: ' . esc_html( $data['pf_payment_id'] ) . "\n";
				}
				if ( isset( $data['payment_status'] ) ) {
					$body .= 'TradeSafe Payment Status: ' . esc_html( $data['payment_status'] ) . "\n";
				}

				$body .= "\nError: " . $tradesafe_error_message . "\n";

				switch ( $tradesafe_error_message ) {
					case PF_ERR_AMOUNT_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['amount_gross'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'order_total' );
						break;

					case PF_ERR_ORDER_ID_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['custom_str3'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'id' );
						break;

					case PF_ERR_SESSIONID_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['custom_str1'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'id' );
						break;

					// For all other errors there is no need to add additional information
					default:
						break;
				}

				wp_mail( $debug_email, $subject, $body );
			} // End if().
		} elseif ( ! $tradesafe_done ) {

			$this->log( 'Check status and update order' );

			if ( self::get_order_prop( $original_order, 'order_key' ) !== $order_key ) {
				$this->log( 'Order key does not match' );
				exit;
			}

			$status = strtolower( $data['payment_status'] );

			$subscriptions = array();
			if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) && function_exists( 'wcs_get_subscriptions_for_order' ) ) {
				$subscriptions = array_merge(
					wcs_get_subscriptions_for_renewal_order( $order_id ),
					wcs_get_subscriptions_for_order( $order_id )
				);
			}

			if ( 'complete' !== $status && 'cancelled' !== $status ) {
				foreach ( $subscriptions as $subscription ) {
					$this->_set_renewal_flag( $subscription );
				}
			}

			if ( 'complete' === $status ) {
				$this->handle_itn_payment_complete( $data, $order, $subscriptions );
			} elseif ( 'failed' === $status ) {
				$this->handle_itn_payment_failed( $data, $order );
			} elseif ( 'pending' === $status ) {
				$this->handle_itn_payment_pending( $data, $order );
			} elseif ( 'cancelled' === $status ) {
				$this->handle_itn_payment_cancelled( $data, $order, $subscriptions );
			}
		} // End if().

		$this->log( PHP_EOL
		            . '----------'
		            . PHP_EOL . 'End ITN call'
		            . PHP_EOL . '----------'
		);

	}

	/**
	 * Handle logging the order details.
	 *
	 * @since 1.4.5
	 */
	public function log_order_details( $order ) {
		if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
			$customer_id = get_post_meta( $order->get_id(), '_customer_user', true );
		} else {
			$customer_id = $order->get_user_id();
		}

		$details = "Order Details:"
		           . PHP_EOL . 'customer id:' . $customer_id
		           . PHP_EOL . 'order id:   ' . $order->get_id()
		           . PHP_EOL . 'parent id:  ' . $order->get_parent_id()
		           . PHP_EOL . 'status:     ' . $order->get_status()
		           . PHP_EOL . 'total:      ' . $order->get_total()
		           . PHP_EOL . 'currency:   ' . $order->get_currency()
		           . PHP_EOL . 'key:        ' . $order->get_order_key()
		           . "";

		$this->log( $details );
	}

	/**
	 * This function mainly responds to ITN cancel requests initiated on TradeSafe, but also acts
	 * just in case they are not cancelled.
	 * @version 1.4.3 Subscriptions flag
	 *
	 * @param array $data should be from the Gatewy ITN callback.
	 * @param WC_Order $order
	 */
	public function handle_itn_payment_cancelled( $data, $order, $subscriptions ) {

		remove_action( 'woocommerce_subscription_status_cancelled', array( $this, 'cancel_subscription_listener' ) );
		foreach ( $subscriptions as $subscription ) {
			if ( 'cancelled' !== $subscription->get_status() ) {
				$subscription->update_status( 'cancelled', __( 'Merchant cancelled subscription on TradeSafe.', 'woocommerce-gateway-tradesafe' ) );
				$this->_delete_subscription_token( $subscription );
			}
		}
		add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'cancel_subscription_listener' ) );
	}

	/**
	 * This function handles payment complete request by TradeSafe.
	 * @version 1.4.3 Subscriptions flag
	 *
	 * @param array $data should be from the Gatewy ITN callback.
	 * @param WC_Order $order
	 */
	public function handle_itn_payment_complete( $data, $order, $subscriptions ) {
		$this->log( '- Complete' );
		$order->add_order_note( __( 'ITN payment completed', 'woocommerce-gateway-tradesafe' ) );
		$order_id = self::get_order_prop( $order, 'id' );

		// Store token for future subscription deductions.
		if ( count( $subscriptions ) > 0 && isset( $data['token'] ) ) {
			if ( $this->_has_renewal_flag( reset( $subscriptions ) ) ) {
				// renewal flag is set to true, so we need to cancel previous token since we will create a new one
				$this->log( 'Cancel previous subscriptions with token ' . $this->_get_subscription_token( reset( $subscriptions ) ) );

				// only request API cancel token for the first subscription since all of them are using the same token
				$this->cancel_subscription_listener( reset( $subscriptions ) );
			}

			$token = sanitize_text_field( $data['token'] );
			foreach ( $subscriptions as $subscription ) {
				$this->_delete_renewal_flag( $subscription );
				$this->_set_subscription_token( $token, $subscription );
			}
		}

		// the same mechanism (adhoc token) is used to capture payment later
		if ( $this->order_contains_pre_order( $order_id )
		     && $this->order_requires_payment_tokenization( $order_id ) ) {

			$token                 = sanitize_text_field( $data['token'] );
			$is_pre_order_fee_paid = get_post_meta( $order_id, '_pre_order_fee_paid', true ) === 'yes';

			if ( ! $is_pre_order_fee_paid ) {
				/* translators: 1: gross amount 2: payment id */
				$order->add_order_note( sprintf( __( 'TradeSafe pre-order fee paid: R %1$s (%2$s)', 'woocommerce-gateway-tradesafe' ), $data['amount_gross'], $data['pf_payment_id'] ) );
				$this->_set_pre_order_token( $token, $order );
				// set order to pre-ordered
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
				update_post_meta( $order_id, '_pre_order_fee_paid', 'yes' );
				WC()->cart->empty_cart();
			} else {
				/* translators: 1: gross amount 2: payment id */
				$order->add_order_note( sprintf( __( 'TradeSafe pre-order product line total paid: R %1$s (%2$s)', 'woocommerce-gateway-tradesafe' ), $data['amount_gross'], $data['pf_payment_id'] ) );
				$order->payment_complete();
				$this->cancel_pre_order_subscription( $token );
			}
		} else {
			$order->payment_complete();
		}

		$debug_email = $this->get_option( 'debug_email', get_option( 'admin_email' ) );
		$vendor_name = get_bloginfo( 'name', 'display' );
		$vendor_url  = home_url( '/' );
		if ( $this->send_debug_email ) {
			$subject = 'TradeSafe ITN on your site';
			$body    =
				"Hi,\n\n"
				. "A TradeSafe transaction has been completed on your website\n"
				. "------------------------------------------------------------\n"
				. 'Site: ' . esc_html( $vendor_name ) . ' (' . esc_url( $vendor_url ) . ")\n"
				. 'Purchase ID: ' . esc_html( $data['m_payment_id'] ) . "\n"
				. 'TradeSafe Transaction ID: ' . esc_html( $data['pf_payment_id'] ) . "\n"
				. 'TradeSafe Payment Status: ' . esc_html( $data['payment_status'] ) . "\n"
				. 'Order Status Code: ' . self::get_order_prop( $order, 'status' );
			wp_mail( $debug_email, $subject, $body );
		}
	}

	/**
	 * @param $data
	 * @param $order
	 */
	public function handle_itn_payment_failed( $data, $order ) {
		$this->log( '- Failed' );
		/* translators: 1: payment status */
		$order->update_status( 'failed', sprintf( __( 'Payment %s via ITN.', 'woocommerce-gateway-tradesafe' ), strtolower( sanitize_text_field( $data['payment_status'] ) ) ) );
		$debug_email = $this->get_option( 'debug_email', get_option( 'admin_email' ) );
		$vendor_name = get_bloginfo( 'name', 'display' );
		$vendor_url  = home_url( '/' );

		if ( $this->send_debug_email ) {
			$subject = 'TradeSafe ITN Transaction on your site';
			$body    =
				"Hi,\n\n" .
				"A failed TradeSafe transaction on your website requires attention\n" .
				"------------------------------------------------------------\n" .
				'Site: ' . esc_html( $vendor_name ) . ' (' . esc_url( $vendor_url ) . ")\n" .
				'Purchase ID: ' . self::get_order_prop( $order, 'id' ) . "\n" .
				'User ID: ' . self::get_order_prop( $order, 'user_id' ) . "\n" .
				'TradeSafe Transaction ID: ' . esc_html( $data['pf_payment_id'] ) . "\n" .
				'TradeSafe Payment Status: ' . esc_html( $data['payment_status'] );
			wp_mail( $debug_email, $subject, $body );
		}
	}

	/**
	 * @since 1.4.0 introduced
	 *
	 * @param $data
	 * @param $order
	 */
	public function handle_itn_payment_pending( $data, $order ) {
		$this->log( '- Pending' );
		// Need to wait for "Completed" before processing
		/* translators: 1: payment status */
		$order->update_status( 'on-hold', sprintf( __( 'Payment %s via ITN.', 'woocommerce-gateway-tradesafe' ), strtolower( sanitize_text_field( $data['payment_status'] ) ) ) );
	}

	/**
	 * @param string $order_id
	 *
	 * @return double
	 */
	public function get_pre_order_fee( $order_id ) {
		foreach ( wc_get_order( $order_id )->get_fees() as $fee ) {
			if ( is_array( $fee ) && 'Pre-Order Fee' == $fee['name'] ) {
				return doubleval( $fee['line_total'] ) + doubleval( $fee['line_tax'] );
			}
		}
	}

	/**
	 * @param string $order_id
	 *
	 * @return bool
	 */
	public function order_contains_pre_order( $order_id ) {
		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			return WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
		}

		return false;
	}

	/**
	 * @param string $order_id
	 *
	 * @return bool
	 */
	public function order_requires_payment_tokenization( $order_id ) {
		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			return WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id );
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function cart_contains_pre_order_fee() {
		if ( class_exists( 'WC_Pre_Orders_Cart' ) ) {
			return WC_Pre_Orders_Cart::cart_contains_pre_order_fee();
		}

		return false;
	}

	/**
	 * Store the TradeSafe subscription token
	 *
	 * @param string $token
	 * @param WC_Subscription $subscription
	 */
	protected function _set_subscription_token( $token, $subscription ) {
		update_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_subscription_token', $token );
	}

	/**
	 * Retrieve the TradeSafe subscription token for a given order id.
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return mixed
	 */
	protected function _get_subscription_token( $subscription ) {
		return get_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_subscription_token', true );
	}

	/**
	 * Retrieve the TradeSafe subscription token for a given order id.
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return mixed
	 */
	protected function _delete_subscription_token( $subscription ) {
		return delete_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_subscription_token' );
	}

	/**
	 * Store the TradeSafe renewal flag
	 * @since 1.4.3
	 *
	 * @param string $token
	 * @param WC_Subscription $subscription
	 */
	protected function _set_renewal_flag( $subscription ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			update_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_renewal_flag', 'true' );
		} else {
			$subscription->update_meta_data( '_tradesafe_renewal_flag', 'true' );
			$subscription->save_meta_data();
		}
	}

	/**
	 * Retrieve the TradeSafe renewal flag for a given order id.
	 * @since 1.4.3
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return bool
	 */
	protected function _has_renewal_flag( $subscription ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			return 'true' === get_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_renewal_flag', true );
		} else {
			return 'true' === $subscription->get_meta( '_tradesafe_renewal_flag', true );
		}
	}

	/**
	 * Retrieve the TradeSafe renewal flag for a given order id.
	 * @since 1.4.3
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return mixed
	 */
	protected function _delete_renewal_flag( $subscription ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			return delete_post_meta( self::get_order_prop( $subscription, 'id' ), '_tradesafe_renewal_flag' );
		} else {
			$subscription->delete_meta_data( '_tradesafe_renewal_flag' );
			$subscription->save_meta_data();
		}
	}

	/**
	 * Store the TradeSafe pre_order_token token
	 *
	 * @param string $token
	 * @param WC_Order $order
	 */
	protected function _set_pre_order_token( $token, $order ) {
		update_post_meta( self::get_order_prop( $order, 'id' ), '_tradesafe_pre_order_token', $token );
	}

	/**
	 * Retrieve the TradeSafe pre-order token for a given order id.
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	protected function _get_pre_order_token( $order ) {
		return get_post_meta( self::get_order_prop( $order, 'id' ), '_tradesafe_pre_order_token', true );
	}

	/**
	 * Wrapper function for wcs_order_contains_subscription
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function order_contains_subscription( $order ) {
		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		}

		return wcs_order_contains_subscription( $order );
	}

	/**
	 * @param $amount_to_charge
	 * @param WC_Order $renewal_order
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {

		$subscription = wcs_get_subscription( get_post_meta( self::get_order_prop( $renewal_order, 'id' ), '_subscription_renewal', true ) );
		$this->log( 'Attempting to renew subscription from renewal order ' . self::get_order_prop( $renewal_order, 'id' ) );

		if ( empty( $subscription ) ) {
			$this->log( 'Subscription from renewal order was not found.' );

			return;
		}

		$response = $this->submit_subscription_payment( $subscription, $amount_to_charge );

		if ( is_wp_error( $response ) ) {
			/* translators: 1: error code 2: error message */
			$renewal_order->update_status( 'failed', sprintf( __( 'TradeSafe Subscription renewal transaction failed (%1$s:%2$s)', 'woocommerce-gateway-tradesafe' ), $response->get_error_code(), $response->get_error_message() ) );
		}
		// Payment will be completion will be capture only when the ITN callback is sent to $this->handle_itn_request().
		$renewal_order->add_order_note( __( 'TradeSafe Subscription renewal transaction submitted.', 'woocommerce-gateway-tradesafe' ) );

	}

	/**
	 * @param WC_Subscription $subscription
	 * @param $amount_to_charge
	 *
	 * @return mixed WP_Error on failure, bool true on success
	 */
	public function submit_subscription_payment( $subscription, $amount_to_charge ) {
		$token     = $this->_get_subscription_token( $subscription );
		$item_name = $this->get_subscription_name( $subscription );

		foreach ( $subscription->get_related_orders( 'all', 'renewal' ) as $order ) {
			$statuses_to_charge = array( 'on-hold', 'failed', 'pending' );
			if ( in_array( $order->get_status(), $statuses_to_charge ) ) {
				$latest_order_to_renew = $order;
				break;
			}
		}
		$item_description = json_encode( array( 'renewal_order_id' => self::get_order_prop( $latest_order_to_renew, 'id' ) ) );

		return $this->submit_ad_hoc_payment( $token, $amount_to_charge, $item_name, $item_description );
	}

	/**
	 * Get a name for the subscription item. For multiple
	 * item only Subscription $date will be returned.
	 *
	 * For subscriptions with no items Site/Blog name will be returned.
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return string
	 */
	public function get_subscription_name( $subscription ) {

		if ( $subscription->get_item_count() > 1 ) {
			return $subscription->get_date_to_display( 'start' );
		} else {
			$items = $subscription->get_items();

			if ( empty( $items ) ) {
				return get_bloginfo( 'name' );
			}

			$item = array_shift( $items );

			return $item['name'];
		}
	}

	/**
	 * Setup api data for the the adhoc payment.
	 *
	 * @since 1.4.0 introduced.
	 *
	 * @param string $token
	 * @param double $amount_to_charge
	 * @param string $item_name
	 * @param string $item_description
	 *
	 * @return bool|WP_Error
	 */
	public function submit_ad_hoc_payment( $token, $amount_to_charge, $item_name, $item_description ) {
		$args = array(
			'body' => array(
				'amount'           => $amount_to_charge * 100, // convert to cents
				'item_name'        => $item_name,
				'item_description' => $item_description,
			),
		);
//		return $this->api_request( 'adhoc', $token, $args );
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
	public function api_request( $command, $api_args, $method = 'POST' ) {
		$token = $this->get_option( 'json_web_token' );
		if ( empty( $token ) ) {
			$this->log( "Error posting API request: No token supplied", true );

			return new WP_Error( '404', __( 'A token is required to submit a request to the TradeSafe API', 'woocommerce-gateway-tradesafe' ), null );
		}

		// Setup the test data, if in test mode.
		if ( 'yes' === $this->get_option( 'testmode' ) ) {
			$this->url = 'https://sandbox.tradesafe.co.za/api';
			$this->url = 'http://local.tradesafe.co.za/api';
		}

		$api_endpoint = sprintf( '%s/%s', $this->url, $command );

		$api_args['timeout'] = 45;
		$api_args['headers'] = array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		);

		if ( isset( $api_args['body'] ) ) {
			$api_args['body'] = json_encode( $api_args['body'] );
		}
		$api_args['method'] = strtoupper( $method );

		$results = wp_remote_request( $api_endpoint, $api_args );

		if ( is_object( $results ) && 'WP_Error' === get_class( $results ) ) {
			return $results;
		}

		if ( isset( $results['response']['code'] ) && 200 !== $results['response']['code'] && 201 !== $results['response']['code'] ) {
			$this->log( "Error posting API request:\n" . print_r( $results['response'], true ) );
			$message        = json_decode( $results['body'], true );
			$message_string = implode( '<br />', $message['errors'] );

			return new WP_Error( $results['response']['code'], $message_string, $results );
		}

		$maybe_json = json_decode( $results['body'], true );

		if ( ! is_null( $maybe_json ) && isset( $maybe_json['error'] ) ) {
			$this->log( "Error posting API request:\n" . print_r( $results['body'], true ) );

			// Use trim here to display it properly e.g. on an order note, since TradeSafe can include CRLF in a message.
			return new WP_Error( 422, trim( $maybe_json['error'] ), $results['body'] );
		}

		return $maybe_json;
	}

	/**
	 * Responds to Subscriptions extension cancellation event.
	 *
	 * @since 1.4.0 introduced.
	 *
	 * @param WC_Subscription $subscription
	 */
	public function cancel_subscription_listener( $subscription ) {
		$token = $this->_get_subscription_token( $subscription );
		if ( empty( $token ) ) {
			return;
		}
//		$this->api_request( 'cancel', $token, array(), 'PUT' );
	}

	/**
	 * @since 1.4.0
	 *
	 * @param string $token
	 *
	 * @return bool|WP_Error
	 */
	public function cancel_pre_order_subscription( $token ) {
//		return $this->api_request( 'cancel', $token, array(), 'PUT' );
	}

	/**
	 * @since 1.4.0 introduced.
	 *
	 * @param      $api_data
	 * @param bool $sort_data_before_merge ? default true.
	 * @param bool $skip_empty_values Should key value pairs be ignored when generating signature?  Default true.
	 *
	 * @return string
	 */
	protected function _generate_parameter_string( $api_data, $sort_data_before_merge = true, $skip_empty_values = true ) {

		// if sorting is required the passphrase should be added in before sort.
		if ( ! empty( $this->pass_phrase ) && $sort_data_before_merge ) {
			$api_data['passphrase'] = $this->pass_phrase;
		}

		if ( $sort_data_before_merge ) {
			ksort( $api_data );
		}

		// concatenate the array key value pairs.
		$parameter_string = '';
		foreach ( $api_data as $key => $val ) {

			if ( $skip_empty_values && empty( $val ) ) {
				continue;
			}

			if ( 'signature' !== $key ) {
				$val              = urlencode( $val );
				$parameter_string .= "$key=$val&";
			}
		}
		// when not sorting passphrase should be added to the end before md5
		if ( $sort_data_before_merge ) {
			$parameter_string = rtrim( $parameter_string, '&' );
		} elseif ( ! empty( $this->pass_phrase ) ) {
			$parameter_string .= 'passphrase=' . urlencode( $this->pass_phrase );
		} else {
			$parameter_string = rtrim( $parameter_string, '&' );
		}

		return $parameter_string;
	}

	/**
	 * @since 1.4.0 introduced.
	 *
	 * @param WC_Order $order
	 */
	public function process_pre_order_payments( $order ) {

		// The total amount to charge is the the order's total.
		$total = $order->get_total() - $this->get_pre_order_fee( self::get_order_prop( $order, 'id' ) );
		$token = $this->_get_pre_order_token( $order );

		if ( ! $token ) {
			return;
		}
		// get the payment token and attempt to charge the transaction
		$item_name = 'pre-order';
		$results   = $this->submit_ad_hoc_payment( $token, $total, $item_name );

		if ( is_wp_error( $results ) ) {
			/* translators: 1: error code 2: error message */
			$order->update_status( 'failed', sprintf( __( 'TradeSafe Pre-Order payment transaction failed (%1$s:%2$s)', 'woocommerce-gateway-tradesafe' ), $results->get_error_code(), $results->get_error_message() ) );

			return;
		}

		// Payment completion will be handled by ITN callback
	}

	/**
	 * Setup constants.
	 *
	 * Setup common values and messages used by the TradeSafe gateway.
	 *
	 * @since 1.0.0
	 */
	public function setup_constants() {
		// Create user agent string.
		define( 'PF_SOFTWARE_NAME', 'WooCommerce' );
		define( 'PF_SOFTWARE_VER', WC_VERSION );
		define( 'PF_MODULE_NAME', 'WooCommerce-TradeSafe-Free' );
		define( 'PF_MODULE_VER', $this->version );

		// Features
		// - PHP
		$pf_features = 'PHP ' . phpversion() . ';';

		// - cURL
		if ( in_array( 'curl', get_loaded_extensions() ) ) {
			define( 'PF_CURL', '' );
			$pf_version  = curl_version();
			$pf_features .= ' curl ' . $pf_version['version'] . ';';
		} else {
			$pf_features .= ' nocurl;';
		}

		// Create user agrent
		define( 'PF_USER_AGENT', PF_SOFTWARE_NAME . '/' . PF_SOFTWARE_VER . ' (' . trim( $pf_features ) . ') ' . PF_MODULE_NAME . '/' . PF_MODULE_VER );

		// General Defines
		define( 'PF_TIMEOUT', 15 );
		define( 'PF_EPSILON', 0.01 );

		// Messages
		// Error
		define( 'PF_ERR_AMOUNT_MISMATCH', __( 'Amount mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_BAD_ACCESS', __( 'Bad access of page', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_BAD_SOURCE_IP', __( 'Bad source IP address', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_CONNECT_FAILED', __( 'Failed to connect to TradeSafe', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_INVALID_SIGNATURE', __( 'Security signature mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_MERCHANT_ID_MISMATCH', __( 'Merchant ID mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_NO_SESSION', __( 'No saved session found for ITN transaction', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_ORDER_ID_MISSING_URL', __( 'Order ID not present in URL', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_ORDER_ID_MISMATCH', __( 'Order ID mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_ORDER_INVALID', __( 'This order ID is invalid', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_ORDER_NUMBER_MISMATCH', __( 'Order Number mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_ORDER_PROCESSED', __( 'This order has already been processed', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_PDT_FAIL', __( 'PDT query failed', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_PDT_TOKEN_MISSING', __( 'PDT token not present in URL', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_SESSIONID_MISMATCH', __( 'Session ID mismatch', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_ERR_UNKNOWN', __( 'Unkown error occurred', 'woocommerce-gateway-tradesafe' ) );

		// General
		define( 'PF_MSG_OK', __( 'Payment was successful', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_MSG_FAILED', __( 'Payment has failed', 'woocommerce-gateway-tradesafe' ) );
		define( 'PF_MSG_PENDING', __( 'The payment is pending. Please note, you will receive another Instant Transaction Notification when the payment status changes to "Completed", or "Failed"', 'woocommerce-gateway-tradesafe' ) );

		do_action( 'woocommerce_gateway_tradesafe_setup_constants' );
	}

	/**
	 * Log system processes.
	 * @since 1.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->get_option( 'testmode' ) || $this->enable_logging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'tradesafe', $message );
		}
	}

	/**
	 * validate_signature()
	 *
	 * Validate the signature against the returned data.
	 *
	 * @param array $data
	 * @param string $signature
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function validate_signature( $data, $signature ) {
		$result = $data['signature'] === $signature;
		$this->log( 'Signature = ' . ( $result ? 'valid' : 'invalid' ) );

		return $result;
	}

	/**
	 * Validate the IP address to make sure it's coming from TradeSafe.
	 *
	 * @param array $source_ip
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_valid_ip( $source_ip ) {
		// Variable initialization
		$valid_hosts = array(
			'www.tradesafe.co.za',
			'sandbox.tradesafe.co.za',
		);

		$valid_ips = array();

		foreach ( $valid_hosts as $pf_hostname ) {
			$ips = gethostbynamel( $pf_hostname );

			if ( false !== $ips ) {
				$valid_ips = array_merge( $valid_ips, $ips );
			}
		}

		// Remove duplicates
		$valid_ips = array_unique( $valid_ips );

		// Adds support for X_Forwarded_For
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$source_ip = (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ) ?: $source_ip;
		}

		$this->log( "Valid IPs:\n" . print_r( $valid_ips, true ) );
		$is_valid_ip = in_array( $source_ip, $valid_ips );

		return apply_filters( 'woocommerce_gateway_tradesafe_is_valid_ip', $is_valid_ip, $source_ip );
	}

	/**
	 * validate_response_data()
	 *
	 * @param array $post_data
	 * @param string $proxy Address of proxy to use or NULL if no proxy.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function validate_response_data( $post_data, $proxy = null ) {
		$this->log( 'Host = ' . $this->validate_url );
		$this->log( 'Params = ' . print_r( $post_data, true ) );

		if ( ! is_array( $post_data ) ) {
			return false;
		}

		$response = wp_remote_post( $this->validate_url, array(
			'body'       => $post_data,
			'timeout'    => 70,
			'user-agent' => PF_USER_AGENT,
		) );

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			$this->log( "Response error:\n" . print_r( $response, true ) );

			return false;
		}

		parse_str( $response['body'], $parsed_response );

		$response = $parsed_response;

		$this->log( "Response:\n" . print_r( $response, true ) );

		// Interpret Response
		if ( is_array( $response ) && in_array( 'VALID', array_keys( $response ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * amounts_equal()
	 *
	 * Checks to see whether the given amounts are equal using a proper floating
	 * point comparison with an Epsilon which ensures that insignificant decimal
	 * places are ignored in the comparison.
	 *
	 * eg. 100.00 is equal to 100.0001
	 *
	 * @author Jonathan Smit
	 *
	 * @param $amount1 Float 1st amount for comparison
	 * @param $amount2 Float 2nd amount for comparison
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function amounts_equal( $amount1, $amount2 ) {
		return ! ( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > PF_EPSILON );
	}

	/**
	 * Get order property with compatibility check on order getter introduced
	 * in WC 3.0.
	 *
	 * @since 1.4.1
	 *
	 * @param WC_Order $order Order object.
	 * @param string $prop Property name.
	 *
	 * @return mixed Property value
	 */
	public static function get_order_prop( $order, $prop ) {
		switch ( $prop ) {
			case 'order_total':
				$getter = array( $order, 'get_total' );
				break;
			default:
				$getter = array( $order, 'get_' . $prop );
				break;
		}

		return is_callable( $getter ) ? call_user_func( $getter ) : $order->{$prop};
	}

	/**
	 *  Show possible admin notices
	 */
	public function admin_notices() {
		if ( 'yes' !== $this->get_option( 'enabled' ) ) {
			return;
		}

		if ( empty( $this->api_key ) ) {
			echo '<div class="error tradesafe-passphrase-message"><p>'
			     . '<strong>'
			     . __( 'TradeSafe Escrow: ', 'woocommerce-gateway-tradesafe' )
			     . '</strong><br />'
			     . __( 'TradeSafe requires a token to work.', 'woocommerce-gateway-tradesafe' )
			     . '</p></div>';
		}
	}


	/**
	 * Check if ID number is valid.
	 *
	 * Format:
	 * {YYMMDD}{G}{SSS}{C}{A}{Z}
	 * YYMMDD : Date of birth.
	 * G  : Gender. 0-4 Female; 5-9 Male.
	 * SSS  : Sequence No. for DOB/G combination.
	 * C  : Citizenship. 0 SA; 1 Other.
	 * A  : Usually 8, or 9 [can be other values]
	 * Z  : Control digit
	 */
	public function valid_id_number( $number ) {
		// Fail if empty
		if ( $number == '' ) {
			return false;
		}

		// Fail if number is too short.
		if ( strlen( $number ) != 13 ) {
			return false;
		}

		$year  = substr( $number, 0, 2 );
		$month = substr( $number, 2, 2 );
		$day   = substr( $number, 4, 2 );

		$current_year    = date( 'y' ) - 16;
		$current_century = substr( date( 'Y' ), 0, 2 );

		if ( $year >= $current_year ) {
			$century = $current_century - 1;
		} else {
			$century = $current_century;
		}

		// Fail if date is invalid.
		if ( ! checkdate( $month, $day, $century . $year ) ) {
			return false;
		}

		// Fail if 11th digit is not 0 or 1.
		if ( $number{10} != 0 && $number{10} != 1 ) {
			return false;
		}

		$odd         = 0;
		$even        = 0;
		$even_string = '';

		for ( $i = 1; $i <= 12; $i ++ ) {
			if ( $i % 2 ) {
				$odd += $number{$i - 1};
			} else {
				$even_string .= $number{$i - 1};
			}
		}

		$even_string = (int) $even_string * 2;
		settype( $even_string, 'string' );

		for ( $x = 0; $x < strlen( $even_string ); $x ++ ) {
			$even += (int) $even_string{$x};
		}

		$total = substr( $odd + $even, - 1 );

		if ( $total > 0 ) {
			$check = 10 - $total;
		} else {
			$check = $total;
		}

		// Pass if check matches control digit otherwise fail.
		if ( $check == $number{12} ) {
			return true;
		} else {
			return false;
		}
	}
}
