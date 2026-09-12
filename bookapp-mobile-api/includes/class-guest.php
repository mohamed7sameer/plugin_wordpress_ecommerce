<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Guest {

	const HEADER = 'X-BookApp-Guest';

	private static $request_key = '';

	public static function init() {
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'expose_header' ), 0, 4 );
	}

	public static function create_table() {
		global $wpdb;
		$table   = $wpdb->prefix . 'bookapp_guest';
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			guest_key varchar(64) NOT NULL,
			wishlist longtext NULL,
			compare_list longtext NULL,
			updated datetime NOT NULL,
			PRIMARY KEY  (guest_key)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function current_key( ?WP_REST_Request $request = null, $create = true ) {
		$key = '';
		if ( $request ) {
			$key = sanitize_text_field( (string) $request->get_header( 'x-bookapp-guest' ) );
			if ( ! $key ) {
				$key = sanitize_text_field( (string) $request->get_param( 'guest_token' ) );
			}
			if ( ! $key ) {
				$p = BookApp_API_Helpers::json_params( $request );
				if ( ! empty( $p['guest_token'] ) ) {
					$key = sanitize_text_field( (string) $p['guest_token'] );
				}
			}
		}
		if ( ! $key && ! empty( $_SERVER['HTTP_X_BOOKAPP_GUEST'] ) ) {
			$key = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_BOOKAPP_GUEST'] ) );
		}
		if ( ! $key && self::$request_key ) {
			$key = self::$request_key;
		}
		if ( ! $key && $create && ! is_user_logged_in() ) {
			$key = wp_generate_uuid4();
		}
		if ( $key ) {
			self::$request_key = $key;
			if ( $create ) {
				self::touch( $key );
			}
		}
		return $key;
	}

	public static function expose_header( $served, $result, $request, $server ) {
		$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
		if ( 0 === strpos( $route, '/bookapp/' ) ) {
			$key = self::current_key( $request, ! is_user_logged_in() );
			if ( $key ) {
				header( 'X-BookApp-Guest: ' . $key );
			}
		}
		return $served;
	}

	public static function get( $key ) {
		if ( ! $key ) {
			return array(
				'wishlist' => array(),
				'compare'  => array(),
			);
		}
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT wishlist, compare_list FROM ' . $wpdb->prefix . 'bookapp_guest WHERE guest_key = %s', $key ), ARRAY_A );
		return array(
			'wishlist' => self::decode_ids( $row['wishlist'] ?? '' ),
			'compare'  => self::decode_ids( $row['compare_list'] ?? '' ),
		);
	}

	public static function set_list( $key, $type, $ids ) {
		if ( ! $key ) {
			return;
		}
		$ids  = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		$data = self::get( $key );
		if ( 'wishlist' === $type ) {
			$data['wishlist'] = $ids;
		} else {
			$data['compare'] = $ids;
		}
		self::save( $key, $data );
	}

	public static function save( $key, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bookapp_guest';
		$wpdb->replace(
			$table,
			array(
				'guest_key'    => $key,
				'wishlist'     => wp_json_encode( $data['wishlist'] ?? array() ),
				'compare_list' => wp_json_encode( $data['compare'] ?? array() ),
				'updated'      => current_time( 'mysql' ),
			)
		);
	}

	public static function touch( $key ) {
		$data = self::get( $key );
		self::save( $key, $data );
	}

	public static function merge_into_user( $key, $user_id ) {
		if ( ! $key || ! $user_id ) {
			return;
		}
		$data = self::get( $key );
		BookApp_API_Lists::merge_for_user( $user_id, $data['wishlist'], $data['compare'] );
		self::save( $key, array( 'wishlist' => array(), 'compare' => array() ) );
	}

	private static function decode_ids( $raw ) {
		$ids = json_decode( (string) $raw, true );
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}
}
