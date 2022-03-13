<?php

// Link to support and pro page from plugins screen
function webtica_sendinblue_filter_action_links( $links ) {

	$links['settings'] = '<a href="' . admin_url( 'options-general.php?page=webtica-sendinblue-free' ) . '">' . __( 'Settings' ) . '</a>';
	$links['proversion'] = '<a href="https://plugins.webtica.be/product/sendinblue-pro-integration-for-elementor-forms/?ref=plugin-settings-page" target="_blank">Get Pro version</a>';
	$links['support'] = '<a href="https://plugins.webtica.be/support/?ref=plugin-settings-page" target="_blank">Support</a>';
	return $links;

}
add_filter( 'plugin_action_links_integration-for-elementor-forms-sendinblue/sendinblue-elementor-integration.php', 'webtica_sendinblue_filter_action_links', 10, 3 );

// Add global site setting API Key option
class WebticaSendinblueFree {
	private $webtica_sendinblue_free_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'webtica_sendinblue_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'webtica_sendinblue_page_init' ) );
	}

	public function webtica_sendinblue_add_plugin_page() {
		add_options_page(
			'Sendinblue', // page_title
			'Sendinblue', // menu_title
			'manage_options', // capability
			'webtica-sendinblue-free', // menu_slug
			array( $this, 'webtica_sendinblue_create_admin_page' ) // function
		);
	}

	public function webtica_sendinblue_create_admin_page() {
		$this->webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' ); ?>

		<div class="wrap">
			<h2>Integration for Elementor form - Sendinblue</h2>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'webtica_sendinblue_option_group' );
					do_settings_sections( 'webtica-sendinblue-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function webtica_sendinblue_page_init() {
		register_setting(
			'webtica_sendinblue_option_group', // option_group
			'webtica_sendinblue_option_name', // option_name
			array( $this, 'webtica_sendinblue_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'webtica_sendinblue_setting_section', // id
			'Settings', // title
			array( $this, 'webtica_sendinblue_section_info' ), // callback
			'webtica-sendinblue-admin' // page
		);

		add_settings_field(
			'global_api_key_webtica_sendinblue', // id
			'Global Sendinblue API key', // title
			array( $this, 'global_api_key_webtica_sendinblue_callback' ), // callback
			'webtica-sendinblue-admin', // page
			'webtica_sendinblue_setting_section' // section
		);
	}

	public function webtica_sendinblue_sanitize($input) {
		$sanitary_values = array();

		if ( isset( $input['global_api_key_webtica_sendinblue'] ) ) {
			$sanitary_values['global_api_key_webtica_sendinblue'] = sanitize_text_field( $input['global_api_key_webtica_sendinblue'] );
		}

		return $sanitary_values;
	}

	public function webtica_sendinblue_section_info() {
		echo "Here you can find all your Webtica Integration for Elementor Form - Sendinblue settings";
	}

	public function global_api_key_webtica_sendinblue_callback() {
		if (empty($this->webtica_sendinblue_options['global_api_key_webtica_sendinblue'])){
			printf(
				'<input class="regular-text" type="text" name="webtica_sendinblue_option_name[global_api_key_webtica_sendinblue]" id="global_api_key_webtica_sendinblue" value="%s">',
				isset( $this->webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ? esc_attr( $this->webtica_sendinblue_options['global_api_key_webtica_sendinblue']) : ''
			);
		}
		else{
			printf(
				'<input class="regular-text" type="password" name="webtica_sendinblue_option_name[global_api_key_webtica_sendinblue]" id="global_api_key_webtica_sendinblue" value="%s">',
				isset( $this->webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ? esc_attr( $this->webtica_sendinblue_options['global_api_key_webtica_sendinblue']) : ''
			);
		}
	}

}
if ( is_admin() )
	$webtica_sendinblue = new WebticaSendinblueFree();