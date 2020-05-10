<?php

echo "Notice: Set error handling\n";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// include composer autoloader
require_once 'vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Driver\Exception;

if(!isset($argv[1])){
	echo "need zip\n";
	die;
}

$zipcode = $argv[1];
$homes = (new MongoDB\Client("mongodb://192.168.1.73:27017"))->zillow->homes;
$record = $homes->find(array('zipcode' => $zipcode), ['projection' => ['images' => 1, '_id' => 1]]);

if(!file_exists("./$zipcode")) {
    mkdir("./$zipcode", 0775, true);
}

foreach($record as $item){
	if(count($item->images) > 0){
		writeFile($item, $zipcode);
	}
}

function writeFile($item, $zipcode){
	$id = "{$item->_id}";
	$snippet = "";
	$snippet .= "<div class=\"grid are-images-unloaded\">";
	$snippet .= "<div class=\"grid__col-sizer\">";
	$snippet .= "<div class=\"grid__gutter-sizer\">";
		
	foreach($item->images as $image){
			//echo "image: $s\n";
			$snippet .= "<div class=\"grid__item\">";
			//$snippet .= "<img src=\"$image\" / alt=\"$id\" onerror=\"this.src='404.jpg'\">";
			$snippet .= "<a href=\"$image\" data-lightbox=\"$id\" data-title=\"$id\">";
			$snippet .= "<img src=\"$image\" / id=\"$id\" onerror=\"this.style.display='none'\">";
			$snippet .= "</a>";
			$snippet .= "</div>";
	}
	$snippet .= "</div>";
	$snippet .= "</div>";
	$snippet .= "</div>";

	$filename = "$id.html";
	$file = "./$zipcode/$filename";
	file_put_contents($file, $snippet);
}
