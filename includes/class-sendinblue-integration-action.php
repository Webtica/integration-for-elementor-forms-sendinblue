<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sendinblue_Integration_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_sendinblue_clear_attributes_cache', array( $this, 'ajax_clear_attributes_cache' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Format phone number for Brevo SMS/WHATSAPP attributes
	 *
	 * Converts local phone numbers to international format required by Brevo API.
	 * Examples (with default country code 32 for Belgium):
	 * - "0471234567" becomes "32471234567"
	 * - "+32471234567" stays "+32471234567"
	 * - "0032471234567" stays "0032471234567"
	 * - "32471234567" stays "32471234567"
	 *
	 * @param string $phone_number The phone number to format
	 * @param string $default_country_code The country code to use (default: '32' for Belgium)
	 * @return string The formatted phone number
	 */
	private function format_phone_number( $phone_number, $default_country_code = '32' ) {
		$phone = trim( $phone_number );

		// Check if it starts with + (keep it as is but clean up)
		$has_plus = ( substr( $phone, 0, 1 ) === '+' );

		// Remove all non-numeric characters
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		if ( empty( $phone ) ) {
			return $phone_number;
		}

		// If original had +, return with + prefix
		if ( $has_plus ) {
			return '+' . $phone;
		}

		// If starts with 00, it's already international format
		if ( substr( $phone, 0, 2 ) === '00' ) {
			return $phone;
		}

		// If starts with country code already, return as is
		if ( strlen( $phone ) > strlen( $default_country_code ) &&
		     substr( $phone, 0, strlen( $default_country_code ) ) === $default_country_code ) {
			return $phone;
		}

		// If starts with 0, remove leading 0 and prepend country code
		if ( substr( $phone, 0, 1 ) === '0' ) {
			$phone = $default_country_code . substr( $phone, 1 );
			return $phone;
		}

		// Otherwise, prepend country code
		$phone = $default_country_code . $phone;

		return $phone;
	}

	/**
	 * Check if attribute is a phone-related attribute (SMS or WHATSAPP)
	 *
	 * @param string $attribute_name The attribute name to check
	 * @return bool True if it's a phone attribute
	 */
	private function is_phone_attribute( $attribute_name ) {
		$phone_attributes = array( 'SMS', 'WHATSAPP', 'sms', 'whatsapp', 'Sms', 'Whatsapp' );
		return in_array( $attribute_name, $phone_attributes, true );
	}

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
	 * Enqueue Editor Scripts
	 */
	public function enqueue_editor_scripts() {
		wp_add_inline_script( 'elementor-editor', "
			jQuery(document).ready(function($) {
				elementor.channels.editor.on('sendinblue:refresh_attributes', function() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'sendinblue_clear_attributes_cache',
							nonce: '" . wp_create_nonce( 'sendinblue_clear_cache' ) . "'
						},
						success: function(response) {
							if (response.success) {
								elementor.notifications.showToast({
									message: 'Brevo attributes cache cleared! Please refresh the page to see updated fields.',
								});
							} else {
								elementor.notifications.showToast({
									message: 'Error: ' + response.data,
								});
							}
						}
					});
				});
			});
		" );
	}

	/**
	 * AJAX Handler to clear attributes cache
	 */
	public function ajax_clear_attributes_cache() {
		check_ajax_referer( 'sendinblue_clear_cache', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$attributes_manager = Sendinblue_Attributes_Manager::get_instance();
		$attributes_manager->clear_cache();

		// Also clear saved custom attributes cache so it re-scans after Brevo attributes are refreshed
		delete_transient( 'sendinblue_saved_custom_attributes' );

		wp_send_json_success();
	}

	/**
	 * Get Brevo Attributes
	 *
	 * Returns available Brevo attributes for dropdown
	 * Fetches from all available API keys (global + form-specific)
	 *
	 * @access private
	 * @return array
	 */
	private function get_brevo_attributes_options() {
		$options = array(
			'' => __( '-- Select Attribute / Empty --', 'sendinblue-elementor-integration' ),
		);

		// Collect all unique API keys from global settings and forms
		$api_keys = $this->get_all_api_keys();

		if ( WP_DEBUG === true ) {
			error_log( sprintf( 'Sendinblue: Found %d unique API key(s)', count( $api_keys ) ) );
		}

		$attributes_manager = Sendinblue_Attributes_Manager::get_instance();
		$all_attributes = array();

		// Fetch attributes from all API keys
		foreach ( $api_keys as $api_key ) {
			if ( empty( $api_key ) ) {
				continue;
			}

			$attributes = $attributes_manager->get_attributes( $api_key );

			if ( WP_DEBUG === true ) {
				error_log( sprintf( 'Sendinblue: Fetched %d attributes from API key ending in ...%s',
					count( $attributes ),
					substr( $api_key, -8 )
				) );
			}

			if ( ! empty( $attributes ) && is_array( $attributes ) ) {
				// Merge attributes, using attribute name as key to avoid duplicates
				foreach ( $attributes as $attribute ) {
					$name = $attribute['name'];
					if ( ! isset( $all_attributes[ $name ] ) ) {
						$all_attributes[ $name ] = $attribute;
					}
				}
			}
		}

		// Convert attributes to dropdown options
		if ( ! empty( $all_attributes ) ) {
			foreach ( $all_attributes as $attribute ) {
				$name = $attribute['name'];
				$type = isset( $attribute['type'] ) ? ' (' . $attribute['type'] . ')' : '';
				$options[ $name ] = $name . $type;
			}
		}

		// Add commonly used defaults if not fetched
		if ( count( $options ) === 1 ) {
			// English defaults
			$options['FIRSTNAME'] = 'FIRSTNAME (text)';
			$options['LASTNAME'] = 'LASTNAME (text)';
			// German defaults
			$options['VORNAME'] = 'VORNAME (text)';
			$options['NACHNAME'] = 'NACHNAME (text)';
			// Common attributes
			$options['SMS'] = 'SMS (text)';
			$options['OPT_IN'] = 'OPT_IN (boolean)';
			$options['DOUBLE_OPT-IN'] = 'DOUBLE_OPT-IN (boolean)';
		}

		// Include saved custom attributes from all forms to ensure they appear in dropdown
		// This helps with migration from older versions where custom text was allowed
		$saved_attributes = $this->get_saved_custom_attributes();
		foreach ( $saved_attributes as $attr_name ) {
			if ( ! isset( $options[ $attr_name ] ) ) {
				$options[ $attr_name ] = $attr_name . ' (custom)';
			}
		}

		return $options;
	}

	/**
	 * Get all unique API keys from global settings and forms
	 *
	 * @return array Array of unique API keys
	 */
	private function get_all_api_keys() {
		$api_keys = array();

		// Get global API key
		$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
		if ( ! empty( $webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ) {
			$api_keys[] = $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
		}

		// Get form-specific API keys from database
		global $wpdb;
		$posts = $wpdb->get_results(
			"SELECT meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_elementor_data'
			LIMIT 100"
		);

		foreach ( $posts as $post ) {
			$elementor_data = json_decode( $post->meta_value, true );
			if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
				continue;
			}

			$this->extract_api_keys( $elementor_data, $api_keys );
		}

		// Return unique API keys
		return array_unique( array_filter( $api_keys ) );
	}

	/**
	 * Recursively extract API keys from Elementor data
	 *
	 * @param array $data Elementor data
	 * @param array &$api_keys Array to collect API keys
	 */
	private function extract_api_keys( $data, &$api_keys ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				// Check if this is a form with Sendinblue integration
				if ( isset( $value['widgetType'] ) && $value['widgetType'] === 'form' ) {
					if ( isset( $value['settings']['submit_actions'] ) &&
					     is_array( $value['settings']['submit_actions'] ) &&
					     in_array( 'sendinblue integration', $value['settings']['submit_actions'] ) ) {

						// Check for form-specific API key (when global key toggle is off)
						if ( isset( $value['settings']['sendinblue_api'] ) &&
						     ! empty( $value['settings']['sendinblue_api'] ) &&
						     ( ! isset( $value['settings']['sendinblue_use_global_api_key'] ) ||
						       $value['settings']['sendinblue_use_global_api_key'] !== 'yes' ) ) {
							$api_keys[] = $value['settings']['sendinblue_api'];
						}
					}
				}

				// Recurse into nested elements
				$this->extract_api_keys( $value, $api_keys );
			}
		}
	}

	/**
	 * Get saved custom attributes from all forms
	 *
	 * @return array Array of unique attribute names
	 */
	private function get_saved_custom_attributes() {
		// Check cache first
		$cached = get_transient( 'sendinblue_saved_custom_attributes' );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$attribute_names = array();

		// Get all Elementor data
		$posts = $wpdb->get_results(
			"SELECT meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_elementor_data'
			LIMIT 100"
		);

		if ( WP_DEBUG === true ) {
			error_log( sprintf( 'Sendinblue: Scanning %d posts for custom attributes', count( $posts ) ) );
		}

		foreach ( $posts as $post ) {
			$elementor_data = json_decode( $post->meta_value, true );
			if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
				continue;
			}

			$this->extract_saved_attributes( $elementor_data, $attribute_names );
		}

		$unique_attributes = array_unique( $attribute_names );

		if ( WP_DEBUG === true ) {
			error_log( sprintf( 'Sendinblue: Found %d unique custom attributes: %s', count( $unique_attributes ), implode( ', ', $unique_attributes ) ) );
		}

		// Cache for 1 hour
		set_transient( 'sendinblue_saved_custom_attributes', $unique_attributes, HOUR_IN_SECONDS );

		return $unique_attributes;
	}

	/**
	 * Recursively extract saved attribute names from Elementor data
	 *
	 * @param array $data Elementor data
	 * @param array &$attribute_names Array to collect attribute names
	 */
	private function extract_saved_attributes( $data, &$attribute_names ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				// Check if this is a form with Sendinblue integration
				if ( isset( $value['widgetType'] ) && $value['widgetType'] === 'form' ) {
					if ( isset( $value['settings']['sendinblue_attribute_list'] ) &&
					     is_array( $value['settings']['sendinblue_attribute_list'] ) ) {

						foreach ( $value['settings']['sendinblue_attribute_list'] as $attribute ) {
							if ( isset( $attribute['sendinblue_custom_attribute_name'] ) &&
							     ! empty( $attribute['sendinblue_custom_attribute_name'] ) ) {
								$attribute_names[] = $attribute['sendinblue_custom_attribute_name'];
							}
						}
					}
				}

				// Recurse into nested elements
				$this->extract_saved_attributes( $value, $attribute_names );
			}
		}
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
			'dynamic_mapping_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('<strong>Dynamic Field Mapping:</strong> Brevo attributes are automatically fetched from your account and cached for 1 hour.', 'sendinblue-elementor-integration'),
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'sendinblue_refresh_attributes',
			[
				'type' => \Elementor\Controls_Manager::BUTTON,
				'label' => __( 'Refresh Brevo Attributes', 'sendinblue-elementor-integration' ),
				'text' => __( 'Clear Cache & Refresh', 'sendinblue-elementor-integration' ),
				'button_type' => 'default',
				'event' => 'sendinblue:refresh_attributes',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'sms_field_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('<strong>SMS/WHATSAPP Support:</strong> When you select SMS or WHATSAPP as an attribute, phone numbers are automatically formatted for the Brevo API. A country code field will appear where you can set your default country code (e.g., 32 for Belgium).', 'sendinblue-elementor-integration'),
			]
		);

		$widget->add_control(
			'sendinblue_name_attribute_field',
			[
				'label' => __( 'Name Field attribute (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_brevo_attributes_options(),
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Select the firstname attribute - Leave empty to skip this field', 'sendinblue-elementor-integration' ),
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
			'sendinblue_name_country_code',
			[
				'label' => __( 'Country Code for Phone Formatting', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '32',
				'default' => '32',
				'label_block' => false,
				'description' => __( 'Enter country code without + or 00 (e.g., 32 for Belgium, 1 for USA, 91 for India). Example: 0471234567 becomes 32471234567', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'sendinblue_name_attribute_field',
							'operator' => 'in',
							'value' => [ 'SMS', 'WHATSAPP', 'sms', 'whatsapp', 'Sms', 'Whatsapp' ],
						],
					],
				],
			]
		);

		$widget->add_control(
			'sendinblue_last_name_attribute_field',
			[
				'label' => __( 'Lastname Field attribute (Optional)', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_brevo_attributes_options(),
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Select the lastname attribute - Leave empty to skip this field', 'sendinblue-elementor-integration' ),
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
			'sendinblue_last_name_country_code',
			[
				'label' => __( 'Country Code for Phone Formatting', 'sendinblue-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '32',
				'default' => '32',
				'label_block' => false,
				'description' => __( 'Enter country code without + or 00 (e.g., 32 for Belgium, 1 for USA, 91 for India). Example: 0471234567 becomes 32471234567', 'sendinblue-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'sendinblue_last_name_attribute_field',
							'operator' => 'in',
							'value' => [ 'SMS', 'WHATSAPP', 'sms', 'whatsapp', 'Sms', 'Whatsapp' ],
						],
					],
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
			$element['sendinblue_name_country_code'],
			$element['sendinblue_last_name_attribute_field'],
			$element['sendinblue_last_name_field'],
			$element['sendinblue_last_name_country_code']
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

		//Check double optin fields
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

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['sendinblue_email_field'] ] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms Sendinblue integration - Client did not enter an e-mail.'); }
			return;
		}

		// Build attributes array
		$attributes = array();

		// Add firstname attribute if both attribute name and field ID are set
		if ( ! empty( $settings['sendinblue_name_attribute_field'] ) &&
		     ! empty( $settings['sendinblue_name_field'] ) &&
		     isset( $fields[$settings['sendinblue_name_field']] ) ) {
			$name_value = $fields[$settings['sendinblue_name_field']];

			// Auto-format phone numbers for SMS and WHATSAPP attributes
			if ( $this->is_phone_attribute( $settings['sendinblue_name_attribute_field'] ) && ! empty( $name_value ) ) {
				$country_code = ! empty( $settings['sendinblue_name_country_code'] )
					? preg_replace( '/[^0-9]/', '', $settings['sendinblue_name_country_code'] )
					: '32';

				$original_value = $name_value;
				$name_value = $this->format_phone_number( $name_value, $country_code );

				if( WP_DEBUG === true ) {
					error_log( sprintf(
						'Elementor forms Sendinblue integration - Phone formatting for %s: "%s" -> "%s" (country code: %s)',
						$settings['sendinblue_name_attribute_field'],
						$original_value,
						$name_value,
						$country_code
					) );
				}
			}

			$attributes[$settings['sendinblue_name_attribute_field']] = $name_value;
		}

		// Add lastname attribute if both attribute name and field ID are set
		if ( ! empty( $settings['sendinblue_last_name_attribute_field'] ) &&
		     ! empty( $settings['sendinblue_last_name_field'] ) &&
		     isset( $fields[$settings['sendinblue_last_name_field']] ) ) {
			$lastname_value = $fields[$settings['sendinblue_last_name_field']];

			// Auto-format phone numbers for SMS and WHATSAPP attributes
			if ( $this->is_phone_attribute( $settings['sendinblue_last_name_attribute_field'] ) && ! empty( $lastname_value ) ) {
				$country_code = ! empty( $settings['sendinblue_last_name_country_code'] )
					? preg_replace( '/[^0-9]/', '', $settings['sendinblue_last_name_country_code'] )
					: '32';

				$original_value = $lastname_value;
				$lastname_value = $this->format_phone_number( $lastname_value, $country_code );

				if( WP_DEBUG === true ) {
					error_log( sprintf(
						'Elementor forms Sendinblue integration - Phone formatting for %s: "%s" -> "%s" (country code: %s)',
						$settings['sendinblue_last_name_attribute_field'],
						$original_value,
						$lastname_value,
						$country_code
					) );
				}
			}

			$attributes[$settings['sendinblue_last_name_attribute_field']] = $lastname_value;
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


			if( WP_DEBUG === true ) {
				error_log('Elementor forms Sendinblue integration - Check email exists response: ' . wp_json_encode($request));
			}
		} else {
			$emailexists = "no";
		}

		if ($doubleoptin == "yes" && $emailexists == "no") {

			$double_optin_body = [
				"attributes" => (object)$attributes,
				"includeListIds" => [(int)$settings['sendinblue_list']],
				"templateId" => (int)$settings['sendinblue_double_optin_template'],
				"redirectionUrl" => $doubleoptinurl,
				"email" => $fields[$settings['sendinblue_email_field']]
			];

			// Log the double optin request body
			if( WP_DEBUG === true ) {
				error_log('Elementor forms Sendinblue integration - Double optin request body: ' . wp_json_encode($double_optin_body));
			}

			//Send data to Sendinblue Double optin
			$double_optin_response = wp_remote_post( 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation', array(
				'method'      => 'POST',
			    'timeout'     => 45,
			    'httpversion' => '1.0',
			    'blocking'    => true,
			    'headers'     => [
		            'accept' => 'application/json',
		            'api-key' => $settings['sendinblue_api'],
			    	'content-Type' => 'application/json',
			    ],
			    'body'        => json_encode($double_optin_body)
				)
			);

			// Log the double optin response
			if( WP_DEBUG === true ) {
				error_log('Elementor forms Sendinblue integration - Double optin response: ' . wp_json_encode($double_optin_response));
			}
		}
		else {
			// Prepare request body for regular contact addition
			$contact_body = [
				"attributes" => (object)$attributes,
				"updateEnabled" => true,
				"listIds" => [(int)$settings['sendinblue_list']],
				"email" => $fields[$settings['sendinblue_email_field']]
			];

			// Log the contact request body
			if( WP_DEBUG === true ) {
				error_log('Elementor forms Sendinblue integration - Contact request body: ' . wp_json_encode($contact_body));
			}

			//Send data to Sendinblue
			$contact_response = wp_remote_post( 'https://api.brevo.com/v3/contacts', array(
				'method'      => 'POST',
		    	'timeout'     => 45,
		    	'httpversion' => '1.0',
		    	'blocking'    => true,
		    	'headers'     => [
	            	'accept' => 'application/json',
	            	'api-key' => $settings['sendinblue_api'],
		    		'content-Type' => 'application/json',
		    	],
		    	'body'        => json_encode($contact_body)
				)
			);

			// Log the contact response
			if( WP_DEBUG === true ) {
				error_log('Elementor forms Sendinblue integration - Contact response: ' . wp_json_encode($contact_response));
			}
		}
	}
}
