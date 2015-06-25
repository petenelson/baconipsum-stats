<?php
/*
Plugin Name: Bacon Ipsum Stats
Description: Logs stats from the Any Ipsum plugin
Version: 1.0.0
Author: Pete Nelson <a href="https://twitter.com/GunGeekATX">(@GunGeekATX)</a>
*/

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

// include required files
$includes = array( 'stats', 'stats-api-controller' );
foreach ( $includes as $include ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-baconipsum-' . $include . '.php';
}

if ( class_exists( 'BaconIpsum_Stats' ) ) {
	add_action( 'plugins_loaded', 'BaconIpsum_Stats::plugins_loaded' );
}

if ( class_exists( 'BaconIpsum_Stats_API_Controller' ) ) {
	add_action( 'rest_api_init', 'BaconIpsum_Stats_API_Controller::register_routes' );
}
