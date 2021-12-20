<?php
/**
 * Bacon Ipsum Stats Logging
 *
 * @package BaconIpsum\Stats
 */

namespace BaconIpsum\Stats\Logging;

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
	// add_action( 'admin_init', n( 'put_es_mapping' ) );
}

/**
 * Sends an HTTP request to Elasticsearch.
 *
 * @param string $method The HTTP method.
 * @param array  $args   Additional args.
 * @return array
 */
function es_request( $method = 'POST', $args = [] ) {

	$results = [
		'success'       => false,
		'response_code' => 0,
		'body'          => 0,
		'index_url'     => false,
	];

	if ( ! defined( 'BACON_IPSUM_STATS_ES_HOST' ) ) {
		return $results;
	}

	$index_name = get_index_name();
	if ( empty( $index_name ) ) {
		return $results;
	}

	$index_url = trailingslashit( BACON_IPSUM_STATS_ES_HOST ) . $index_name;

	$results['index_url'] = $index_url;

	$wp_remote_args = [
		'method'  => $method,
		'headers' => [
			'Content-Type' => 'application/json',
		],
	];

	if ( defined( 'ES_SHIELD' ) ) {
		$wp_remote_args['headers']['Authorization'] = 'Basic ' . base64_encode( ES_SHIELD );
	}

	if ( isset( $args['body'] ) ) {
		$wp_remote_args['body'] = $args['body'];
	}

	$response = wp_remote_request( $index_url, $wp_remote_args );

	$results['response_code'] = wp_remote_retrieve_response_code( $response );
	$results['body']          = wp_remote_retrieve_body( $response );

	return $results;
}

/**
 * Gets the Elasticsearch stats index name.
 *
 * @param  int $blog_id `null` means current blog.
 * @return string
 */
function get_index_name( $blog_id = null ) {

	if ( empty( $blog_id ) ) {
		$blog_id = get_current_blog_id();
	}

	$site_url   = get_site_url( $blog_id );
	$index_name = false;
	$slug       = 'baconipsum-stats';

	if ( ! empty( $site_url ) ) {
		$index_name = preg_replace( '#https?://(www\.)?#i', '', $site_url );
		$index_name = preg_replace( '#[^\w]#', '', $index_name ) . '-' . $slug . '-' . $blog_id;

		// Add the current year.
		$index_name .= '-' . gmdate( 'Y' );
	}

	return $index_name;
}

/**
 * Deletes the mapping in Elasticsearch.
 *
 * @return array
 */
function delete_es_mapping() {
	$results = es_request( 'DELETE' );
	return $results;
}

/**
 * Puts the mapping in Elasticsearch.
 *
 * @return array
 */
function put_es_mapping( $delete_mapping = true ) {
	if ( $delete_mapping ) {
		delete_es_mapping();
	}

	$args = [
		'body' => file_get_contents( BACON_IPSUM_STATS_PATH . 'includes/mapping.json' ),
	];

	$results = es_request( 'PUT', $args );

	var_dump( $results ); die();
}
