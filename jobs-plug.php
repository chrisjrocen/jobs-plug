<?php
/**
 * Plugin Name: Jobs Plug
 * Plugin URI: https://ocenchris.com/jobs-plug
 * Description: A lightweight WordPress plugin for managing job postings and notices.
 * Version: 0.0.1
 * Author: Chris Ocen
 * Author URI: https://ocenchris.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jobs-plug
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package JobsPlug
 */

namespace JobsPlug;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Plugin version.
 */
define( 'JOBS_PLUG_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'JOBS_PLUG_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'JOBS_PLUG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'JOBS_PLUG_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Require the main plugin class.
 */
require_once JOBS_PLUG_PATH . 'includes/class-jobs-plug.php';

/**
 * Plugin activation hook.
 */
function activate_jobs_plug() {
	// Register the custom post type and taxonomies so rewrite rules are added.
	$plugin = Jobs_Plug::get_instance();
	$plugin->register_job_post_type();
	$plugin->register_job_taxonomies();

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate_jobs_plug' );

/**
 * Plugin deactivation hook.
 */
function deactivate_jobs_plug() {
	// Deactivation code here.
	// Example: Clean up temporary data.

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate_jobs_plug' );

/**
 * Initialize the plugin.
 */
function run_jobs_plug() {
	return Jobs_Plug::get_instance();
}

// Start the plugin.
run_jobs_plug();
