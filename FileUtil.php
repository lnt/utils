<?php

namespace RudraX\Utils {

	class FileUtil {
		public static $PROJECT_ROOT_DIR = "";
		public static $BUILD_DIR = "build/";
		
		/**
		 *
		 * @param string $file_name        	
		 * @return number
		 */
		public static function is_remote_file($file_name = "") {
			return (strpos ( $file_name, '://' ) > 0 ? 1 : 0) && preg_match ( "#\.[a-zA-Z0-9]{1,4}$#", $file_name ) ? 1 : 0;
		}
		/**
		 *
		 * @param unknown $str        	
		 * @return string
		 */
		public static function resolve_path($str) {
			$array = explode ( '/', $str );
			$domain = array_shift ( $array );
			$parents = array ();
			foreach ( $array as $dir ) {
				switch ($dir) {
					case '.' :
						// Don't need to do anything here
						break;
					case '..' :
						$popped = array_pop ( $parents );
						if (empty ( $popped )) {
							// Its meaningful, cant afford to loose it
							$parents [] = $dir;
						} else if ($popped == "..") {
							// Sorry, will have to put it back
							$parents [] = $popped;
							$parents [] = $dir;
						}
						break;
					case "" :
						// Some stupid guy didn't do his job :P
						break;
					default :
						$parents [] = $dir;
						break;
				}
			}
			return $domain . '/' . implode ( '/', $parents );
		}
		
		/**
		 *
		 * @param string $root        	
		 * @param string $dirName        	
		 * @param number $rights        	
		 * @return boolean
		 */
		public static function mkdir($root, $dirName, $rights = 0755) {
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
		public static function build_mkdir($dir, $rights = 0755) {
			if (! is_dir ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . $dir )) {
				return self::mkdir ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR, $dir, $rights );
			}
			return false;
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
		public static function build_write($filepath, $content) {
			try {
				$isInFolder = preg_match ( "/^(.*)\/([^\/]+)$/", $filepath, $filepathMatches );
				if ($isInFolder) {
					$folderName = $filepathMatches [1];
					$fileName = $filepathMatches [2];
					self::build_mkdir ( $folderName );
				}
			} catch ( Exception $e ) {
				echo "ERR: error writing  to '$filepath', " . $e->getMessage ();
			}
			return file_put_contents ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . $filepath, $content );
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
		public static function build_export_object($filename, $objet) {
			return self::build_write ( $filename, '<?php return ' . var_export ( $objet, true ) . ';' );
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
		public static function write_ini_file($assoc_arr, $path, $has_sections = FALSE) {
			$content = "";
			if ($has_sections) {
				foreach ( $assoc_arr as $key => $elem ) {
					$content .= "[" . $key . "]\n";
					foreach ( $elem as $key2 => $elem2 ) {
						if (is_array ( $elem2 )) {
							for($i = 0; $i < count ( $elem2 ); $i ++) {
								$content .= $key2 . "[] = " . $elem2 [$i] . "\n";
							}
						} else if ($elem2 == "")
							$content .= $key2 . " = \n";
						else
							$content .= $key2 . " = " . $elem2 . "\n";
					}
				}
			} else {
				foreach ( $assoc_arr as $key => $elem ) {
					if (is_array ( $elem )) {
						for($i = 0; $i < count ( $elem ); $i ++) {
							$content .= $key . "[] = \"" . $elem [$i] . "\"\n";
						}
					} else if ($elem == "")
						$content .= $key . " = \n";
					else
						$content .= $key . " = \"" . $elem . "\"\n";
				}
			}
			
			if (! $handle = fopen ( $path, 'w' )) {
				return false;
			}
			
			$success = fwrite ( $handle, $content );
			fclose ( $handle );
			return $success;
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
}