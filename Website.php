<?php

namespace RudraX\Utils {

	class Webapp {
		public static $BASE_DIR;
		public static $BASE_DIR; // Absolute path to your installation, ex: /var/www/mywebsite
		public static $DOC_ROOT; // ex: /var/www
		public static $BASE_URL; // ex: '' or '/mywebsite'
		public static $PROTOCOL;
		public static $PORT;
		public static $DISP_PORT;
		public static $DOMAIN;
		public static $FULL_URL; // Ex: 'http://example.com', 'https://example.com/mywebsite', etc.
		public static function init() {
			self::$BASE_DIR = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
			self::$DOC_ROOT = preg_replace ( "!{$_SERVER['SCRIPT_NAME']}$!", '', $_SERVER ['SCRIPT_FILENAME'] ); // ex: /var/www
			self::$BASE_URL = preg_replace ( "!^{self::$DOC_ROOT}!", '', self::$BASE_DIR ); // ex: '' or '/mywebsite'
			self::$PROTOCOL = empty ( $_SERVER ['HTTPS'] ) ? 'http' : 'https';
			self::$PORT = $_SERVER ['SERVER_PORT'];
			self::$DISP_PORT = (self::$PROTOCOL == 'http' && self::$PORT == 80 || self::$PROTOCOL == 'https' && self::$PORT == 443) ? '' : ":".self::$PORT;
			self::$DOMAIN = $_SERVER ['SERVER_NAME'];
			self::$FULL_URL = self::$PROTOCOL."://".self::$DOMAIN.self::$DISP_PORT.self::$BASE_URL;
		}
	}
	
	Webapp::init ();
}