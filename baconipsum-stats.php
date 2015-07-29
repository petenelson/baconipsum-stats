<?php
/*
Plugin Name: Bacon Ipsum Stats
Description: Logs stats from the Any Ipsum plugin
Version: 1.1.0
Author: Pete Nelson <a href="https://twitter.com/GunGeekATX">(@GunGeekATX)</a>
*/

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

// include required files
$includes = array( 'stats', 'stats-api-controller', 'stats-frontend' );
foreach ( $includes as $include ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-baconipsum-' . $include . '.php';
}


if ( class_exists( 'BaconIpsum_Stats' ) ) {
	$bis = new BaconIpsum_Stats();
	add_action( 'plugins_loaded', array( $bis, 'plugins_loaded' ) );
}


if ( class_exists( 'BaconIpsum_Stats_API_Controller' ) ) {
	$bis_api_controller = new BaconIpsum_Stats_API_Controller();
	add_action( 'rest_api_init', array( $bis_api_controller, 'register_routes' ) );
}

if ( class_exists( 'BaconIpsum_Stats_Frontend' ) ) {
	$bisf = new BaconIpsum_Stats_Frontend();
	add_action( 'plugins_loaded', array( $bisf, 'plugins_loaded' ) );
}


// reference https://github.com/WP-API/WP-API/blob/develop/lib/infrastructure/class-wp-rest-server.php
// serve_request() function
// add_filter( 'rest_pre_serve_request', 'multiformat_rest_pre_serve_request', 10, 4 );

function multiformat_rest_pre_serve_request( $served, $result, $request, $server ) {

	// assumes 'format' was passed into the intial API route
	// example: https://baconipsum.com/wp-json/baconipsum/test-response?format=text
	// the default JSON response will be handled automatically by WP-API

	switch ( $request['format'] ) {

		case 'text':
			header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );

			echo $result->data->my_text_data;
			$served = true; // tells the WP-API that we sent the response already
			break;

		case 'xml':
			header( 'Content-Type: application/xml; charset=' . get_option( 'blog_charset' )  );

			$xmlDoc = new DOMDocument();
			$response = $xmlDoc->appendChild( $xmlDoc->createElement( 'Response' ) );
			$response->appendChild( $xmlDoc->createElement( 'My_Text_Data', $result->data->my_text_data ) );

			echo $xmlDoc->saveXML();
			$served = true;
			break;

	}

	return $served;

}
