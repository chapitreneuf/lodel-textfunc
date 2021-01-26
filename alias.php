<?php

/** 
 * Alias pour simuler les filtres de la Lodelia
 */

if (!function_exists('vignettiser')) {
	function vignettiser($width, $height) {
		return illustrations($width, $height);
	}
}

if (!function_exists('embedMedia')) {
	function embedMedia($width, $onlyDesc) {
		return media();
	}
}
