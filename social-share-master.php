<?php
/**
 * Plugin Name: Social Share Master
 * Description: Add floating and inline social share buttons with click-to-copy link, share counts and Open Graph/Twitter meta support.
 * Version: 1.0.0
 * Author: mrshahbazdev
 * Author URI: https://github.com/mrshahbazdev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: social-share-master
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSM_VERSION', '1.0.0' );
define( 'SSM_FILE', __FILE__ );
define( 'SSM_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSM_URL', plugin_dir_url( __FILE__ ) );

require_once SSM_DIR . 'includes/class-social-share-master.php';
add_action( 'plugins_loaded', array( 'Social_Share_Master', 'init' ) );
