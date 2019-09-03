<?php

/**
 * Class TradeSafeProfile
 */
class TradeSafeProfile {
	private static $initiated = false;

	public static function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	// Initializes WordPress hooks
	private static function init_hooks() {
		self::$initiated = true;

		// Actions
		add_action( 'show_user_profile', [ 'TradeSafeProfile', 'view' ] );
		add_action( 'edit_user_profile', [ 'TradeSafeProfile', 'view' ] );
		add_action( 'woocommerce_account_tradesafe_endpoint', [ 'TradeSafeProfile', 'view' ] );
		add_action( 'register_form', [ 'TradeSafeProfile', 'registration_form' ] );
		add_action( 'woocommerce_register_form_start', [ 'TradeSafeProfile', 'registration_form' ] );
		add_action( 'woocommerce_register_post', [ 'TradeSafeProfile', 'my_account_registration_errors' ], 10, 3 );
		add_action( 'user_register', [ 'TradeSafeProfile', 'user_register' ] );
		add_action( 'woocommerce_created_customer', [ 'TradeSafeProfile', 'user_register' ] );
		add_action( 'wp_ajax_nopriv_woocommerce_tradesafe_ajax_login', [ 'TradeSafeProfile', 'ajax_login' ] );

		// Filters
		add_filter( 'woocommerce_account_menu_items', [ 'TradeSafeProfile', 'menu_link' ], 40 );
		add_filter( 'registration_errors', [ 'TradeSafeProfile', 'registration_errors' ], 10, 3 );
	}

	/**
	 * @param $user
	 */
	public static function view( $user ) {
		if ( '' === $user ) {
			$user = wp_get_current_user();
		}

		$tradesafe           = new TradeSafeAPIWrapper();
		$tradesafe_id        = get_user_meta( $user->ID, 'tradesafe_user_id', true );
		$tradesafe_user_data = $tradesafe->get_user( $tradesafe_id );

		if ( ! is_admin() ) {
			if ( '' === $tradesafe_id ) {
				$token_cache_id   = 'tradesafe-token-' . $user->ID;
				$token            = get_transient( $token_cache_id );
				$url              = $tradesafe->endpoint;
				$auth_key         = $tradesafe->owner()['id'];
				$edit_account_url = wc_get_endpoint_url( 'tradesafe', '', wc_get_page_permalink( 'myaccount' ) );

				if ( false === $token ) {
					$token_request = $tradesafe->auth_token();
					if ( ! is_wp_error( $token_request ) ) {
						$token_lifetime = $token_request['expire'] - $token_request['created'];
						$token          = $token_request['token'];
						set_transient( $token_cache_id, $token, $token_lifetime );
					}
				}

				require_once TRADESAFE_PLUGIN_DIR . '/templates/profile-edit.php';
			} else {
				if ( ! is_wp_error( $tradesafe_user_data ) ) {
					$profile['user']['name']['title'] = 'Name';
					$profile['user']['name']['value'] = $tradesafe_user_data['first_name'] . ' ' . $tradesafe_user_data['last_name'];

					$profile['user']['id_number']['title'] = 'ID Number';
					$profile['user']['id_number']['value'] = $tradesafe_user_data['id_number'];

					$profile['user']['email']['title'] = 'Email';
					$profile['user']['email']['value'] = $tradesafe_user_data['email'];

					$profile['user']['mobile']['title'] = 'Mobile';
					$profile['user']['mobile']['value'] = $tradesafe_user_data['mobile'];

					if ( isset( $tradesafe_user_data['company'] ) && '' !== $tradesafe_user_data['company']['name'] ) {
						$profile['company']['name']['title'] = 'Name';
						$profile['company']['name']['value'] = $tradesafe_user_data['company']['name'] . ' ' . $tradesafe_user_data['company']['type'];

						$profile['company']['reg']['title'] = 'Registration Number';
						$profile['company']['reg']['value'] = $tradesafe_user_data['company']['reg_number'];
					}

					if ( isset( $tradesafe_user_data['bank'] ) ) {
						$profile['bank']['name']['title'] = 'Bank';
						$profile['bank']['name']['value'] = $tradesafe_user_data['bank']['name'];

						$profile['bank']['number']['title'] = 'Account Number';
						$profile['bank']['number']['value'] = $tradesafe_user_data['bank']['account'];

						$profile['bank']['type']['title'] = 'Account Type';
						$profile['bank']['type']['value'] = $tradesafe_user_data['bank']['type'];
					}
				}

				require_once TRADESAFE_PLUGIN_DIR . '/templates/profile-view.php';
			}
		}
	}

	/**
	 * @param $menu_links
	 *
	 * @return array
	 */
	public static function menu_link( $menu_links ) {
		$menu_links = array_slice( $menu_links, 0, 5, true )
					  + array( 'tradesafe' => 'TradeSafe' )
					  + array_slice( $menu_links, 5, null, true );

		return $menu_links;
	}

	/**
	 * Ajax Login
	 */
	public static function ajax_login() {
		$tradesafe     = new TradeSafeAPIWrapper();
		$request_token = $tradesafe->auth_token();
		$request_owner = $tradesafe->owner();
		$data          = array(
			'success_url' => $_POST['page_url'],
			'failure_url' => $_POST['page_url'],
		);

		if ( ! is_wp_error( $request_token ) && ! is_wp_error( $request_owner ) ) {
			$data['auth_key']   = $request_owner['id'];
			$data['auth_token'] = $request_token['token'];

			print json_encode( $data );
		}

		die();
	}

	/**
	 * Process authorisation callback
	 */
	public static function callback_auth() {
		$json = file_get_contents( 'php://input' );
		$data = json_decode( $json, true );

		if ( isset( $data['user_id'] ) && isset( $data['parameters']['user_id'] ) ) {
			update_user_meta( $data['parameters']['user_id'], 'tradesafe_user_id', $data['user_id'] );
			status_header( 200 );
		} else {
			status_header( 404 );
			include get_query_template( '404' );
		}

		die();
	}

	/**
	 * Unlink account
	 */
	public static function unlink() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			delete_user_meta( $user->ID, 'tradesafe_user_id' );
			$edit_account_url = wc_get_endpoint_url( 'tradesafe', '', wc_get_page_permalink( 'myaccount' ) );
			wp_redirect( $edit_account_url );
		} else {
			status_header( 404 );
			include get_query_template( '404' );
			die();
		}
	}

	/**
	 * Registration Form
	 */
	public static function registration_form() {
		$tradesafe = new TradeSafeAPIWrapper();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'woocommerce-tradesafe-register-js', plugins_url( '/assets/js/register.js', 'woocommerce-tradesafe-gateway' ) );

		wp_register_script( 'tradesafe-settings', false );
		wp_localize_script( 'tradesafe-settings', 'tradesafe_params', array( 'api_url' => $tradesafe->endpoint ) );
		wp_enqueue_script( 'tradesafe-settings' );

		if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
			$request = $tradesafe->get_user( $_GET['auth_key'] );

			if ( ! is_wp_error( $request ) ) {
				$_POST['email'] = esc_html( $_GET['email'] );
				require_once TRADESAFE_PLUGIN_DIR . '/tamplates/register-token.php';
			}
		} else {
			$bank_account_types = $tradesafe->constant( 'bank-account-types' );
			$cbc_list           = $tradesafe->constant( 'bank-codes' );

			$user_fields = [
				'first_name'    => array( 'First Name', 'text' ),
				'last_name'     => array( 'Last Name', 'text' ),
				'mobile_number' => array( 'Mobile Number', 'text' ),
				'id_number'     => array( 'ID Number', 'text' ),
			];

			$bank_fields = [
				'bank_name'    => array( 'Bank', 'select', $cbc_list ),
				'bank_account' => array( 'Account Number', 'text' ),
				// 'bank_branch'  => array( 'Branch', 'text' ),
				'bank_type'    => array( 'Account Type', 'select', $bank_account_types ),
			];

			require_once TRADESAFE_PLUGIN_DIR . '/templates/register.php';
		}
	}

	/**
	 * @param $errors
	 * @param $sanitized_user_login
	 * @param $user_email
	 *
	 * @return mixed
	 */
	public static function registration_errors( $errors, $sanitized_user_login, $user_email ) {
		return self::_validate_registration_form( $errors, $user_email );
	}

	/**
	 * @param $username
	 * @param $email
	 * @param $validation_errors
	 *
	 * @return mixed
	 */
	public static function my_account_registration_errors( $username, $email, $validation_errors ) {
		return self::_validate_registration_form( $validation_errors, $email );
	}

	/**
	 * @param $errors
	 * @param $user_email
	 *
	 * @return mixed
	 */
	public static function _validate_registration_form( $errors, $user_email ) {
		if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
			$token              = get_option( 'tradesafe_api_token' );
			$verification_token = hash( 'sha256', $token . $_GET['auth_key'] . $_GET['email'] );

			if ( $_GET['verify'] !== $verification_token ) {
				$errors->add( 'error', __( 'Invalid verification token', 'woocommerce-tradesafe-gateway' ) );
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

			$tradesafe = new TradeSafeAPIWrapper();
			$request   = $tradesafe->verify_user( $user );
			$logger    = new WC_Logger();

			if ( is_wp_error( $request ) ) {
				$message[] = __( 'Account Creation Failed.', 'woocommerce-tradesafe-gateway' );

				foreach ( $request->errors as $error_messages ) {
					foreach ( $error_messages as $error_message ) {
						$message[] = $error_message;
					}
				}

				$errors->add( 'error', implode( '<br />', $message ) );
				$logger->add( 'tradesafe', 'Verified Failed: ' . __( $request->get_error_message(), 'woocommerce-tradesafe-gateway' ) );
			}

			$logger->add( 'tradesafe', 'Verified User' );
		}

		return $errors;
	}

	/**
	 * @param $user_id
	 */
	public static function user_register( $user_id ) {
		$tradesafe = new TradeSafeAPIWrapper();
		$user      = get_user_by( 'ID', $user_id );

		if ( isset( $_GET['auth_key'] ) && isset( $_GET['verify'] ) ) {
			$settings           = get_option( 'woocommerce_tradesafe_settings' );
			$token              = $settings['json_web_token'];
			$verification_token = hash( 'sha256', $token . $_GET['auth_key'] . $_GET['email'] );

			if ( $_GET['verify'] === $verification_token ) {
				update_user_meta( $user_id, 'tradesafe_user_id', $_GET['auth_key'] );
			}
		} else {
			$user = [
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
			];

			$request = $tradesafe->add_user( $user );

			$logger = new WC_Logger();
			if ( ! is_wp_error( $request ) ) {
				update_user_meta( $user_id, 'tradesafe_user_id', $request['user_id'] );
				$logger->add( 'tradesafe', 'Created / Linked User Account ' . $user_id . '-' . $request['user_id'] );
			} else {
				$logger->add( 'tradesafe', 'Account Creation Failed ' . __( $request->get_error_message(), 'woocommerce-tradesafe-gateway' ) );
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
}
