<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats' ) ) {

	class BaconIpsum_Stats {

		protected static $version      = '2016-11-17-01';
		protected static $plugin_name  = 'baconipsum-stats';

		private $_queries = array();

		public function plugins_loaded() {
			add_action( 'anyipsum-after-starts-with-row', 'BaconIpsum_Stats::display_spicy_jalapeno_row', 10, 3 );
			add_filter( 'anyipsum-generated-filler', 'BaconIpsum_Stats::alter_generated_filler' );
		}

		/**
		 * Logs stats.
		 * @param  array $args The Any Ipsum generated content args.
		 * @return void
		 */
		static public function log_anyipsum_generated( $args ) {

			global $wpdb;

			$params = self::get_params();
			// don't log invalid data
			if ( ! in_array( $args['source'], $params->sources ) || ! in_array( $args['type'], $params->types ) ) {
				return;
			}

			$r = $wpdb->insert( self::logging_table_name(),
				array(
					'added'                 => current_time( 'timestamp' ),
					'added_date'            => current_time( 'mysql' ),
					'source'                => $args['source'],
					'type'                  => $args['type'],
					'format'                => $args['format'],
					'start_with_lorem'      => true === $args['start-with-lorem'] ? 1 : 0,
					'number_of_paragraphs'  => $args['number-of-paragraphs'] ,
					'number_of_sentences'   => $args['number-of-sentences'] ,
					'ip_address'            => $_SERVER['REMOTE_ADDR'],
					'error'                 => ( ! empty( $args['error'] ) ? trim( $args['error'] ) : '' ),
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
				)
			);

		}


		static public function get_params() {
			$p            = new stdClass();
			$p->sources   = array( 'web', 'api', 'cli', 'sms' );
			$p->types     = array( 'all-meat', 'meat-and-filler' );
			return $p;
		}

		/**
		 * Displays 
		 * @param  [type] $content  [description]
		 * @param  [type] $type     [description]
		 * @param  [type] $settings [description]
		 * @return [type]           [description]
		 */
		static public function display_spicy_jalapeno_row( $content, $type, $settings ) {
			?>
				<tr class="anyipsum-spicy">
					<td class="anyipsum-left-cell"></td>
					<td class="anyipsum-right-cell">
						<input id="spicy-jalapeno" type="checkbox" name="make-it-spicy" value="1" <?php checked( self::is_spicy() ); ?> />
						<label for="spicy-jalapeno"><?php _e( 'Make it spicy', 'any-ipsum' ); ?></label>
					</td>
				</tr>
			<?php
		}

		static public function is_spicy() {
			if ( class_exists( 'WPAnyIpsumCore' ) ) {
				return '1' === WPAnyIpsumCore::get_request( 'make-it-spicy' );
			}
		}

		static public function alter_generated_filler( $paragraphs ) {

			if ( is_array( $paragraphs ) && ! empty( $paragraphs ) && self::is_spicy() ) {
				$paragraphs[0] = 'Spicy jalapeno ' . lcfirst( $paragraphs[0] );
			}

			return $paragraphs;
		}

	}


}
