<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Lists {

	const COMPARE_META = 'bookapp_compare_ids';

	public static function wishlist_ids( $guest_key = '' ) {
		if ( is_user_logged_in() && class_exists( '\XTS\WC_Wishlist\Wishlist' ) ) {
			$wishlist = new \XTS\WC_Wishlist\Wishlist( false, get_current_user_id() );
			$all      = $wishlist->get_all();
			$ids      = array();
			foreach ( (array) $all as $row ) {
				if ( is_array( $row ) && ! empty( $row['product_id'] ) ) {
					$ids[] = (int) $row['product_id'];
				} elseif ( is_numeric( $row ) ) {
					$ids[] = (int) $row;
				} elseif ( is_array( $row ) && isset( $row['product_id'] ) ) {
					$ids[] = (int) $row['product_id'];
				}
			}
			// Cookie storage uses product_id as keys.
			if ( empty( $ids ) && is_array( $all ) ) {
				foreach ( array_keys( $all ) as $maybe ) {
					if ( is_numeric( $maybe ) ) {
						$ids[] = (int) $maybe;
					}
				}
			}
			return array_values( array_unique( array_filter( $ids ) ) );
		}
		$data = BookApp_API_Guest::get( $guest_key );
		return $data['wishlist'];
	}

	public static function compare_ids( $guest_key = '' ) {
		if ( is_user_logged_in() ) {
			$ids = get_user_meta( get_current_user_id(), self::COMPARE_META, true );
			return is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
		}
		$data = BookApp_API_Guest::get( $guest_key );
		return $data['compare'];
	}

	public static function add_wishlist( $product_id, $guest_key = '' ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! wc_get_product( $product_id ) || BookApp_API_Visibility::is_product_hidden( $product_id ) ) {
			return BookApp_API_Helpers::error( 'bookapp_invalid_product', 'Product not found.', 404 );
		}
		if ( is_user_logged_in() && class_exists( '\XTS\WC_Wishlist\Wishlist' ) ) {
			$wishlist = new \XTS\WC_Wishlist\Wishlist( false, get_current_user_id() );
			$wishlist->add( $product_id, $wishlist->get_id() );
			return self::wishlist_ids();
		}
		$ids = self::wishlist_ids( $guest_key );
		if ( ! in_array( $product_id, $ids, true ) ) {
			$ids[] = $product_id;
		}
		BookApp_API_Guest::set_list( $guest_key, 'wishlist', $ids );
		return $ids;
	}

	public static function remove_wishlist( $product_id, $guest_key = '' ) {
		$product_id = absint( $product_id );
		if ( is_user_logged_in() && class_exists( '\XTS\WC_Wishlist\Wishlist' ) ) {
			$wishlist = new \XTS\WC_Wishlist\Wishlist( false, get_current_user_id() );
			foreach ( (array) $wishlist->get_all() as $row ) {
				$wid = ! empty( $row['wishlist_id'] ) ? (int) $row['wishlist_id'] : (int) $wishlist->get_id();
				$pid = isset( $row['product_id'] ) ? (int) $row['product_id'] : 0;
				if ( $pid === $product_id || (int) $row === $product_id ) {
					$wishlist->remove( $product_id, $wid );
				}
			}
			$wishlist->remove( $product_id, $wishlist->get_id() );
			return self::wishlist_ids();
		}
		$ids = array_values( array_diff( self::wishlist_ids( $guest_key ), array( $product_id ) ) );
		BookApp_API_Guest::set_list( $guest_key, 'wishlist', $ids );
		return $ids;
	}

	public static function add_compare( $product_id, $guest_key = '' ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! wc_get_product( $product_id ) || BookApp_API_Visibility::is_product_hidden( $product_id ) ) {
			return BookApp_API_Helpers::error( 'bookapp_invalid_product', 'Product not found.', 404 );
		}
		$ids = self::compare_ids( $guest_key );
		if ( ! in_array( $product_id, $ids, true ) ) {
			$ids[] = $product_id;
		}
		self::save_compare( $ids, $guest_key );
		return $ids;
	}

	public static function remove_compare( $product_id, $guest_key = '' ) {
		$ids = array_values( array_diff( self::compare_ids( $guest_key ), array( absint( $product_id ) ) ) );
		self::save_compare( $ids, $guest_key );
		return $ids;
	}

	public static function save_compare( $ids, $guest_key = '' ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::COMPARE_META, $ids );
			return;
		}
		BookApp_API_Guest::set_list( $guest_key, 'compare', $ids );
	}

	public static function merge_for_user( $user_id, $wishlist_ids, $compare_ids ) {
		$was = get_current_user_id();
		wp_set_current_user( $user_id );
		if ( class_exists( '\XTS\WC_Wishlist\Wishlist' ) ) {
			$wishlist = new \XTS\WC_Wishlist\Wishlist( false, $user_id );
			foreach ( (array) $wishlist_ids as $pid ) {
				$wishlist->add( (int) $pid, $wishlist->get_id() );
			}
		}
		$existing = get_user_meta( $user_id, self::COMPARE_META, true );
		$existing = is_array( $existing ) ? $existing : array();
		update_user_meta( $user_id, self::COMPARE_META, array_values( array_unique( array_merge( $existing, array_map( 'absint', (array) $compare_ids ) ) ) ) );
		wp_set_current_user( $was );
	}
}
