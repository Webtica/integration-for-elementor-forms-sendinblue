<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Sendinblue_Integration_Unsubscribe_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'sendinblue unsubscribe integration';
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
		return __( 'Sendinblue Unsubscribe', 'sendinblue-elementor-integration' );
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
			'section_sendinblue_unsubscribe-elementor-integration',
			[
				'label' => __( 'Sendinblue Unsubscribe', 'sendinblue-elementor-integration' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'unsubscribe_note_alert_delete',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('<b>PLEASE NOTE - THIS ACTION DELETES THE INPUT EMAIL IN SENDINBLUE!</b>', 'sendinblue-elementor-integration'),
			]
		);

		$widget->add_control(
			'sendinblue_unsubscribe_use_global_api_key',
			[
				'label' => __( 'Global Sendinblue API key', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'sendinblue_unsubscribe_use_global_api_key_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('You can set your global API key <a href="' . admin_url( 'options-general.php?page=webtica-sendinblue-free' ) . '" target="_blank">here.</a> this means you only need to set your Sendinblue API key once.', 'sendinblue-elementor-integration'),
				'condition' => array(
					'sendinblue_use_global_api_key' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'sendinblue_unsubscribe_api',
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
			'sendinblue_unsubscribe_gdpr_checkbox',
			[
				'label' => __( 'GDPR Checkbox', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'sendinblue_unsubscribe_gdpr_checkbox_field',
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
			'sendinblue_unsubscribe_email_field',
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
			'pro_unsubscribe_version_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need more attributes? <a href="https://plugins.webtica.be/product/sendinblue-pro-integration-for-elementor-forms/?ref=plugin-widget" target="_blank">Check out our Pro version.</a>', 'sendinblue-elementor-integration'),
			]
		);

		$widget->add_control(
			'need_unsubscribe_help_note',
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
			$element['sendinblue_unsubscribe_use_global_api_key'],
			$element['sendinblue_unsubscribe_api'],
			$element['sendinblue_unsubscribe_gdpr_checkbox'],
			$element['sendinblue_unsubscribe_gdpr_checkbox_field'],
			$element['sendinblue_unsubscribe_list'],
			$element['sendinblue_unsubscribe_email_field']
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
		$useglobalkey = $settings['sendinblue_unsubscribe_use_global_api_key'];
		if ($useglobalkey == "yes") {
			$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
			$globalapikey = $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
			if ( empty( $globalapikey ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue Global API Key not set.'); }
				return;
			}
			else {
				$settings['sendinblue_unsubscribe_api'] = $globalapikey;
			}
		}
		else {
			//  Make sure that there is an Sendinblue API key set
			if ( empty( $settings['sendinblue_unsubscribe_api'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Sendinblue API Key not set.'); }
				return;
			}
		}

		// Make sure that there is a Sendinblue Email field ID
		if ( empty( $settings['sendinblue_unsubscribe_email_field'] ) ) {
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
		if (strpos($settings['sendinblue_unsubscribe_email_field'], '[field id=') !== false) {
			$settings['sendinblue_unsubscribe_email_field'] = substr($settings['sendinblue_unsubscribe_email_field'], strpos($settings['sendinblue_unsubscribe_email_field'], '"') + 1);
			$settings['sendinblue_unsubscribe_email_field'] = trim($settings['sendinblue_unsubscribe_email_field'], '"]');
		}

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['sendinblue_unsubscribe_email_field'] ] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Client did not enter an e-mail.'); }
			return;
		}

		//GDPR Checkbox
		$gdprcheckbox = $settings['sendinblue_unsubscribe_gdpr_checkbox'];
		if ($gdprcheckbox == "yes") {
			//  Make sure that there is a acceptence field if switch is set
			if ( empty( $settings['sendinblue_unsubscribe_gdpr_checkbox_field'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Acceptence field ID is not set.'); }
				return;
			}
			// Make sure that checkbox is on
			$gdprcheckboxchecked = $fields[$settings['sendinblue_unsubscribe_gdpr_checkbox_field']];
			if ($gdprcheckboxchecked != "on") {
				if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - GDPR Checkbox was not thicked.'); }
				return;
			}
		}
		$requesturl = 'https://api.brevo.com/v3/contacts/'.urlencode($fields[$settings['sendinblue_unsubscribe_email_field']]);
		//Send data to Sendinblue
		wp_remote_request( $requesturl, array(
				'method'      => 'DELETE',
		    	'timeout'     => 45,
		    	'httpversion' => '1.0',
		    	'blocking'    => false,
		    	'headers'     => [
	            	'accept' => 'application/json',
	            	'api-key' => $settings['sendinblue_unsubscribe_api'],
		    		'content-Type' => 'application/json',
		    	],
		    	'body'        => ''
			)
		);	
	}
}