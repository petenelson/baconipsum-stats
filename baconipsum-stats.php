<?php
/*
Plugin Name: Bacon Ipsum Stats
Description: Logs stats from the Any Ipsum plugin
Version: 1.1.1
Author: Pete Nelson <a href="https://twitter.com/CodeGeekATX">(@CodeGeekATX)</a>
*/

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

define( 'BACON_IPSUM_STATS_URL', plugin_dir_url( __FILE__ ) );
define( 'BACON_IPSUM_STATS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'BACON_IPSUM_STATS_VERSION', '1.1.1' );

// Add your custom one to wp-config.php
// define( 'BACON_IPSUM_STATS_ES_HOST', 'http://elasticsearch1:9200' );

// Include files and run setup hooks.
$files = [
	'includes/elasticsearch.php' => 'Elasticsearch\setup',
	'includes/any-ipsum.php'     => 'AnyIpsum\setup',
];

foreach ( $files as $file => $setup ) {
	require_once $file;
	if ( ! empty( $setup ) ) {
		call_user_func( "\\BaconIpsum\\Stats\\$setup" );
	}
}
