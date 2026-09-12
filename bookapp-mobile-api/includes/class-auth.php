<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Auth {

	public static function init() {
		add_filter( 'determine_current_user', array( __CLASS__, 'determine_user' ), 20 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'maybe_auth_error' ), 20 );
	}

	public static function bearer_token() {
		$headers = array();
		if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$headers[] = wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
		}
		if ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$headers[] = wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
		}
		if ( ! empty( $_SERVER['HTTP_X_BOOKAPP_AUTHORIZATION'] ) ) {
			$headers[] = wp_unslash( $_SERVER['HTTP_X_BOOKAPP_AUTHORIZATION'] );
		}
		foreach ( $headers as $header ) {
			if ( preg_match( '/Bearer\s+(\S+)/i', $header, $m ) ) {
				return $m[1];
			}
		}
		return '';
	}

	public static function determine_user( $user_id ) {
		if ( $user_id ) {
			return $user_id;
		}
		$token = self::bearer_token();
		if ( ! $token ) {
			return $user_id;
		}
		$payload = self::decode_token( $token );
		if ( ! $payload || empty( $payload['user_id'] ) ) {
			return $user_id;
		}
		return (int) $payload['user_id'];
	}

	public static function maybe_auth_error( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}
		$token = self::bearer_token();
		if ( ! $token ) {
			return $result;
		}
		$payload = self::decode_token( $token );
		if ( ! $payload ) {
			return new WP_Error( 'bookapp_invalid_token', 'Invalid or expired token.', array( 'status' => 401 ) );
		}
		return $result;
	}

	public static function decode_token( $token ) {
		$parts = explode( '.', $token );
		if ( 3 !== count( $parts ) ) {
			return null;
		}
		list( $h64, $p64, $s64 ) = $parts;
		$secret                  = self::secret();
		if ( ! $secret ) {
			return null;
		}
		$expected = self::b64url_encode( hash_hmac( 'sha256', $h64 . '.' . $p64, $secret, true ) );
		if ( ! hash_equals( $expected, $s64 ) ) {
			return null;
		}
		$payload = json_decode( self::b64url_decode( $p64 ), true );
		if ( ! is_array( $payload ) ) {
			return null;
		}
		if ( ! empty( $payload['exp'] ) && time() > (int) $payload['exp'] ) {
			return null;
		}
		if ( ! empty( $payload['jti'] ) && ! self::jti_valid( $payload['jti'] ) ) {
			return null;
		}
		if ( empty( $payload['user_id'] ) && ! empty( $payload['data']['user']['id'] ) ) {
			$payload['user_id'] = (int) $payload['data']['user']['id'];
		}
		return $payload;
	}

	public static function issue_token( $user_id, $expires_in = 2592000 ) {
		$expires_in = max( 3600, min( 2592000, (int) $expires_in ) );
		$request    = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'username'   => '',
					'password'   => '',
					'expires_in' => $expires_in,
				)
			)
		);

		// Prefer issuing through the existing JWT plugin while the user is current.
		$was = get_current_user_id();
		wp_set_current_user( $user_id );
		$response = rest_do_request( $request );
		wp_set_current_user( $was );

		if ( $response->is_error() ) {
			return self::issue_local_token( $user_id, $expires_in );
		}
		$data = $response->get_data();
		if ( empty( $data['token'] ) ) {
			return self::issue_local_token( $user_id, $expires_in );
		}
		return $data;
	}

	public static function issue_local_token( $user_id, $expires_in ) {
		$secret = self::secret();
		if ( ! $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'wpmcp_jwt_secret_key', $secret, false );
		}
		$now  = time();
		$jti  = wp_generate_password( 32, false );
		$body = array(
			'iss'     => get_bloginfo( 'url' ),
			'iat'     => $now,
			'nbf'     => $now,
			'exp'     => $now + $expires_in,
			'jti'     => $jti,
			'user_id' => (int) $user_id,
		);
		$header  = self::b64url_encode( wp_json_encode( array( 'typ' => 'JWT', 'alg' => 'HS256' ) ) );
		$payload = self::b64url_encode( wp_json_encode( $body ) );
		$sig     = self::b64url_encode( hash_hmac( 'sha256', $header . '.' . $payload, $secret, true ) );

		$registry = get_option( 'jwt_token_registry', array() );
		if ( ! is_array( $registry ) ) {
			$registry = array();
		}
		$registry[ $jti ] = array(
			'user_id'    => (int) $user_id,
			'issued_at'  => $now,
			'expires_at' => $now + $expires_in,
			'revoked'    => false,
		);
		update_option( 'jwt_token_registry', $registry, false );

		return array(
			'token'      => $header . '.' . $payload . '.' . $sig,
			'user_id'    => (int) $user_id,
			'expires_in' => $expires_in,
			'expires_at' => $now + $expires_in,
		);
	}

	public static function secret() {
		return (string) get_option( 'wpmcp_jwt_secret_key', '' );
	}

	private static function jti_valid( $jti ) {
		$registry = get_option( 'jwt_token_registry', array() );
		if ( ! is_array( $registry ) || ! isset( $registry[ $jti ] ) ) {
			return true;
		}
		$row = $registry[ $jti ];
		if ( ! empty( $row['revoked'] ) ) {
			return false;
		}
		if ( ! empty( $row['expires_at'] ) && time() > (int) $row['expires_at'] ) {
			return false;
		}
		return true;
	}

	private static function b64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( $data ) {
		$remainder = strlen( $data ) % 4;
		if ( $remainder ) {
			$data .= str_repeat( '=', 4 - $remainder );
		}
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	public static function user_payload( WP_User $user ) {
		$customer = new WC_Customer( $user->ID );
		return array(
			'id'           => $user->ID,
			'email'        => $user->user_email,
			'username'     => $user->user_login,
			'first_name'   => $customer->get_first_name(),
			'last_name'    => $customer->get_last_name(),
			'display_name' => $user->display_name,
			'phone'        => $customer->get_billing_phone(),
			'roles'        => array_values( $user->roles ),
			'billing'      => $customer->get_billing(),
			'shipping'     => $customer->get_shipping(),
		);
	}

	public static function require_login() {
		if ( ! is_user_logged_in() ) {
			return BookApp_API_Helpers::error( 'bookapp_auth_required', 'Login required.', 401 );
		}
		return true;
	}
}
