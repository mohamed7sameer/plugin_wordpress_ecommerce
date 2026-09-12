<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bind BookApp Mobile API to the licensed store host only.
 */
class BookApp_API_License {

	/**
	 * SHA-256 of the licensed host (lowercase, no www, no port).
	 */
	const HOST_HASH = 'e2584274baf995bed8d6fe7d7266d3e3d589cc62cc52e3fd2e1bb1cd56780fcd';

	public static function is_licensed() {
		$hosts = self::hosts();
		if ( ! $hosts ) {
			return false;
		}

		foreach ( $hosts as $host ) {
			if ( ! hash_equals( self::HOST_HASH, hash( 'sha256', $host ) ) ) {
				return false;
			}
		}

		return true;
	}

	public static function hosts() {
		$hosts = array();

		foreach ( array( home_url(), site_url() ) as $url ) {
			$host = self::normalize_host( wp_parse_url( $url, PHP_URL_HOST ) );
			if ( $host ) {
				$hosts[] = $host;
			}
		}

		return array_values( array_unique( $hosts ) );
	}

	public static function normalize_host( $host ) {
		$host = strtolower( (string) $host );
		$host = preg_replace( '/:\d+$/', '', $host );
		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}
		return $host;
	}

	public static function on_activate() {
		if ( self::is_licensed() ) {
			return;
		}

		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( plugin_basename( BOOKAPP_API_FILE ) );
		}

		wp_die(
			esc_html( 'BookApp Mobile API is licensed only for this store and cannot be activated here.' ),
			esc_html( 'BookApp license' ),
			array( 'response' => 403, 'back_link' => true )
		);
	}

	public static function boot_locked() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'block_rest' ), 1, 3 );
	}

	public static function admin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>BookApp Mobile API is licensed only for this store. The plugin is disabled on this domain.</p></div>';
	}

	public static function block_rest( $result, $server, $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return $result;
		}

		$route = $request->get_route();
		if ( 0 !== strpos( $route, '/bookapp/' ) ) {
			return $result;
		}

		return new WP_Error(
			'bookapp_site_locked',
			'This plugin is licensed for another site.',
			array( 'status' => 403 )
		);
	}
}
