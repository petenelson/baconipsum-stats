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
