<?php

/**
 * Class WC_Gateway_TradeSafe_Base
 */
class WC_Gateway_TradeSafe_Base extends WC_Payment_Gateway {
	public $available_currencies = [ 'ZAR' ];

	/**
	 * @return bool
	 */
	public function is_available() {
		if ( '' === get_option( 'tradesafe_api_token' ) ) {
			return false;
		}

		if ( ! in_array( get_woocommerce_currency(), $this->available_currencies ) ) {
			return false;
		}

		if ( 'no' === $this->get_option( 'enabled' ) ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 */
	public function admin_options() {
		if ( in_array( get_woocommerce_currency(), $this->available_currencies ) ) {
			parent::admin_options();
		} else {
			?>
			<h3><?php _e( 'TradeSafe', 'woocommerce-tradesafe-gateway' ); ?></h3>
			<div class="inline error"><p>
					<strong><?php _e( 'Gateway Disabled', 'woocommerce-tradesafe-gateway' ); ?></strong> 
									  <?php
										/* translators: 1: a href link 2: closing href */
										echo sprintf( __( 'Choose South African Rand as your store currency in %1$sGeneral Settings%2$s to enable the TradeSafe Gateway.', 'woocommerce-tradesafe-gateway' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">', '</a>' );
										?>
				</p></div>
			<?php
		}
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order     = new WC_Order( $order_id );
		$tradesafe = new TradeSafeAPIWrapper();

		if ( $order->meta_exists( 'tradesafe_id' ) ) {
			// Get contract
			$contract = $tradesafe->get_contract( $order->get_meta( 'tradesafe_id' ) );

			if ( is_wp_error( $contract ) ) {
				$messages = '';

				foreach ( $contract->errors as $errors ) {
					foreach ( $errors as $code => $error ) {
						$messages .= $error;
					}
				}

				return array(
					'result'   => 'failure',
					'messages' => $messages,
				);
			}
		} else {
			$owner = $tradesafe->owner();

			$user  = wp_get_current_user();
			$buyer = get_user_meta( $user->ID, 'tradesafe_user_id', true );

			$checkout_url = $order->get_checkout_payment_url();
			$order_url    = $order->get_view_order_url();
			$base_value   = (float) $order->get_subtotal() + (float) $order->get_shipping_total() + (float) $order->get_total_tax();

			$data = [
				'name'              => get_bloginfo( 'name' ) . ' - Order ' . $order->get_order_number(),
				'reference'         => $order->get_order_key(),
				'success_url'       => $checkout_url,
				'failure_url'       => $order_url,
				'industry'          => get_option( 'tradesafe_site_industry', 'GENERAL_GOODS_SERVICES' ),
				'description'       => sprintf( __( 'New order from %1$s. Order ID: %2$s', 'woocommerce-tradesafe-gateway' ), get_bloginfo( 'name' ), $order->get_order_number() ),
				'value'             => (float) number_format_i18n( $base_value, 2 ),
				'completion_days'   => 30,
				'completion_months' => 0,
				'completion_years'  => 0,
				'inspection_days'   => 7,
				'delivery_required' => false,
			];

			$data['buyer'] = $buyer;

			if ( 'marketplace' === get_option( 'tradesafe_site_role' ) ) {
				$sellers = [];
				foreach ( $order->get_items() as $item_id => $item ) {
					$item_data               = $item->get_data();
					$author_id               = get_post_field( 'post_author', $item_data['product_id'] );
					$sellers[ $author_id ][] = $item_data;
				}

				if ( count( $sellers ) > 1 ) {
					wc_add_notice( 'Multiple sellers are not currently supported.', 'error' );

					return array(
						'result'   => 'failure',
						'messages' => 'Multiple sellers are not currently supported.',
					);
				}

				reset( $sellers );
				$seller_id = key( $sellers );
				$seller    = get_user_meta( $seller_id, 'tradesafe_user_id', true );

				if ( '' === $seller ) {
					wc_add_notice( 'Seller is not registered with TradeSafe or has not linked their account.', 'error' );

					return array(
						'result'   => 'failure',
						'messages' => 'Seller is not registered with TradeSafe or has not linked their account.',
					);
				}

				$data['seller']               = $seller;
				$data['agent']                = $owner['id'];
				$data['fee_allocation']       = 3;
				$data['agent_fee']            = get_option( 'tradesafe_site_fee' );
				$data['agent_fee_allocation'] = (int) get_option( 'tradesafe_site_fee_allocation', '1' );
			} else {
				$data['seller']         = $owner['id'];
				$data['fee_allocation'] = 1;
			}

			// Check if contract data is valid
			$validate = $tradesafe->verify_contract( $data );

			if ( is_wp_error( $validate ) ) {
				$messages = '';

				foreach ( $validate->errors as $errors ) {
					foreach ( $errors as $code => $error ) {
						$messages .= $error;
					}
				}

				wc_add_notice( $messages, 'error' );

				return array(
					'result'   => 'failure',
					'messages' => $messages,
				);
			}

			// Create contract
			$contract = $tradesafe->add_contract( $data );

			if ( is_wp_error( $contract ) ) {
				$messages = '';

				foreach ( $contract->errors as $errors ) {
					foreach ( $errors as $code => $error ) {
						$messages .= $error;
					}
				}

				wc_add_notice( $messages, 'error' );

				return array(
					'result'   => 'failure',
					'messages' => $messages,
				);
			}

			// Mark as on-hold
			$order->update_status( 'pending', __( 'Awaiting payment.', 'woocommerce-tradesafe-gateway' ) );
			$order->update_meta_data( 'tradesafe_id', $contract['Contract']['id'] );
			$order->save();
		}

		switch ( $order->get_payment_method() ) {
			case 'tradesafe_manualeft':
				$redirect = $contract['Contract']['payment_url'];
				break;
			case 'tradesafe_eftsecure':
				$redirect = $contract['Contract']['eftsecure_payment_url'];
				break;
			case 'tradesafe_ecentric':
				$redirect = $contract['Contract']['ecentric_payment_redirect'];
				break;
			default:
				wc_add_notice( 'There was a problem processing the payment.', 'error' );

				return array(
					'result'   => 'failure',
					'messages' => 'There was a problem processing the payment.',
				);
		}

		// Empty Cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $redirect,
		);
	}

	/**
	 * @param int    $order_id
	 * @param null   $amount
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return parent::process_refund( $order_id, $amount, $reason ); // TODO: Change the autogenerated stub
	}
}
