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
				'label' => __( 'Double Optin', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
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

		// Send data to Sendinblue
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
		    'body'        => json_encode(["attributes" => [ "FIRSTNAME" => $fields[$settings['sendinblue_name_field']], "LASTNAME" => $fields[$settings['sendinblue_last_name_field']] ],"updateEnabled" => true,"listIds" => [(int)$settings['sendinblue_list']],"email" => $fields[$settings['sendinblue_email_field']]])
			)
		);

	}

}