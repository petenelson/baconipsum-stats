<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( !class_exists( 'BaconIpsum_Stats' ) ) {

	class BaconIpsum_Stats {

		protected static $instance     = null;
		protected static $version      = '2015-06-19-01';
		protected static $plugin_name  = 'baconipsum-stats';

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new BaconIpsum_Stats();
			}

			return self::$instance;
		}


		public static function plugins_loaded() {

			add_action( 'admin_init', 'BaconIpsum_Stats::create_tables' );


		}


		public static function create_tables() {

			//if ( get_site_option( self::$plugin_name , $default, $use_cache );


		}


	}


}