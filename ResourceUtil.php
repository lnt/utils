<?php

namespace RudraX\Utils {

	use \MagicMin\MagicMinifier;

	global $minifier;
	global $defaultMinConfig;
	$defaultMinConfig = array (
			'echo' => false,
			'encode' => false,
			// 'timer' => true,
			'closure' => false,
			'gzip' => false 
	);
	
	date_default_timezone_set ( 'America/Los_Angeles' );
	
	$minifier = new \MagicMin\MagicMinifier ( $defaultMinConfig );
	class ResourceUtil {
		public static $PROJECT_ROOT_DIR = "";
		public static $BUILD_DIR = "build/";
		public static $LIB_DIR = "lib/";
		public static $RESOURCES_DIR = "resources/";
		public static $RESOURCE_DIST_DIR = "dist";
		public static $RESOURCE_SRC_DIR = "src";
		private static $webmodules = null;
		
		public static function setMinfierConfig($config){
			global $minifier;
			global $defaultMinConfig;
			$minifier = new \MagicMin\MagicMinifier ( array_merge ( $defaultMinConfig, $minConfig ) );
		}
		
		public static function js_minfiy($file, $target = null, $version = "") {
			if ($target == null) {
				$target = $file;
			}
			if(FileUtil::is_remote_file($file)){
				return $file;
			}
			global $minifier;
			return $minifier->minify ( self::$PROJECT_ROOT_DIR . $file, self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . self::$RESOURCE_DIST_DIR . "/" . $target );
		}
		public static function scan_modules_dir($dir, $filemodules = array("_" => array(),"bundles" => array())) {
			if (! is_dir ( $dir )) {
				return $filemodules;
			}
			$d = dir ( $dir );
			// Console::log ( "Scanning Resource Folder= ", $dir );
			while ( false !== ($entry = $d->read ()) ) {
				if ($entry != '.' && $entry != '..') {
					if (is_dir ( $dir . '/' . $entry )) {
						$filemodules = self::scan_modules_dir ( $dir . '/' . $entry, $filemodules );
					} else if (strcmp ( $entry, "module.properties" ) == 0) {
						try {
							$mod_file = $dir . '/' . $entry;
							$mode_time = filemtime ( $mod_file );
							// if(RX_MODE_DEBUG) Browser::log("fresh ....",$dir);
							$filemodules ["_"] [$mod_file] = $mode_time;
							$r = parse_ini_file ( $dir . '/' . $entry, TRUE );
							// Browser::console($dir.'/'.$entry);
							foreach ( $r as $mod => $files ) {
								$filemodules ['bundles'] [$mod] = array (
										"files" => array () 
								);
								foreach ( $files as $key => $file ) {
									if ($key == '@') {
										$filemodules ['bundles'] [$mod] [$key] = explode ( ',', $file );
									} else if ($key != '@' && ! FileUtil::is_remote_file ( $file )) {
										$file_path = FileUtil::resolve_path ( StringUtil::replace_first ( self::$PROJECT_ROOT_DIR, "", $dir . '/' . $file ) );
										$filemodules ['bundles'] [$mod] ["files"] [] = $file_path;
									} else
										$filemodules ['bundles'] [$mod] ["files"] [] = $file;
								}
							}
						} catch ( Exception $e ) {
							echo 'Caught exception: ', $e->getMessage (), "\n";
						}
					}
				}
			}
			$d->close ();
			return $filemodules;
		}
		public static function scan_modules($dirs = array("lib","resources"), $target = "resources/bundle.json") {
			self::$webmodules = array (
					"_" => array (),
					"bundles" => array () 
			);
			foreach ( $dirs as $dir ) {
				self::$webmodules = self::scan_modules_dir ( self::$PROJECT_ROOT_DIR . $dir, self::$webmodules );
			}
			FileUtil::build_mkdir ( self::$RESOURCE_DIST_DIR );
			FileUtil::build_write ( self::$RESOURCE_DIST_DIR . "/" . $target, json_encode ( self::$webmodules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			FileUtil::build_export_object ( self::$RESOURCE_DIST_DIR . "/" . $target . ".php", self::$webmodules );
			return self::$webmodules;
		}
		public static function build_js($minConfig = array()) {
			self::setMinfierConfig($minConfig);
			
			if (self::$webmodules != null && ! empty ( self::$webmodules ['bundles'] )) {
				foreach ( self::$webmodules ['bundles'] as $module => $moduleObject ) {
					foreach ( $moduleObject ["files"] as $file ) {
						//Console::log ( "Minifying  " . $file );
						self::js_minfiy ( $file );
					}
				}
			}
		}
	}
}