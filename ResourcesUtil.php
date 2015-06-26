<?php
class ResourceUtil {
	public static $PROJECT_ROOT_DIR = "";
	public static $BUILD_DIR = "build/";
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
	public static function build_mkdir($dir, $rights) {
		return self::mkdir ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR, $dir, $rights );
	}
	public static function build_read($file) {
		return readfile ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR . $file );
	}
	public static function build_write($file, $content) {
		return file_put_contents ( self::$PROJECT_ROOT_DIR . self::$BUILD_DIR.$file, $content );
	}
	public static function checkDirectory() {
		try {
			if (! is_dir ( self::$PROJECT_ROOT_DIR . "build/" )) {
				self::mkdir ( "cache" );
			}
		} catch ( Exception $e ) {
			echo "build directory not found in project root, please create with appropritae permissions and try again";
		}
	}
}