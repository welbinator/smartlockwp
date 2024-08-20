<?php
/**
 * Plugin Name: SmartLockWP
 * Plugin URI:  https://example.com/plugins/smartlockwp
 * Description: Integrates Seam.co with the Hotel Booking plugin by Motopress to generate unlock codes automatically when a user makes a booking.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: smartlockwp
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Load Composer's autoloader.
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * Includes the necessary files.
 */
require plugin_dir_path(__FILE__) . 'includes/class-smartlockwp-seam-client.php';
require plugin_dir_path(__FILE__) . 'includes/class-smartlockwp-admin.php';
require plugin_dir_path(__FILE__) . 'includes/integrations/motopress-hotel-booking/class-smartlockwp-motopress-metabox.php';

/**
 * Begins execution of the plugin.
 */
function run_smartlockwp() {
    new SmartLockWP_Admin();
    new SmartLockWP_Motopress_Metabox();
}

run_smartlockwp();
