<?php
/* Integrate svg-edit libraries with Chamilo default documents
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/
$language_file = array('userInfo');
//Chamilo load libraries
require_once '../../../../../inc/global.inc.php';

//Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

$user_disk_path = api_get_path(SYS_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';
$user_web_path  = api_get_path(WEB_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';
//get all files and folders
$scan_files = scandir($user_disk_path);

//get all svg and png files
$accepted_extensions = array('.svg', '.png');

if (is_array($scan_files) && count($scan_files) > 0) {
	foreach ($scan_files as & $file) {
		$slideshow_extension = strrchr($file, '.');
		$slideshow_extension = strtolower($slideshow_extension);
		if (in_array($slideshow_extension, $accepted_extensions)) {
			$png_svg_files[] =$file;
		}
	}
}
$style = '<style>';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).'base.css";';
$style .= '@import "'.api_get_path(WEB_CSS_PATH).api_get_visual_theme().'/default.css";';
$style .='</style>';

?>
<!doctype html>
<?php echo api_get_js('jquery.min.js'); ?>
<?php echo $style ?>

<body>

<?php

echo '<h2>'.get_lang('SocialNetwork').': '.get_lang('MyFiles').'</h2>';

if (!empty($png_svg_files)) {
	echo '<h3>'.get_lang('SelectSVGEditImage').'</h3>';
	echo '<ul>';
	foreach($png_svg_files as $filename) {
		$image=$user_disk_path.$filename;
		
		if (strpos($filename, "svg")){
			$new_sizes['width'] = 60;
			$new_sizes['height'] = 60;
		}
		else {
			$new_sizes = api_resize_image($image, 60, 60);
		}
		
			echo '<li style="display:inline; padding:8px;"><a href="'.$user_web_path.$filename.'" alt "'.$filename.'" title="'.$filename.'"><img src="'.$user_web_path.$filename.'" width="'.$new_sizes['width'].'" height="'.$new_sizes['height'].'" border="0"></a></li>';
	}
	echo '</ul>';	
} else {
	Display::display_warning_message(get_lang('NoSVGImages'));
}
?>
</body>
<script>
$('a').click(function() {
	var href = this.href;
	
	// Convert Non-SVG images to data URL first 
	// (this could also have been done server-side by the library)
	if(this.href.indexOf('.svg') === -1) {

		var meta_str = JSON.stringify({
			name: $(this).text(),
			id: href
		});
		window.top.postMessage(meta_str, "*");
	
		var img = new Image();
		img.onload = function() {
			var canvas = document.createElement("canvas");
			canvas.width = this.width;
			canvas.height = this.height;
			// load the raster image into the canvas
			canvas.getContext("2d").drawImage(this,0,0);
			// retrieve the data: URL
			try {
				var dataurl = canvas.toDataURL();
			} catch(err) {
				// This fails in Firefox with file:// URLs :(
				alert("Data URL conversion failed: " + err);
				var dataurl = "";
			}
			window.top.postMessage('|' + href + '|' + dataurl, "*");
		}
		img.src = href;
	} else {
		// Send metadata (also indicates file is about to be sent)
		var meta_str = JSON.stringify({
			name: $(this).text(),
			id: href
		});
		window.top.postMessage(meta_str, "*");
		// Do ajax request for image's href value
		$.get(href, function(data) {
			data = '|' + href + '|' + data;
			// This is where the magic happens!
			window.top.postMessage(data, "*");
			
		}, 'html'); // 'html' is necessary to keep returned data as a string
	}
	return false;
});
</script>