<?php

/**
 * Class TradeSafeAPIWrapper
 */
class TradeSafeAPIWrapper {
	// Variables
	private $token = '';
	public $endpoint = '';
	private $production = false;
	private $debugging = false;

	/**
	 * TradeSafeAPIWrapper constructor.
	 */
	public function __construct() {
		$this->debugging  = get_option( 'tradesafe_api_debugging', false );
		$this->production = get_option( 'tradesafe_api_production', false );

		if ( $this->debugging && '' !== TRADESAFE_API_DEBUG_DOMAIN ) {
			$domain = TRADESAFE_API_DEBUG_DOMAIN;
		} else {
			if ( $this->production ) {
				$domain = TRADESAFE_API_PROD_DOMAIN;
			} else {
				$domain = TRADESAFE_API_TEST_DOMAIN;
			}
		}

		$this->token    = get_option( 'tradesafe_api_token' );
		$this->endpoint = sprintf( 'https://%s/api', $domain );
	}

	private function request( $route, $data = null, $method = 'GET' ) {
		if ( empty( $this->token ) ) {
			$this->log( "No token configured", true );

			return new WP_Error( '400', __( 'A token is required to submit a request to the TradeSafe API', TRADESAFE_PLUGIN_NAME ), null );
		}

		// Create request
		$url     = $this->endpoint . '/' . $route;
		$request = [
			'timeout' => 60,
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			]
		];

		if ( isset( $data ) ) {
			$request['body'] = json_encode( $data );
		}

		// Needed when using custom certs in a local test env
		if ( $this->debugging && '' !== TRADESAFE_API_DEBUG_CA_PATH ) {
			$request['sslcertificates'] = TRADESAFE_API_DEBUG_CA_PATH;
		}

		// Send the request
		$response = wp_remote_request( $url, $request );

		// Check for error
		if ( is_object( $response ) && 'WP_Error' === get_class( $response ) ) {
			$this->log( $response );

			return $response;
		}

		$response_body = json_decode( $response['body'], true );

		if ( ! isset( $response['response']['code'] ) ) {
			$response['response']['code'] = 500;
		}

		switch ( $response['response']['code'] ) {
			case 200:
			case 201:
				return $response_body;
				break;
			default:
				$this->log( $response );
				$messages = [];

				if ( isset( $response_body['error'] ) ) {
					$messages[] = $response_body['error'];
				}

				if ( isset( $response_body['errors'] ) ) {
					foreach ( $response_body['errors'] as $error ) {
						$messages[] = $error;
					}
				}

				$message_string = implode( '<br />', $messages );

				return new WP_Error( $response['response']['code'], $message_string, $response );
		}
	}

	/**
	 * @param $message
	 */
	private function log( $message ) {
		if ( $this->debugging ) {
			if ( empty( $this->logger ) ) {
				$this->logger = new WC_Logger();
			}

			$error_message = '';
			if ( is_wp_error( $message ) ) {
				foreach ( $message->errors as $errors ) {
					foreach ( $errors as $error_code => $error ) {
						$error_message .= sprintf( "%s: %s\n", $error_code, $error );
					}
				}
			} elseif ( is_string( $message ) ) {
				$error_message = $message;
			} else {
				$error_message = serialize( $message );
			}

			$this->logger->add( TRADESAFE_PLUGIN_NAME, $error_message );
			error_log( json_encode( $error_message ) );
		}
	}

	/**
	 * @return array|mixed|object|WP_Error
	 */
	public function auth_token() {
		return $this->request( 'authorize/token' );
	}

	/**
	 * @return array|WP_Error
	 */
	public function owner() {
		static $owner;

		if ( ! isset( $owner ) || is_wp_error( $owner ) ) {
			$owner = $this->request( 'verify/owner' );;
		}

		return $owner;
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	public function constant( $type ) {
		static $constants = [];

		if ( ! isset( $constants[ $type ] ) ) {
			$constants[ $type ] = $this->request( 'constant/' . $type );
		}

		return $constants[ $type ];
	}

	/**
	 * @param $id
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_user( $id ) {
		return $this->request( 'user/' . $id );
	}

	/**
	 * @param $data
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function verify_user( $data ) {
		return $this->request( 'verify/user', $data, 'POST' );
	}

	/**
	 * @param $data
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function add_user( $data ) {
		return $this->request( 'user', $data, 'POST' );
	}

	/**
	 * @param $id
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_contract( $id ) {
		return $this->request( 'contract/' . $id );
	}

	/**
	 * @param $data
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function verify_contract( $data ) {
		return $this->request( 'validate/contract', $data, 'POST' );
	}

	/**
	 * @param $data
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function add_contract( $data ) {
		return $this->request( 'contract', $data, 'POST' );
	}

	/**
	 * @param $data
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function update_contract( $id, $data ) {
		return $this->request( 'contract/' . $id, $data, 'PUT' );
	}
}
