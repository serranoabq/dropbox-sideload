<?php
/*
    Plugin Name: Dropbox Sideloader
    Description: Plugin to enable sideloading media from Dropbox.
    Version: 0.1
    Author: Justin R. Serrano
		* plugin scope: dropbox-sideload
*/
add_action('admin_init', 'dropbox_sideload_admin_init');
function dropbox_sideload_admin_init(){

	
	// add_action('load-media_page_add-from-server', array(&$this, 'add_styles') );
	// add_action('media_upload_server', array(&$this, 'add_styles') );

	
	// add_filter('plugin_action_links_' . $this->basename, array(&$this, 'add_configure_link'));
		
	// Add a Dropbox Sideload tab to the medua uploader
	add_filter('media_upload_tabs', 'dropbox_sideload_menu');
	
	// Add Dropbox Sideload tab handler
	add_action('media_upload_dropbox',  'dropbox_sideload_tab_handler');
	
	register_setting('dropbox-sideload', 'dropbox-api');
}

// Assign a tab to the media uploader
function dropbox_sideload_menu($tabs) {
	$tabs['dropbox']='Dropbox Sideload';
	return $tabs;
}

// Handle Dropbox Sideload tab
function dropbox_sideload_tab_handler() {

	//add styles
	
	return wp_iframe('dropbox_sideload_form');
}

add_action('admin_menu', 'dropbox_sideload_admin_menu');
function dropbox_sideload_admin_menu() {
		if ( ! function_exists('submit_button') ) return;
		if ( current_user_can('upload_files') )
			add_media_page( __('Dropbox Sideload', 'dropbox-sideload'), __('Dropbox Sideload', 'dropbox-sideload'), 'read', 'dropbox-sideload', 'dropbox_sideload_menu_page' );
		add_options_page( __('Dropbox Sideload Settings', 'dropbox-sideload'), __('Dropbox Sideload', 'dropbox-sideload'), 'manage_options', 'dropbox-sideload-settings', 'dropbox_sideload_options_page' );
	}

function dropbox_sideload_menu_page(){
	if( !current_user_can('upload_files')) return;
	
	echo '<div class="wrap">';
		screen_icon('upload');
		echo '<h2>' . __('Dropbox Sideload', 'dropbox-sideload') . '</h2>';

		//Do the content
		dropbox_sideload_main_content();
		
		echo '</div>';
}

function dropbox_sideload_main_content(){
	global $pagenow;
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	$import_to_gallery = isset($_POST['gallery']) && 'on' == $_POST['gallery'];
	if ( ! $import_to_gallery && !isset($_REQUEST['cwd']) )
		$import_to_gallery = true; // cwd should always be set, if it's not, and neither is gallery, this must be the first page load.
	
	if ( 'upload.php' == $pagenow )
		$url = admin_url('upload.php?page=dropbox-sideload');
	else
		$url = admin_url('media-upload.php?tab=dropbox');

	if ( $post_id )
		$url = add_query_arg('post_id', $post_id, $url);

	?>
	<div class="dropbox_sideload_wrap">
	
	<form method="post" action="<?php echo $url ?>">
	<?php if ( 'media-upload.php' == $GLOBALS['pagenow'] && $post_id > 0 ) : ?>
		<p><?php printf(__('Once you have selected files to be imported, go to the <a href="%s">Media Library tab</a> to add them to your post.', 'dropbox-sideload'), esc_url(admin_url('media-upload.php?type=image&tab=library&post_id=' . $post_id)) ); ?></p>
   <?php endif; ?>
	<?php if ( $post_id != 0 ) : ?>
		<input type="checkbox" name="gallery" id="gallery-import" <?php checked( $import_to_gallery ); ?> /> <label for="gallery-import"><?php _e('Attach sideloaded files to this post', 'dropbox-sideload')?></label>
		<br class="clear" />
	<?php endif; ?>
	<br class="clear" />
	<?php submit_button( __('Import', 'dropbox-sideload'), 'primary', 'import', false); ?>
	</form>
	</div>
<?php
}

function dropbox_sideload_options_page(){
	if ( ! current_user_can('manage_options') )
		return;

	echo '<div class="wrap">';
	screen_icon('options-general');
	echo '<h2>' . __('Dropbox Sideload Settings', 'dropbox-sideload') . '</h2>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'dropbox_sideload' );
	do_settings_sections( 'dropbox_sideload' );
	?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Dropbox API</th>
			<td><input type="text" name="dropbox-api" value="<?php echo esc_attr( get_option('dropbox-api') ); ?>" /></td>
			<td>Enter your Dropbox Chooser API key</td>
		</tr>
	</table>
<?php
	submit_button( __('Save Changes', 'dropbox-sideload'), 'primary', 'submit');
	echo '</form>';
	echo '</div>';
}





/*
function xdropbox_sideload_admin_menu() {
	if ( ! function_exists('submit_button') )
		return;
	if ( sd_id_user_allowed() )
		add_media_page( __('Dropbox Sideload', 'dropbox-sideload'), __('Dropbox Sideload', 'dropbox-sideload'), 'read', 'dropbox-sideload', array(&$this, 'menu_page') );
	add_options_page( __('Add From Server Settings', 'add-from-server'), __('Add From Server', 'add-from-server'), 'manage_options', 'add-from-server-settings', array(&$this, 'options_page') );
	}

function dropbox_sideload_add_configure_link($_links) {
	$links = array();
	if ( current_user_can('manage_options') )
		$links[] = '<a href="' . admin_url('options-general.php?page=cloud-sideload') . '">' . __('Options', 'cloud-sideload') . '</a>';

	return array_merge($links, $_links);
}	
	
	
	
	
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
	
	if( isset( $_REQUEST['dropbox-file'] ) ){
		$dpurl = $_REQUEST['dropbox-file']; // Input a .zip URL here
		$tmp = download_url( $dpurl );
		
		$file_array = array(
				'name' => basename( $dpurl ),
				'tmp_name' => $tmp
		);
		
		// Check for download errors
		if ( is_wp_error( $tmp ) ) {
				@unlink( $file_array[ 'tmp_name' ] );
				echo '<div class="ds_error">Error downloading the file from Dropbox</div>';
				return $tmp;
		}

		$id = media_handle_sideload( $file_array, $post_id );
		// Check for handle sideload errors.
		if ( is_wp_error( $id ) ) {
			echo '<div class="ds_error">Error sideloading the file from Dropbox</div>';
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		$attachment_url = wp_get_attachment_url( $id );
		// Do whatever you have to here
		echo '<div class="success">';
		echo '<h3>File Downloaded Succesfully!</h3>';
		echo 'The file: <br/>';
		echo '<code>'.$file_array[ 'name' ] .'</code></br>';
		echo 'was downloaded to</br>';
		echo '<code>'.$attachment_url.'</code></br>';
		echo 'Press the <b>Insert Into Post</b> button';
		echo '</div>';
		
	} else {
	
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
	}



//Needed script to make sure wordpress' media upload scripts are inplace
wp_enqueue_script('media-upload');

//Add tab to the media upload button


//Add menu handle to when the media upload action occurs
add_action('media_upload_dropbox', 'dropbox_sideload_menu_handle');
*/



?>
