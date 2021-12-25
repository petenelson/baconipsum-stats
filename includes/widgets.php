<?php
/**
 * Bacon Ipsum Dashboard Widgets
 *
 * @package BaconIpsum\Stats
 */

namespace BaconIpsum\Stats\Widgets;
				
use BaconIpsum\Stats\AnyIpsum;

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
	add_action( 'wp_dashboard_setup', n( 'register_dashboard_widgets') );
}

/**
 * Registers dashboard widgets.
 *
 * @return void
 */
function register_dashboard_widgets() {

	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget(
			'baconipsum-stats-widget',
			'Bacon Ipsum Stats',
			n( 'display_stats_widget')
		);
	}
}

/**
 * Displays the stats dashboard widget.
 *
 * @return void
 */
function display_stats_widget() {

	$data  = AnyIpsum\get_24hr_source_aggregate();
	$total = 0;

	?>

	<div class="inside">

		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th>Source</th>
					<th>Count</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $data['sources'] as $source => $count ) : $total += $count; ?>

					<tr>
						<td><?php echo esc_html( $source ); ?></td>
						<td><?php echo esc_html( number_format( $count ) ); ?></td>
					</tr>

				<?php endforeach; ?>

				<tr>
					<td><strong>Total</strong></td>
					<td><strong><?php echo esc_html( number_format( $total ) ); ?></strong></td>
				</tr>
				
			</tbody>

		</table>

	</div>

	<?php

}
