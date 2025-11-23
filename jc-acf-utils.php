<?php
/**
 * Plugin Name: JC Icaro ACF Utils
 * Description: Custom ACF forms and utility code by JC Icaro.
 * Version: 1.0.0
 * Author: JC Icaro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'JC_ACF_UTILS_PATH', plugin_dir_path( __FILE__ ) );
define( 'JC_ACF_UTILS_URL', plugin_dir_url( __FILE__ ) );

// Include Core Class
require_once JC_ACF_UTILS_PATH . 'includes/class-jc-acf-core.php';

// Initialize the plugin
new JC_ACF_Core();
