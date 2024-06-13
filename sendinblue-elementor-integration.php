<?php

/**
 * Plugin Name: Integration for Elementor forms - Sendinblue
 * Description: Easily connect and send data to sendinblue / Brevo from elementor forms.
 * Author: Webtica
 * Author URI: https://webtica.be/
 * Version: 1.5.8
 * Elementor tested up to: 3.21.8
 * Elementor Pro tested up to: 3.21.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

//load plugins functionallity and settings
require dirname(__FILE__).'/init-sendinblue-integration-action.php';
require dirname(__FILE__).'/includes/settings.php';

//Check if Elementor pro is installed
function sendinblue_integration_check_elementor_pro_is_active() {

	if ( !is_plugin_active('elementor-pro/elementor-pro.php') ) {
		echo "<div class='error'><p><strong>Sendinblue Elementor integration</strong> requires <strong> Elementor Pro plugin to be installed and activated</strong> </p></div>";
	}
}
add_action('admin_notices', 'sendinblue_integration_check_elementor_pro_is_active');