<?php
/**
 * Class: JC_ACF_Core
 * Main plugin class that initializes all components.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JC_ACF_Core {

	/**
	 * Constants
	 */
	const SHORTCODE_NEW_POST    = 'jc_acf_form_new_post';
	const SHORTCODE_UPDATE_POST = 'jc_acf_form_update_post';
	const SHORTCODE_LIST_RECORDS = 'jc_acf_list_records';
	const HIDDEN_FIELD_TITLE    = '_jc_acf_title_field';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once JC_ACF_UTILS_PATH . 'includes/class-jc-acf-forms.php';
		require_once JC_ACF_UTILS_PATH . 'includes/class-jc-acf-shortcodes.php';
	}

	/**
	 * Initialize classes.
	 */
	private function init() {
		new JC_ACF_Forms();
		new JC_ACF_Shortcodes();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'jc-acf-styles',
			JC_ACF_UTILS_URL . 'assets/css/jc-acf-styles.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'jc-acf-scripts',
			JC_ACF_UTILS_URL . 'assets/js/jc-acf-scripts.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
}
