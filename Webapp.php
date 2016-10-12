<?php

namespace RudraX\Utils {

    function clean_url($path)
    {
        return str_replace('\\', '/', $path);
    }

    class Webapp
    {
        public static $BASE_DIR; // Absolute path to your installation, ex: /var/www/mywebsite
        public static $SCRIPT_NAME;
        public static $SCRIPT_FILENAME;
        public static $DOC_ROOT; // ex: /var/www
        public static $BASE_URL; // ex: '' or '/mywebsite'
        public static $PROTOCOL;
        public static $PORT;
        public static $DISP_PORT;
        public static $DOMAIN;
        public static $FULL_URL; // Ex: 'http://example.com', 'https://example.com/mywebsite', etc.
        public static $SUBDOMAIN;

        public static function init()
        {

            self::$SCRIPT_NAME = clean_url($_SERVER ['SCRIPT_NAME']);
            self::$SCRIPT_FILENAME = clean_url($_SERVER ['SCRIPT_FILENAME']);

            self::$BASE_DIR = clean_url(dirname(self::$SCRIPT_FILENAME));; // Absolute path to your installation, ex: /var/www/mywebsite
            self::$DOC_ROOT = str_replace(self::$SCRIPT_NAME, '', self::$SCRIPT_FILENAME); // ex: /var/www
            self::$BASE_URL = str_replace(self::$DOC_ROOT, '', self::$BASE_DIR); // ex: '' or '/mywebsite'
            self::$PROTOCOL = empty ($_SERVER ['HTTPS']) ? 'http' : 'https';
            self::$PORT = $_SERVER ['SERVER_PORT'];
            self::$DISP_PORT = (self::$PROTOCOL == 'http' && self::$PORT == 80 || self::$PROTOCOL == 'https' && self::$PORT == 443) ? '' : ":" . self::$PORT;
            self::$DOMAIN = $_SERVER ['SERVER_NAME'];
            self::$FULL_URL = self::$PROTOCOL . "://" . self::$DOMAIN . self::$DISP_PORT . self::$BASE_URL;

            $subdomains = explode(".", $_SERVER['HTTP_HOST']);
            self::$SUBDOMAIN = array_shift($subdomains);
        }
    }

    Webapp::init();
}