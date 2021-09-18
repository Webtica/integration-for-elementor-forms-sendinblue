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
			'sendinblue_url',
			[
				'label' => __( 'Sendy URL', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'http://your_sendy_installation/',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter the URL where you have Sendy installed', 'sendinblue-elementor-integration' ),
			]
		);

		$widget->add_control(
			'sendinblue_list',
			[
				'label' => __( 'Sendinblue List ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'separator' => 'before',
				'description' => __( 'the list id you want to subscribe a user to. This encrypted & hashed id can be found under View all lists section named ID.', 'sendinblue-elementor-integration' ),
			]
		);

		$widget->add_control(
			'sendinblue_email_field',
			[
				'label' => __( 'Email Field ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'sendinblue_name_field',
			[
				'label' => __( 'Name Field ID', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
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
			$element['sendinblue_url'],
			$element['sendinblue_list'],
			$element['sendinblue_email_field'],
			$element['sendinblue_name_field']
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

		//  Make sure that there is a Sendy installation url
		if ( empty( $settings['sendinblue_url'] ) ) {
			return;
		}

		//  Make sure that there is a Sendy list ID
		if ( empty( $settings['sendinblue_list'] ) ) {
			return;
		}

		// Make sure that there is a Sendy Email field ID
		// which is required by Sendy's API to subsribe a user
		if ( empty( $settings['sendinblue_email_field'] ) ) {
			return;
		}

		// Get sumitetd Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		// Make sure that the user emtered an email
		// which is required by Sendy's API to subsribe a user
		if ( empty( $fields[ $settings['sendinblue_email_field'] ] ) ) {
			return;
		}

		// If we got this far we can start building our request data
		// Based on the param list at https://sendy.co/api
		$sendy_data = [
			'email' => $fields[ $settings['sendinblue_email_field'] ],
			'list' => $settings['sendinblue_list'],
			'ipaddress' => \ElementorPro\Classes\Utils::get_client_ip(),
			'referrer' => isset( $_POST['referrer'] ) ? $_POST['referrer'] : '',
		];

		// add name if field is mapped
		if ( empty( $fields[ $settings['sendinblue_name_field'] ] ) ) {
			$sendy_data['name'] = $fields[ $settings['sendinblue_name_field'] ];
		}

		// Send the request
		wp_remote_post( $settings['sendinblue_url'] . 'subscribe', [
			'body' => $sendy_data,
		] );
	}

}