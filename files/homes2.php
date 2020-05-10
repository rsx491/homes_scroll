<?php

//echo "Notice: Set error handling\n";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// include composer autoloader
require_once 'vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Driver\Exception;

$zipcode = '33130';
if(isset($_POST['locale'])){
	$pieces = explode("/",$_POST['locale']);
	$zipcode = $pieces[0];
}

makeFiles($zipcode);

function makeFiles($zipcode){
	if(!file_exists("./$zipcode")) {
		$file = 'homes_creation.php';
		shell_exec("php $file $zipcode >/dev/null 2>&1 &");
		sleep(2);
	}
	return;
}

$json = file_get_contents('./zip_codes_long.json');
$zip_codes = json_decode($json, true);
$florida = $zip_codes['Florida'];

$homes = (new MongoDB\Client("mongodb://192.168.1.73:27017"))->zillow->homes;
$record = $homes->find(array('zipcode' => $zipcode, 'images' => ['$ne' => []]), ['projection' => ['_id' => 1]]);

$home_pages = [];
$count = 0;
foreach($record as $item){
	$id = "{$item->_id}";
	if($count < 5){
		$first_pages[] = "$zipcode/{$id}.html";
	}else{
		$homes_pages[] = "$zipcode/{$id}.html";
	}
	$count++;
}

if($count == 0){
	$no_results = true;
	$first_pages = 'No Listings/Go Back';
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Page Title</title>
    <meta name="description" content="Webpage for xxxx">
	<script src="https://unpkg.com/infinite-scroll@3/dist/infinite-scroll.pkgd.js" type="text/javascript"></script>
	<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.js" type="text/javascript"></script>
	<link rel="stylesheet" href="./node_modules/lightbox2/dist/css/lightbox.min.css">
	<style>
		body {
		  font-family: sans-serif;
		  line-height: 1.4;
		  font-size: 18px;
		  padding: 20px;
		  max-width: 60%;
		  margin: 0 auto;
		}
		
		input.rounded {
			border: 1px solid #ccc;
			-moz-border-radius: 10px;
			-webkit-border-radius: 10px;
			border-radius: 10px;
			-moz-box-shadow: 2px 2px 3px #666;
			-webkit-box-shadow: 2px 2px 3px #666;
			box-shadow: 2px 2px 3px #666;
			#font-size: 20px;
			padding: 4px 7px;
			outline: 0;
			-webkit-appearance: none;
		}
		input.rounded:focus {
			border-color: #339933;
		}
		
		.center {
		  margin: auto;
		  width: 50%;
		  text-align: center;
		  padding: 10px;
		}

		.grid {
		  max-width: 1200px;
		}

		/* reveal grid after images loaded */
		.grid.are-images-unloaded {
		  opacity: 0;
		}

		.grid__item,
		.grid__col-sizer {
		  width: 32%;
		}

		.grid__gutter-sizer { width: 5%; }

		/* hide by default */
		.grid.are-images-unloaded .image-grid__item {
		  opacity: 0;
		}

		.grid__item {
		  margin-bottom: 20px;
		  float: left;
		}

		.grid__item--height1 { height: 140px; background: #EA0; }
		.grid__item--height2 { height: 220px; background: #C25; }
		.grid__item--height3 { height: 300px; background: #19F; }

		.grid__item--width2 { width: 80%; }

		.grid__item img {
		  display: block;
		  max-width: 100%;
		}

		.page-load-status {
		  display: none; /* hidden by default */
		  padding-top: 20px;
		  border-top: 1px solid #DDD;
		  text-align: center;
		  color: #777;
		}
		
		/*the container must be positioned relative:*/
		.autocomplete {
		  position: relative;
		  display: inline-block;
		}

		input {
		  border: 1px solid transparent;
		  background-color: #f1f1f1;
		  #padding: 10px;
		  font-size: 20px;
		}

		input[type=text] {
		  background-color: #f1f1f1;
		  width: 100%;
		}

		input[type=submit] {
		  background-color: DodgerBlue;
		  color: #fff;
		  cursor: pointer;
		}

		.autocomplete-items {
		  position: absolute;
		  border: 1px solid #d4d4d4;
		  border-bottom: none;
		  border-top: none;
		  z-index: 99;
		  /*position the autocomplete items to be the same width as the container:*/
		  top: 100%;
		  left: 0;
		  right: 0;
		}

		.autocomplete-items div {
		  padding: 10px;
		  cursor: pointer;
		  background-color: #fff; 
		  border-bottom: 1px solid #d4d4d4; 
		}

		/*when hovering an item:*/
		.autocomplete-items div:hover {
		  background-color: #e9e9e9; 
		}

		/*when navigating through the items using the arrow keys:*/
		.autocomplete-active {
		  background-color: DodgerBlue !important; 
		  color: #ffffff; 
		}		
	</style>
  </head>
  <body> 
	  <h1>Search Florida Homes <?php echo $zipcode ?>
	  <br> Keep scrolling as images load
	  Cache disabled</h1></h1>
		<form autocomplete="off" action="/homes2.php" method="POST">
		  <div class="autocomplete" style="width:300px;padding-bottom:10px;">
			<input id="myInput" type="text" name="locale" placeholder="Florida Zipcode">
		  </div>
		  <input type="submit">
		</form>
		
		<?php
			if($count==0){
				echo "$first_pages";
			}else{
				foreach($first_pages as $pages){
					echo file_get_contents($pages);
				}
			}
		?> 
  </body>
  <script>
		//-------------------------------------//

		var grid = document.querySelector('.grid');

		var msnry = new Masonry( grid, {
		  itemSelector: 'none', // select none at first
		  columnWidth: '.grid__col-sizer',
		  gutter: '.grid__gutter-sizer',
		  percentPosition: true,
		  stagger: 30,
		  // nicer reveal transition
		  visibleStyle: { transform: 'translateY(0)', opacity: 1 },
		  hiddenStyle: { transform: 'translateY(100px)', opacity: 0 },
		});


		// initial items reveal
		imagesLoaded( grid, function() {
		  grid.classList.remove('are-images-unloaded');
		  msnry.options.itemSelector = '.grid__item';
		  var items = grid.querySelectorAll('.grid__item');
		  msnry.appended( items );
		});

		//-------------------------------------//
		// hack CodePen to load pens as pages

		var nextPenSlugs = [
		<?php
			if(is_array($homes_pages)){
				if(count($homes_pages)>0){
					$last = end($homes_pages);
					foreach($homes_pages as $page){
						if($page != $last)
							echo "'" . $page . "',";
						else
							echo "'" . $page . "'";
					}
				}
			}
		?>
		];

		function getPenPath() {
		  var slug = nextPenSlugs[ this.loadCount ];
		  return slug;
		}

		//-------------------------------------//
		// init Infinte Scroll

		var infScroll = new InfiniteScroll( grid, {
		  path: getPenPath,
		  append: '.grid__item',
		  outlayer: msnry,
		  status: '.page-load-status',
		  history: false
		});
		
	function autocomplete(inp, arr) {
	  /*the autocomplete function takes two arguments,
	  the text field element and an array of possible autocompleted values:*/
	  var currentFocus;
	  /*execute a function when someone writes in the text field:*/
	  inp.addEventListener("input", function(e) {
		  var a, b, i, val = this.value;
		  /*close any already open lists of autocompleted values*/
		  closeAllLists();
		  if (!val) { return false;}
		  currentFocus = -1;
		  /*create a DIV element that will contain the items (values):*/
		  a = document.createElement("DIV");
		  a.setAttribute("id", this.id + "autocomplete-list");
		  a.setAttribute("class", "autocomplete-items");
		  /*append the DIV element as a child of the autocomplete container:*/
		  this.parentNode.appendChild(a);
		  /*for each item in the array...*/
		  for (i = 0; i < arr.length; i++) {
			/*check if the item starts with the same letters as the text field value:*/
			if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
			  /*create a DIV element for each matching element:*/
			  b = document.createElement("DIV");
			  /*make the matching letters bold:*/
			  b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
			  b.innerHTML += arr[i].substr(val.length);
			  /*insert a input field that will hold the current array item's value:*/
			  b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
			  /*execute a function when someone clicks on the item value (DIV element):*/
			  b.addEventListener("click", function(e) {
				  /*insert the value for the autocomplete text field:*/
				  inp.value = this.getElementsByTagName("input")[0].value;
				  /*close the list of autocompleted values,
				  (or any other open lists of autocompleted values:*/
				  closeAllLists();
			  });
			  a.appendChild(b);
			}
		  }
	  });
	  /*execute a function presses a key on the keyboard:*/
	  inp.addEventListener("keydown", function(e) {
		  var x = document.getElementById(this.id + "autocomplete-list");
		  if (x) x = x.getElementsByTagName("div");
		  if (e.keyCode == 40) {
			/*If the arrow DOWN key is pressed,
			increase the currentFocus variable:*/
			currentFocus++;
			/*and and make the current item more visible:*/
			addActive(x);
		  } else if (e.keyCode == 38) { //up
			/*If the arrow UP key is pressed,
			decrease the currentFocus variable:*/
			currentFocus--;
			/*and and make the current item more visible:*/
			addActive(x);
		  } else if (e.keyCode == 13) {
			/*If the ENTER key is pressed, prevent the form from being submitted,*/
			e.preventDefault();
			if (currentFocus > -1) {
			  /*and simulate a click on the "active" item:*/
			  if (x) x[currentFocus].click();
			}
		  }
	  });
	  function addActive(x) {
		/*a function to classify an item as "active":*/
		if (!x) return false;
		/*start by removing the "active" class on all items:*/
		removeActive(x);
		if (currentFocus >= x.length) currentFocus = 0;
		if (currentFocus < 0) currentFocus = (x.length - 1);
		/*add class "autocomplete-active":*/
		x[currentFocus].classList.add("autocomplete-active");
	  }
	  function removeActive(x) {
		/*a function to remove the "active" class from all autocomplete items:*/
		for (var i = 0; i < x.length; i++) {
		  x[i].classList.remove("autocomplete-active");
		}
	  }
	  function closeAllLists(elmnt) {
		/*close all autocomplete lists in the document,
		except the one passed as an argument:*/
		var x = document.getElementsByClassName("autocomplete-items");
		for (var i = 0; i < x.length; i++) {
		  if (elmnt != x[i] && elmnt != inp) {
			x[i].parentNode.removeChild(x[i]);
		  }
		}
	  }
	  /*execute a function when someone clicks in the document:*/
	  document.addEventListener("click", function (e) {
		  closeAllLists(e.target);
	  });
	}

	/*florida zip codes*/
	let zips = [
		<?php
		$last = end($florida);
		$last = $last["ZIP Code"];
		foreach($florida as $row){
			$zip = $row["ZIP Code"];
			$locale = $row["City"];
			if($zip != $last)
				echo "'" . $zip . "/" . $locale . "',";
			else
				echo "'" . $zip . "/" . $locale . "'";
		}?>];

	/*initiate the autocomplete function on the "myInput" element, and pass along the countries array as possible autocomplete values:*/
	autocomplete(document.getElementById("myInput"), zips);
</script>
<script src="./node_modules/lightbox2/dist/js/lightbox-plus-jquery.min.js"></script>
</html>


