<?php
/*
	Plugin Name: CloudVPS Object 
	Plugin URI: http://wordpress.org/plugins/cloudvps-object-store/
	Description: Connect your WordPress site to CloudVPS Object Store.
	Version: 1.0-dev

	Author: Marko Heijnen
	Author URI: http://markoheijnen.com

	Text Domain: cloudvps-object-store
	Domain Path: /languages
 */

class Cloud_Vps_Objects {
	private $folder;

	private static $token;
	private static $store;

	private $cdn;

	public function __construct() {
		$this->folder = dirname(__FILE__);

		$this->load();
	}

	public static function get_token() {
		if( ! self::$token ) {
			self::$token = new Cloud_Vps_Objects_Token(
				'',
				'',
				''
			);
		}

		return self::$token;
	}

	public static function get_store() {
		if( ! self::$store )
			self::$store = new Cloud_Vps_Objects_Store( self::get_token() );

		return self::$store;
	}







	private function load() {
		include $this->folder . '/inc/cdn.php';
		include $this->folder . '/inc/token.php';
		include $this->folder . '/inc/object-store.php';

		if ( defined('WP_CLI') && WP_CLI )
			include( $this->folder . '/inc/wp-cli.php' );

		$this->cdn = new Cloud_Vps_Objects_Cdn();
	}

}

new Cloud_Vps_Objects;