<?php

/**
 * Plugin Name: Integration for Elementor forms - Sendinblue
 * Description: Easily connect and send data to sendinblue from elementor forms.
 * Author: Webtica
 * Author URI: https://webtica.be/
 * Version: 1.3.0
 * Elementor tested up to: 3.4.8
 * Elementor Pro tested up to: 3.5.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

//load plugins functionallity
require dirname(__FILE__).'/init-sendinblue-integration-action.php';

//Check if Elementor pro is installed
function sendinblue_integration_check_elementor_pro_is_active() {

	if ( !is_plugin_active('elementor-pro/elementor-pro.php') ) {
		echo "<div class='error'><p><strong>Sendinblue Elementor integration</strong> requires <strong> Elementor Pro plugin to be installed and activated</strong> </p></div>";
	}
}
add_action('admin_notices', 'sendinblue_integration_check_elementor_pro_is_active');