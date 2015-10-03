<?php

namespace RudraX\Utils {

	class Console {
		public static function block($msg) {
			self::line ();
			echo  $msg;
			self::line ();
		}
		public static function line() {
			echo "\n###################################################################################################\n";
		}
		public static function log($msg) {
			static $log_handle = null;
			
			$log = "[" . @date ( "Y-m-d H:i:s O" ) . "] " . $msg . PHP_EOL;
			if (defined ( "WRITE_TO_LOG" )) {
				if ($log_handle === null) {
					$log_handle = fopen ( WRITE_TO_LOG, 'a' );
				}
				
				fwrite ( $log_handle, $log );
			}
			
			echo $log;
		}
		public static function error($message) {
			self::log ( "ERROR: $message" );
		}
		public static function println($message) {
			self::log ( "MESSAGE: $message" );
		}
	}
}
