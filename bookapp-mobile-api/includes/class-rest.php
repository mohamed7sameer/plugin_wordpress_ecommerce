<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'cors' ), 15 );
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'store_catalog_in_stock' ), 10, 3 );
	}

	public static function store_catalog_in_stock( $response, $handler, $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return $response;
		}
		$route = $request->get_route();
		if ( '/wc/store/v1/products' !== $route && '/wc/store/v1/products/collection-data' !== $route ) {
			return $response;
		}
		$request->set_param( 'stock_status', array( 'instock', 'onbackorder' ) );
		return $response;
	}

	public static function cors() {
		add_filter(
			'rest_pre_serve_request',
			static function ( $served, $result, $request ) {
				$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
				if ( 0 !== strpos( $route, '/bookapp/' ) && 0 !== strpos( $route, '/wc/store' ) ) {
					return $served;
				}
				header( 'Access-Control-Allow-Origin: *' );
				header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
				header( 'Access-Control-Allow-Headers: Authorization, X-BookApp-Authorization, X-BookApp-Guest, X-BookApp-App, X-BookApp-Platform, X-BookApp-Version, Cart-Token, Nonce, Content-Type, Accept, Accept-Language' );
				header( 'Access-Control-Expose-Headers: Cart-Token, Nonce, X-BookApp-Guest' );
				return $served;
			},
			15,
			3
		);
	}

	public static function register_routes() {
		$pub  = array( __CLASS__, 'allow' );
		$auth = array( __CLASS__, 'must_login' );
		$namespaces = array( BOOKAPP_API_NS );

		foreach ( $namespaces as $ns ) {
			register_rest_route( $ns, '/bootstrap', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'bootstrap' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/settings', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'settings' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/countries', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'countries' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/countries/(?P<code>[A-Za-z]{2})', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'country' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/home', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'home' ), 'permission_callback' => $pub ) );
			register_rest_route( BOOKAPP_API_NS_V2, '/home', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'home_v2' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/pages/(?P<slug>[a-z0-9_-]+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'page' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/auth/login', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'login' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/auth/register', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'register' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/auth/forgot-password', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'forgot_password' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/auth/reset-password', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'reset_password' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/me', array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'me' ), 'permission_callback' => $auth ),
				array( 'methods' => 'PATCH', 'callback' => array( __CLASS__, 'update_me' ), 'permission_callback' => $auth ),
			) );
			register_rest_route( $ns, '/me/password', array( 'methods' => 'PUT', 'callback' => array( __CLASS__, 'update_password' ), 'permission_callback' => $auth ) );
			register_rest_route( $ns, '/me/addresses', array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'addresses' ), 'permission_callback' => $auth ),
				array( 'methods' => 'PUT', 'callback' => array( __CLASS__, 'update_addresses' ), 'permission_callback' => $auth ),
			) );

			register_rest_route( $ns, '/orders', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'orders' ), 'permission_callback' => $auth ) );
			register_rest_route( $ns, '/orders/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'order' ), 'permission_callback' => $auth ) );
			register_rest_route( $ns, '/orders/cancel', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'cancel_order' ), 'permission_callback' => $auth ) );
			register_rest_route( $ns, '/orders/track', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'track_order' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/wishlist', array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'wishlist_get' ), 'permission_callback' => $pub ),
				array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'wishlist_add' ), 'permission_callback' => $pub ),
			) );
			register_rest_route( $ns, '/wishlist/(?P<product_id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'wishlist_remove' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/compare', array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'compare_get' ), 'permission_callback' => $pub ),
				array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'compare_add' ), 'permission_callback' => $pub ),
			) );
			register_rest_route( $ns, '/compare/(?P<product_id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'compare_remove' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/products/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'product' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/products/(?P<id>\d+)/reviews', array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'reviews_get' ), 'permission_callback' => $pub ),
				array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'reviews_add' ), 'permission_callback' => $auth ),
			) );
			register_rest_route( $ns, '/authors', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'authors' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/publishers', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'publishers' ), 'permission_callback' => $pub ) );

			register_rest_route( $ns, '/checkout', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'checkout' ), 'permission_callback' => $pub ) );
			register_rest_route( $ns, '/payments/easykash/return', array( 'methods' => 'GET', 'callback' => array( 'BookApp_API_Payments', 'return_page' ), 'permission_callback' => $pub ) );
		}
	}

	public static function allow() {
		return true;
	}

	public static function must_login() {
		return is_user_logged_in() ? true : BookApp_API_Helpers::error( 'bookapp_auth_required', 'Login required.', 401 );
	}

	public static function bootstrap( WP_REST_Request $request ) {
		$lang = BookApp_API_Helpers::request_lang( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$settings = BookApp_API_Settings::get();
		$location = BookApp_API_Helpers::default_location();

		$payments = array();
		if ( function_exists( 'WC' ) ) {
			foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gw ) {
				$payments[] = array(
					'id'          => $id,
					'title'       => $gw->get_title(),
					'description' => wp_strip_all_tags( $gw->get_description() ),
					'offline'     => 'cod' === $id,
					'webview'     => 'easykash' === $id,
				);
			}
		}

		$languages = array();
		if ( function_exists( 'pll_languages_list' ) ) {
			foreach ( pll_languages_list( array( 'fields' => false ) ) as $l ) {
				$languages[] = array(
					'code'    => $l->slug,
					'name'    => $l->name,
					'default' => ! empty( $l->is_default ),
					'dir'     => ! empty( $l->is_rtl ) ? 'rtl' : 'ltr',
				);
			}
		} else {
			$languages = array(
				array( 'code' => 'ar', 'name' => 'العربية', 'default' => true, 'dir' => 'rtl' ),
				array( 'code' => 'en', 'name' => 'English', 'default' => false, 'dir' => 'ltr' ),
			);
		}

		return rest_ensure_response(
			array(
				'site'         => array(
					'name'     => get_bloginfo( 'name' ),
					'url'      => home_url( '/' ),
					'currency' => get_woocommerce_currency(),
					'symbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
					'lang'     => $lang,
				),
				'languages'    => $languages,
				'guest_token'  => $guest,
				'auth'         => array(
					'logged_in' => is_user_logged_in(),
					'user'      => is_user_logged_in() ? BookApp_API_Auth::user_payload( wp_get_current_user() ) : null,
				),
				'counts'       => array(
					'wishlist' => count( BookApp_API_Visibility::filter_product_ids( BookApp_API_Lists::wishlist_ids( $guest ) ) ),
					'compare'  => count( BookApp_API_Visibility::filter_product_ids( BookApp_API_Lists::compare_ids( $guest ) ) ),
				),
				'payments'     => $payments,
				'support'      => BookApp_API_Settings::support_payload( $settings ),
				'placeholders' => BookApp_API_Settings::placeholders_payload( $settings ),
				'identity'     => BookApp_API_Settings::identity_payload( $lang, $settings ),
				'app'          => BookApp_API_Settings::app_payload( $lang, $settings, $request ),
				'store_api'    => array(
					'base'              => rest_url( 'wc/store/v1' ),
					'cart_token_header' => 'Cart-Token',
					'nonce_header'      => 'Nonce',
					'note'              => 'Use Store API for catalog, cart, coupons, and shipping. Send X-BookApp-App: 1 on checkout so EasyKash returns to the app.',
				),
				'pages'        => array( 'privacy-policy', 'terms-of-use', 'refund_returns' ),
				'locations'    => array(
					'default_country' => $location['country'],
					'default_state'   => $location['state'],
					'countries'       => rest_url( BOOKAPP_API_NS . '/countries' ),
				),
			)
		);
	}

	public static function settings( WP_REST_Request $request ) {
		$lang = BookApp_API_Helpers::request_lang( $request );
		return rest_ensure_response( BookApp_API_Settings::payload( $lang, null, $request ) );
	}

	public static function countries( WP_REST_Request $request ) {
		if ( ! function_exists( 'WC' ) ) {
			return BookApp_API_Helpers::error( 'bookapp_woocommerce_missing', 'WooCommerce is not available.', 503 );
		}
		$lang = BookApp_API_Helpers::request_lang( $request );
		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
			$type = 'shipping';
		}
		$shippable = $request->get_param( 'shippable' );
		$states    = $request->get_param( 'include_states' );
		$country   = strtoupper( sanitize_key( (string) $request->get_param( 'country' ) ) );
		return rest_ensure_response(
			BookApp_API_Helpers::countries_payload(
				$lang,
				array(
					'type'           => $type,
					'shippable'      => null === $shippable ? true : rest_sanitize_boolean( $shippable ),
					'include_states' => rest_sanitize_boolean( $states ),
					'country'        => $country,
				)
			)
		);
	}

	public static function country( WP_REST_Request $request ) {
		if ( ! function_exists( 'WC' ) ) {
			return BookApp_API_Helpers::error( 'bookapp_woocommerce_missing', 'WooCommerce is not available.', 503 );
		}
		$lang    = BookApp_API_Helpers::request_lang( $request );
		$code    = strtoupper( sanitize_key( (string) $request['code'] ) );
		$type    = sanitize_key( (string) $request->get_param( 'type' ) );
		$payload = BookApp_API_Helpers::countries_payload(
			$lang,
			array(
				'type'           => in_array( $type, array( 'billing', 'shipping' ), true ) ? $type : 'shipping',
				'shippable'      => false,
				'include_states' => true,
				'country'        => $code,
			)
		);
		if ( empty( $payload['countries'] ) ) {
			return BookApp_API_Helpers::error( 'bookapp_country_missing', 'Country not found.', 404 );
		}
		$item            = $payload['countries'][0];
		$item['lang']    = $lang;
		$item['default'] = $payload['default'];
		return rest_ensure_response( $item );
	}

	public static function home( WP_REST_Request $request ) {
		$lang  = BookApp_API_Helpers::request_lang( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		return rest_ensure_response( BookApp_API_Home::payload( $lang, $guest ) );
	}

	public static function home_v2( WP_REST_Request $request ) {
		$lang  = BookApp_API_Helpers::request_lang( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		return rest_ensure_response( BookApp_API_Home_V2::payload( $lang, $guest ) );
	}

	public static function page( WP_REST_Request $request ) {
		$lang = BookApp_API_Helpers::request_lang( $request );
		$slug = sanitize_title( $request['slug'] );
		$page = BookApp_API_Helpers::page_by_slug( $slug, $lang );
		if ( ! $page ) {
			return BookApp_API_Helpers::error( 'bookapp_page_missing', 'Page not found.', 404 );
		}
		return rest_ensure_response(
			array(
				'id'      => $page->ID,
				'slug'    => $page->post_name,
				'title'   => get_the_title( $page ),
				'content' => apply_filters( 'the_content', $page->post_content ),
			)
		);
	}

	public static function login( WP_REST_Request $request ) {
		$p        = BookApp_API_Helpers::json_params( $request );
		$username = isset( $p['username'] ) ? sanitize_text_field( $p['username'] ) : '';
		$password = isset( $p['password'] ) ? (string) $p['password'] : '';
		if ( ! $username || ! $password ) {
			return BookApp_API_Helpers::error( 'bookapp_missing_fields', 'Username and password are required.', 400 );
		}
		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			return BookApp_API_Helpers::error( 'bookapp_invalid_credentials', 'Invalid username or password.', 403 );
		}
		wp_set_current_user( $user->ID );
		$guest = BookApp_API_Guest::current_key( $request, false );
		if ( $guest ) {
			BookApp_API_Guest::merge_into_user( $guest, $user->ID );
		}
		$token = BookApp_API_Auth::issue_token( $user->ID, isset( $p['expires_in'] ) ? (int) $p['expires_in'] : 2592000 );
		return rest_ensure_response(
			array(
				'token'      => $token['token'],
				'expires_in' => $token['expires_in'] ?? 2592000,
				'user'       => BookApp_API_Auth::user_payload( $user ),
			)
		);
	}

	public static function register( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$email = isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '';
		$pass  = isset( $p['password'] ) ? (string) $p['password'] : '';
		$first = isset( $p['first_name'] ) ? sanitize_text_field( $p['first_name'] ) : '';
		$last  = isset( $p['last_name'] ) ? sanitize_text_field( $p['last_name'] ) : '';
		$phone = isset( $p['phone'] ) ? sanitize_text_field( $p['phone'] ) : '';
		if ( ! $email || ! $pass ) {
			return BookApp_API_Helpers::error( 'bookapp_missing_fields', 'Email and password are required.', 400 );
		}
		if ( email_exists( $email ) ) {
			return BookApp_API_Helpers::error( 'bookapp_email_exists', 'Email already registered.', 409 );
		}
		$user_id = wc_create_new_customer( $email, '', $pass, array( 'first_name' => $first, 'last_name' => $last ) );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		if ( $phone ) {
			update_user_meta( $user_id, 'billing_phone', $phone );
		}
		wp_set_current_user( $user_id );
		$guest = BookApp_API_Guest::current_key( $request, false );
		if ( $guest ) {
			BookApp_API_Guest::merge_into_user( $guest, $user_id );
		}
		$token = BookApp_API_Auth::issue_token( $user_id );
		return rest_ensure_response(
			array(
				'token' => $token['token'],
				'user'  => BookApp_API_Auth::user_payload( get_user_by( 'id', $user_id ) ),
			)
		);
	}

	public static function forgot_password( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$login = isset( $p['email'] ) ? sanitize_text_field( $p['email'] ) : '';
		$user  = get_user_by( 'email', $login );
		if ( ! $user ) {
			$user = get_user_by( 'login', $login );
		}
		if ( $user ) {
			$reset = retrieve_password( $user->user_login );
			if ( is_wp_error( $reset ) ) {
				return $reset;
			}
		}
		return rest_ensure_response( array( 'ok' => true, 'message' => 'If the account exists, a reset email was sent.' ) );
	}

	public static function reset_password( WP_REST_Request $request ) {
		$p        = BookApp_API_Helpers::json_params( $request );
		$login    = isset( $p['login'] ) ? sanitize_text_field( $p['login'] ) : '';
		$key      = isset( $p['key'] ) ? sanitize_text_field( $p['key'] ) : '';
		$password = isset( $p['password'] ) ? (string) $p['password'] : '';
		$user     = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			return BookApp_API_Helpers::error( 'bookapp_invalid_reset', 'Invalid or expired reset key.', 400 );
		}
		if ( strlen( $password ) < 6 ) {
			return BookApp_API_Helpers::error( 'bookapp_weak_password', 'Password is too short.', 400 );
		}
		reset_password( $user, $password );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function me() {
		return rest_ensure_response( BookApp_API_Auth::user_payload( wp_get_current_user() ) );
	}

	public static function update_me( WP_REST_Request $request ) {
		$p        = BookApp_API_Helpers::json_params( $request );
		$customer = new WC_Customer( get_current_user_id() );
		foreach ( array( 'first_name', 'last_name' ) as $field ) {
			if ( isset( $p[ $field ] ) ) {
				$fn = 'set_' . $field;
				$customer->$fn( sanitize_text_field( $p[ $field ] ) );
			}
		}
		if ( isset( $p['display_name'] ) ) {
			wp_update_user(
				array(
					'ID'           => get_current_user_id(),
					'display_name' => sanitize_text_field( $p['display_name'] ),
				)
			);
		}
		if ( isset( $p['phone'] ) ) {
			$customer->set_billing_phone( sanitize_text_field( $p['phone'] ) );
		}
		$customer->save();
		return self::me();
	}

	public static function update_password( WP_REST_Request $request ) {
		$p        = BookApp_API_Helpers::json_params( $request );
		$current  = isset( $p['current_password'] ) ? (string) $p['current_password'] : '';
		$new      = isset( $p['new_password'] ) ? (string) $p['new_password'] : '';
		$user     = wp_get_current_user();
		if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			return BookApp_API_Helpers::error( 'bookapp_bad_password', 'Current password is wrong.', 403 );
		}
		wp_set_password( $new, $user->ID );
		wp_set_current_user( $user->ID );
		$token = BookApp_API_Auth::issue_token( $user->ID );
		return rest_ensure_response( array( 'ok' => true, 'token' => $token['token'] ) );
	}

	public static function addresses() {
		$c = new WC_Customer( get_current_user_id() );
		return rest_ensure_response(
			array(
				'billing'  => $c->get_billing(),
				'shipping' => $c->get_shipping(),
			)
		);
	}

	public static function update_addresses( WP_REST_Request $request ) {
		$p = BookApp_API_Helpers::json_params( $request );
		$c = new WC_Customer( get_current_user_id() );
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			if ( empty( $p[ $group ] ) || ! is_array( $p[ $group ] ) ) {
				continue;
			}
			foreach ( $p[ $group ] as $key => $value ) {
				$fn = 'set_' . $group . '_' . $key;
				if ( method_exists( $c, $fn ) ) {
					$c->$fn( sanitize_text_field( $value ) );
				}
			}
		}
		$c->save();
		return self::addresses();
	}

	public static function orders( WP_REST_Request $request ) {
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$list = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => 20,
				'page'        => $page,
				'paginate'    => true,
			)
		);
		$items = array();
		foreach ( $list->orders as $order ) {
			$items[] = self::order_payload( $order, false );
		}
		return rest_ensure_response(
			array(
				'items'      => $items,
				'total'      => (int) $list->total,
				'total_pages'=> (int) $list->max_num_pages,
				'page'       => $page,
			)
		);
	}

	public static function order( WP_REST_Request $request ) {
		$order = self::owned_order( $request['id'] );
		if ( is_wp_error( $order ) ) {
			return $order;
		}
		return rest_ensure_response( self::order_payload( $order, true ) );
	}

	public static function cancel_order( WP_REST_Request $request ) {
		$p  = BookApp_API_Helpers::json_params( $request );
		$id = isset( $p['order_id'] ) ? $p['order_id'] : $request->get_param( 'order_id' );
		if ( ! $id ) {
			return BookApp_API_Helpers::error( 'bookapp_missing_fields', 'order_id is required.', 400 );
		}
		$order = self::owned_order( $id );
		if ( is_wp_error( $order ) ) {
			return $order;
		}
		if ( ! self::order_is_cancellable( $order ) ) {
			return BookApp_API_Helpers::error(
				'bookapp_order_not_cancellable',
				'This order cannot be cancelled.',
				409,
				array(
					'can_cancel'     => false,
					'order_status'   => $order->get_status(),
					'payment_method' => $order->get_payment_method(),
				)
			);
		}
		$updated = $order->update_status( 'cancelled', 'Cancelled by customer from the app.' );
		if ( ! $updated ) {
			return BookApp_API_Helpers::error( 'bookapp_order_cancel_failed', 'Could not cancel this order.', 500 );
		}
		$order = wc_get_order( $order->get_id() );
		return rest_ensure_response( self::order_payload( $order, true ) );
	}

	public static function track_order( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$id    = isset( $p['order_id'] ) ? absint( $p['order_id'] ) : 0;
		$email = isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '';
		$order = $id ? wc_get_order( $id ) : false;
		if ( ! $order || ( $email && strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) ) {
			return BookApp_API_Helpers::error( 'bookapp_track_failed', 'Order not found.', 404 );
		}
		return rest_ensure_response( self::order_payload( $order, true ) );
	}

	private static function owned_order( $id ) {
		$order = wc_get_order( absint( $id ) );
		if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() ) {
			return BookApp_API_Helpers::error( 'bookapp_order_missing', 'Order not found.', 404 );
		}
		return $order;
	}

	private static function order_is_cancellable( WC_Order $order ) {
		return 'cod' === $order->get_payment_method()
			&& in_array( $order->get_status(), array( 'pending', 'processing' ), true );
	}

	private static function current_user_can_cancel_order( WC_Order $order ) {
		$user_id = get_current_user_id();
		if ( ! $user_id || (int) $order->get_customer_id() !== (int) $user_id ) {
			return false;
		}
		return self::order_is_cancellable( $order );
	}

	private static function order_payload( WC_Order $order, $detailed ) {
		$items = array();
		if ( $detailed ) {
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				$items[] = array(
					'id'       => $item->get_id(),
					'name'     => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'total'    => $item->get_total(),
					'product'  => $product ? BookApp_API_Helpers::product_card( $product ) : null,
				);
			}
		}
		return array(
			'id'               => $order->get_id(),
			'number'           => $order->get_order_number(),
			'status'           => $order->get_status(),
			'status_name'      => wc_get_order_status_name( $order->get_status() ),
			'date_created'     => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
			'total'            => $order->get_total(),
			'currency'         => $order->get_currency(),
			'payment_method'   => $order->get_payment_method(),
			'payment_title'    => $order->get_payment_method_title(),
			'needs_payment'    => $order->needs_payment(),
			'can_cancel'       => self::current_user_can_cancel_order( $order ),
			'billing'          => $order->get_address( 'billing' ),
			'shipping'         => $order->get_address( 'shipping' ),
			'items'            => $items,
			'shipping_total'   => $order->get_shipping_total(),
			'discount_total'   => $order->get_discount_total(),
		);
	}

	public static function wishlist_get( WP_REST_Request $request ) {
		$lang  = BookApp_API_Helpers::request_lang( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$ids   = BookApp_API_Visibility::filter_product_ids( BookApp_API_Lists::wishlist_ids( $guest ) );
		return rest_ensure_response(
			array(
				'guest_token' => $guest,
				'count'       => count( $ids ),
				'ids'         => $ids,
				'products'    => BookApp_API_Helpers::products_by_ids( $ids, $lang, $ids, BookApp_API_Lists::compare_ids( $guest ) ),
			)
		);
	}

	public static function wishlist_add( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$ids   = BookApp_API_Lists::add_wishlist( isset( $p['product_id'] ) ? $p['product_id'] : 0, $guest );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		return self::wishlist_get( $request );
	}

	public static function wishlist_remove( WP_REST_Request $request ) {
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		BookApp_API_Lists::remove_wishlist( $request['product_id'], $guest );
		return self::wishlist_get( $request );
	}

	public static function compare_get( WP_REST_Request $request ) {
		$lang  = BookApp_API_Helpers::request_lang( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$ids   = BookApp_API_Visibility::filter_product_ids( BookApp_API_Lists::compare_ids( $guest ) );
		$wish  = BookApp_API_Lists::wishlist_ids( $guest );
		$products = BookApp_API_Helpers::products_by_ids( $ids, $lang, $wish, $ids );
		$fields   = array( 'name', 'prices', 'sku', 'attributes', 'average_rating', 'is_in_stock' );
		return rest_ensure_response(
			array(
				'guest_token' => $guest,
				'count'       => count( $ids ),
				'ids'         => $ids,
				'fields'      => $fields,
				'products'    => $products,
			)
		);
	}

	public static function compare_add( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$ids   = BookApp_API_Lists::add_compare( isset( $p['product_id'] ) ? $p['product_id'] : 0, $guest );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		return self::compare_get( $request );
	}

	public static function compare_remove( WP_REST_Request $request ) {
		$guest = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		BookApp_API_Lists::remove_compare( $request['product_id'], $guest );
		return self::compare_get( $request );
	}

	public static function product( WP_REST_Request $request ) {
		$lang    = BookApp_API_Helpers::request_lang( $request );
		$guest   = BookApp_API_Guest::current_key( $request, ! is_user_logged_in() );
		$wish    = BookApp_API_Lists::wishlist_ids( $guest );
		$compare = BookApp_API_Lists::compare_ids( $guest );
		$id      = absint( $request['id'] );
		$card    = BookApp_API_Helpers::product_card( $id, $lang, array( 'in_wishlist' => in_array( $id, $wish, true ), 'in_compare' => in_array( $id, $compare, true ) ) );
		if ( ! $card ) {
			return BookApp_API_Helpers::error( 'bookapp_product_missing', 'Product not found.', 404 );
		}
		$related = array_values(
			array_filter(
				wc_get_related_products( $id, 16 ),
				static function ( $rid ) {
					$related_product = wc_get_product( $rid );
					return $related_product && $related_product->is_in_stock();
				}
			)
		);
		$card['related'] = BookApp_API_Helpers::products_by_ids( array_slice( $related, 0, 8 ), $lang, $wish, $compare );
		$card['reviews'] = self::review_items( $id );
		return rest_ensure_response( $card );
	}

	public static function reviews_get( WP_REST_Request $request ) {
		return rest_ensure_response( array( 'items' => self::review_items( absint( $request['id'] ) ) ) );
	}

	public static function reviews_add( WP_REST_Request $request ) {
		if ( ! get_option( 'woocommerce_enable_reviews' ) ) {
			return BookApp_API_Helpers::error( 'bookapp_reviews_off', 'Reviews are disabled.', 403 );
		}
		$p       = BookApp_API_Helpers::json_params( $request );
		$content = isset( $p['content'] ) ? sanitize_textarea_field( $p['content'] ) : '';
		$rating  = isset( $p['rating'] ) ? (int) $p['rating'] : 0;
		if ( ! $content || $rating < 1 || $rating > 5 ) {
			return BookApp_API_Helpers::error( 'bookapp_review_invalid', 'Rating (1-5) and content are required.', 400 );
		}
		$user    = wp_get_current_user();
		$comment = wp_insert_comment(
			array(
				'comment_post_ID'      => absint( $request['id'] ),
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_content'      => $content,
				'comment_type'         => 'review',
				'user_id'              => $user->ID,
				'comment_approved'     => get_option( 'comment_moderation' ) ? 0 : 1,
			)
		);
		if ( ! $comment ) {
			return BookApp_API_Helpers::error( 'bookapp_review_failed', 'Could not save review.', 500 );
		}
		update_comment_meta( $comment, 'rating', $rating );
		return rest_ensure_response(
			array(
				'ok'          => true,
				'id'          => $comment,
				'moderation'  => (bool) get_option( 'comment_moderation' ),
			)
		);
	}

	private static function review_items( $product_id ) {
		$comments = get_comments(
			array(
				'post_id' => $product_id,
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => 50,
			)
		);
		$out = array();
		foreach ( $comments as $c ) {
			$out[] = array(
				'id'           => (int) $c->comment_ID,
				'author'       => $c->comment_author,
				'content'      => $c->comment_content,
				'rating'       => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
				'date_created' => $c->comment_date,
			);
		}
		return $out;
	}

	public static function authors( WP_REST_Request $request ) {
		return self::terms_response( 'pa_writer', $request, 'author' );
	}

	public static function publishers( WP_REST_Request $request ) {
		return self::terms_response( 'pa_publisher', $request, 'publisher' );
	}

	private static function terms_response( $tax, WP_REST_Request $request, $image_kind = '' ) {
		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$terms  = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
				'number'     => 40,
				'offset'     => ( $page - 1 ) * 40,
				'search'     => $search,
			)
		);
		$items = array();
		foreach ( (array) $terms as $term ) {
			$item = array(
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => (int) $term->count,
			);
			if ( 'author' === $image_kind || 'publisher' === $image_kind ) {
				$image_url     = BookApp_API_Helpers::term_image_url( $term->term_id );
				$item['image'] = $image_url ? $image_url : null;
			}
			$items[] = $item;
		}
		return rest_ensure_response( array( 'items' => $items, 'page' => $page ) );
	}

	public static function checkout( WP_REST_Request $request ) {
		$p     = BookApp_API_Helpers::json_params( $request );
		$nonce = $request->get_header( 'nonce' );
		$token = $request->get_header( 'cart-token' );
		if ( ! $nonce ) {
			$nonce = isset( $p['nonce'] ) ? $p['nonce'] : '';
		}
		if ( ! $token ) {
			$token = isset( $p['cart_token'] ) ? $p['cart_token'] : '';
		}

		$inner = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$inner->set_header( 'Content-Type', 'application/json' );
		$inner->set_header( 'X-BookApp-App', '1' );
		if ( $nonce ) {
			$inner->set_header( 'Nonce', $nonce );
		}
		if ( $token ) {
			$inner->set_header( 'Cart-Token', $token );
		}
		$auth = BookApp_API_Auth::bearer_token();
		if ( $auth ) {
			$inner->set_header( 'Authorization', 'Bearer ' . $auth );
		}
		$inner->set_body( wp_json_encode( $p ) );

		$response = rest_do_request( $inner );
		$data     = $response->get_data();
		if ( $response->is_error() ) {
			return $response->as_error();
		}

		$payment = isset( $data['payment_result'] ) ? $data['payment_result'] : array();
		$method  = isset( $p['payment_method'] ) ? $p['payment_method'] : '';
		$redirect = isset( $payment['redirect_url'] ) ? $payment['redirect_url'] : '';

		$data['app_payment'] = array(
			'method'          => $method,
			'requires_webview'=> ( 'easykash' === $method && $redirect ),
			'payment_url'     => $redirect,
			'return_contains' => array( 'bookapp/v1/payments/easykash/return', 'bookapp-payment:' ),
			'note'            => 'easykash' === $method
				? 'Open payment_url in a WebView. Customer enters Visa/wallet/installments on EasyKash (same as the website). Close WebView when the URL contains /payments/easykash/return then refresh the order.'
				: 'COD completes without a WebView.',
		);

		return new WP_REST_Response( $data, $response->get_status() );
	}
}
