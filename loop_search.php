<?php

/*
	Boucle de recherche externe pour lodel

	Utilisation:
		<LOOP NAME="search" Q="[#Q]" LIMIT="6" SITE="edinum.org" ENGINE="qwant">
	Options:
		-engine: pour l'instant uniquement qwant
		-site: par défaut le site en cours

	On reçoit dans la boucle : title, 'favicon', 'url', 'desc', 'date'

*/

function loop_search(&$context, $funcname, $args) {
	// mise en place des options de la boucle
	$options = ['engine'=>'qwant', 'site'=>preg_replace('@^.*\/\/@','',$context['siteurl']), 'limit'=>10, 'q'=>''];
	foreach ($options as $option => $value) {
		$$option = empty($args[$option]) ? $value : $args[$option];
	}
	if (intval($limit) < 1)
		$limit = $options['limit'];
	$q = urlencode($q);

	// appel au moteur de recherche
	$search_func = "search_".$engine;
	$results = $search_func($q, $limit, $site);

	// pas de résultats
	$localcontext = $context;
	if (empty($results)) {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $localcontext);
		return;
	}

	$total = count($results);
	$localcontext['total'] = $total;

	// code avant
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $localcontext);

	$count = 0;
	foreach ($results as $result) {
		$localcontext['count'] = ++$count;
		$docontext = array_merge($localcontext, $result);

		if ($count == 1 && function_exists("code_dofirst_$funcname")) {
			call_user_func("code_dofirst_$funcname", $docontext);
		} elseif( $count == $total && function_exists("code_dolast_$funcname") ) {
			call_user_func("code_dolast_$funcname", $docontext);
		} else {
			call_user_func("code_do_$funcname", $docontext);
		}
	}

	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $localcontext);
}

// ask qwant
function search_qwant($q, $limit, $site) {
	$url = 'https://api.qwant.com/egp/search/web?q=site:'.$site.'+'.$q;
	$ret = file_get_contents($url);
	if (!$ret)
		return array();
	
	$json = json_decode($ret, true);
	if (!$json || empty($json['data']['result']['items']))
		return array();

	$results = array_slice($json['data']['result']['items'], 0, $limit);

	return $results;
}
