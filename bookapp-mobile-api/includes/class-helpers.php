<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Helpers {

	public static function request_lang( WP_REST_Request $request ) {
		$lang = sanitize_key( (string) $request->get_param( 'lang' ) );
		if ( ! $lang ) {
			$accept = (string) $request->get_header( 'accept-language' );
			$lang   = ( $accept && 0 === stripos( $accept, 'en' ) ) ? 'en' : 'ar';
		}
		if ( ! in_array( $lang, array( 'ar', 'en' ), true ) ) {
			$lang = 'ar';
		}
		self::switch_language( $lang );
		return $lang;
	}

	public static function request_platform( WP_REST_Request $request ) {
		$platform = strtolower( sanitize_key( (string) $request->get_header( 'x-bookapp-platform' ) ) );
		if ( ! $platform ) {
			$platform = strtolower( sanitize_key( (string) $request->get_param( 'platform' ) ) );
		}
		if ( 'iphone' === $platform || 'ipad' === $platform ) {
			$platform = 'ios';
		}
		return in_array( $platform, array( 'android', 'ios' ), true ) ? $platform : '';
	}

	public static function request_app_version( WP_REST_Request $request ) {
		$version = trim( (string) $request->get_header( 'x-bookapp-version' ) );
		if ( ! $version ) {
			$version = trim( (string) $request->get_param( 'app_version' ) );
		}
		$version = preg_replace( '/[^0-9.]/', '', $version );
		return preg_match( '/^\d+(\.\d+){0,3}$/', $version ) ? $version : '';
	}

	public static function switch_language( $lang ) {
		if ( function_exists( 'PLL' ) && isset( PLL()->model ) ) {
			$pll = PLL()->model->get_language( $lang );
			if ( $pll ) {
				PLL()->curlang = $pll;
			}
		}
	}

	public static function i18n( $value, $lang ) {
		if ( is_array( $value ) ) {
			if ( isset( $value[ $lang ] ) && '' !== $value[ $lang ] ) {
				return $value[ $lang ];
			}
			if ( isset( $value['ar'] ) && '' !== $value['ar'] ) {
				return $value['ar'];
			}
			if ( isset( $value['en'] ) ) {
				return $value['en'];
			}
			return '';
		}
		return (string) $value;
	}

	public static function json_params( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}
		return is_array( $params ) ? $params : array();
	}

	public static function error( $code, $message, $status = 400, $data = array() ) {
		$data['status'] = $status;
		return new WP_Error( $code, $message, $data );
	}

	public static function product_card( $product, $lang = 'ar', $flags = array() ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$image_id  = $product->get_image_id();
		$gallery   = $product->get_gallery_image_ids();
		$images    = array();
		$all_ids   = array_filter( array_merge( array( $image_id ), $gallery ) );
		foreach ( $all_ids as $aid ) {
			$src = wp_get_attachment_image_url( $aid, 'woocommerce_single' );
			if ( $src ) {
				$images[] = array(
					'id'  => (int) $aid,
					'src' => $src,
					'alt' => get_post_meta( $aid, '_wp_attachment_image_alt', true ),
				);
			}
		}

		$cats = array();
		foreach ( wc_get_product_term_ids( $product->get_id(), 'product_cat' ) as $term_id ) {
			$term = get_term( $term_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$cats[] = array(
					'id'   => (int) $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		$attributes = self::product_attributes( $product );

		$regular = $product->get_regular_price();
		$sale    = $product->get_sale_price();
		$price   = $product->get_price();

		return array(
			'id'              => $product->get_id(),
			'name'            => $product->get_name(),
			'slug'            => $product->get_slug(),
			'permalink'       => $product->get_permalink(),
			'sku'             => $product->get_sku(),
			'type'            => $product->get_type(),
			'short_description' => wp_strip_all_tags( $product->get_short_description() ),
			'description'     => wp_kses_post( $product->get_description() ),
			'on_sale'         => $product->is_on_sale(),
			'is_in_stock'     => $product->is_in_stock(),
			'is_purchasable'  => $product->is_purchasable(),
			'stock_status'    => $product->get_stock_status(),
			'average_rating'  => (float) $product->get_average_rating(),
			'review_count'    => (int) $product->get_review_count(),
			'prices'          => array(
				'price'                  => $price,
				'regular_price'          => $regular,
				'sale_price'             => $sale,
				'currency_code'          => get_woocommerce_currency(),
				'currency_symbol'        => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'currency_minor_unit'    => wc_get_price_decimals(),
			),
			'images'          => $images,
			'categories'      => $cats,
			'attributes'      => $attributes,
			'in_wishlist'     => ! empty( $flags['in_wishlist'] ),
			'in_compare'      => ! empty( $flags['in_compare'] ),
		);
	}

	public static function author_image( $term_id ) {
		$url = self::term_image_url( $term_id );
		return $url ? $url : null;
	}

	public static function publisher_image( $term_id ) {
		$url = self::term_image_url( $term_id );
		return $url ? $url : null;
	}

	public static function term_image_url( $term_id ) {
		$term_id = (int) $term_id;
		if ( ! $term_id ) {
			return '';
		}

		foreach ( array( 'pa_term_image', 'thumbnail_id', 'image', 'product_attribute_image' ) as $key ) {
			$url = self::image_meta_to_url( get_term_meta( $term_id, $key, true ) );
			if ( $url ) {
				return $url;
			}
		}

		return '';
	}

	private static function image_meta_to_url( $meta ) {
		if ( empty( $meta ) ) {
			return '';
		}
		if ( is_array( $meta ) ) {
			if ( ! empty( $meta['url'] ) && is_string( $meta['url'] ) ) {
				return esc_url_raw( $meta['url'] );
			}
			if ( ! empty( $meta['id'] ) ) {
				$src = wp_get_attachment_image_url( (int) $meta['id'], 'medium' );
				return $src ? $src : '';
			}
			return '';
		}
		if ( is_numeric( $meta ) ) {
			$src = wp_get_attachment_image_url( (int) $meta, 'medium' );
			return $src ? $src : '';
		}
		if ( is_string( $meta ) && preg_match( '#^https?://#i', $meta ) ) {
			return esc_url_raw( $meta );
		}
		return '';
	}

	public static function product_attributes( WC_Product $product ) {
		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$tax   = $attribute->get_name();
				$terms = wc_get_product_terms( $product->get_id(), $tax, array( 'fields' => 'all' ) );
				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					continue;
				}
				$tax_obj      = get_taxonomy( $tax );
				$attributes[] = array(
					'id'      => $tax,
					'name'    => $tax_obj ? $tax_obj->labels->singular_name : $tax,
					'options' => array_map(
						static function ( $t ) use ( $tax ) {
							$option = array(
								'id'   => (int) $t->term_id,
								'name' => $t->name,
								'slug' => $t->slug,
							);
							if ( 'pa_writer' === $tax ) {
								$option['image'] = BookApp_API_Helpers::author_image( $t->term_id );
							} elseif ( 'pa_publisher' === $tax ) {
								$option['image'] = BookApp_API_Helpers::publisher_image( $t->term_id );
							}
							return $option;
						},
						$terms
					),
				);
				continue;
			}

			$options = array();
			foreach ( (array) $attribute->get_options() as $option ) {
				$name = is_string( $option ) ? trim( $option ) : '';
				if ( '' === $name ) {
					continue;
				}
				$options[] = array(
					'id'   => 0,
					'name' => $name,
					'slug' => sanitize_title( $name ),
				);
			}
			if ( empty( $options ) ) {
				continue;
			}
			$attributes[] = array(
				'id'      => $attribute->get_name(),
				'name'    => wc_attribute_label( $attribute->get_name(), $product ),
				'options' => $options,
			);
		}

		return $attributes;
	}

	public static function products_by_ids( $ids, $lang, $wishlist_ids = array(), $compare_ids = array() ) {
		$out = array();
		foreach ( array_filter( array_map( 'absint', (array) $ids ) ) as $id ) {
			if ( BookApp_API_Visibility::is_product_hidden( $id ) ) {
				continue;
			}
			$card = self::product_card(
				$id,
				$lang,
				array(
					'in_wishlist' => in_array( $id, $wishlist_ids, true ),
					'in_compare'  => in_array( $id, $compare_ids, true ),
				)
			);
			if ( $card ) {
				$out[] = $card;
			}
		}
		return $out;
	}

	public static function query_product_ids( $args ) {
		$source = isset( $args['source'] ) ? $args['source'] : 'latest';
		$limit  = isset( $args['limit'] ) ? max( 1, min( 24, (int) $args['limit'] ) ) : 10;
		$query  = array(
			'status'       => 'publish',
			'limit'        => $limit,
			'return'       => 'ids',
			'orderby'      => isset( $args['orderby'] ) ? $args['orderby'] : 'date',
			'order'        => isset( $args['order'] ) ? $args['order'] : 'DESC',
			'stock_status' => array( 'instock', 'onbackorder' ),
		);

		if ( 'sale' === $source ) {
			$query['include'] = array_values( wc_get_product_ids_on_sale() );
			if ( empty( $query['include'] ) ) {
				return array();
			}
		} elseif ( 'featured' === $source ) {
			$query['featured'] = true;
		} elseif ( in_array( $source, array( 'category', 'tag', 'author' ), true ) && ! empty( $args['term_id'] ) ) {
			$tax = 'category' === $source ? 'product_cat' : ( 'tag' === $source ? 'product_tag' : 'pa_writer' );
			$query['tax_query'] = array(
				array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => array( (int) $args['term_id'] ),
				),
			);
		}

		return wc_get_products( $query );
	}

	public static function page_by_slug( $slug, $lang ) {
		$page = get_page_by_path( $slug );
		if ( ! $page && function_exists( 'pll_get_post' ) ) {
			$pages = get_posts(
				array(
					'name'        => $slug,
					'post_type'   => 'page',
					'post_status' => 'publish',
					'numberposts' => 1,
				)
			);
			$page = $pages ? $pages[0] : null;
		}
		if ( ! $page ) {
			return null;
		}
		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $page->ID, $lang );
			if ( $translated ) {
				$page = get_post( $translated );
			}
		}
		return $page;
	}

	public static function locale_for_lang( $lang ) {
		return 'en' === $lang ? 'en_US' : 'ar';
	}

	public static function with_locale( $lang, $callback ) {
		$target   = self::locale_for_lang( $lang );
		$switched = false;
		if ( function_exists( 'switch_to_locale' ) ) {
			$switched = switch_to_locale( $target );
		}
		if ( function_exists( 'WC' ) ) {
			WC()->countries = new WC_Countries();
		}
		try {
			return $callback();
		} finally {
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
		}
	}

	public static function default_location() {
		$raw   = (string) get_option( 'woocommerce_default_country', 'EG:EGGZ' );
		$parts = explode( ':', $raw );
		return array(
			'country' => isset( $parts[0] ) && $parts[0] ? strtoupper( $parts[0] ) : 'EG',
			'state'   => isset( $parts[1] ) ? (string) $parts[1] : '',
		);
	}

	public static function shippable_country_codes() {
		$codes = array();
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return $codes;
		}
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			$locations = isset( $zone['zone_locations'] ) ? $zone['zone_locations'] : array();
			foreach ( $locations as $loc ) {
				$type = is_object( $loc ) ? $loc->type : ( isset( $loc['type'] ) ? $loc['type'] : '' );
				$code = is_object( $loc ) ? $loc->code : ( isset( $loc['code'] ) ? $loc['code'] : '' );
				if ( ! $code ) {
					continue;
				}
				if ( 'country' === $type ) {
					$codes[ strtoupper( $code ) ] = true;
				} elseif ( 'state' === $type ) {
					$country = strtoupper( (string) strtok( $code, ':' ) );
					if ( $country ) {
						$codes[ $country ] = true;
					}
				}
			}
		}
		$codes = array_keys( $codes );
		sort( $codes );
		return $codes;
	}

	public static function country_states( $country_code ) {
		$states = WC()->countries->get_states( $country_code );
		if ( empty( $states ) || ! is_array( $states ) ) {
			return array();
		}
		$out = array();
		foreach ( $states as $code => $name ) {
			$out[] = array(
				'code' => (string) $code,
				'name' => html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ),
			);
		}
		return $out;
	}

	public static function countries_payload( $lang, $args = array() ) {
		return self::with_locale(
			$lang,
			static function () use ( $lang, $args ) {
				$type           = isset( $args['type'] ) && 'billing' === $args['type'] ? 'billing' : 'shipping';
				$only_shippable = ! isset( $args['shippable'] ) || $args['shippable'];
				$include_states = ! empty( $args['include_states'] );
				$filter         = isset( $args['country'] ) ? strtoupper( (string) $args['country'] ) : '';

				$wc  = WC()->countries;
				$map = 'billing' === $type ? $wc->get_allowed_countries() : $wc->get_shipping_countries();
				$shippable = array_fill_keys( BookApp_API_Helpers::shippable_country_codes(), true );
				$default   = BookApp_API_Helpers::default_location();
				$list      = array();

				foreach ( $map as $code => $name ) {
					$code = strtoupper( (string) $code );
					if ( $filter && $code !== $filter ) {
						continue;
					}
					$is_shippable = isset( $shippable[ $code ] );
					if ( $only_shippable && ! $is_shippable ) {
						continue;
					}
					$states = BookApp_API_Helpers::country_states( $code );
					$item   = array(
						'code'       => $code,
						'name'       => html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ),
						'shippable'  => $is_shippable,
						'has_states' => ! empty( $states ),
					);
					if ( $include_states || $filter ) {
						$item['states'] = $states;
					}
					$list[] = $item;
				}

				return array(
					'lang'      => $lang,
					'type'      => $type,
					'default'   => $default,
					'count'     => count( $list ),
					'countries' => $list,
				);
			}
		);
	}
}
