<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * الهوم الجديدة (v2) — مبنية على كومبوننتس قابلة للترتيب والإضافة والحذف.
 * التخزين في option منفصل تمامًا عن الهوم القديمة (bookapp_home).
 * الـ endpoint: GET /wp-json/bookapp/v2/home
 */
class BookApp_API_Home_V2 {

	const OPTION = 'bookapp_home_components';

	public static function types() {
		return array( 'announcement', 'banners', 'component_ids', 'products_section', 'call_to_action' );
	}

	public static function action_types() {
		return array( '', 'category', 'tag', 'product', 'author', 'publisher', 'url' );
	}

	/**
	 * actionTarget: ID أرقام للأنواع العادية، أو رابط http(s) لما actionType = url.
	 */
	public static function sanitize_action_target( $action_type, $value ) {
		$value = trim( (string) $value );
		if ( 'url' === $action_type ) {
			$url = esc_url_raw( $value );
			return ( $url && preg_match( '#^https?://#i', $url ) ) ? $url : '';
		}
		return preg_replace( '/\D/', '', $value );
	}

	public static function entity_types() {
		return array( 'category', 'tag', 'product', 'author', 'publisher' );
	}

	public static function product_sources() {
		return array( 'all', 'sale', 'featured', 'category', 'tag', 'author', 'publisher', 'product' );
	}

	public static function sorts() {
		return array( 'newest', 'oldest', 'best_selling', 'price_low_to_high', 'price_high_to_low' );
	}

	/**
	 * كومبوننتس افتراضية — بتظهر لحد ما يتم أول حفظ من صفحة Home Builder.
	 */
	public static function defaults() {
		return array(
			array(
				'type'    => 'announcement',
				'enabled' => 1,
				'data'    => array(
					'text'          => array(
						'ar' => 'يمكنك الدفع أونلاين من خلال فيزا - محفظة - ميزة كما يمكنك التقسيط من خلال البنك الأهلي 6 و 12 شهر ومن خلال فاليو وسهولة',
						'en' => 'Pay online with Visa, wallet, or Meeza. Installments via NBE (6-12 months), ValU, and Souhoola.',
					),
					'action_type'   => 'category',
					'action_target' => '198',
				),
			),
			array(
				'type'    => 'banners',
				'enabled' => 1,
				'data'    => array( 'banner_ids' => array() ),
			),
			array(
				'type'    => 'component_ids',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'تصفح حسب التصنيف', 'en' => 'Browse by category' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'action_type'   => 'category',
					'ids'           => array( 187, 198, 197 ),
					'default_image' => 'https://books.ay7aaga.com/wp-content/uploads/2024/04/مكتبة-أي-حاجة-في-سور-الازبكية.jpg',
				),
			),
			array(
				'type'    => 'products_section',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'تخفيضات متجر كتب اي حاجة', 'en' => 'On sale' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'sort_by'       => 'newest',
					'action_type'   => 'sale',
					'action_target' => '',
					'limit'         => 10,
				),
			),
			array(
				'type'    => 'products_section',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'المنتجات المميزة', 'en' => 'Featured products' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'sort_by'       => 'newest',
					'action_type'   => 'featured',
					'action_target' => '',
					'limit'         => 10,
				),
			),
			array(
				'type'    => 'products_section',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'إصدارات حديثة', 'en' => 'New releases' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'sort_by'       => 'newest',
					'action_type'   => 'category',
					'action_target' => '198',
					'limit'         => 10,
				),
			),
			array(
				'type'    => 'component_ids',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'مليون اسم واسم', 'en' => 'Authors' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'action_type'   => 'author',
					'ids'           => array( 4132, 2305, 2489, 364, 332, 693 ),
					'default_image' => '',
				),
			),
			array(
				'type'    => 'call_to_action',
				'enabled' => 1,
				'data'    => array(
					'title'         => array( 'ar' => 'أي حاجة لبيع الكتب الورقية', 'en' => 'Ay7aaga for printed books' ),
					'subtitle'      => array( 'ar' => 'في مجموعة أي حاجة.كوم للتسويق الإلكتروني وفرنا أفضل متجر لبيع الكتب الورقية أونلاين', 'en' => 'The best online store for printed books.' ),
					'button'        => array( 'ar' => 'عرض الكل', 'en' => 'View all' ),
					'action_type'   => 'category',
					'action_target' => '198',
				),
			),
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION, null );
		return is_array( $saved ) ? $saved : self::defaults();
	}

	public static function payload( $lang, $guest_key ) {
		$components = self::get();
		$wish       = BookApp_API_Lists::wishlist_ids( $guest_key );
		$compare    = BookApp_API_Lists::compare_ids( $guest_key );

		$out = array();
		foreach ( $components as $component ) {
			if ( empty( $component['type'] ) || ! in_array( $component['type'], self::types(), true ) ) {
				continue;
			}
			if ( isset( $component['enabled'] ) && ! $component['enabled'] ) {
				continue;
			}
			$data  = isset( $component['data'] ) && is_array( $component['data'] ) ? $component['data'] : array();
			$built = null;

			switch ( $component['type'] ) {
				case 'announcement':
					$built = self::build_announcement( $data, $lang );
					break;
				case 'banners':
					$built = self::build_banners( $data, $lang );
					break;
				case 'component_ids':
					$built = self::build_component_ids( $data, $lang, $wish, $compare );
					break;
				case 'products_section':
					$built = self::build_products_section( $data, $lang, $wish, $compare );
					break;
				case 'call_to_action':
					$built = self::build_cta( $data, $lang );
					break;
			}

			if ( null !== $built ) {
				$out[] = array(
					'component' => $component['type'],
					'data'      => $built,
				);
			}
		}

		return array(
			'lang'       => $lang,
			'components' => $out,
		);
	}

	private static function build_announcement( $data, $lang ) {
		return array(
			'text'         => BookApp_API_Helpers::i18n( $data['text'] ?? '', $lang ),
			'actionType'   => (string) ( $data['action_type'] ?? '' ),
			'actionTarget' => (string) ( $data['action_target'] ?? '' ),
		);
	}

	private static function build_banners( $data, $lang ) {
		$ids = array_values( array_filter( array_map( 'absint', (array) ( $data['banner_ids'] ?? array() ) ) ) );
		return array(
			'banner_ids' => $ids,
			'banners'    => BookApp_API_Banners::items( $ids, $lang ),
		);
	}

	private static function build_component_ids( $data, $lang, $wish, $compare ) {
		$action_type   = (string) ( $data['action_type'] ?? 'category' );
		$ids           = array_values( array_filter( array_map( 'absint', (array) ( $data['ids'] ?? array() ) ) ) );
		$default_image = (string) ( $data['default_image'] ?? '' );

		if ( 'product' === $action_type ) {
			$items = BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $compare );
		} else {
			$items = self::term_items( $action_type, $ids, $lang, $default_image );
		}

		return array(
			'title'         => BookApp_API_Helpers::i18n( $data['title'] ?? '', $lang ),
			'button_text'   => BookApp_API_Helpers::i18n( $data['button'] ?? '', $lang ),
			'actionType'    => $action_type,
			'ids'           => $ids,
			'default_image' => $default_image,
			'items'         => $items,
		);
	}

	private static function term_items( $action_type, $ids, $lang, $default_image ) {
		$tax_map = array(
			'category'  => 'product_cat',
			'tag'       => 'product_tag',
			'author'    => 'pa_writer',
			'publisher' => 'pa_publisher',
		);
		if ( ! isset( $tax_map[ $action_type ] ) ) {
			return array();
		}
		$taxonomy = $tax_map[ $action_type ];

		$terms = array();
		if ( $ids ) {
			foreach ( $ids as $id ) {
				$term = get_term( $id, $taxonomy );
				if ( $term && ! is_wp_error( $term ) && ! BookApp_API_Visibility::is_term_hidden( $term->term_id ) ) {
					$terms[] = $term;
				}
			}
		} elseif ( 'category' === $action_type ) {
			// ids فاضية + category = كل التصنيفات الرئيسية (parent).
			$args = array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
			);
			if ( function_exists( 'PLL' ) ) {
				$args['lang'] = $lang;
			}
			$found = get_terms( $args );
			$terms = is_wp_error( $found ) ? array() : $found;
		}

		$items = array();
		foreach ( $terms as $term ) {
			$image = BookApp_API_Helpers::term_image_url( $term->term_id );
			if ( ! $image ) {
				$image = $default_image ? $default_image : null;
			}

			$item = array(
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'count' => (int) $term->count,
				'image' => $image,
			);
			if ( 'category' === $action_type ) {
				$item['parent'] = (int) $term->parent;
			}
			if ( 'author' === $action_type && $term->description ) {
				$item['bio'] = $term->description;
			}
			$items[] = $item;
		}
		return $items;
	}

	private static function build_products_section( $data, $lang, $wish, $compare ) {
		$source = (string) ( $data['action_type'] ?? 'all' );
		$target = (string) ( $data['action_target'] ?? '' );
		$sort   = in_array( $data['sort_by'] ?? '', self::sorts(), true ) ? $data['sort_by'] : 'newest';
		$limit  = max( 1, min( 24, (int) ( $data['limit'] ?? 10 ) ) );

		// السيكشن مرتبط بتيرم مخفي من التطبيق → الكومبوننت كله يتشال.
		if ( in_array( $source, array( 'category', 'tag', 'author', 'publisher' ), true )
			&& $target
			&& BookApp_API_Visibility::is_term_hidden( (int) $target ) ) {
			return null;
		}

		if ( 'product' === $source ) {
			$ids = $target ? array( (int) $target ) : array();
		} else {
			$ids = self::query_products( $source, (int) $target, $sort, $limit );
		}

		return array(
			'title'         => BookApp_API_Helpers::i18n( $data['title'] ?? '', $lang ),
			'button_text'   => BookApp_API_Helpers::i18n( $data['button'] ?? '', $lang ),
			'sort_by'       => $sort,
			'action_type'   => $source,
			'action_target' => $target,
			'limit'         => $limit,
			'products'      => BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $compare ),
		);
	}

	private static function query_products( $source, $target, $sort, $limit ) {
		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'fields'              => 'ids',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_query'          => array(
				array(
					'key'     => '_stock_status',
					'value'   => array( 'instock', 'onbackorder' ),
					'compare' => 'IN',
				),
			),
			'tax_query'           => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			),
		);

		switch ( $sort ) {
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'best_selling':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			case 'price_low_to_high':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;
			case 'price_high_to_low':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		if ( 'sale' === $source ) {
			$on_sale = array_values( array_filter( array_map( 'absint', (array) wc_get_product_ids_on_sale() ) ) );
			if ( ! $on_sale ) {
				return array();
			}
			$args['post__in'] = $on_sale;
		} elseif ( 'featured' === $source ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'featured' ),
			);
		} elseif ( in_array( $source, array( 'category', 'tag', 'author', 'publisher' ), true ) ) {
			if ( ! $target ) {
				return array();
			}
			$tax_map             = array(
				'category'  => 'product_cat',
				'tag'       => 'product_tag',
				'author'    => 'pa_writer',
				'publisher' => 'pa_publisher',
			);
			$args['tax_query'][] = array(
				'taxonomy' => $tax_map[ $source ],
				'field'    => 'term_id',
				'terms'    => array( $target ),
			);
		}

		$query = new WP_Query( $args );
		return array_map( 'intval', $query->posts );
	}

	private static function build_cta( $data, $lang ) {
		return array(
			'title'        => BookApp_API_Helpers::i18n( $data['title'] ?? '', $lang ),
			'subtitle'     => BookApp_API_Helpers::i18n( $data['subtitle'] ?? '', $lang ),
			'button_text'  => BookApp_API_Helpers::i18n( $data['button'] ?? '', $lang ),
			'actionType'   => (string) ( $data['action_type'] ?? '' ),
			'actionTarget' => (string) ( $data['action_target'] ?? '' ),
		);
	}
}
