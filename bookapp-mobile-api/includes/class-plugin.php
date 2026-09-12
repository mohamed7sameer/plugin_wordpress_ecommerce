<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		BookApp_API_Visibility::init();
		BookApp_API_Auth::init();
		BookApp_API_Guest::init();
		BookApp_API_Banners::init();
		BookApp_API_Payments::init();
		BookApp_API_REST::init();
		if ( is_admin() ) {
			BookApp_API_Admin::init();
			BookApp_API_Home_Builder_Admin::init();
		}
	}

	public static function activate() {
		BookApp_API_License::on_activate();
		BookApp_API_Guest::create_table();
		if ( ! get_option( 'bookapp_home' ) ) {
			update_option( 'bookapp_home', BookApp_API_Home::defaults(), false );
		}
		if ( ! get_option( 'bookapp_app_settings' ) ) {
			update_option( 'bookapp_app_settings', BookApp_API_Settings::defaults(), false );
		}
		flush_rewrite_rules();
	}
}
