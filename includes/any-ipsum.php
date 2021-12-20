<?php
/**
 * Bacon Ipsum Any Ipsum Tweaks
 *
 * @package BaconIpsum\Stats
 */

namespace BaconIpsum\Stats\AnyIpsum;

/**
 * Quickly provide a namespaced way to get functions.
 *
 * @param string $function Name of function in namespace.
 * @return string
 */
function n( $function ) {
	return __NAMESPACE__ . "\\$function";
}

/**
 * WordPress hooks and filters.
 * @return void
 */
function setup() {
	add_action( 'plugins_loaded', n( 'remove_emoji_actions' ),  20 );
}

/**
 * Removes some of the WP emoji actions.
 *
 * @return void
 */
function remove_emoji_actions() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
