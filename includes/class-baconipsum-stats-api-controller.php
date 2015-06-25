<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats_API_Controller' ) ) {

	class BaconIpsum_Stats_API_Controller  {

		protected static $version      = '2015-06-24-01';
		protected static $plugin_name  = 'baconipsum-stats-api';


		public function register_routes() {

			register_rest_route( 'baconipsum', '/stats', array(
				'methods'         => WP_REST_Server::METHOD_POST,
				'callback'        => array( $this, 'get_stats' ),
				'args'            => array(
					'from'        => array(
						'sanitize_callback'  => array( $this, 'validate_timestamp' ),
						'default'            => current_time( 'timestamp' ) - ( DAYS_IN_SECONDS * 30 ),
					),
					'to'          => array(
						'sanitize_callback' => array( $this, 'validate_timestamp' ),
						'default'           => current_time( 'timestamp' ),
					),
				),
			) );


		}


		public function get_stats( WP_REST_Request $request ) {

			$data = new stdClass();
			$data->from = $request['from'];
			$data->to = $request['to'];
			$data->hello = 'world';

			return rest_ensure_response( $data );

		}


		public function validate_timestamp( $timestamp ) {
			$timestamp = absint( $timestamp );
			// TODO add range checking here
			return $timestamp;
		}

		private function get_timestamp_range( ) {


		}



	}

}

