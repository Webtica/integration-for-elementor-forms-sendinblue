<?php

/**
 * Plugin Name: Sendinblue Elementor integration
 * Description: Connect and send data to sendinblue from submitted elementor forms
 * Author: Webtica
 * Author URI: https://webtica.be/
 * Version: 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

//Check if Elementor pro is installed
function sendinblue_integration_check_elementor_pro_is_active() {

	if ( !is_plugin_active('elementor-pro/elementor-pro.php') ) {
		echo "<div class='error'><p><strong>Sendinblue Elementor integration</strong> requires <strong> Elementor Pro plugin to be installed and activated</strong> </p></div>";
	}
}
add_action('admin_notices', 'sendinblue_integration_check_elementor_pro_is_active');