<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Sendinblue_Integration_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'sendinblue integration';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return __( 'Sendinblue', 'sendinblue-elementor-integration' );
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_sendinblue-elementor-integration',
			[
				'label' => __( 'Sendinblue', 'sendinblue-elementor-integration' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'sendinblue_use_global_api_key',
			[
				'label' => __( 'Global Sendinblue API key', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'sendinblue_use_global_api_key_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('You can set your global API key <a href="' . admin_url( 'options-general.php?page=webtica-sendinblue-free' ) . '" target="_blank">here.</a> this means you only need to set your Sendinblue API key once.', 'sendinblue-elementor-integration'),
				'condition' => array(
					'sendinblue_use_global_api_key' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'sendinblue_api',
			[
				'label' => __( 'Sendinblue API key', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'xkeysib-xxxxxxxx',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter your V3 API key from Sendinblue', 'sendinblue-elementor-integration' ),
				'condition' => array(
					'sendinblue_use_global_api_key!' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_double_optin',
			[
				'label' => __( 'Double Opt-in', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'sendinblue_double_optin_template',
			[
				'label' => __( 'Double Opt-in Template ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => '5',
				'separator' => 'before',
				'description' => __( 'Enter your double opt-in template ID - <a href="https://help.sendinblue.com/hc/en-us/articles/360019540880-Create-a-double-opt-in-DOI-confirmation-template-for-Sendinblue-form" target="_blank">More info here</a>', 'sendinblue-elementor-integration' ),
    			'condition' => array(
    				'sendinblue_double_optin' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_double_optin_redirect_url',
			[
				'label' => __( 'Double Opt-in Redirect URL', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://website.com/thank-you',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter the url you want to redirect to after the subscriber confirms double opt-in', 'sendinblue-elementor-integration' ),
    			'condition' => array(
    				'sendinblue_double_optin' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_double_optin_check_if_email_exists',
			[
				'label' => __( 'Check if user already exists - Skip Double Opt-in', 'sendinblue-elementor-integration' ),
				'description' => __( 'Note: This will skip the notification email. This will still update the users fields', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before',
    			'condition' => array(
    				'sendinblue_double_optin' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'sendinblue_gdpr_checkbox',
			[
				'label' => __( 'GDPR Checkbox', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'sendinblue_gdpr_checkbox_field',
			[
				'label' => __( 'Acceptance Field ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'acceptancefieldid',
				'separator' => 'before',
				'description' => __( 'Enter the acceptance checkbox field id - you can find this under the acceptance field advanced tab - if the acceptance checkbox is not checked then the email and extra information will not be added to the list', 'sendinblue-elementor-integration' ),
    			'condition' => array(
    				'sendinblue_gdpr_checkbox' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_list',
			[
				'label' => __( 'Sendinblue List ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => '5',
				'separator' => 'before',
				'description' => __( 'Enter your list number', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_email_field',
			[
				'label' => __( 'Email Field ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'email',
				'default' => 'email',
				'separator' => 'before',
				'description' => __( 'Enter the email field id - you can find this under the email field advanced tab', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_name_attribute_field',
			[
				'label' => __( 'Name Field attribute (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'FIRSTNAME',
				'separator' => 'before',
				'description' => __( 'Enter the firstname attribute name - you can find this under contact attributes settings in Sendinblue - If this field is not set it wil default to FIRSTNAME', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_name_field',
			[
				'label' => __( 'Name Field ID (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'name',
				'description' => __( 'Enter the name field id - you can find this under the name field advanced tab', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_last_name_attribute_field',
			[
				'label' => __( 'Lastname Field attribute (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'LASTNAME',
				'separator' => 'before',
				'description' => __( 'Enter the lastname attribute name - you can find this under contact attributes settings in Sendinblue - If this field is not set it wil default to LASTNAME', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'sendinblue_last_name_field',
			[
				'label' => __( 'Lastname Field ID (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'lastname',
				'description' => __( 'Enter the lastname field id - you can find this under the lastname field advanced tab', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'pro_version_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need more attributes? <a href="https://plugins.webtica.be/product/sendinblue-pro-integration-for-elementor-forms/?ref=plugin-widget" target="_blank">Check out our Pro version.</a>', 'sendinblue-elementor-integration'),
			]
		);

		$widget->add_control(
			'need_help_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need help? <a href="https://plugins.webtica.be/support/?ref=plugin-widget" target="_blank">Check out our support page.</a>', 'sendinblue-elementor-integration'),
			]
		);

		$widget->end_controls_section();

	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['sendinblue_use_global_api_key'],
			$element['sendinblue_api'],
			$element['sendinblue_double_optin'],
			$element['sendinblue_double_optin_template'],
			$element['sendinblue_double_optin_redirect_url'],
			$element['sendinblue_double_optin_check_if_email_exists'],
			$element['sendinblue_gdpr_checkbox'],
			$element['sendinblue_gdpr_checkbox_field'],
			$element['sendinblue_list'],
			$element['sendinblue_email_field'],
			$element['sendinblue_name_attribute_field'],
			$element['sendinblue_name_field'],
			$element['sendinblue_last_name_attribute_field'],
			$element['sendinblue_last_name_field']
		);

		return $element;
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		//Global key
		$useglobalkey = $settings['sendinblue_use_global_api_key'];
		if ($useglobalkey == "yes") {
			$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
			$globalapikey = $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
			if ( empty( $globalapikey ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue Global API Key not set.'); }
				return;
			}
			else {
				$settings['sendinblue_api'] = $globalapikey;
			}
		}
		else {
			//  Make sure that there is an Sendinblue API key set
			if ( empty( $settings['sendinblue_api'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue API Key not set.'); }
				return;
			}
		}

		//  Make sure that there is a Sendinblue list ID
		if ( empty( $settings['sendinblue_list'] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue list ID not set.'); }
			return;
		}

		//Doubleoptin
		$doubleoptin = $settings['sendinblue_double_optin'];
		if ($doubleoptin == "yes") {
			//  Make sure that there is a Sendinblue double optin ID if switch is set
			if ( empty( $settings['sendinblue_double_optin_template'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue double optin template ID not set.'); }
				return;
			}
			//  Make sure that there is a Sendinblue double optin redirect URL else set default url
			if ( empty( $settings['sendinblue_double_optin_redirect_url'] ) ) {
				$doubleoptinurl = get_site_url();
			}
			else {
				$doubleoptinurl = $settings['sendinblue_double_optin_redirect_url'];
			}
		}

		// Make sure that there is a Sendinblue Email field ID
		if ( empty( $settings['sendinblue_email_field'] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue e-mail field ID not set.'); }
			return;
		}

		// Get submitted Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		//Check if email field contains the elementor form attribute shortcodes
		if (strpos($settings['sendinblue_email_field'], '[field id=') !== false) {
			$settings['sendinblue_email_field'] = substr($settings['sendinblue_email_field'], strpos($settings['sendinblue_email_field'], '"') + 1);
			$settings['sendinblue_email_field'] = trim($settings['sendinblue_email_field'], '"]');
		}
		//Check if first name field contains the elementor form attribute shortcodes
		if (strpos($settings['sendinblue_name_field'], '[field id=') !== false) {
			$settings['sendinblue_name_field'] = substr($settings['sendinblue_name_field'], strpos($settings['sendinblue_name_field'], '"') + 1);
			$settings['sendinblue_name_field'] = trim($settings['sendinblue_name_field'], '"]');
		}
		//Check if last name field contains the elementor form attribute shortcodes
		if (strpos($settings['sendinblue_last_name_field'], '[field id=') !== false) {
			$settings['sendinblue_last_name_field'] = substr($settings['sendinblue_last_name_field'], strpos($settings['sendinblue_last_name_field'], '"') + 1);
			$settings['sendinblue_last_name_field'] = trim($settings['sendinblue_last_name_field'], '"]');
		}

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['sendinblue_email_field'] ] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Client did not enter an e-mail.'); }
			return;
		}

		//GDPR Checkbox
		$gdprcheckbox = $settings['sendinblue_gdpr_checkbox'];
		if ($gdprcheckbox == "yes") {
			//  Make sure that there is a acceptence field if switch is set
			if ( empty( $settings['sendinblue_gdpr_checkbox_field'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Acceptence field ID is not set.'); }
				return;
			}
			// Make sure that checkbox is on
			$gdprcheckboxchecked = $fields[$settings['sendinblue_gdpr_checkbox_field']];
			if ($gdprcheckboxchecked != "on") {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - GDPR Checkbox was not thicked.'); }
				return;
			}
		}

		// Sendinblue attribute names - Firstname
		if (empty($settings['sendinblue_name_attribute_field'])) {
			$sendinblueattributename = "FIRSTNAME";
		}
		else {
			$sendinblueattributename = $settings['sendinblue_name_attribute_field'];
		}

		// Sendinblue attribute names - Lastname
		if (empty($settings['sendinblue_last_name_attribute_field'])) {
			$sendinblueattributelastname = "LASTNAME";
		}
		else {
			$sendinblueattributelastname = $settings['sendinblue_last_name_attribute_field'];
		}

		//Check if user already exists
		$emailexistsswitch = $settings['sendinblue_double_optin_check_if_email_exists'];
		if ($emailexistsswitch == "yes") {
			$requesturl = 'https://api.brevo.com/v3/contacts/'.urlencode($fields[$settings['sendinblue_email_field']]);
			//Send data to Sendinblue
			$request = wp_remote_request( $requesturl, array(
					'method'      => 'GET',
					'timeout'     => 45,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => [
						'accept' => 'application/json',
						'api-key' => $settings['sendinblue_api'],
						'content-Type' => 'application/json',
					],
					'body'        => ''
				)
			);
			$response_code = wp_remote_retrieve_response_code( $request );	
			if ($response_code == 200){
				$emailexists = "yes";
			} else {
				$emailexists = "no";
			}
		} else {
			$emailexists = "no";
		}

		if ($doubleoptin == "yes" && $emailexists == "no") {
			//Send data to Sendinblue Double optin
			wp_remote_post( 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation', array(
				'method'      => 'POST',
			    'timeout'     => 45,
			    'httpversion' => '1.0',
			    'blocking'    => false,
			    'headers'     => [
		            'accept' => 'application/json',
		            'api-key' => $settings['sendinblue_api'],
			    	'content-Type' => 'application/json',
			    ],
			    'body'        => json_encode(["attributes" => [ $sendinblueattributename => $fields[$settings['sendinblue_name_field']], $sendinblueattributelastname => $fields[$settings['sendinblue_last_name_field']] ], "includeListIds" => [(int)$settings['sendinblue_list']], "templateId" => (int)$settings['sendinblue_double_optin_template'], "redirectionUrl" => $doubleoptinurl, "email" => $fields[$settings['sendinblue_email_field']]])
				)
			);
		}
		else {
			//Send data to Sendinblue
			wp_remote_post( 'https://api.brevo.com/v3/contacts', array(
				'method'      => 'POST',
		    	'timeout'     => 45,
		    	'httpversion' => '1.0',
		    	'blocking'    => false,
		    	'headers'     => [
	            	'accept' => 'application/json',
	            	'api-key' => $settings['sendinblue_api'],
		    		'content-Type' => 'application/json',
		    	],
		    	'body'        => json_encode(["attributes" => [ $sendinblueattributename => $fields[$settings['sendinblue_name_field']], $sendinblueattributelastname => $fields[$settings['sendinblue_last_name_field']] ], "updateEnabled" => true, "listIds" => [(int)$settings['sendinblue_list']], "email" => $fields[$settings['sendinblue_email_field']]])
				)
			);	
		}
	}
}