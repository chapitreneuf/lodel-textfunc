<?php

/** filtre illustrations pour lodel

 Utilisation : [#TEXTE|illustrations(200)]
 Il remplit un tableau #IMAGES utilisable pour la table des illustrations

*/

// Uncomment to test
// $html = file_get_contents('text.html');
// $html_ill = illustrations($html, 100);
// echo $html_ill;

function illustrations($html, $width=400) {
	$dom = text_to_dom($html);
	$images = find_and_group_illustrations($dom);
	illustration_thumbnails($dom, $images, $width);
	$images = export_illustrations_to_lodel($dom, $images);
	C::set('images', $images);

// 	var_export($images);
	return dom_to_text($dom);
}

// function name is explicit enough
function find_and_group_illustrations(&$dom) {
	$images_nodes = xpath_find($dom, '//p[@class=\'texte\' and img]');

	// find all elements that belong to the image
	$images = array();
	$nb_img = 0;
	foreach ($images_nodes as $image) {
		$images[$nb_img]['image'] = $image;
		$image->attributes->getNamedItem('class')->nodeValue = 'imageillustration';
		foreach ([['previous','titre'], ['next','legende'], ['next','credit']] as $search) {
			list($direction, $class_name) = $search;
			$found = find_Sibling($direction, $image, 'p', $class_name.'illustration', 'texte');
			if ($found) {
				$images[$nb_img][$class_name] = $found;
			}
		}
		$nb_img +=1;
	}
	
	// create container and put illustrations elements in in
	foreach ($images as $index => $image) {
		$container = create_element($dom, 'div', [['id','illustration-'.($index+1)], ['class','groupe-illustration']]);
		// put container just before img tag
		$container = $image['image']->parentNode->insertBefore($container, $image['image']);
		foreach(['titre', 'image', 'legende', 'credit'] as $to_move) {
			if (isset($image[$to_move])) {
				// remove node and put it in container
				$old_node = $image[$to_move]->parentNode->removeChild($image[$to_move]);
				$images[$index][$to_move] = $container->appendChild($old_node);
			}
		}
	}

	return $images;
}

// do thumbnail of illustrations
function illustration_thumbnails(&$dom, &$images, $width) {
	foreach ($images as &$image) {
		$img = $image['image']->firstChild;
		$src = $img->attributes->getNamedItem('src')->nodeValue;
// 		$thumb_src = $src.'.thumb'; // uncomment next line in lodel
		$thumb_src = vignette($src, $width);
// 		error_log('Image convertie : ' . $thumb_src);
		$image['src'] = $src;
		$image['thumb_src'] = $thumb_src;
		$img->attributes->getNamedItem('src')->nodeValue = $thumb_src;
		$a = create_element($dom, 'a', [['href', $src]]);
		$img = $image['image']->replaceChild($a, $img);
		$a->appendChild($img);
	}
}

function export_illustrations_to_lodel(&$dom, $images) {
	foreach ($images as &$image) {
		foreach(['titre', 'image', 'legende', 'credit'] as $export) {
			if (isset($image[$export])) {
				$image[$export] = $dom->saveXML($image[$export]);
			}
		}
	}
	return $images;
}

// find the sibling of $node using $nodeName and $class_name
function find_Sibling($direction, $node, $nodeName, $class_name, $stop_class) {
	$property = "{$direction}Sibling";
	while ($previous = $node->$property) {
		if ($previous->nodeName == $nodeName) {
			if ($classes = get_classes($previous)) {
				if (in_array($class_name, $classes))
					return $previous;
				if (in_array($stop_class, $classes))
					return false;
			}
		}
		$node = $previous;
	}
	return false;
}
