<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats_API' ) ) {

	class BaconIpsum_Stats_API_Controller  {

		protected static $instance     = null;
		protected static $version      = '2015-06-24-01';
		protected static $plugin_name  = 'baconipsum-stats-api';

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new BaconIpsum_Stats_API_Controller();
			}

			return self::$instance;
		}


		public static function register_routes() {

			register_rest_route( 'baconipsum', '/stats', array(
				'methods'         => WP_REST_Server::METHOD_POST,
				'callback'        => 'BaconIpsum_Stats_API_Controller::get_stats',
				'args'            => array(
					'from'        => array(
						'sanitize_callback'  => 'BaconIpsum_Stats_API_Controller::validate_timestamp',
						'default'            => current_time( 'timestamp' ) - ( DAYS_IN_SECONDS * 30 ),
					),
					'to'          => array(
						'sanitize_callback' => 'BaconIpsum_Stats_API_Controller::validate_timestamp',
						'default'           => current_time( 'timestamp' ),
					),
				),
			) );


		}


		public static function get_stats( WP_REST_Request $request ) {

			$data = new stdClass();
			$data->from = $request['from'];
			$data->to = $request['to'];
			$data->hello = 'world';

			return rest_ensure_response( $data );

		}


		public static function validate_timestamp( $timestamp ) {
			$timestamp = absint( $timestamp );
			// TODO add range checking here
			return $timestamp;
		}


	}

}

