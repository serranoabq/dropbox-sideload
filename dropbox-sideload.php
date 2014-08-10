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

//Add scripts & styles
function dropbox_sideload_scripts() {	
	
	wp_enqueue_script('dropboxjs', 'https://www.dropbox.com/static/api/2/dropins.js' );
	wp_enqueue_script('dropbox-sideload', plugins_url('dropbox-sideload.js?'.rand(), __FILE__), array('dropboxjs') );
	wp_enqueue_style('dropbox-sideload', plugins_url('dropbox-sideload.css', __FILE__));
}

function dropbox_sideload_form () {
	global $pagenow;
	media_upload_header();
	dropbox_sideload_scripts();
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	$url = admin_url('media-upload.php?tab=dropbox');

	if ( $post_id )
		$url = add_query_arg('post_id', $post_id, $url);

	?>
	<div id="dropbox-sideload-form">
		<form method="post" action="<?php echo $url; ?>">
		<input type="hidden" name="post_id" id="<?php echo $post_id; ?>" value="0" />
		<?php wp_nonce_field(); ?>
		<div id="media-upload-notice"></div>
		<div id="media-upload-error"></div>
		<h3 class="media-title">Upload files from your Dropbox</h3>
		<ol id="dropbox-sideload-steps">
		<li id="step1"><a href="#" id="dropbox-choose">Choose from Dropbox</a></li>
		<li id="step2"><input type="text" name="dropbox-file" id="dropbox-file" readonly /></li>
		<li id="step3"><input type="submit" class="button media-button" id="dropbox-form-submit" name="dropbox-form-submit" disabled="disabled"/></li>
		</ol>
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

add_action( 'admin_init', 'handle_dropbox_sideload');
function handle_dropbox_sideload(){
	global $post;
	echo '<!--';
		var_dump($_REQUEST);
		var_dump($_POST);
	echo '-->';
	/*
	$url = ''; // Input a .zip URL here
	$tmp = download_url( $url );
	$file_array = array(
			'name' => basename( $url ),
			'tmp_name' => $tmp
	);

	// Check for download errors
	if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array[ 'tmp_name' ] );
			return $tmp;
	}

	$id = media_handle_sideload( $file_array, 0 );
	// Check for handle sideload errors.
	if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
	}

	$attachment_url = wp_get_attachment_url( $id );
	// Do whatever you have to here
	*/
}
?>
