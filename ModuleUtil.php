<?php

namespace RudraX\Utils {

	class ModuleUtil extends ResourceUtil {
		private static $webmodules = null;
		public static function readJSON($file) {
			return json_decode ( file_get_contents ( $file ), true );
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
					} else if (strcmp ( $entry, "module.json" ) == 0) {
						try {
							$mod_file = $dir . '/' . $entry;
							$mode_time = filemtime ( $mod_file );
							// if(RX_MODE_DEBUG) Browser::log("fresh ....",$dir);
							$filemodules ["_"] [$mod_file] = $mode_time;
							$r = self::readJSON ( $dir . '/' . $entry );
							// Browser::console($dir.'/'.$entry);
							if(isset($r["name"])){
								Console::println("scanning bundle : ".$r["name"]);
								foreach ( $r as $mod => $files ) {
									if ($mod != 'name') {
										$filemodules ['bundles'] [$mod] = array (
												"js" => array ()
										);
											
										if (isset ( $files ["on"] )) {
											$filemodules ['bundles'] [$mod] ["on"] = $files ["on"];
										}
											
										if (isset ( $files ["js"] )) {
											$jsFiles = $files ["js"];
											foreach ( $jsFiles as $key => $file ) {
												if (! FileUtil::is_remote_file ( $file )) {
													$file_path = FileUtil::resolve_path ( StringUtil::replace_first ( self::$PROJECT_ROOT_DIR, "", $dir . '/' . $file ) );
													$filemodules ['bundles'] [$mod] ["js"] [] = $file_path;
												} else
													$filemodules ['bundles'] [$mod] ["js"] [] = $file;
											}
										}
									}
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
		public static function scan_modules($dirs = array("lib","resources"), $target = "resources/resources.json") {
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
	}
}