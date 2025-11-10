<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sendinblue Migration Class
 * Handles plugin upgrades and data migrations between versions
 */
class Sendinblue_Migration {

	/**
	 * Current plugin version
	 */
	const CURRENT_VERSION = '2.0.0';

	/**
	 * Option name for storing plugin version
	 */
	const VERSION_OPTION = 'sendinblue_elementor_integration_version';

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		add_action( 'admin_notices', array( $this, 'show_migration_notice' ) );
	}

	/**
	 * Check if migration is needed
	 */
	public function check_version() {
		$installed_version = get_option( self::VERSION_OPTION, '0.0.0' );

		// If versions don't match, run migration
		if ( version_compare( $installed_version, self::CURRENT_VERSION, '<' ) ) {
			$this->run_migrations( $installed_version );
			update_option( self::VERSION_OPTION, self::CURRENT_VERSION );

			// Set a flag to show migration notice
			set_transient( 'sendinblue_migration_notice', true, 30 * DAY_IN_SECONDS );
		}
	}

	/**
	 * Show admin notice after migration
	 */
	public function show_migration_notice() {
		if ( ! get_transient( 'sendinblue_migration_notice' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$missing_attributes = get_transient( 'sendinblue_migration_missing_attrs' );

		?>
		<div class="notice notice-<?php echo ! empty( $missing_attributes ) ? 'warning' : 'success'; ?> is-dismissible">
			<p>
				<strong><?php _e( 'Sendinblue Elementor Integration:', 'sendinblue-elementor-integration' ); ?></strong>
				<?php _e( 'Your forms have been successfully migrated to version 2.0.0.', 'sendinblue-elementor-integration' ); ?>
			</p>
			<?php if ( ! empty( $missing_attributes ) ) : ?>
				<p>
					<strong><?php _e( 'Action Required:', 'sendinblue-elementor-integration' ); ?></strong>
					<?php _e( 'The following custom attributes were not found in your Brevo account:', 'sendinblue-elementor-integration' ); ?>
					<code><?php echo esc_html( implode( ', ', $missing_attributes ) ); ?></code>
				</p>
				<p>
					<?php _e( 'Please ensure these attributes exist in your Brevo account, then:', 'sendinblue-elementor-integration' ); ?>
				</p>
				<ol>
					<li><?php _e( 'Edit your Elementor form(s)', 'sendinblue-elementor-integration' ); ?></li>
					<li><?php _e( 'Go to Sendinblue integration settings', 'sendinblue-elementor-integration' ); ?></li>
					<li><?php _e( 'Click "Clear Cache & Refresh" button', 'sendinblue-elementor-integration' ); ?></li>
					<li><?php _e( 'Refresh the page to see updated attributes', 'sendinblue-elementor-integration' ); ?></li>
					<li><?php _e( 'Select the correct attribute from the dropdown for each field mapping', 'sendinblue-elementor-integration' ); ?></li>
				</ol>
			<?php else : ?>
				<p>
					<?php _e( 'All field mappings have been preserved. Please review your forms to ensure everything is working correctly.', 'sendinblue-elementor-integration' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php

		// Clear the notice after showing it once
		delete_transient( 'sendinblue_migration_notice' );
	}

	/**
	 * Run necessary migrations based on installed version
	 *
	 * @param string $from_version The version we're upgrading from
	 */
	private function run_migrations( $from_version ) {
		// Migration from 1.x.x to 2.0.0
		if ( version_compare( $from_version, '2.0.0', '<' ) ) {
			$this->migrate_to_2_0_0();
		}
	}

	/**
	 * Migrate from old version to 2.0.0
	 * Handles conversion from individual firstname/lastname fields to repeater attribute list
	 */
	private function migrate_to_2_0_0() {
		global $wpdb;

		// Clear attributes cache to force fresh fetch during migration
		if ( class_exists( 'Sendinblue_Attributes_Manager' ) ) {
			$attributes_manager = Sendinblue_Attributes_Manager::get_instance();
			$attributes_manager->clear_cache();
		}

		// Get all posts that contain Elementor data
		$posts = $wpdb->get_results(
			"SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_elementor_data'"
		);

		if ( empty( $posts ) ) {
			return;
		}

		$migrated_count = 0;
		$missing_attributes = array();

		foreach ( $posts as $post ) {
			$elementor_data = json_decode( $post->meta_value, true );

			if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
				continue;
			}

			$modified = false;

			// Recursively search for form widgets with Sendinblue integration
			$elementor_data = $this->migrate_elementor_data( $elementor_data, $modified, $missing_attributes );

			// If we modified the data, save it back
			if ( $modified ) {
				update_metadata( 'post', $post->post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
				$migrated_count++;
			}
		}

		// Log migration results if WP_DEBUG is enabled
		if ( WP_DEBUG === true ) {
			if ( $migrated_count > 0 ) {
				error_log( sprintf(
					'Sendinblue Elementor Integration: Migrated %d forms to version 2.0.0',
					$migrated_count
				) );
			}

			if ( ! empty( $missing_attributes ) ) {
				$unique_missing = array_unique( $missing_attributes );
				error_log( sprintf(
					'Sendinblue Migration Warning: The following attributes were not found in your Brevo account: %s',
					implode( ', ', $unique_missing )
				) );
			}
		}

		// Store missing attributes for admin notice
		if ( ! empty( $missing_attributes ) ) {
			set_transient( 'sendinblue_migration_missing_attrs', array_unique( $missing_attributes ), 30 * DAY_IN_SECONDS );
		}
	}

	/**
	 * Recursively migrate Elementor data structure
	 *
	 * @param array $data Elementor data array
	 * @param bool &$modified Reference to track if data was modified
	 * @param array &$missing_attributes Reference to collect missing attributes
	 * @return array Modified data
	 */
	private function migrate_elementor_data( $data, &$modified, &$missing_attributes ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			// Check if this is a form widget with Sendinblue settings
			if ( is_array( $value ) ) {
				// Check for form settings
				if ( isset( $value['widgetType'] ) && $value['widgetType'] === 'form' ) {
					if ( isset( $value['settings']['submit_actions'] ) &&
					     is_array( $value['settings']['submit_actions'] ) &&
					     in_array( 'sendinblue integration', $value['settings']['submit_actions'] ) ) {

						// Get API key for this form
						$api_key = $this->get_api_key_from_settings( $value['settings'] );

						// Validate and normalize attribute field names
						$data[$key]['settings'] = $this->validate_attribute_fields(
							$value['settings'],
							$api_key,
							$modified,
							$missing_attributes
						);
					}
				}

				// Recurse into nested elements
				$data[$key] = $this->migrate_elementor_data( $value, $modified, $missing_attributes );
			}
		}

		return $data;
	}

	/**
	 * Validate and normalize attribute field names
	 *
	 * @param array $settings Form settings
	 * @param string $api_key API key
	 * @param bool &$modified Reference to track if data was modified
	 * @param array &$missing_attributes Reference to collect missing attributes
	 * @return array Updated settings
	 */
	private function validate_attribute_fields( $settings, $api_key, &$modified, &$missing_attributes ) {
		// Get available Brevo attributes
		$available_attributes = $this->get_available_attributes( $api_key );

		// Validate firstname attribute
		if ( isset( $settings['sendinblue_name_attribute_field'] ) &&
		     ! empty( $settings['sendinblue_name_attribute_field'] ) ) {

			$attribute_name = $settings['sendinblue_name_attribute_field'];
			$formatted_name = strtoupper( trim( $attribute_name ) );

			// If the name changed during formatting, update it
			if ( $formatted_name !== $attribute_name ) {
				$settings['sendinblue_name_attribute_field'] = $formatted_name;
				$modified = true;
			}

			// Check if attribute exists in Brevo
			if ( ! empty( $available_attributes ) && ! in_array( $formatted_name, $available_attributes ) ) {
				$missing_attributes[] = $formatted_name;
			}
		}

		// Validate lastname attribute
		if ( isset( $settings['sendinblue_last_name_attribute_field'] ) &&
		     ! empty( $settings['sendinblue_last_name_attribute_field'] ) ) {

			$attribute_name = $settings['sendinblue_last_name_attribute_field'];
			$formatted_name = strtoupper( trim( $attribute_name ) );

			// If the name changed during formatting, update it
			if ( $formatted_name !== $attribute_name ) {
				$settings['sendinblue_last_name_attribute_field'] = $formatted_name;
				$modified = true;
			}

			// Check if attribute exists in Brevo
			if ( ! empty( $available_attributes ) && ! in_array( $formatted_name, $available_attributes ) ) {
				$missing_attributes[] = $formatted_name;
			}
		}

		return $settings;
	}

	/**
	 * Get API key from form settings
	 *
	 * @param array $settings Form settings
	 * @return string API key
	 */
	private function get_api_key_from_settings( $settings ) {
		// Check if using global API key
		if ( isset( $settings['sendinblue_use_global_api_key'] ) && $settings['sendinblue_use_global_api_key'] === 'yes' ) {
			$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
			if ( ! empty( $webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ) {
				return $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
			}
		}

		// Check for form-specific API key
		if ( isset( $settings['sendinblue_api'] ) && ! empty( $settings['sendinblue_api'] ) ) {
			return $settings['sendinblue_api'];
		}

		// Try global as fallback
		$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
		if ( ! empty( $webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ) {
			return $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
		}

		return '';
	}

	/**
	 * Get available Brevo attributes from API
	 *
	 * @param string $api_key API key to use for fetching
	 * @return array Array of attribute names
	 */
	private function get_available_attributes( $api_key = '' ) {
		// If no API key provided, try to get global
		if ( empty( $api_key ) ) {
			$webtica_sendinblue_options = get_option( 'webtica_sendinblue_option_name' );
			if ( ! empty( $webtica_sendinblue_options['global_api_key_webtica_sendinblue'] ) ) {
				$api_key = $webtica_sendinblue_options['global_api_key_webtica_sendinblue'];
			}
		}

		if ( empty( $api_key ) ) {
			return array();
		}

		// Use the attributes manager to get fresh attributes (cache was cleared at start of migration)
		if ( class_exists( 'Sendinblue_Attributes_Manager' ) ) {
			$attributes_manager = Sendinblue_Attributes_Manager::get_instance();
			$attributes = $attributes_manager->get_attributes( $api_key );

			if ( ! empty( $attributes ) && is_array( $attributes ) ) {
				return array_column( $attributes, 'name' );
			}
		}

		return array();
	}
}

// Initialize migration
Sendinblue_Migration::get_instance();
