<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Settings {

	public static function defaults() {
		$logo = 'https://books.ay7aaga.com/wp-content/uploads/2024/10/Blue-and-Yellow-Festive-Illustrated-Bunch-of-Ballons-Party-Services-Logo-388-x-249-بيكسل-9-e1730293765432.png';
		$icon = 'https://books.ay7aaga.com/wp-content/uploads/2024/11/cropped-Blue-and-Yellow-Festive-Illustrated-Bunch-of-Ballons-Party-Services-Logo-388-x-249-بيكسل-16.png';

		return array(
			'whatsapp'      => '201050994329',
			'support_email' => get_option( 'admin_email' ),
			'app'           => array(
				'android' => array(
					'latest'    => '1.0.0',
					'minimum'   => '1.0.0',
					'store_url' => '',
				),
				'ios'     => array(
					'latest'    => '1.0.0',
					'minimum'   => '1.0.0',
					'store_url' => '',
				),
				'title'   => array(
					'ar' => 'تحديث جديد',
					'en' => 'New update',
				),
				'message' => array(
					'ar' => 'حدّث التطبيق عشان تستفيد من آخر المزايا والإصلاحات.',
					'en' => 'Update the app to get the latest features and fixes.',
				),
			),
			'placeholders'  => array(
				'author'    => BOOKAPP_API_URL . 'assets/default-author.png',
				'publisher' => BOOKAPP_API_URL . 'assets/default-publisher.png',
			),
			'identity'      => array(
				'name'    => array(
					'short' => array(
						'ar' => 'كتب أي حاجة',
						'en' => 'Ay7aaga Books',
					),
					'full'  => array(
						'ar' => 'متجر كتب أي حاجة',
						'en' => 'Ay7aaga Book Store',
					),
				),
				'tagline' => array(
					'ar' => 'أي حاجة لبيع الكتب الورقية',
					'en' => 'Ay7aaga for printed books',
				),
				'logo'    => array(
					'full'   => $logo,
					'icon'   => $icon,
					'splash' => $logo,
				),
				'colors'  => array(
					'primary'    => '#00669D',
					'secondary'  => '#11AAFF',
					'success'    => '#00D603',
					'warning'    => '#E0B252',
					'error'      => '#DD3333',
					'text'       => '#000000',
					'title'      => '#333333',
					'muted'      => '#6B7280',
					'background' => '#FFFFFF',
					'surface'    => '#F8FAFC',
					'status_bar' => '#00669D',
					'link'       => '#00669D',
					'link_hover' => '#11AAFF',
				),
				'fonts'   => array(
					'family'  => 'Alexandria',
					'source'  => 'google',
					'url'     => 'https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700&display=swap',
					'weights' => array( 400, 500, 600, 700 ),
					'sizes'   => array(
						'body'    => 15,
						'heading' => 22,
						'title'   => 16,
						'button'  => 14,
						'caption' => 12,
					),
				),
			),
		);
	}

	public static function get() {
		$saved = get_option( 'bookapp_app_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return self::merge( self::defaults(), $saved );
	}

	public static function save( $incoming ) {
		$current = self::get();
		$d       = self::defaults();
		$id      = $current['identity'];

		$id['name']['short']['ar'] = sanitize_text_field( $incoming['name_short_ar'] ?? $id['name']['short']['ar'] );
		$id['name']['short']['en'] = sanitize_text_field( $incoming['name_short_en'] ?? $id['name']['short']['en'] );
		$id['name']['full']['ar']  = sanitize_text_field( $incoming['name_full_ar'] ?? $id['name']['full']['ar'] );
		$id['name']['full']['en']  = sanitize_text_field( $incoming['name_full_en'] ?? $id['name']['full']['en'] );
		$id['tagline']['ar']       = sanitize_text_field( $incoming['tagline_ar'] ?? $id['tagline']['ar'] );
		$id['tagline']['en']       = sanitize_text_field( $incoming['tagline_en'] ?? $id['tagline']['en'] );

		$id['logo']['full']   = self::url_or( $incoming['logo_full'] ?? '', $id['logo']['full'] );
		$id['logo']['icon']   = self::url_or( $incoming['logo_icon'] ?? '', $id['logo']['icon'] );
		$id['logo']['splash'] = self::url_or( $incoming['logo_splash'] ?? '', $id['logo']['splash'] );

		foreach ( array_keys( $d['identity']['colors'] ) as $key ) {
			$field = 'color_' . $key;
			$id['colors'][ $key ] = self::hex_or( $incoming[ $field ] ?? '', $id['colors'][ $key ] );
		}

		$family = sanitize_text_field( $incoming['font_family'] ?? $id['fonts']['family'] );
		if ( ! $family ) {
			$family = $d['identity']['fonts']['family'];
		}
		$weights = self::parse_weights( $incoming['font_weights'] ?? implode( ',', $id['fonts']['weights'] ) );
		$url     = esc_url_raw( $incoming['font_url'] ?? '' );
		if ( ! $url ) {
			$url = self::google_url( $family, $weights );
		}

		$id['fonts']['family']  = $family;
		$id['fonts']['source']  = 'google';
		$id['fonts']['url']     = $url;
		$id['fonts']['weights'] = $weights;
		foreach ( array( 'body', 'heading', 'title', 'button', 'caption' ) as $role ) {
			$id['fonts']['sizes'][ $role ] = max( 8, min( 48, absint( $incoming[ 'font_size_' . $role ] ?? $id['fonts']['sizes'][ $role ] ) ) );
		}

		$placeholders = isset( $current['placeholders'] ) && is_array( $current['placeholders'] ) ? $current['placeholders'] : $d['placeholders'];

		$app = isset( $current['app'] ) && is_array( $current['app'] ) ? self::merge( $d['app'], $current['app'] ) : $d['app'];
		foreach ( array( 'android', 'ios' ) as $os ) {
			$latest  = self::version_or( $incoming[ $os . '_latest' ] ?? '', $app[ $os ]['latest'] );
			$minimum = self::version_or( $incoming[ $os . '_minimum' ] ?? '', $app[ $os ]['minimum'] );
			if ( version_compare( $minimum, $latest, '>' ) ) {
				$minimum = $latest;
			}
			$app[ $os ]['latest']    = $latest;
			$app[ $os ]['minimum']   = $minimum;
			$app[ $os ]['store_url'] = esc_url_raw( (string) ( $incoming[ $os . '_store' ] ?? $app[ $os ]['store_url'] ) );
		}
		$app['title']['ar']   = sanitize_text_field( $incoming['app_title_ar'] ?? $app['title']['ar'] );
		$app['title']['en']   = sanitize_text_field( $incoming['app_title_en'] ?? $app['title']['en'] );
		$app['message']['ar'] = sanitize_textarea_field( $incoming['app_message_ar'] ?? $app['message']['ar'] );
		$app['message']['en'] = sanitize_textarea_field( $incoming['app_message_en'] ?? $app['message']['en'] );

		$out = array(
			'whatsapp'      => preg_replace( '/\D+/', '', (string) ( $incoming['whatsapp'] ?? $current['whatsapp'] ) ),
			'support_email' => sanitize_email( $incoming['support_email'] ?? $current['support_email'] ),
			'app'           => $app,
			'placeholders'  => $placeholders,
			'identity'      => $id,
		);

		update_option( 'bookapp_app_settings', $out, false );
		return $out;
	}

	public static function support_payload( $settings = null ) {
		$settings = is_array( $settings ) ? self::merge( self::defaults(), $settings ) : self::get();
		return array(
			'whatsapp' => $settings['whatsapp'],
			'email'    => $settings['support_email'],
		);
	}

	public static function identity_payload( $lang, $settings = null ) {
		$settings = is_array( $settings ) ? self::merge( self::defaults(), $settings ) : self::get();
		$id       = $settings['identity'];
		$colors   = $id['colors'];
		$fonts    = $id['fonts'];
		$roles    = array(
			'body'    => array( 'weight' => 400 ),
			'heading' => array( 'weight' => 500 ),
			'title'   => array( 'weight' => 400 ),
			'button'  => array( 'weight' => 500 ),
			'caption' => array( 'weight' => 400 ),
		);

		$font_roles = array();
		foreach ( $roles as $role => $meta ) {
			$font_roles[ $role ] = array(
				'family' => $fonts['family'],
				'weight' => (int) $meta['weight'],
				'size'   => (int) $fonts['sizes'][ $role ],
			);
		}

		$palettes = array();
		foreach ( array( 'primary', 'secondary', 'success', 'warning', 'error' ) as $key ) {
			$palettes[ $key ] = self::palette( $colors[ $key ] );
		}

		return array(
			'name'    => array(
				'short' => BookApp_API_Helpers::i18n( $id['name']['short'], $lang ),
				'full'  => BookApp_API_Helpers::i18n( $id['name']['full'], $lang ),
			),
			'tagline' => BookApp_API_Helpers::i18n( $id['tagline'], $lang ),
			'dir'     => 'en' === $lang ? 'ltr' : 'rtl',
			'logo'    => array(
				'full'   => $id['logo']['full'],
				'icon'   => $id['logo']['icon'],
				'splash' => $id['logo']['splash'] ? $id['logo']['splash'] : $id['logo']['full'],
			),
			'colors'  => array(
				'primary'    => $palettes['primary'],
				'secondary'  => $palettes['secondary'],
				'success'    => $palettes['success'],
				'warning'    => $palettes['warning'],
				'error'      => $palettes['error'],
				'text'       => array(
					'primary'   => $colors['text'],
					'secondary' => $colors['title'],
					'muted'     => $colors['muted'],
					'inverse'   => '#FFFFFF',
				),
				'background' => array(
					'default' => $colors['background'],
					'surface' => $colors['surface'],
					'muted'   => '#F1F5F9',
				),
				'link'       => array(
					'idle'  => $colors['link'],
					'hover' => $colors['link_hover'],
				),
				'status_bar' => $colors['status_bar'],
				'splash'     => $colors['primary'],
			),
			'fonts'   => array(
				'family'  => $fonts['family'],
				'fallback'=> array( 'Tahoma', 'Arial', 'sans-serif' ),
				'source'  => $fonts['source'],
				'url'     => $fonts['url'] ? $fonts['url'] : self::google_url( $fonts['family'], $fonts['weights'] ),
				'weights' => array_values( array_map( 'intval', $fonts['weights'] ) ),
				'roles'   => $font_roles,
			),
		);
	}

	public static function placeholders_payload( $settings = null ) {
		return array(
			'author'    => null,
			'publisher' => null,
		);
	}

	public static function payload( $lang, $settings = null, $request = null ) {
		$settings = is_array( $settings ) ? self::merge( self::defaults(), $settings ) : self::get();
		return array(
			'support'      => self::support_payload( $settings ),
			'placeholders' => self::placeholders_payload( $settings ),
			'identity'     => self::identity_payload( $lang, $settings ),
			'app'          => self::app_payload( $lang, $settings, $request ),
		);
	}

	public static function app_payload( $lang, $settings = null, $request = null ) {
		$settings = is_array( $settings ) ? self::merge( self::defaults(), $settings ) : self::get();
		$app      = isset( $settings['app'] ) && is_array( $settings['app'] ) ? self::merge( self::defaults()['app'], $settings['app'] ) : self::defaults()['app'];
		$out      = array(
			'android' => array(
				'latest'    => (string) $app['android']['latest'],
				'minimum'   => (string) $app['android']['minimum'],
				'store_url' => (string) $app['android']['store_url'],
			),
			'ios'     => array(
				'latest'    => (string) $app['ios']['latest'],
				'minimum'   => (string) $app['ios']['minimum'],
				'store_url' => (string) $app['ios']['store_url'],
			),
			'title'   => BookApp_API_Helpers::i18n( $app['title'], $lang ),
			'message' => BookApp_API_Helpers::i18n( $app['message'], $lang ),
			'status'  => null,
		);
		if ( $request instanceof WP_REST_Request ) {
			$out['status'] = self::app_status( $app, $request );
		}
		return $out;
	}

	public static function app_status( $app, WP_REST_Request $request ) {
		$platform = BookApp_API_Helpers::request_platform( $request );
		$installed = BookApp_API_Helpers::request_app_version( $request );
		if ( ! $platform || ! $installed ) {
			return null;
		}
		$os = $app[ $platform ];
		return array(
			'platform'         => $platform,
			'installed'        => $installed,
			'latest'           => (string) $os['latest'],
			'minimum'          => (string) $os['minimum'],
			'store_url'        => (string) $os['store_url'],
			'update_available' => version_compare( $installed, $os['latest'], '<' ),
			'force_update'     => version_compare( $installed, $os['minimum'], '<' ),
		);
	}

	private static function version_or( $value, $fallback ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^\d+(\.\d+){0,3}$/', $value ) ) {
			return $value;
		}
		$fallback = trim( (string) $fallback );
		return preg_match( '/^\d+(\.\d+){0,3}$/', $fallback ) ? $fallback : '1.0.0';
	}

	public static function palette( $hex ) {
		$hex   = self::normalize_hex( $hex, '#000000' );
		$scale = self::scale( $hex );
		return array(
			'main'  => $hex,
			'on'    => self::on_color( $hex ),
			'scale' => $scale,
		);
	}

	public static function scale( $hex ) {
		$base  = self::hex_to_rgb( $hex );
		$white = array( 255, 255, 255 );
		$black = array( 0, 0, 0 );
		$map   = array(
			'50'  => array( $white, 0.92 ),
			'100' => array( $white, 0.80 ),
			'200' => array( $white, 0.60 ),
			'300' => array( $white, 0.40 ),
			'400' => array( $white, 0.20 ),
			'500' => null,
			'600' => array( $black, 0.12 ),
			'700' => array( $black, 0.28 ),
			'800' => array( $black, 0.46 ),
			'900' => array( $black, 0.64 ),
		);
		$out = array();
		foreach ( $map as $step => $mix ) {
			$out[ $step ] = $mix ? self::rgb_to_hex( self::mix( $base, $mix[0], $mix[1] ) ) : $hex;
		}
		return $out;
	}

	private static function google_url( $family, $weights ) {
		$family  = trim( (string) $family );
		$weights = array_values( array_unique( array_filter( array_map( 'intval', (array) $weights ) ) ) );
		sort( $weights );
		if ( ! $weights ) {
			$weights = array( 400, 500, 600, 700 );
		}
		return 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $family ) . ':wght@' . implode( ';', $weights ) . '&display=swap';
	}

	private static function parse_weights( $value ) {
		$parts = preg_split( '/[,\s]+/', (string) $value );
		$out   = array();
		foreach ( $parts as $part ) {
			$n = absint( $part );
			if ( $n >= 100 && $n <= 900 ) {
				$out[] = $n;
			}
		}
		$out = array_values( array_unique( $out ) );
		sort( $out );
		return $out ? $out : array( 400, 500, 600, 700 );
	}

	private static function url_or( $value, $fallback ) {
		$value = esc_url_raw( (string) $value );
		return $value ? $value : $fallback;
	}

	private static function hex_or( $value, $fallback ) {
		$hex = self::normalize_hex( $value, '' );
		return $hex ? $hex : self::normalize_hex( $fallback, '#000000' );
	}

	public static function normalize_hex( $value, $fallback = '#000000' ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^#?([0-9a-fA-F]{6})$/', $value, $m ) ) {
			return '#' . strtoupper( $m[1] );
		}
		if ( preg_match( '/^#?([0-9a-fA-F]{3})$/', $value, $m ) ) {
			$h = $m[1];
			return '#' . strtoupper( $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2] );
		}
		if ( preg_match( '/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $value, $m ) ) {
			return self::rgb_to_hex( array( (int) $m[1], (int) $m[2], (int) $m[3] ) );
		}
		return $fallback;
	}

	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( self::normalize_hex( $hex, '#000000' ), '#' );
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	private static function rgb_to_hex( $rgb ) {
		return sprintf(
			'#%02X%02X%02X',
			max( 0, min( 255, (int) $rgb[0] ) ),
			max( 0, min( 255, (int) $rgb[1] ) ),
			max( 0, min( 255, (int) $rgb[2] ) )
		);
	}

	private static function mix( $from, $to, $amount ) {
		return array(
			(int) round( $from[0] + ( $to[0] - $from[0] ) * $amount ),
			(int) round( $from[1] + ( $to[1] - $from[1] ) * $amount ),
			(int) round( $from[2] + ( $to[2] - $from[2] ) * $amount ),
		);
	}

	private static function on_color( $hex ) {
		$rgb = self::hex_to_rgb( $hex );
		$lin = array();
		foreach ( $rgb as $channel ) {
			$c = $channel / 255;
			$lin[] = $c <= 0.04045 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		$l = 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
		return $l > 0.4 ? '#000000' : '#FFFFFF';
	}

	private static function merge( $base, $over ) {
		foreach ( $over as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && self::is_assoc( $value ) ) {
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
}
