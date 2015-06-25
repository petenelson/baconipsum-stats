<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats' ) ) {

	class BaconIpsum_Stats {

		protected static $version      = '2015-06-19-04';
		protected static $plugin_name  = 'baconipsum-stats';


		public function plugins_loaded() {

			add_action( 'admin_init', array( $this, 'create_tables' ) );
			add_action( 'anyipsum-filler-generated', array( $this, 'log_anyipsum_generated' ) );

		}


		public function create_tables() {

			if ( self::$version !== get_site_option( self::$plugin_name . '-version' ) ) {

				// for reference
				// You must put each field on its own line in your SQL statement.
				// You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
				// You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
				// You must not use any apostrophes or backticks around field names.
				// Field types must be all lowercase.
				// SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.

				global $wpdb;

				$table_name = $this->logging_table_name();
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
				  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				  added bigint(20) UNSIGNED NOT NULL,
				  source varchar(20) NOT NULL,
				  type varchar(50) NOT NULL,
				  format varchar(20) NOT NULL,
				  start_with_lorem tinyint(1) UNSIGNED NOT NULL,
				  number_of_paragraphs int(11) UNSIGNED NOT NULL,
				  number_of_sentences int(11) UNSIGNED NOT NULL,
				  PRIMARY KEY  (id)
				) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				dbDelta( $sql );

				update_site_option( self::$plugin_name . '-version', self::$version );

			}


		}


		public function log_anyipsum_generated( $args ) {

			global $wpdb;

			$wpdb->insert( $this->logging_table_name(),
				array(
					'added' => current_time( 'timestamp' ),
					'source' => $args['source'],
					'type' => $args['type'],
					'format' => $args['format'],
					'start_with_lorem' => true === $args['start-with-lorem'] ? 1 : 0,
					'number_of_paragraphs' => $args['number-of-paragraphs'] ,
					'number_of_sentences' => $args['number-of-sentences'] ,
					),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					)
			);


		}


		public function get_stats( $args ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'from'       => 0,
				'to'         => 0,
				'source'     => '',
				'type'       => '',
			 ) );

			// sanitize timestamps
			$timestamps = $this->min_max_timestamps();
			if ( $args['from'] < $timestamps->min_timestamp ) {
				$args['from'] = $timestamps->min_timestamp;
			}

			if ( $args['to'] < $timestamps->max_timestamp ) {
				$args['to'] = $timestamps->max_timestamp;
			}

			$s = new stdClass();
			$s->from     = $args['from'];
			$s->to       = $to;

			$where =  $wpdb->prepare( 'added >= %d and added <= %d', $args['from'], $args['to'] );

			// total counts
			//$counts = $this->query_table( $select = 'count(*) as counts', $where  )

			// counts by source

			// counts by type

			// counts by paragraphs

			// counts by sentences

		}



		public function min_max_timestamps() {
			$timestamps = $this->query_table( 'MIN( added ) AS min_timestamp, MAX( added ) AS max_timestamp', $where = '', $group_by = '', $type = 'row' );
			$timestamps->min_timestamp = absint( $timestamps->min_timestamp );
			$timestamps->max_timestamp = absint( $timestamps->max_timestamp );
			return $ts;
		}


		private function logging_table_name() {
			global $wpdb;
			return $wpdb->prefix . 'anyipsum_log';
		}


		private function query_table( $select, $where, $group_by = '', $type = 'results' ) {
			global $wpdb;
			$table_name = $this->logging_table_name();

			$query = $select . " from $table_name where " . $where . " " . $group_by;

			switch ( $type ) {
				case 'row':
					$results = $wpdb->get_row( $query );
					break;

				case 'var':
					$results = $wpdb->get_var( $query );
					break;

				default:
					$results = $wpdb->get_results( $query );
					break;
			}

			return $results;

		}


	}


}