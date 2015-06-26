<?php

namespace RudraX\Utils;

	class ResourceUtil {
		public static $PROJECT_ROOT_DIR = "";
		public static $BUILD_DIR = "build/";
		
		/**
		 *
		 * @param string $root        	
		 * @param string $dirName        	
		 * @param number $rights        	
		 * @return boolean
		 */
		public static function mkdir($root, $dirName, $rights = 0777) {
			$dirs = explode ( '/', $dirName );
			$dir = $root;
			foreach ( $dirs as $part ) {
				$dir .= $part . '/';
				if (! is_dir ( $dir ) && strlen ( $dir ) > 0) {
					if (! mkdir ( $dir, $rights )) {
						return false;
					}
				}
			}
			return true;
		}
		/**
		 *
		 * @param string $dir        	
		 * @param string $rights        	
		 * @return boolean
		 */
		public static function build_mkdir($dir, $rights) {
			return self::mkdir ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR, $dir, $rights );
		}
		
		/**
		 * Create file in build build folder
		 *
		 * @param string $file        	
		 * @return number
		 */
		public static function build_read($file = "") {
			return readfile ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . $file );
		}
		public static function build_write($file, $content) {
			return file_put_contents ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . $file, $content );
		}
		public static function build_check() {
			try {
				if (! is_dir ( self::$PROJECT_ROOT_DIR . "build/" )) {
					self::build_mkdir ( "cache" );
				}
			} catch ( Exception $e ) {
				echo "build directory not found in project root, please create with appropritae permissions and try again";
			}
		}
		public static function get_recursive_file_list($folder, $prefix = '') {
			
			// Add trailing slash
			$folder = (substr ( $folder, strlen ( $folder ) - 1, 1 ) == '/') ? $folder : $folder . '/';
			
			$return = array ();
			
			foreach ( self::clean_scandir ( $folder ) as $file ) {
				if (is_dir ( $folder . $file )) {
					$return = array_merge ( $return, self::get_recursive_file_list ( $folder . $file, $prefix . $file . '/' ) );
				} else {
					$return [] = $prefix . $file;
				}
			}
			return $return;
		}
		public static function clean_scandir($folder, $ignore = array()) {
			$ignore [] = '.';
			$ignore [] = '..';
			$ignore [] = '.DS_Store';
			$return = array ();
			
			foreach ( scandir ( $folder ) as $file ) {
				if (! in_array ( $file, $ignore )) {
					$return [] = $file;
				}
			}
			
			return $return;
		}
	}


