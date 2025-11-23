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
	}
}
