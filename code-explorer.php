<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bowo.io
 * @since             1.0.0
 * @package           Code_Explorer
 *
 * @wordpress-plugin
 * Plugin Name:       Code Explorer
 * Plugin URI:        https://wordpress.org/plugins/code-explorer/
 * Description:       Fast directory explorer and file/code viewer with syntax highlighting.
 * Version:           1.3.2
 * Author:            Bowo
 * Author URI:        https://bowo.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       code-explorer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CODE_EXPLORER_VERSION', '1.3.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-code-explorer-activator.php
 */
function activate_code_explorer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-code-explorer-activator.php';
	Code_Explorer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-code-explorer-deactivator.php
 */
function deactivate_code_explorer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-code-explorer-deactivator.php';
	Code_Explorer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_code_explorer' );
register_deactivation_hook( __FILE__, 'deactivate_code_explorer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-code-explorer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_code_explorer() {

	$plugin = new Code_Explorer();
	$plugin->run();

}
run_code_explorer();
