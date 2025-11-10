<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sendinblue Attributes Manager
 * Handles fetching and caching of Brevo/Sendinblue contact attributes
 */
class Sendinblue_Attributes_Manager {

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
	 * Fetch attributes from Brevo API with pagination
	 *
	 * @param string $api_key The Brevo API key
	 * @param int $limit Number of attributes to fetch (1-100)
	 * @param int $offset Offset for pagination
	 * @return array|WP_Error Array of attributes or error
	 */
	public function fetch_attributes( $api_key, $limit = 50, $offset = 0 ) {

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'API key is required', 'sendinblue-elementor-integration' ) );
		}

		// Validate pagination parameters
		$limit = max( 1, min( 100, intval( $limit ) ) );
		$offset = max( 0, intval( $offset ) );

		$url = add_query_arg( array(
			'limit' => $limit,
			'offset' => $offset,
		), 'https://api.brevo.com/v3/contacts/attributes' );

		$response = wp_remote_get( $url, array(
			'timeout' => 45,
			'headers' => array(
				'accept' => 'application/json',
				'api-key' => $api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			return new WP_Error( 'api_error', sprintf( __( 'API returned status %d: %s', 'sendinblue-elementor-integration' ), $response_code, $body ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['attributes'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid API response', 'sendinblue-elementor-integration' ) );
		}

		return $this->normalize_attributes( $data['attributes'] );
	}

	/**
	 * Fetch all attributes with automatic pagination
	 *
	 * @param string $api_key The Brevo API key
	 * @param int $max_items Maximum number of items to fetch (0 for all)
	 * @return array|WP_Error Array of attributes or error
	 */
	public function fetch_all_attributes( $api_key, $max_items = 0 ) {

		$all_attributes = array();
		$offset = 0;
		$limit = 100;
		$fetched_count = 0;

		do {
			$attributes = $this->fetch_attributes( $api_key, $limit, $offset );

			if ( is_wp_error( $attributes ) ) {
				return $attributes;
			}

			// Use + operator to preserve attribute names as keys
			$all_attributes = $all_attributes + $attributes;

			$batch_size = count( $attributes );
			$fetched_count += $batch_size;
			$offset += $limit;

			// Check if we reached max items
			if ( $max_items > 0 && $fetched_count >= $max_items ) {
				break;
			}

		} while ( $batch_size >= $limit );

		return $all_attributes;
	}

	/**
	 * Normalize attributes from API response
	 *
	 * @param array $attributes Raw attributes from API
	 * @return array Normalized attributes
	 */
	private function normalize_attributes( $attributes ) {

		$normalized = array();

		foreach ( $attributes as $attribute ) {
			if ( ! isset( $attribute['name'] ) ) {
				continue;
			}

			$name = $attribute['name'];
			$normalized[ $name ] = array(
				'name' => $name,
				'type' => isset( $attribute['type'] ) ? $attribute['type'] : 'text',
				'category' => isset( $attribute['category'] ) ? $attribute['category'] : '',
				'enumeration' => isset( $attribute['enumeration'] ) ? $attribute['enumeration'] : array(),
			);
		}

		return $normalized;
	}

	/**
	 * Get cached attributes or fetch from API
	 * Cache expires after 1 hour
	 *
	 * @param string $api_key The Brevo API key
	 * @return array|WP_Error Array of attributes or error
	 */
	public function get_attributes( $api_key ) {

		if ( empty( $api_key ) ) {
			if ( WP_DEBUG === true ) {
				error_log( 'Sendinblue Attributes Manager - Empty API key provided' );
			}
			return array();
		}

		$cache_key = 'sendinblue_attributes_' . md5( $api_key );
		$cached = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			if ( WP_DEBUG === true ) {
				error_log( sprintf( 'Sendinblue Attributes Manager - Returning %d cached attributes', count( $cached ) ) );
			}
			return $cached;
		}

		if ( WP_DEBUG === true ) {
			error_log( 'Sendinblue Attributes Manager - Cache miss, fetching from API...' );
		}

		$attributes = $this->fetch_all_attributes( $api_key );

		if ( is_wp_error( $attributes ) ) {
			if ( WP_DEBUG === true ) {
				error_log( 'Sendinblue Attributes Manager - Error fetching attributes: ' . $attributes->get_error_message() );
			}
			return array();
		}

		if ( WP_DEBUG === true ) {
			error_log( sprintf( 'Sendinblue Attributes Manager - Successfully fetched %d attributes from API', count( $attributes ) ) );
		}

		// Cache for 1 hour
		set_transient( $cache_key, $attributes, HOUR_IN_SECONDS );

		return $attributes;
	}

	/**
	 * Clear attributes cache
	 *
	 * @param string $api_key Optional API key to clear specific cache
	 */
	public function clear_cache( $api_key = '' ) {

		if ( ! empty( $api_key ) ) {
			$cache_key = 'sendinblue_attributes_' . md5( $api_key );
			delete_transient( $cache_key );
		} else {
			// Clear all caches matching pattern
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sendinblue_attributes_%'" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sendinblue_attributes_%'" );
		}
	}
}
