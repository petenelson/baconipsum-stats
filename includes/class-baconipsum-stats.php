<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats' ) ) {

	class BaconIpsum_Stats {

		protected static $instance     = null;
		protected static $version      = '2015-06-19-04';
		protected static $plugin_name  = 'baconipsum-stats';

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new BaconIpsum_Stats();
			}

			return self::$instance;
		}


		public static function plugins_loaded() {

			add_action( 'admin_init', 'BaconIpsum_Stats::create_tables' );
			add_action( 'anyipsum-filler-generated', 'BaconIpsum_Stats::log_anyipsum_generated' );

		}


		public static function create_tables() {

			if ( self::$version !== get_site_option( self::$plugin_name . '-version' ) ) {

				// for reference
				// You must put each field on its own line in your SQL statement.
				// You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
				// You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
				// You must not use any apostrophes or backticks around field names.
				// Field types must be all lowercase.
				// SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.

				global $wpdb;

				$table_name = self::logging_table_name();
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


		public static function log_anyipsum_generated( $args ) {

			global $wpdb;

			$wpdb->insert( self::logging_table_name(),
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


		private static function logging_table_name() {
			global $wpdb;
			return $wpdb->prefix . 'anyipsum_log';
		}


	}


}