<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats_Frontend' ) ) {

	class BaconIpsum_Stats_Frontend {

		protected static $version      = '2015-06-25-01';
		protected static $plugin_name  = 'baconipsum-stats-frontend';

		public function plugins_loaded() {

			add_shortcode( 'baconipsum-stats', array( $this, 'shortcode') );

			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );

		}


		public function shortcode() {

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-form' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'baconipsum-stats-datepicker', 'https://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css' );

			$bi_stats = new BaconIpsum_Stats();
			$timestamps = $bi_stats->min_max_timestamps();

			$javascript_data = new stdClass();
			$javascript_data->from = $timestamps->min_timestamp;
			$javascript_data->to = $timestamps->max_timestamp;

			$html = '';

			ob_start();
			?>

				<div class="baconipsum-stats">
					<form class="baconipsum-stats-form" method="post" action="<?php echo site_url( '/wp-json/baconipsum/stats' ); ?>">

						From: <input style="width: 7em;" type="text" name="from" class="date-from datepicker" value="<?php echo date( 'm/d/Y', $javascript_data->from ); ?>" />
						To: <input style="width: 7em;" type="text" name="to" class="date-to datepicker" value="<?php echo date( 'm/d/Y', $javascript_data->to ); ?>" />

						<input type="submit" value="Go" />
						<img class="ajax-spinner" style="display: none;" src="<?php echo plugin_dir_url(  dirname( __FILE__ ) ) ?>public/ajax-spinner.gif" />


						<div id="pie-source" class="chart" style="width: 100%; height: 300px; display: none;"></div>
						<div id="pie-type" class="chart" style="width: 100%; height: 300px; display: none;"></div>
						<div id="pie-swl" class="chart" style="width: 100%; height: 300px; display: none;"></div>
						<div id="pie-format" class="chart" style="width: 100%; height: 300px; display: none;"></div>
						<div id="pie-paras" class="chart" style="width: 100%; height: 300px; display: none;"></div>


					</form>
				</div>

				<script type="text/javascript" src="https://www.google.com/jsapi"></script>
				<script type="text/javascript">

					var baconIpsumAPIResponse;

					google.load("visualization", "1", {packages:["corechart"]});

					jQuery(document).ready(function() {

						jQuery('.baconipsum-stats .datepicker').datepicker({

						});

						var ajaxOptions = {
							beforeSubmit:  function() {
								jQuery('.baconipsum-stats .chart').hide();
								jQuery('.baconipsum-stats .ajax-spinner').show();
							},
							success: baconIpsumPopulateCharts
						}

						jQuery('.baconipsum-stats-form').ajaxForm( ajaxOptions );

						jQuery(window).resize(function(){
							baconIpsumPopulateCharts( baconIpsumAPIResponse );
						});

					})



					function baconIpsumPopulateCharts( response ) {

						if ( ! response ) {
							return;
						}

						jQuery('.baconipsum-stats .chart').show();
						jQuery('.baconipsum-stats .ajax-spinner').hide();

						baconIpsumPopulateChart( response.chart_data.types, 'Types', 'pie-type' );
						baconIpsumPopulateChart( response.chart_data.sources, 'Source', 'pie-source' );
						baconIpsumPopulateChart( response.chart_data.paragraphs, 'Number of Paragraphs', 'pie-paras' );
						baconIpsumPopulateChart( response.chart_data.start_with_lorem, 'Start With Lorem', 'pie-swl' );
						baconIpsumPopulateChart( response.chart_data.format, 'Format', 'pie-format' );

						baconIpsumAPIResponse = response;

					}


					function baconIpsumPopulateChart( data, title, id ) {
						var options = {
							title: title,
							is3D: true
						};
						var chart = new google.visualization.PieChart(document.getElementById(id));
						chart.draw( google.visualization.arrayToDataTable( data ), options );
					}

				</script>


			<?php
			$html = ob_get_contents();
			ob_end_clean();

			return $html;

		}



	} // end class

}