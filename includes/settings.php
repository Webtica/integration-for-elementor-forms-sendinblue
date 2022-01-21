<?php

// Link to support and pro page from plugins screen
function webtica_sendinblue_filter_action_links( $links ) {

	$links['proversion'] = '<a href="https://plugins.webtica.be/product/sendinblue-pro-integration-for-elementor-forms/?ref=plugin-settings-page" target="_blank">Get Pro version</a>';
	$links['support'] = '<a href="https://plugins.webtica.be/support/?ref=plugin-settings-page" target="_blank">Support</a>';
	return $links;

}
add_filter( 'plugin_action_links_integration-for-elementor-forms-sendinblue/sendinblue-elementor-integration.php', 'webtica_sendinblue_filter_action_links', 10, 3 );