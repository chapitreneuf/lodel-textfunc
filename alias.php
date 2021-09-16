<?php

/** 
 * Alias pour simuler les filtres de la Lodelia
 */

if (!function_exists('vignettiser')) {
	function vignettiser($text, $width, $height) {
		return illustrations($text, $width);
	}
}

if (!function_exists('embedMedia')) {
	function embedMedia($text, $width = 400, $onlyDesc = false) {
		return media($text);
	}
}
