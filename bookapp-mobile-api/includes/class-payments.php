<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Payments {

	public static function init() {
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( __CLASS__, 'process_easykash_for_app' ), 5, 2 );
	}

	public static function is_app_request() {
		if ( ! empty( $_SERVER['HTTP_X_BOOKAPP_APP'] ) ) {
			return true;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return ( false !== strpos( $uri, '/bookapp/v1/' ) );
	}

	public static function process_easykash_for_app( $context, &$result ) {
		if ( ! self::is_app_request() ) {
			return;
		}
		$method = method_exists( $context, 'get_payment_method' ) ? $context->get_payment_method() : ( $context->payment_method ?? '' );
		if ( 'easykash' !== $method ) {
			return;
		}
		if ( $result->status ) {
			return;
		}

		$order  = $context->order;
		$pay    = self::create_easykash_session( $order );
		if ( is_wp_error( $pay ) ) {
			throw new Exception( $pay->get_error_message() );
		}

		$result->set_status( 'pending' );
		$result->set_redirect_url( $pay['payment_url'] );
		$result->set_payment_details(
			array(
				'result'      => 'success',
				'redirect'    => $pay['payment_url'],
				'payment_url' => $pay['payment_url'],
				'method'      => 'easykash',
			)
		);
	}

	public static function create_easykash_session( WC_Order $order ) {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = isset( $gateways['easykash'] ) ? $gateways['easykash'] : null;
		if ( ! $gateway || 'yes' !== $gateway->enabled ) {
			return new WP_Error( 'bookapp_easykash_disabled', 'Online payment is not available.' );
		}

		$key = $gateway->get_option( 'private_key' );
		if ( ! $key ) {
			return new WP_Error( 'bookapp_easykash_config', 'Online payment is not configured.' );
		}

		$return = rest_url( BOOKAPP_API_NS . '/payments/easykash/return' );
		$return = add_query_arg( 'order_id', $order->get_id(), $return ) . '&';

		$ref  = date( 'His' ) . $order->get_id();
		$body = array(
			'amount'            => (float) $order->get_total(),
			'currency'          => get_woocommerce_currency(),
			'paymentOptions'    => array(),
			'cashExpiry'        => 12,
			'name'              => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'Customer',
			'email'             => $order->get_billing_email() ?: 'guest@example.com',
			'mobile'            => $order->get_billing_phone() ?: '0123456789',
			'redirectUrl'       => $return,
			'customerReference' => $ref,
			'type'              => 'Direct Pay',
		);

		$response = wp_remote_post(
			'https://back.easykash.net/api/directpayv1/pay',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['redirectUrl'] ) ) {
			return new WP_Error( 'bookapp_easykash_failed', 'Could not start online payment.' );
		}

		$order->update_meta_data( '_bookapp_easykash_ref', $ref );
		$order->save();

		return array(
			'payment_url' => $data['redirectUrl'],
			'reference'   => $ref,
		);
	}

	public static function return_page( WP_REST_Request $request ) {
		$order_id = absint( $request->get_param( 'order_id' ) );
		$status   = strtoupper( sanitize_text_field( (string) $request->get_param( 'status' ) ) );
		$voucher  = sanitize_text_field( (string) $request->get_param( 'voucher' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		$ok = in_array( $status, array( 'SUCCESS', 'PAID', 'PENDING', 'NEW' ), true );
		if ( $order && $voucher ) {
			$order->update_meta_data( 'easykash_voucher', $voucher );
			$order->save();
		}
		if ( $order && in_array( $status, array( 'FAILED', 'EXPIRED' ), true ) ) {
			$order->update_status( 'failed', 'EasyKash return: ' . $status );
		}

		$payload = array(
			'ok'       => $ok,
			'status'   => $status ?: 'UNKNOWN',
			'order_id' => $order_id,
			'voucher'  => $voucher,
		);

		if ( 'application/json' === $request->get_header( 'accept' ) ) {
			return rest_ensure_response( $payload );
		}

		$html = '<!doctype html><html lang="ar"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment</title></head><body style="font-family:sans-serif;padding:24px;text-align:center">';
		$html .= '<h1>' . ( $ok ? 'تم الدفع' : 'فشل الدفع' ) . '</h1>';
		$html .= '<p id="bookapp-status">' . esc_html( $status ) . '</p>';
		$html .= '<script>window.BOOKAPP_PAYMENT=' . wp_json_encode( $payload ) . ';document.title="bookapp-payment:"+' . wp_json_encode( $status ) . ';</script>';
		$html .= '</body></html>';

		return new WP_REST_Response( $html, 200, array( 'Content-Type' => 'text/html; charset=UTF-8' ) );
	}
}
