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
							if (isset ( $r ["name"] )) {
								Console::println ( "scanning bundle : " . $r ["name"] );
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
		public static function scan_modules($dirs = array("lib","resources"), $target = "resources/resource.json") {
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
		private static function getAllFiles($bundleName,$files=array(),&$scannedBundles =array()) {
			if (self::$webmodules != null && ! empty ( self::$webmodules ['bundles'] )  && isset(self::$webmodules ['bundles'][$bundleName])) {
				$scannedBundles[$bundleName] = true;
				if(isset(self::$webmodules ['bundles'][$bundleName]["on"])){
					foreach (self::$webmodules ['bundles'][$bundleName]["on"] as $index=>$otherBundleName){
						$files = self::getAllFiles($otherBundleName,$files,$scannedBundles);
					}
				}
				if(isset(self::$webmodules ['bundles'][$bundleName]["js"])){
					foreach (self::$webmodules ['bundles'][$bundleName]["js"] as $index=>$fileName){
						if(!isset($files[$fileName])){
							$files[$fileName] = true;
						}
					}
				}
			}
			return $files;
		}
		public static function bundlify($indexBundles = array("webmodules/bootloader")) {
			$scannedFiles = array();
			Console::log("Robo Task Bundlifying...");
			foreach ($indexBundles as $indexb=>$indexBundle){
				$files = array(); $scannedBundles =array();
				$files = self::getAllFiles($indexBundle,$files,$scannedBundles);
				Console::log("Bundlifying : ".$indexBundle);
				$fileContent = "";
				$fileName =  "resources/bundled/" . implode(".", explode("/",  $indexBundle)).".js";
				Console::log("Reading and Minifying file :".$fileName);
				foreach ($files as $index=>$file){
					Console::log("File : ".$index);
					if(!FileUtil::is_remote_file($index)){
						$newFile = self::js_minfiy ( $index,"somerandomcodeword.js" );
						$fileContent= $fileContent.";\n\n".file_get_contents($newFile);
						unlink($newFile);
					}
				}
				
 				$fileContent.=";\n(function(foo,bundles){foo.__bundled__ = foo.__bundled__ ? foo.__bundled__.concat(bundles) : bundles;})(this,"
				.json_encode(array_keys($scannedBundles),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
 				.");";
						
				FileUtil::build_write ( self::$RESOURCE_DIST_DIR . "/" . $fileName,$fileContent);
				
				
				$scannedFiles = array_merge($files);
			}
			/*
			 * foreach ($files as $file) {
    			fwrite($out, file_get_contents($file));
}
			 */
		}
		public static function build_js($minConfig = array()) {
			self::setMinfierConfig ( $minConfig );
			
			if (self::$webmodules != null && ! empty ( self::$webmodules ['bundles'] )) {
				foreach ( self::$webmodules ['bundles'] as $module => $moduleObject ) {
					foreach ( $moduleObject ["files"] as $file ) {
						// Console::log ( "Minifying " . $file );
						self::js_minfiy ( $file );
					}
				}
			}
		}
	}
}