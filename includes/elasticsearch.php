<?php
/**
 * Bacon Ipsum Elasticsearch integration
 *
 * @package BaconIpsum\Stats
 */

namespace BaconIpsum\Stats\Elasticsearch;

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
	add_action( 'admin_init', n( 'maybe_create_mapping' ) );
	add_action( 'admin_action_delete_baconipsum_stats_index', n( 'maybe_delete_index' ) );
}

/**
 * Gets the cache group ID.
 *
 * @return string
 */
function get_cache_group() {
	return 'baconipsum_stats';
}

/**
 * Sends an HTTP request to Elasticsearch.
 *
 * @param string $method The HTTP method.
 * @param array  $args   Additional args.
 * @return array
 */
function es_request( $method = 'POST', $args = [] ) {

	$args = wp_parse_args(
		$args,
		[
			'blocking' => true, // Pass false to run async requests.
			'body'     => false,
			'endpoint' => false,
		]
	);

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

	$index_url = get_index_url();

	if ( ! empty( $args['endpoint'] ) ) {
		$index_url = trailingslashit( $index_url ) . $args['endpoint'];
	}

	$results['index_url'] = $index_url;

	$wp_remote_args = [
		'method'   => $method,
		'blocking' => $args['blocking'],
		'headers'  => [
			'Content-Type' => 'application/json',
		],
	];

	if ( defined( 'ES_SHIELD' ) ) {
		$wp_remote_args['headers']['Authorization'] = 'Basic ' . base64_encode( ES_SHIELD );
	}

	if ( ! empty( $args['body'] ) ) {
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
 * Gets the Elasticsearch stats index URL.
 *
 * @param  int $blog_id `null` means current blog.
 * @return string
 */
function get_index_url( $blog_id = null ) {

	if ( ! defined( 'BACON_IPSUM_STATS_ES_HOST' ) ) {
		return false;
	}

	$index_name = get_index_name( $blog_id );
	if ( empty( $index_name ) ) {
		return false;
	}

	$index_url = trailingslashit( BACON_IPSUM_STATS_ES_HOST ) . $index_name;

	return $index_url;
}

/**
 * Deletes the mapping in Elasticsearch.
 *
 * @return array
 */
function delete_es_mapping() {

	$index_url = get_index_url();

	if ( empty( $index_url ) ) {
		return;
	}

	$results = es_request( 'DELETE' );

	wp_cache_delete( 'index_exists_' . md5( $index_url ), get_cache_group() );

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

	return $results;
}

/**
 * Creates the mapping if it doesn't exist.
 *
 * @return void
 */
function maybe_create_mapping() {
	if ( ! index_exists() ) {
		put_es_mapping();
	}
}

/**
 * Determines if the index exists.
 *
 * @return void
 */
function index_exists() {

	$index_url = get_index_url();

	if ( empty( $index_url ) ) {
		return false;
	}

	$cache_key     = 'index_exists_' . md5( $index_url );
	$cached_exists = wp_cache_get( $cache_key, get_cache_group() );

	if ( false !== $cached_exists ) {
		return true;
	}

	// Call ES to see if it exists.
	$results = es_request( 'GET' );

	if ( 200 === $results['response_code'] ) {
		wp_cache_set( $cache_key, 'true', get_cache_group(), MINUTE_IN_SECONDS * 5 );
		return true;
	} else {
		return false;
	}
}

/**
 * Deletes the index if the user has the right permissions.
 *
 * @return void
 */
function maybe_delete_index() {

	if ( current_user_can( 'manage_options' ) ) {
		delete_es_mapping();
		wp_safe_redirect( admin_url() );
		exit;
	}
}
