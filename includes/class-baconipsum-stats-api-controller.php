<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats_API_Controller' ) ) {

	class BaconIpsum_Stats_API_Controller  {

		protected static $version      = '2015-06-24-01';
		protected static $plugin_name  = 'baconipsum-stats-api';

		private $bi_stats              = null;


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


		private function stats() {
			if ( empy( $this->bi_stats ) ) {
				$this->bi_stats = new BaconIpsum_Stats();
			}
			return $this->bi_stats;
		}


		public function get_stats( WP_REST_Request $request ) {
			$data = $this->stats->get_stats( $from = $request['from'], $to = $request->to );
			return rest_ensure_response( $data );
		}


		public function validate_timestamp( $timestamp ) {

			$timestamp = absint( $timestamp );
			$timestamps = $this->stats->min_max_timestamps();

			if ( $timestamp < $timestamps->min_timestamp || $timestamp > $timestamps->max_timestamps ) {
				return 0;
			} else {
				return $timestamp;
			}

		}



	} // end class

}

