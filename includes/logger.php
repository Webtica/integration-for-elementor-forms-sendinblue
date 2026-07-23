<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the plugin's own debug logging is enabled.
 *
 * The plugin's logging is intentionally decoupled from WordPress' global
 * WP_DEBUG constant so that routine informational messages no longer flood
 * debug.log on sites that keep WP_DEBUG enabled. Logging is OFF by default.
 *
 * Enable it in either of two ways:
 *   1. Define WEBTICA_SENDINBLUE_DEBUG as true in wp-config.php (developer override), or
 *   2. Tick "Enable debug logging" on the Sendinblue settings page.
 *
 * @return bool True when plugin debug logging should be written.
 */
function webtica_sendinblue_free_debug_enabled() {
	// Developer/staging override via constant always wins.
	if ( defined( 'WEBTICA_SENDINBLUE_DEBUG' ) && WEBTICA_SENDINBLUE_DEBUG ) {
		return true;
	}

	// Settings-page checkbox (cached per request).
	static $option_enabled = null;

	if ( null === $option_enabled ) {
		$options        = get_option( 'webtica_sendinblue_option_name' );
		$option_enabled = ! empty( $options['debug_logging_webtica_sendinblue'] );
	}

	return $option_enabled;
}
