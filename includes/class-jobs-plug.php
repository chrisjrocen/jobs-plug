<?php
/**
 * Main plugin class.
 *
 * @package JobsPlug
 */

namespace JobsPlug;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main plugin class.
 */
class Jobs_Plug {

	/**
	 * Single instance of the class.
	 *
	 * @var Jobs_Plug
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return Jobs_Plug
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Initialization hook.
		add_action( 'init', array( $this, 'init' ) );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
	}

	/**
	 * Initialize plugin functionality.
	 */
	public function init() {
		// Load text domain for translations.
		load_plugin_textdomain(
			'jobs-plug',
			false,
			dirname( JOBS_PLUG_BASENAME ) . '/languages'
		);

		// Plugin initialization code here.
	}

	/**
	 * Admin initialization.
	 */
	public function admin_init() {
		// Admin-specific initialization code here.
	}

	/**
	 * Register admin menu.
	 */
	public function admin_menu() {
		// Admin menu registration code here.
	}
}
