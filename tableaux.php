<?php

/** filtre tableaux pour lodel

 Utilisation : [#TEXTE|tableaux]
 Ajoute un attribut title aux tableaux du texte, calculé à partir du p.titreillustration précédent le tableau.

*/

function tableaux($html) {
	$dom = text_to_dom($html);
	$tables = xpath_find($dom, '//table');
	foreach ($tables as $table) {
		$preceding = $table->previousSibling;
		$classname = $preceding->getAttribute('class');
		$title = $preceding->textContent;
		if ($classname !== 'titreillustration' or $title == '') {
			continue;
		}
		$table->setAttribute('title', $title);
	}
	return dom_to_text($dom);
}
