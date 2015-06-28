<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats_API_Controller' ) ) {

	class BaconIpsum_Stats_API_Controller  {

		protected static $version      = '2015-06-24-01';
		protected static $plugin_name  = 'baconipsum-stats-api';

		private $_stats                = null;


		public function register_routes() {

			register_rest_route( 'baconipsum', '/stats', array(
				'methods'         => array( WP_REST_Server::METHOD_GET, WP_REST_Server::METHOD_POST ),
				'callback'        => array( $this, 'get_stats' ),
				'args'            => array(
					'from'        => array(
						'sanitize_callback'  => array( $this, 'to_timestamp' ),
						'default'            => current_time( 'timestamp' ) - ( DAYS_IN_SECONDS * 30 ),
					),
					'to'          => array(
						'sanitize_callback' => array( $this, 'to_timestamp' ),
						'default'           => current_time( 'timestamp' ),
					),
					'source'      => array(
						'sanitize_callback' => 'sanitize_key',
					),
					'type'        => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
			) );

			register_rest_route( 'baconipsum', '/params', array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_params' ),
			) );

			register_rest_route( 'baconipsum', '/test-response', array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'test_response' ),
				'args'            => array(
					'format'      => array(
							'default' => 'json',
						),
					),
			) );

		}


		public function __get( $name ) {

			if ( 'stats' === $name && empty( $this->_stats ) ) {
				$this->_stats = new BaconIpsum_Stats();
			}

			if ( property_exists( $this, '_' . $name ) ) {
				$name = '_' . $name;
				return $this->$name;
			}

		}



		public function get_stats( WP_REST_Request $request ) {
			$data = $this->stats->get_stats( array(
				'from' => $request['from'],
				'to' => $request['to'],
				'source' => $request['source'],
				'type' => $request['type'],
				'include_queries' => false,
			) );

			$data->count_formatted = number_format( $data->count );

			$data->chart_data = new stdClass();

			$data->chart_data->sources = array();
			$data->chart_data->sources[] = array( 'Source', 'Count');
			foreach ($data->sources as $key => $value) {
				$data->chart_data->sources[] = array( $key, $value );
			}

			$data->chart_data->types = array();
			$data->chart_data->types[] = array( 'Type', 'Count');
			foreach ($data->types as $key => $value) {
				$data->chart_data->types[] = array( $key, $value );
			}

			$data->chart_data->start_with_lorem = array();
			$data->chart_data->start_with_lorem[] = array( 'Start With Lorem', 'Count');
			foreach ($data->start_with_lorem as $key => $value) {
				$data->chart_data->start_with_lorem[] = array( strval( $key ), $value );
			}

			$data->chart_data->paragraphs = array();
			$data->chart_data->paragraphs[] = array( 'Number of Paragraphs', 'Count');
			foreach ($data->paragraphs as $key => $value) {
				$data->chart_data->paragraphs[] = array( strval( $key ), $value );
			}

			$data->chart_data->format = array();
			$data->chart_data->format[] = array( 'Format', 'Count');
			foreach ($data->format as $key => $value) {
				$data->chart_data->format[] = array( strval( $key ), $value );
			}

			return rest_ensure_response( $data );
		}


		public function get_params( WP_REST_Request $request ) {
			$data = $this->stats->get_params();
			return rest_ensure_response( $data );
		}


		public function test_response( WP_REST_Request $request ) {
			$data = new stdClass();
			$data->my_text_data = 'The server time is ' . current_time( 'mysql' );
			return rest_ensure_response( $data );
		}


		public function to_timestamp( $date ) {
			return $this->validate_timestamp( strtotime( $date ) );
		}


		public function validate_timestamp( $timestamp ) {
			return absint( $timestamp );
		}




	} // end class

}

