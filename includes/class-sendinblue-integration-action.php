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
			'sendinblue_api',
			[
				'label' => __( 'Sendinblue API key', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'xkeysib-xxxxxxxx',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter your V3 API key from Sendinblue', 'sendinblue-elementor-integration' ),
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
				'separator' => 'before',
				'description' => __( 'Enter the email field id - you can find this under the email field advanced tab', 'sendinblue-elementor-integration' ),
			]
		);

		$widget->add_control(
			'sendinblue_name_field',
			[
				'label' => __( 'Name Field ID (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'name',
				'separator' => 'before',
				'description' => __( 'Enter the name field id - you can find this under the name field advanced tab', 'sendinblue-elementor-integration' ),
			]
		);

		$widget->add_control(
			'sendinblue_last_name_field',
			[
				'label' => __( 'Lastname Field ID (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'lastname',
				'separator' => 'before',
				'description' => __( 'Enter the lastname field id - you can find this under the lastname field advanced tab', 'sendinblue-elementor-integration' ),
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
			$element['sendinblue_api'],
			$element['sendinblue_double_optin'],
			$element['sendinblue_double_optin_template'],
			$element['sendinblue_double_optin_redirect_url'],
			$element['sendinblue_list'],
			$element['sendinblue_email_field'],
			$element['sendinblue_name_field'],
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

		//  Make sure that there is an Sendinblue API key set
		if ( empty( $settings['sendinblue_api'] ) ) {
			return;
		}

		//  Make sure that there is a Sendinblue list ID
		if ( empty( $settings['sendinblue_list'] ) ) {
			return;
		}

		if ($doubleoptin == "yes") {
			//  Make sure that there is a Sendinblue double optin ID if switch is set
			if ( empty( $settings['sendinblue_double_optin_template'] ) ) {
				return;
			}
			//  Make sure that there is a Sendinblue double optin redirect URL if switch is set
			if ( empty( $settings['sendinblue_double_optin_redirect_url'] ) ) {
				return;
			}
		}

		// Make sure that there is a Sendinblue Email field ID
		if ( empty( $settings['sendinblue_email_field'] ) ) {
			return;
		}

		// Get submitted Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['sendinblue_email_field'] ] ) ) {
			return;
		}

		$doubleoptin = $settings['sendinblue_double_optin'];


		if ($doubleoptin == "yes") {
			//Send data to Sendinblue Double optin
			$dpubleoptin = wp_remote_post( 'https://api.sendinblue.com/v3/contacts/doubleOptinConfirmation', array(
				'method'      => 'POST',
			    'timeout'     => 45,
			    'httpversion' => '1.0',
			    'blocking'    => false,
			    'headers'     => [
		            'accept' => 'application/json',
		            'api-key' => $settings['sendinblue_api'],
			    	'content-Type' => 'application/json',
			    ],
			    'body'        => json_encode(["attributes" => [ "FIRSTNAME" => $fields[$settings['sendinblue_name_field']], "LASTNAME" => $fields[$settings['sendinblue_last_name_field']] ], "includeListIds" => [(int)$settings['sendinblue_list']], "templateId" => (int)$settings['sendinblue_double_optin_template'], "redirectionUrl" => $settings['sendinblue_double_optin_redirect_url'], "email" => $fields[$settings['sendinblue_email_field']]])
				)
			);
		}
		else {
			//Send data to Sendinblue
			wp_remote_post( 'https://api.sendinblue.com/v3/contacts', array(
				'method'      => 'POST',
		    	'timeout'     => 45,
		    	'httpversion' => '1.0',
		    	'blocking'    => false,
		    	'headers'     => [
	            	'accept' => 'application/json',
	            	'api-key' => $settings['sendinblue_api'],
		    		'content-Type' => 'application/json',
		    	],
		    	'body'        => json_encode(["attributes" => [ "FIRSTNAME" => $fields[$settings['sendinblue_name_field']], "LASTNAME" => $fields[$settings['sendinblue_last_name_field']] ], "updateEnabled" => true, "listIds" => [(int)$settings['sendinblue_list']], "email" => $fields[$settings['sendinblue_email_field']]])
				)
			);	
		}
	}
}