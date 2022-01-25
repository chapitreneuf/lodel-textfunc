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

function loop_websearch(&$context, $funcname, $args) {
	// mise en place des options de la boucle
	$options = ['engine'=>'qwant', 'site'=>'', 'limit'=>10, 'q'=>'', 'offset'=>0];
	$options['site'] = !empty($context['options']['metadonneessite']['urldusite']) ? $context['options']['metadonneessite']['urldusite'] : $context['siteurl'];
	$options['site'] = preg_replace('@^.*\/\/@', '', $options['site']);
	foreach ($options as $option => $value) {
		$$option = empty($args[$option]) ? $value : $args[$option];
	}

	if (intval($limit) < 1)
		$limit = $options['limit'];
	$q = urlencode($q);

	// appel au moteur de recherche
	$search_func = "search_".$engine;
	$results = $search_func($q, $limit, $offset, $site);

	// pas de résultats
	$localcontext = $context;

	if (empty($results['items'])) {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $localcontext);
		return;
	}

	$loop_max = $results['count'];
	$localcontext['total'] = $results['total'];

	// code avant
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $localcontext);

	$count = 0;
	foreach ($results['items'] as $result) {
		$localcontext['count'] = ++$count;
		$docontext = array_merge($localcontext, $result);

		if ($count == 1 && function_exists("code_dofirst_$funcname")) {
			call_user_func("code_dofirst_$funcname", $docontext);
		} elseif( $count == $loop_max && function_exists("code_dolast_$funcname") ) {
			call_user_func("code_dolast_$funcname", $docontext);
		} else {
			call_user_func("code_do_$funcname", $docontext);
		}
	}

	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $localcontext);
}

// ask qwant
// Output:
// [
//		'total' => (int) supposed max number of items
//		'count' => (int) number of items
//		'last_page' => (bool) more results or not,
//		'items' => [ [ 'title','url','favicon','source','desc','_id'], … ]
// ]
function search_qwant($q, $limit, $offset, $site) {

	// Limit doit être un multiple de 10, qwant n'accepte que 10
	if (10 % $limit !== 0) {
		trigger_error("websearch LOOP: limit argument must be a multiple of 10. $limit is not !", E_USER_ERROR);
	}

	// URL de l'API + la recherche
	$url = 'https://api.qwant.com/v3/search/web?q=site:'.$site.'+'.$q;

	// ajout des paramètres (non docummentés) pour qwant
	$url .= "&count=10&safesearch=1&locale=fr_FR&device=desktop";

	// ajout de la pagination
	if ($offset) {
		$page = floor($offset/10) * 10;
		if ($page)
			$url .= "&offset=$page";
	}

	// chargment de la réponse
	$ret = curl_get($url);
	if (!$ret) {
		error_log("Pb avec qwant $url ." . var_export($ret, true));
		return array();
	}
	
	// on décode, test
	$json = json_decode($ret, true);
	if (!$json || empty($json['data']['result'])) {
		error_log("Pb avec qwant $url." . var_export($ret, true));
		return array('total'=>0, 'items'=>[], 'count'=>0, 'last_page'=>1);
	}

	$results = $json['data']['result']['items']['mainline'];
	foreach( $json['data']['result']['items']['mainline'] as $c ) {
		if($c['type'] == "web") {
			$results = $c;
			break;
		}
	}
	$qwant_items = sizeof($results['items']);

	// tailler les résultats pour correspondre à limit et offset
	$start = $offset % 10;
	$results['items'] = array_slice($results['items'], $start, $limit);
	$results['count'] = sizeof($results['items']);

	// We don't have real total nor last_page
	// Trick loop total to be bigger if not on last page
	$results['last_page'] =  $qwant_items < 10 && $start + $limit >= $qwant_items;
	$results['total'] = $offset + $results['count'];
	if (!$results['last_page']) {
		$results['total'] += 1;
	}

	// error_log('Results: ' . var_export($results, 1));

	return $results;
}
