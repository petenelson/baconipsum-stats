<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats' ) ) {

	class BaconIpsum_Stats {

		protected static $version      = '2016-11-17-01';
		protected static $plugin_name  = 'baconipsum-stats';

		private $_queries = array();

		public function plugins_loaded() {

			add_action( 'admin_init', array( $this, 'create_tables' ) );
			add_action( 'anyipsum-filler-generated', 'BaconIpsum_Stats::log_anyipsum_generated' );

			add_action( 'anyipsum-after-starts-with-row', 'BaconIpsum_Stats::display_spicy_jalapeno_row', 10, 3 );

			add_filter( 'anyipsum-generated-filler', 'BaconIpsum_Stats::alter_generated_filler' );

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
				  added_date datetime NULL,
				  source varchar(20) NOT NULL,
				  type varchar(50) NOT NULL,
				  format varchar(20) NOT NULL,
				  start_with_lorem tinyint(1) UNSIGNED NOT NULL,
				  number_of_paragraphs int(11) UNSIGNED NOT NULL,
				  number_of_sentences int(11) UNSIGNED NOT NULL,
				  ip_address varchar(20) NULL,
				  error varchar(250) NULL,
				  PRIMARY KEY  (id),
				  KEY ix_ip_address (ip_address)
				) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				dbDelta( $sql );

				update_site_option( self::$plugin_name . '-version', self::$version );

			}

		}


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


		public function get_stats( $args ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'from'             => 0,
				'to'               => 0,
				'source'           => '',
				'type'             => '',
				'include_queries'  => false,
			 ) );

			// sanitize timestamps
			$timestamps = $this->min_max_timestamps();
			if ( $args['from'] < $timestamps->min_timestamp ) {
				$args['from'] = $timestamps->min_timestamp;
			}

			if ( $args['to'] > $timestamps->max_timestamp || $args['to'] < $timestamps->min_timestamp ) {
				$args['to'] = $timestamps->max_timestamp;
			}

			$args['to'] = $args['to'] + DAY_IN_SECONDS  - 1;

			$s = new stdClass();
			$s->args          = $args;
			$s->generated     = current_time( 'timestamp' );
			$s->timestamps    = $timestamps;

			$where =  $wpdb->prepare( 'added >= %d and added <= %d', $args['from'], $args['to'] );

			if ( ! empty( $args['source'] ) ) {
				$where .= $wpdb->prepare( " and source = '%s'", $args['source'] );
			}

			if ( ! empty( $args['type'] ) ) {
				$where .= $wpdb->prepare( " and type = '%s'", $args['type'] );
			}

			$params = $this->get_params();

			// filter where to valid types and sources
			$where .= " and source in (" . implode( ',', array_map( array( $this, 'add_single_quotes' ), $params->sources ) ) . ')';
			$where .= " and type in (" . implode( ',', array_map( array( $this, 'add_single_quotes' ), $params->types ) ) . ')';

			// total counts
			$s->count = absint( $this->query_table( $select = 'count(*)', $where, $group_by = '', $type = 'var' ) );

			// counts by source
			$s->sources = $this->array_a_to_kv( $this->query_table( $select = 'source, count(*) as `count`', $where, $group_by = 'source', $type = 'results', $output = OBJECT_K ) );

			// counts by type
			$s->types = $this->array_a_to_kv( $this->query_table( $select = 'type, count(*) as `count`', $where, $group_by = 'type', $type = 'results', $output = OBJECT_K ) );

			// counts by format
			$s->format = $this->array_a_to_kv( $this->query_table( $select = 'format, count(*) as `count`', $where, $group_by = 'format', $type = 'results', $output = OBJECT_K ) );

			// counts by paragraphs
			$s->start_with_lorem = $this->array_a_to_kv( $this->query_table( $select = 'start_with_lorem, count(*) as `count`', $where, $group_by = 'start_with_lorem', $type = 'results', $output = OBJECT_K ) );

			$s->start_with_lorem['Yes'] = absint( isset( $s->start_with_lorem[0] ) ? $s->start_with_lorem[0] : 0 );
			$s->start_with_lorem['No'] = absint( isset( $s->start_with_lorem[1] ) ? $s->start_with_lorem[1] : 0 );

			unset( $s->start_with_lorem[0] );
			unset( $s->start_with_lorem[1] );

			// counts by paragraphs
			$s->paragraphs = $this->array_a_to_kv( $this->query_table( $select = 'number_of_paragraphs, count(*) as `count`', $where, $group_by = 'number_of_paragraphs', $type = 'results', $output = OBJECT_K ) );

			// counts by sentences
			if ( $args['include_queries'] ) {
				$s->queries = $this->_queries;
			}

			return $s;

		}


		private function array_a_to_kv( $results ) {
			$a = array();
			foreach ( $results as $key => $value ) {
				$a[ strval( $key ) ] = absint( $value->count );
			}
			return $a;
		}


		private function get_distinct( $column ) {
			return $this->query_table( 'distinct(' . $column . ')', $where = '', $group_by = '', $type = 'col' );
		}


		public function min_max_timestamps() {
			if ( ! empty( $this->_timestamps ) ) {
				return $this->_timestamps;
			}
			$timestamps = $this->query_table( 'MIN( added ) AS min_timestamp, MAX( added ) AS max_timestamp', $where = '', $group_by = '', $type = 'row' );
			$timestamps->min_timestamp = absint( $timestamps->min_timestamp );
			$timestamps->max_timestamp = absint( $timestamps->max_timestamp );
			$this->_timestamps = $timestamps;
			return $timestamps;
		}


		static public function logging_table_name() {
			global $wpdb;
			return $wpdb->prefix . 'anyipsum_log';
		}


		private function query_table( $select, $where, $group_by = '', $type = 'results', $output = OBJECT ) {

			global $wpdb;
			$table_name = self::logging_table_name();

			if ( empty( $where ) ) {
				$where = '1';
			}

			if ( ! empty( $group_by ) ) {
				$group_by = 'group by ' . $group_by;
			}

			$query = 'select ' . $select . " from $table_name where " . $where . " " . $group_by;

			switch ( $type ) {
				case 'row':
					$results = $wpdb->get_row( $query, $output );
					break;

				case 'col':
					$results = $wpdb->get_col( $query );
					break;

				case 'var':
					$results = $wpdb->get_var( $query );
					break;

				default:
					$results = $wpdb->get_results( $query, $output );
					break;
			}

			$this->_queries[] = $query;

			return $results;

		}


		private function add_single_quotes( $value ) {
			return "'" . esc_sql( $value ) . "'";
		}


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
