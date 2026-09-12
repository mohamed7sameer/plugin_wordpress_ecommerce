<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Home {

	public static function defaults() {
		return array(
			'announcement' => array(
				'enabled' => 1,
				'text'    => array(
					'ar' => 'يمكنك الدفع أونلاين من خلال فيزا - محفظة - ميزة كما يمكنك التقسيط من خلال البنك الأهلي 6 و 12 شهر ومن خلال فاليو وسهولة',
					'en' => 'Pay online with Visa, wallet, or Meeza. Installments via NBE (6–12 months), ValU, and Souhoola.',
				),
				'link'    => '',
			),
			'hero'         => array(
				'title'    => array(
					'ar' => 'أي حاجة لبيع الكتب الورقية',
					'en' => 'Ay7aaga for printed books',
				),
				'subtitle' => array(
					'ar' => 'في مجموعة أي حاجة.كوم للتسويق الإلكتروني وفرنا أفضل متجر لبيع الكتب الورقية أونلاين',
					'en' => 'The best online store for printed books.',
				),
				'image'    => 'https://books.ay7aaga.com/wp-content/uploads/2025/02/البيت-الابيض-في-عهد-ترامب.jpg',
				'cta1'     => array(
					'label' => array( 'ar' => 'إصدارات حديثة', 'en' => 'New releases' ),
					'type'  => 'category',
					'id'    => 198,
				),
				'cta2'     => array(
					'label' => array( 'ar' => 'سور الأزبكية', 'en' => 'Azbakeya' ),
					'type'  => 'category',
					'id'    => 187,
				),
			),
			'highlights'   => array(
				array( 'title' => array( 'ar' => 'كتب سور الأزبكية', 'en' => 'Azbakeya books' ), 'button' => array( 'ar' => 'اطلع عليه', 'en' => 'Browse' ), 'type' => 'category', 'id' => 187 ),
				array( 'title' => array( 'ar' => 'الإصدارات الحديثة', 'en' => 'New releases' ), 'button' => array( 'ar' => 'اطلع عليه', 'en' => 'Browse' ), 'type' => 'category', 'id' => 198 ),
				array( 'title' => array( 'ar' => 'نوادر الكتب', 'en' => 'Rare books' ), 'button' => array( 'ar' => 'اطلع عليه', 'en' => 'Browse' ), 'type' => 'category', 'id' => 197 ),
				array( 'title' => array( 'ar' => 'الكتب الخارجية', 'en' => 'External books' ), 'button' => array( 'ar' => 'اطلع عليه', 'en' => 'Browse' ), 'type' => 'category', 'id' => 265 ),
			),
			'why'          => array(
				array( 'title' => array( 'ar' => 'سهولة التصفح والاختيار', 'en' => 'Easy browsing' ), 'text' => array( 'ar' => 'من غير أي إزعاج', 'en' => 'No friction' ) ),
				array( 'title' => array( 'ar' => 'فيد واستفيد', 'en' => 'Give and get' ), 'text' => array( 'ar' => 'بسيطة', 'en' => 'Simple' ) ),
				array( 'title' => array( 'ar' => 'معارض للكتب مخفضة', 'en' => 'Discounted book fairs' ), 'text' => array( 'ar' => 'كتب لقطة وبأسعار مفيش', 'en' => 'Great prices' ) ),
				array( 'title' => array( 'ar' => 'كتب إلكترونية', 'en' => 'E-books' ), 'text' => array( 'ar' => 'كدا ماشي وكدا ماشي', 'en' => 'Flexible' ) ),
			),
			'promo'        => array(
				'enabled' => 1,
				'title'   => array( 'ar' => 'تخفيضات متجر كتب اي حاجة', 'en' => 'Ay7aaga book deals' ),
				'image'   => 'https://books.ay7aaga.com/wp-content/uploads/2025/06/Purple-Yellow-Modern-Memphis-Financial-Tips-Carousel-Instagram-Post-15.jpg',
				'type'    => 'sale',
				'id'      => 0,
			),
			'sections'     => array(
				array( 'title' => array( 'ar' => 'تخفيضات متجر كتب اي حاجة', 'en' => 'On sale' ), 'source' => 'sale', 'term_id' => 0, 'limit' => 10, 'button' => array( 'ar' => 'جميع الكتب المخفضة', 'en' => 'All deals' ) ),
				array( 'title' => array( 'ar' => 'كل ما هو قديم ونادر', 'en' => 'Old and rare' ), 'source' => 'category', 'term_id' => 197, 'limit' => 10, 'button' => array( 'ar' => '', 'en' => '' ) ),
				array( 'title' => array( 'ar' => 'الدكتور نبيل فاروق', 'en' => 'Nabil Farouk' ), 'source' => 'author', 'term_id' => 4132, 'limit' => 10, 'button' => array( 'ar' => '', 'en' => '' ) ),
				array( 'title' => array( 'ar' => 'الأكثر مبيعا', 'en' => 'Best sellers' ), 'source' => 'tag', 'term_id' => 3924, 'limit' => 10, 'button' => array( 'ar' => '', 'en' => '' ) ),
				array( 'title' => array( 'ar' => 'إصدارات حديثة', 'en' => 'New releases' ), 'source' => 'category', 'term_id' => 198, 'limit' => 10, 'button' => array( 'ar' => '', 'en' => '' ) ),
				array( 'title' => array( 'ar' => 'منتجات مميزة', 'en' => 'Featured' ), 'source' => 'featured', 'term_id' => 0, 'limit' => 10, 'button' => array( 'ar' => '', 'en' => '' ) ),
			),
			'tabs'         => array(
				'title' => array( 'ar' => 'مليون كتاب وكتاب', 'en' => 'A million books' ),
				'items' => array(
					array( 'title' => array( 'ar' => 'كتب', 'en' => 'Books' ), 'id' => 217 ),
					array( 'title' => array( 'ar' => 'روايات', 'en' => 'Novels' ), 'id' => 232 ),
					array( 'title' => array( 'ar' => 'مترجمات', 'en' => 'Translated' ), 'id' => 286 ),
					array( 'title' => array( 'ar' => 'قصص قصيرة', 'en' => 'Short stories' ), 'id' => 202 ),
					array( 'title' => array( 'ar' => 'موسوعات', 'en' => 'Encyclopedias' ), 'id' => 290 ),
					array( 'title' => array( 'ar' => 'شعر', 'en' => 'Poetry' ), 'id' => 304 ),
					array( 'title' => array( 'ar' => 'مجلات', 'en' => 'Magazines' ), 'id' => 261 ),
					array( 'title' => array( 'ar' => 'كتب أطفال', 'en' => 'Children' ), 'id' => 522 ),
				),
			),
			'authors'      => array(
				'title'    => array( 'ar' => 'مليون اسم واسم', 'en' => 'Authors' ),
				'term_ids' => array( 4132, 2305, 2489, 364, 332, 693, 3454, 2488 ),
			),
			'publishers'   => array(
				'title'  => array( 'ar' => 'دور النشر', 'en' => 'Publishers' ),
				'text'   => array( 'ar' => 'يمكنك من عرض إصداراتك علينا من خلال إرسال القوائم على الواتساب', 'en' => 'Send your catalog on WhatsApp' ),
				'button' => array( 'ar' => 'راسلنا', 'en' => 'Contact us' ),
			),
		);
	}

	public static function get() {
		$saved = get_option( 'bookapp_home', array() );
		return self::merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function merge( $base, $saved ) {
		foreach ( $saved as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && self::is_assoc( $base[ $key ] ) ) {
				$base[ $key ] = self::merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	private static function is_assoc( $arr ) {
		if ( ! is_array( $arr ) || array() === $arr ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	public static function payload( $lang, $guest_key ) {
		$home     = self::get();
		$wish     = BookApp_API_Lists::wishlist_ids( $guest_key );
		$compare  = BookApp_API_Lists::compare_ids( $guest_key );
		$settings = BookApp_API_Settings::get();

		$sections = array();
		foreach ( (array) $home['sections'] as $section ) {
			if ( empty( $section['title'] ) ) {
				continue;
			}
			// سيكشن مرتبط بتيرم مخفي من التطبيق → يتشال من الهوم.
			if ( in_array( $section['source'] ?? '', array( 'category', 'tag', 'author' ), true )
				&& ! empty( $section['term_id'] )
				&& BookApp_API_Visibility::is_term_hidden( (int) $section['term_id'] ) ) {
				continue;
			}
			$ids    = BookApp_API_Helpers::query_product_ids( $section );
			$blocks = BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $compare );
			$sections[] = array(
				'title'    => BookApp_API_Helpers::i18n( $section['title'], $lang ),
				'source'   => $section['source'],
				'term_id'  => (int) ( $section['term_id'] ?? 0 ),
				'button'   => BookApp_API_Helpers::i18n( $section['button'] ?? '', $lang ),
				'products' => $blocks,
			);
		}

		$tabs = array();
		foreach ( (array) $home['tabs']['items'] as $tab ) {
			if ( BookApp_API_Visibility::is_term_hidden( (int) $tab['id'] ) ) {
				continue;
			}
			$ids    = BookApp_API_Helpers::query_product_ids(
				array(
					'source'  => 'category',
					'term_id' => $tab['id'],
					'limit'   => 10,
				)
			);
			$tabs[] = array(
				'title'    => BookApp_API_Helpers::i18n( $tab['title'], $lang ),
				'term_id'  => (int) $tab['id'],
				'products' => BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $compare ),
			);
		}

		$authors = array();
		foreach ( (array) $home['authors']['term_ids'] as $tid ) {
			$term = get_term( (int) $tid, 'pa_writer' );
			if ( ! $term || is_wp_error( $term ) || BookApp_API_Visibility::is_term_hidden( $term->term_id ) ) {
				continue;
			}
			$ids       = BookApp_API_Helpers::query_product_ids(
				array(
					'source'  => 'author',
					'term_id' => $term->term_id,
					'limit'   => 8,
				)
			);
			$authors[] = array(
				'id'       => (int) $term->term_id,
				'name'     => $term->name,
				'count'    => (int) $term->count,
				'image'    => BookApp_API_Helpers::author_image( $term->term_id ),
				'products' => BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $compare ),
			);
		}

		return array(
			'announcement' => array(
				'enabled' => ! empty( $home['announcement']['enabled'] ),
				'text'    => BookApp_API_Helpers::i18n( $home['announcement']['text'], $lang ),
				'link'    => $home['announcement']['link'],
			),
			'hero'         => array(
				'title'    => BookApp_API_Helpers::i18n( $home['hero']['title'], $lang ),
				'subtitle' => BookApp_API_Helpers::i18n( $home['hero']['subtitle'], $lang ),
				'image'    => $home['hero']['image'],
				'cta1'     => self::cta( $home['hero']['cta1'], $lang ),
				'cta2'     => self::cta( $home['hero']['cta2'], $lang ),
			),
			'highlights'   => array_values(
				array_map(
					static function ( $item ) use ( $lang ) {
						return array(
							'title'  => BookApp_API_Helpers::i18n( $item['title'], $lang ),
							'button' => BookApp_API_Helpers::i18n( $item['button'], $lang ),
							'type'   => $item['type'],
							'id'     => (int) $item['id'],
						);
					},
					array_filter(
						(array) $home['highlights'],
						static function ( $item ) {
							return empty( $item['id'] ) || ! BookApp_API_Visibility::is_term_hidden( (int) $item['id'] );
						}
					)
				)
			),
			'why'          => array_map(
				static function ( $item ) use ( $lang ) {
					return array(
						'title' => BookApp_API_Helpers::i18n( $item['title'], $lang ),
						'text'  => BookApp_API_Helpers::i18n( $item['text'], $lang ),
					);
				},
				$home['why']
			),
			'promo'        => array(
				'enabled' => ! empty( $home['promo']['enabled'] ),
				'title'   => BookApp_API_Helpers::i18n( $home['promo']['title'], $lang ),
				'image'   => $home['promo']['image'],
				'type'    => $home['promo']['type'],
				'id'      => (int) $home['promo']['id'],
			),
			'sections'     => $sections,
			'tabs'         => array(
				'title' => BookApp_API_Helpers::i18n( $home['tabs']['title'], $lang ),
				'items' => $tabs,
			),
			'authors'      => array(
				'title' => BookApp_API_Helpers::i18n( $home['authors']['title'], $lang ),
				'items' => $authors,
			),
			'publishers'   => array(
				'title'    => BookApp_API_Helpers::i18n( $home['publishers']['title'], $lang ),
				'text'     => BookApp_API_Helpers::i18n( $home['publishers']['text'], $lang ),
				'button'   => BookApp_API_Helpers::i18n( $home['publishers']['button'], $lang ),
				'image'    => null,
				'whatsapp' => $settings['whatsapp'],
			),
		);
	}

	private static function cta( $cta, $lang ) {
		return array(
			'label' => BookApp_API_Helpers::i18n( $cta['label'], $lang ),
			'type'  => $cta['type'],
			'id'    => (int) $cta['id'],
		);
	}
}
