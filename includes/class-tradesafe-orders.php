<?php

/**
 * Class TradeSafeOrders
 */
class TradeSafeOrders {
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

	//Initializes WordPress hooks
	private static function init_hooks() {
		self::$initiated = true;

		// Actions
		add_action( 'woocommerce_order_status_pending_to_cancelled', [ 'TradeSafeOrders', 'cancel' ], 0, 1 );
		add_action( 'woocommerce_cancelled_order', [ 'TradeSafeOrders', 'cancel' ] );

		// Filters
		add_filter( 'woocommerce_my_account_my_orders_actions', [ 'TradeSafeOrders', 'orders_actions' ], 100, 2 );
	}

	/**
	 * @param $order_id
	 */
	public static function accept( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafeAPIWrapper();

		$data = array(
			'step' => 'GOODS_ACCEPTED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'completed', sprintf( __( 'Accepted Goods or Services.', 'woocommerce-tradesafe-gateway' ) ) );

			wp_redirect( '/my-account/orders/' );
		} else {
			wp_die( $response, 'An error occurred', [ 'response' => 400, 'back_link' => true ] );
		}
	}

	/**
	 * @param $order_id
	 */
	public static function extend( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafeAPIWrapper();
		$response     = $tradesafe->get_contract( $tradesafe_id );

		if ( is_wp_error( $response ) ) {
			wp_redirect( '/my-account/orders/' );
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

			wp_redirect( '/my-account/orders/' );
		} else {
			wp_die( $response, 'An error occurred', [ 'response' => 400, 'back_link' => true ] );
		}
	}

	/**
	 * @param $order_id
	 */
	public static function cancel( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafeAPIWrapper();

		$data = array(
			'step' => 'DECLINED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'canceled', sprintf( __( 'Canceled Order.', 'woocommerce-tradesafe-gateway' ) ) );

			wp_redirect( '/my-account/orders/' );
		} else {
			wp_die( $response, 'An error occurred', [ 'response' => 400, 'back_link' => true ] );
		}
	}

	/**
	 * @param $order_id
	 */
	public static function decline( $order_id ) {
		$order        = wc_get_order( $order_id );
		$tradesafe_id = $order->get_meta( 'tradesafe_id' );
		$tradesafe    = new TradeSafeAPIWrapper();

		$data = array(
			'step' => 'DISPUTED',
		);

		$response = $tradesafe->update_contract( $tradesafe_id, $data );
		if ( ! is_wp_error( $response ) ) {
			$order->update_status( 'on-hold', sprintf( __( 'Order is disputed.', 'woocommerce-tradesafe-gateway' ) ) );

			wp_redirect( '/my-account/orders/' );
		} else {
			wp_die( $response, 'An error occurred', [ 'response' => 400, 'back_link' => true ] );
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
			$request = json_decode( file_get_contents( 'php://input' ), true );

			if ( isset( $request['meta']['reference'] ) ) {
				$order_key = $request['meta']['reference'];
				$order_id  = wc_get_order_id_by_order_key( $order_key );
				$order     = new WC_Order( $order_id );

				if ( $order->meta_exists( 'tradesafe_id' ) && $request['id'] === $order->get_meta( 'tradesafe_id' ) ) {
					switch ( $request['step'] ) {
						case "FUNDS_RECEIVED":
							$order->update_status( 'processing', sprintf( __( 'Payment via TradeSafe.', 'woocommerce-tradesafe-gateway' ) ) );
							$data = array(
								'step' => 'SENT',
							);

							$tradesafe = new TradeSafeAPIWrapper();
							$response  = $tradesafe->update_contract( $request['id'], $data );

							if ( is_wp_error( $response ) ) {
								$order->update_status( 'on-hold', sprintf( __( 'Reset to on On Hold because of error.', 'woocommerce-tradesafe-gateway' ) ) );
								status_header( 400 );
								die();
							}
							break;
						case "DECLINED":
							$order->update_status( 'cancelled', sprintf( __( 'Order canceled.', 'woocommerce-tradesafe-gateway' ) ) );
							break;
						default:
							status_header( 400 );
							die();
					}

					status_header( 200 );
					die();
				}
			}
		}

		status_header( 404 );
		die();
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

		die(':^)');
	}
}
