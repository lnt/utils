<?php

namespace RudraX\Utils {

	class StringUtil {
		
		/**
		 *
		 * @param unknown $search        	
		 * @param string $replace        	
		 * @param string $subject        	
		 * @return string|mixed
		 */
		public static function replace_first($search, $replace = "", $subject = "") {
			if (empty ( $search )) {
				return $subject;
			}
			$pos = strpos ( $subject, $search );
			if ($pos !== false) {
				$newstring = substr_replace ( $subject, $replace, $pos, strlen ( $search ) );
			}
			return $newstring;
		}
	}
}