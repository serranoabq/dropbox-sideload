<?php
/*
    Plugin Name: SideDrop Plugin
    Description: Plugin to enable sideloading media from Dropbox.
    Version: 0.1
    Author: Justin R. Serrano
*/

// Add Dropbox Chooser API-- This should proobably be an option
define( 'DROPBOX_SIDELOAD_API', 'fagq0ntze9syjlq');

// There's no easy way to add the id and app-key so hijack the clean_url filter
add_filter('clean_url','unclean_url',10,3);
function unclean_url( $good_protocol_url, $original_url, $_context){
	if (false !== strpos($original_url, 'dropbox.com')){
		remove_filter('clean_url','unclean_url',10,3);
      $url_parts = parse_url($good_protocol_url);
      return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . "' id='dropboxjs' data-app-key='".DROPBOX_SIDELOAD_API;
	}
	return $good_protocol_url;
}

//Assign a name to the tab
function dropbox_sideload_menu($tabs) {
	$tabs['dropbox']='Dropbox Sideload';
	return $tabs;
}

//Add scripts 
function dropbox_sideload_scripts() {	
	wp_enqueue_script('dropboxjs', 'https://www.dropbox.com/static/api/2/dropins.js' );
	wp_enqueue_script('dropbox-sideload', plugins_url('dropbox-sideload.js', __FILE__), array('dropboxjs') );
	
}

function dropbox_sideload_form () {
	global post;
	media_upload_header();
	dropbox_sideload_scripts();
	?>
	<div id="dropbox-sideload-form">
		<form enctype="multipart/form-data" method="post" action="/wp-admin/media-upload.php?tab=dropbox" class="media-upload-form type-form validate" id="image-form">
		<input type="submit" name="save" id="save" class="button hidden" value="Save Changes"  />
		<input type="hidden" name="post_id" id="post_id" value="0" />
		<?php wp_nonce_field(); ?>
		
		<h3 class="media-title">Upload files from your Dropbox</h3>
		<div id="media-upload-notice"></div>
		<div id="media-upload-error"></div>
		
	</div> 	

<?php }

function dropbox_sideload_menu_handle() {
	return wp_iframe('dropbox_sideload_form');
}

//Needed script to make sure wordpress' media upload scripts are inplace
wp_enqueue_script('media-upload');

//Add tab to the media upload button
add_filter('media_upload_tabs', 'dropbox_sideload_menu');

//Add menu handle to when the media upload action occurs
add_action('media_upload_dropbox', 'dropbox_sideload_menu_handle');


?>
