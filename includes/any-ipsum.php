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
	add_action( 'anyipsum-filler-generated', n( 'log_anyipsum_generated' ) );
	add_action( 'anyipsum-after-starts-with-row', n( 'display_spicy_jalapeno_row' ) );
	add_filter( 'anyipsum-generated-filler', n( 'alter_generated_filler' ) );
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

/**
 * Logs Any Ipsum stats from generated filler.
 *
 * @param  array $args List of args.
 * @return void
 */
function log_anyipsum_generated( $args ) {

	$params = get_valid_params();
	if ( ! in_array( $args['source'], $params['sources'] ) || ! in_array( $args['type'], $params['types'] ) || ! in_array( $args['format'], $params['formats'] ) ) {
		return;
	}

	$logging_enabled = 1 === absint( get_option( 'baconipsum_stats_logging_enabled' ) );
	if ( ! $logging_enabled ) {
		return;
	}

	$server = filter_var_array(
		$_SERVER, // phpcs:ignore
		[
			'REMOTE_ADDR' => FILTER_SANITIZE_STRING,
		]
	);

	// Data for Elasticsearch.
	$body = [
		'timestamp'             => time(),
		'date'                  => gmdate( 'c' ),
		'source'                => $args['source'],
		'type'                  => $args['type'],
		'format'                => $args['format'],
		'start_with_lorem'      => (bool) $args['start-with-lorem'],
		'number_of_paragraphs'  => absint( $args['number-of-paragraphs'] ),
		'number_of_sentences'   => absint( $args['number-of-sentences'] ),
		'ip_address'            => $server['REMOTE_ADDR'],
		'error'                 => ( ! empty( $args['error'] ) ? trim( $args['error'] ) : '' ),
		'bytes'                 => isset( $args['output'] ) ? strlen( $args['output'] ) : 0,
	];

	$args = [
		'blocking' => false,
		'body'     => wp_json_encode( $body ),
		'endpoint' => '_doc'
	];

	$results = \BaconIpsum\Stats\Elasticsearch\es_request( 'POST', $args );
}

/**
 * Gets a list of valid params.
 *
 * @return array
 */
function get_valid_params() {

	$params = [
		'sources' => [ 'web', 'api', 'cli', 'sms' ],
		'types'   => [ 'all-meat', 'meat-and-filler', 'all-custom', 'custom-and-filler' ],
		'formats' => [ 'html', 'text', 'json' ],
	];

	return $params;
}

/**
 * Displays the spice jalapeno option.
 *
 * @return void
 */
function display_spicy_jalapeno_row() {
	?>
		<tr class="anyipsum-spicy">
			<td class="anyipsum-left-cell"></td>
			<td class="anyipsum-right-cell">
				<input id="spicy-jalapeno" type="checkbox" name="make-it-spicy" value="1" <?php checked( is_spicy() ); ?> />
				<label for="spicy-jalapeno"><?php esc_html_e( 'Make it spicy', 'any-ipsum' ); ?></label>
			</td>
		</tr>
	<?php
}

/**
 * Determines if spice is enabled.
 *
 * @return boolean
 */
function is_spicy() {
	if ( class_exists( '\WPAnyIpsumCore' ) ) {
		return '1' === \WPAnyIpsumCore::get_request( 'make-it-spicy' );
	} else {
		return false;
	}
}

/**
 * Adds Spicy Jalapeno to generated filler.
 *
 * @param  array $paragraphs List of paragraphs.
 * @return array
 */
function alter_generated_filler( $paragraphs ) {

	if ( is_array( $paragraphs ) && ! empty( $paragraphs ) && is_spicy() ) {
		$paragraphs[0] = 'Spicy jalapeno ' . lcfirst( $paragraphs[0] );
	}

	return $paragraphs;
}
