<?php
/**
 * Plugin Name: BookApp Mobile API
 * Description: REST API for the BookApp mobile app (auth, home, wishlist, compare, orders, reviews, EasyKash).
 * Version: 1.1.6
 * Author: BookApp
 * Text Domain: bookapp-mobile-api
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BOOKAPP_API_VERSION', '1.1.6' );
define( 'BOOKAPP_API_FILE', __FILE__ );
define( 'BOOKAPP_API_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOOKAPP_API_URL', plugin_dir_url( __FILE__ ) );
define( 'BOOKAPP_API_NS', 'bookapp/v1' );
define( 'BOOKAPP_API_NS_V2', 'bookapp/v2' );

require_once BOOKAPP_API_DIR . 'includes/class-license.php';

register_activation_hook( __FILE__, array( 'BookApp_API_License', 'on_activate' ) );

if ( ! BookApp_API_License::is_licensed() ) {
	BookApp_API_License::boot_locked();
	return;
}

require_once BOOKAPP_API_DIR . 'includes/class-helpers.php';
require_once BOOKAPP_API_DIR . 'includes/class-visibility.php';
require_once BOOKAPP_API_DIR . 'includes/class-settings.php';
require_once BOOKAPP_API_DIR . 'includes/class-auth.php';
require_once BOOKAPP_API_DIR . 'includes/class-guest.php';
require_once BOOKAPP_API_DIR . 'includes/class-lists.php';
require_once BOOKAPP_API_DIR . 'includes/class-home.php';
require_once BOOKAPP_API_DIR . 'includes/class-banners.php';
require_once BOOKAPP_API_DIR . 'includes/class-home-v2.php';
require_once BOOKAPP_API_DIR . 'includes/class-payments.php';
require_once BOOKAPP_API_DIR . 'includes/class-rest.php';
require_once BOOKAPP_API_DIR . 'includes/class-admin.php';
require_once BOOKAPP_API_DIR . 'includes/class-home-builder-admin.php';
require_once BOOKAPP_API_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'BookApp_API_Plugin', 'activate' ) );

BookApp_API_Plugin::instance();
