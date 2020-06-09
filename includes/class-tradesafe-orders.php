<?php

/**
 * Class TradeSafe_Orders
 */
class TradeSafe_Orders {
	// Define Variables
	private static $initiated = false;

	/**
	 * Init
	 */
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
		add_action( 'woocommerce_order_status_pending_to_cancelled', [ 'TradeSafe_Orders', 'cancel' ], 0, 1 );
		add_action( 'woocommerce_cancelled_order', [ 'TradeSafe_Orders', 'cancel' ] );

		// Filters
		add_filter( 'woocommerce_my_account_my_orders_actions', [ 'TradeSafe_Orders', 'orders_actions' ], 100, 2 );
	}

	/**
	 * @param $order_id
	 */
	public static function accept( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafe_API_Wrapper();

		$data = array(
			'step' => 'GOODS_ACCEPTED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'completed', sprintf( __( 'Accepted Goods or Services.', 'woocommerce-tradesafe-gateway' ) ) );

			$redirect = home_url( '/my-account/orders/' );
			wp_redirect( $redirect );
		} else {
			wp_die(
				$response,
				'An error occurred',
				[
					'response'  => 400,
					'back_link' => true,
				]
			);
		}
	}

	/**
	 * @param $order_id
	 */
	public static function extend( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafe_API_Wrapper();
		$response     = $tradesafe->get_contract( $tradesafe_id );

		if ( is_wp_error( $response ) ) {
			$redirect = home_url( '/my-account/orders/' );
			wp_redirect( $redirect );
		}

		if ( '' !== $response['Contract']['completion_days_renegotiated'] ) {
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

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'processing', sprintf( __( 'Extended Delivery Time.', 'woocommerce-tradesafe-gateway' ) ) );

			$redirect = home_url( '/my-account/orders/' );
			wp_redirect( $redirect );
		} else {
			wp_die(
				$response,
				'An error occurred',
				[
					'response'  => 400,
					'back_link' => true,
				]
			);
		}
	}

	/**
	 * @param $order_id
	 */
	public static function cancel( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafe_API_Wrapper();

		$data = array(
			'step' => 'DECLINED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'canceled', sprintf( __( 'Canceled Order.', 'woocommerce-tradesafe-gateway' ) ) );

			$redirect = home_url( '/my-account/orders/' );
			wp_redirect( $redirect );
		} else {
			wp_die(
				$response,
				'An error occurred',
				[
					'response'  => 400,
					'back_link' => true,
				]
			);
		}
	}

	/**
	 * @param $order_id
	 */
	public static function decline( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafe_API_Wrapper();

		$data = array(
			'step' => 'DISPUTED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'on-hold', sprintf( __( 'Order is disputed.', 'woocommerce-tradesafe-gateway' ) ) );

			$redirect = home_url( '/my-account/orders/' );
			wp_redirect( $redirect );
		} else {
			wp_die(
				$response,
				'An error occurred',
				[
					'response'  => 400,
					'back_link' => true,
				]
			);
		}
	}

	/**
	 * @param $actions
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function orders_actions( $actions, $order ) {
		if ( $order->has_status( array( 'processing' ) ) ) {
			// Set the action button
			$actions['accept'] = array(
				'url'    => site_url( '/tradesafe/accept/' . $order->get_id() . '/' ),
				'name'   => __( 'Accept', 'woocommerce-tradesafe-gateway' ),
				'action' => 'accept',
			);

			// Set the action button
			$actions['extend'] = array(
				'url'    => site_url( '/tradesafe/extend/' . $order->get_id() . '/' ),
				'name'   => __( 'Extend', 'woocommerce-tradesafe-gateway' ),
				'action' => 'extend',
			);

			// Set the action button
			$actions['decline'] = array(
				'url'    => site_url( '/tradesafe/decline/' . $order->get_id() . '/' ),
				'name'   => __( 'Decline', 'woocommerce-tradesafe-gateway' ),
				'action' => 'decline',
			);
		}

		return $actions;
	}

	/**
	 * Process data from TradeSafe
	 */
	public static function callback() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_USER_AGENT'] === 'api.tradesafe.co.za' ) {
			$payload = file_get_contents( 'php://input' );
			$request = json_decode( $payload, true );

			$tradesafe_checksum = $_SERVER['HTTP_TRADESAFE_CHECKSUM'];
			$checksum_data      = base64_encode( $payload ) . '.' . get_option( 'tradesafe_api_token' );
			$checksum           = sha1( $checksum_data );

			if ( $tradesafe_checksum !== $checksum ) {
				status_header( 400 );
				print json_encode( __( 'Invalid checksum.', 'woocommerce-tradesafe-gateway' ) );
				exit;
			}

			if ( isset( $request['meta']['reference'] ) ) {
				$order_key = $request['meta']['reference'];
				$order_id  = wc_get_order_id_by_order_key( $order_key );
				$order     = new WC_Order( $order_id );

				if ( $order->meta_exists( 'tradesafe_id' ) && $request['id'] === $order->get_meta( 'tradesafe_id' ) ) {
					if ( isset( $request['Fees'] ) ) {
						$fees     = $request['Fees'];
						$fee_meta = [
							'tradesafe_buyer_deposit' => $fees['deposit'],
							'tradesafe_buyer_fee'     => $fees['buyer_total_fees'],
							'tradesafe_seller_fee'    => $fees['seller_total_fees'],
							'tradesafe_agent_fee'     => $fees['agent_total_fees'],
							'tradesafe_seller_payout' => $fees['payout'],
							'tradesafe_agent_payout'  => $fees['agent_payout'],
						];

						if ( isset( $fees['refund'] ) ) {
							$fee_meta['tradesafe_buyer_payout'] = $fees['refund'];
						}

						foreach ( $fee_meta as $key => $value ) {
							if ( $order->meta_exists( $key ) ) {
								$order->update_meta_data( $key, $value );
							} else {
								$order->add_meta_data( $key, $value );
							}
						}
						$order->save();
					}

					switch ( $request['step'] ) {
						case 'FUNDS_RECEIVED':
							$order->update_status( 'processing', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-tradesafe-gateway' ) ) );
							WC()->mailer()->get_emails();
							do_action( 'tradesafe_payment_processed', $order );
							$data = array(
								'step' => 'SENT',
							);

							$tradesafe = new TradeSafe_API_Wrapper();
							$response  = $tradesafe->update_contract( $request['id'], $data );

							if ( is_wp_error( $response ) ) {
								$order->update_status( 'on-hold', sprintf( __( 'Reset to on On Hold because of error.', 'woocommerce-tradesafe-gateway' ) ) );
								print json_encode( __( 'Could not update contract.', 'woocommerce-tradesafe-gateway' ) );
								status_header( 400 );
								exit;
							}
							break;
						case 'DECLINED':
							$order->update_status( 'cancelled', sprintf( __( 'Order canceled.', 'woocommerce-tradesafe-gateway' ) ) );
							break;
						default:
							status_header( 400 );
							exit;
					}

					status_header( 200 );
					exit;
				} else {
					status_header( 400 );
					print json_encode( __( 'No contract associated with order.', 'woocommerce-tradesafe-gateway' ) );
					exit;
				}
			} else {
				status_header( 404 );
				exit;
			}
		}

		status_header( 404 );
		exit;
	}

	/**
	 * Update order to calculate the correct fee
	 *
	 * @param $order_id
	 * @param $payment_method
	 * @param $redirect
	 *
	 * @throws WC_Data_Exception
	 */
	public static function update_order_payment_method( $order_id ) {
		$order            = new WC_Order( $order_id );
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$payment_method   = $_POST['payment_method'];
		$base_value       = (float) $order->get_subtotal() + (float) $order->get_shipping_total() + (float) $order->get_total_tax();

		if ( isset( $payment_gateways[ $payment_method ]->enabled ) && 'yes' === $payment_gateways[ $payment_method ]->enabled ) {
			$fee = TradeSafe::calculate_fee( $base_value, $payment_method );
			$order->set_payment_method( $payment_gateways[ $payment_method ] );

			foreach ( $order->get_fees() as $fee_id => $item_fee ) {
				if ( __( 'Processing Fee', 'woocommerce-tradesafe-gateway' ) === $item_fee->get_name() ) {
					$item_fee->set_amount( $fee );
					$item_fee->set_total( $fee );
					$item_fee->save();
				}
			}

			$order->calculate_totals();
			$order->save();
			status_header( 200 );
		} else {
			status_header( 400 );
		}

		exit;
	}
}
