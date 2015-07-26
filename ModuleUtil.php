<?php

namespace RudraX\Utils {

	class ModuleUtil extends ResourceUtil {
		private static $webmodules = null;
		private static $target_resources_json = "resources/resource.json";
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
		private static function write_resource_json(){
			FileUtil::build_mkdir ( self::$RESOURCE_DIST_DIR );
			FileUtil::build_write ( self::$RESOURCE_DIST_DIR . "/" . self::$target_resources_json, json_encode ( self::$webmodules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			FileUtil::build_export_object ( self::$RESOURCE_DIST_DIR . "/" . self::$target_resources_json . ".php", self::$webmodules );
			return self::$webmodules;
		}
		public static function scan_modules($dirs = array("lib","resources"), $target = null) {
			self::$webmodules = array (
					"_" => array (),
					"bundles" => array () 
			);
			if($target!==null){
				self::$target_resources_json = $target;
			}
			foreach ( $dirs as $dir ) {
				self::$webmodules = self::scan_modules_dir ( self::$PROJECT_ROOT_DIR . $dir, self::$webmodules );
			}
			self::write_resource_json();
		}
		
		private static $scanned_files = array();
		private static $scanned_bundles = array();
		
		private static function getAllFiles($bundleName,&$files=array(),&$bundles =array()) {
			if (self::$webmodules != null && ! empty ( self::$webmodules ['bundles'] )  && isset(self::$webmodules ['bundles'][$bundleName])) {
				$bundles[$bundleName] = true;
				self::$scanned_bundles[$bundleName] = true;
				if(isset(self::$webmodules ['bundles'][$bundleName]["on"])){
					foreach (self::$webmodules ['bundles'][$bundleName]["on"] as $index=>$otherBundleName){
						if(!isset(self::$scanned_bundles[$otherBundleName])){
							$files = self::getAllFiles($otherBundleName,$files,$bundles);
						}
					}
				}
				if(isset(self::$webmodules ['bundles'][$bundleName]["js"])){
					foreach (self::$webmodules ['bundles'][$bundleName]["js"] as $index=>$fileName){
						if(!isset($files[$fileName]) && !isset(self::$scanned_files[$fileName])){
							$files[$fileName] = true;
							self::$scanned_files[$fileName] = true;
						}
					}
				}
			}
			return $files;
		}
		
		public static function bundlify($indexBundles = array("webmodules/bootloader")) {
			self::$scanned_files = array();
			self::$scanned_bundles = array();
			Console::log("Robo Task Bundlifying...");
			$indexBundles = array_unique($indexBundles);
			
			foreach ($indexBundles as $indexb=>$indexBundle){
				$files = array(); $bundles =array();
				$files = self::getAllFiles($indexBundle,$files,$bundles);
				Console::log("Bundlifying : ".$indexBundle);
				$fileContent = "";
				Console::log("Reading and Minifying file for bundle :".$indexBundle);
				$indexBundleCounter = 0;
				if(empty(self::$webmodules ['bundles'][$indexBundle]["bundled"])){
					self::$webmodules ['bundles'][$indexBundle]["bundled"] = array();
				}
				foreach ($files as $index=>$file){
					Console::log("File : ".$index);
					if(!FileUtil::is_remote_file($index)){
						$newFile = self::js_minfiy ( $index,"somerandomcodeword.js" );
						$fileContent= $fileContent.";\n\n".file_get_contents($newFile);
						unlink($newFile);
					} else {
						$bundledFileName=self::finalize($indexBundle, $indexBundleCounter,$bundles,$fileContent);
						//self::$webmodules ['bundles'][$indexBundle]["bundled"][] = $bundledFileName;
						self::$webmodules ['bundles'][$indexBundle]["bundled"][] = $index;
						$indexBundleCounter++;
						$fileContent = "";
					}
				}
				$bundledFileName = self::finalize($indexBundle, $indexBundleCounter, $bundles,$fileContent);
				//self::$webmodules ['bundles'][$indexBundle]["bundled"][] = $bundledFileName;
				
				//$scannedFiles = array_merge($files);
			}
			self::write_resource_json();
		}
		
		private static function finalize($indexBundle,$indexBundleCounter,$scannedBundles,$fileContent){
			$bundledFileName =  "resources/bundled/" . implode(".", explode("/",  $indexBundle)).
			($indexBundleCounter==0 ? "" : ("-".$indexBundleCounter))
			.".js";
			$scannedBundlesArray = array_keys($scannedBundles);
			$fileContent.=";\n(function(foo,bundles){foo.__bundled__ = foo.__bundled__ ? foo.__bundled__.concat(bundles) : bundles;})(this,"
					.json_encode($scannedBundlesArray,JSON_UNESCAPED_SLASHES)
					.");";
				
			FileUtil::build_write ( self::$RESOURCE_DIST_DIR . "/" . $bundledFileName,$fileContent);
			foreach ($scannedBundlesArray as $bundledName){
				self::$webmodules ['bundles'][$bundledName]["bundled"][] = $bundledFileName;
			}
			return $bundledFileName;
		}
		
		public static function build_js($minConfig = array()) {
			
			self::setMinfierConfig ( $minConfig );
			
			if (self::$webmodules != null && ! empty ( self::$webmodules ['bundles'] )) {
				foreach ( self::$webmodules ['bundles'] as $module => $moduleObject ) {
					foreach ( $moduleObject ["files"] as $file ) {
						self::js_minfiy ( $file );
					}
				}
			}
			
		}
	}
}