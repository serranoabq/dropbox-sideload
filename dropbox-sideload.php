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
	//Register our JS & CSS
	wp_register_script ( 'dropboxjs', 'https://www.dropbox.com/static/api/2/dropins.js' );
	wp_register_script( 'dropbox-sideload', 
		plugins_url( 'dropbox-sideload.js', __FILE__ ), array( 'dropboxjs' ) );
	wp_register_style( 'dropbox-sideload', 
		plugins_url('dropbox-sideload.css', __FILE__ ) );
		
	// NEEDS: dropbox_sideload_add_styles()
	add_action( 'load-media_page_dropbox-sideload', 'dropbox_sideload_add_styles' );
	add_action( 'media_upload_dropbox', 'dropbox_sideload_add_styles' );

	// Add a Dropbox Sideload tab to the media uploader
	// NEEDS: dropbox_sideload_menu()
	add_filter( 'media_upload_tabs', 'dropbox_sideload_menu' );
	
	// Add Dropbox Sideload tab handler
	// NEEDS: dropbox_sideload_tab_handler()
	add_action( 'media_upload_dropbox',  'dropbox_sideload_tab_handler' );
	
	register_setting( 'dropbox-sideload', 'dropbox-api' );
}

// Assign a tab to the media uploader
function dropbox_sideload_menu($tabs) {
	$tabs['dropbox']='Dropbox Sideload';
	return $tabs;
}

// Handle Dropbox Sideload tab
function dropbox_sideload_tab_handler() {
	// NEEDS: dropbox_sideload_main_content()
	
	//Set the body ID
	$GLOBALS['body_id'] = 'media-upload';
	
	//Do an IFrame header
	iframe_header( __('Dropbox Sideload', 'dropbox-sideload') );
	
	//Add the Media buttons	
	media_upload_header();

	//Do the content
	dropbox_sideload_main_content();

	//Do a footer
	iframe_footer();
		
	//return wp_iframe('dropbox_sideload_form');
}

add_action('admin_menu', 'dropbox_sideload_admin_menu');
function dropbox_sideload_admin_menu() {
	// NEEDS: dropbox_sideload_menu_page. 
	if ( ! function_exists('submit_button') ) return;
	
	if ( current_user_can('upload_files') )
		add_media_page( __('Dropbox Sideload', 'dropbox-sideload'), __('Dropbox Sideload', 'dropbox-sideload'), 'read', 'dropbox-sideload', 'dropbox_sideload_menu_page' );
	}

function dropbox_sideload_menu_page(){
	// NEEDS: dropbox_sideload_main_content
	if( !current_user_can('upload_files')) return;
	
	echo '<div class="wrap"><h2>' . __('Dropbox Sideload', 'dropbox-sideload') . '</h2>';

	//Do the content
	dropbox_sideload_main_content();
		
	echo '</div>';
}

function dropbox_sideload_main_content(){
	// NEEDS: dropbox_sideload_handle_sideload()
	global $pagenow;
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	$dropbox_api = get_option('dropbox-api');
	
	if (empty($dropbox_api)) {
		if (isset($_REQUEST['dropbox-api']) && !empty($_REQUEST['dropbox-api'])){
			$dropbox_api=$_REQUEST['dropbox-api'];
			update_option('dropbox-api',$dropbox_api);
		} else {
			$dropbox_api = '';
		}
	}
	$step1 = !empty($dropbox_api);
	$button = __('Next', 'dropbox-sideload');
	$status = '';
	
	if ( 'upload.php' == $pagenow ){
		$url = admin_url( 'upload.php?page=dropbox-sideload' );
		$library_url = admin_url( 'upload.php' );
	} else { 
		$url = admin_url('media-upload.php?tab=dropbox' );
		$library_url = admin_url( 'media-upload.php?tab=library' );
	}
	if ( $post_id )
		$url = add_query_arg( 'post_id', $post_id, $url );

	if( isset( $_REQUEST['dropbox-file'] ) && ! empty( $_REQUEST['dropbox-file'] ) && $step1){
		$attachment_url = dropbox_sideload_handle_sideload( $_REQUEST['dropbox-file'] );
		$button = __( 'Sideload', 'dropbox-sideload' );
		
		if ($attachment_url){
			$status = sprintf(__('Once you have sideloaded the file, go to the <a href="%s">Media Library</a> to add it to your post.', 'dropbox-sideload'), $library_url) ;
		}else{ 
			$status = __('Error sideloading the file from Dropbox', 'dropbox-sideload');
		}
		$step4 = true;
	}
	?>
	<div class="dropbox_sideload_wrap">
	<form method="post" action="<?php echo $url ?>">
	<ol id="dropbox-sideload-steps">
		<li id="step1" class="<?php echo $step1?'done':'required';?>">
			Enter your Dropbox API key:<br/>
			<input type="text" name="dropbox-api" id="dropbox-api" value="<?php echo $dropbox_api; ?>" <?php echo $step1?'readonly':'';?>/>
		</li>
		<li id="step2" class="<?php echo $step1?'required':'disabled';?>">
			<a href="#" id="dropbox-choose">Choose from Dropbox</a>
		</li>
		<li id="step3" class="disabled">
			<input type="text" name="dropbox-file" id="dropbox-file" readonly />
		</li>
		<li id="step4" class="<?php echo $step4?'done':'disabled'?>">
			Status: <?php echo $status; ?>
		</li>
	</ol>
	<br class="clear" />
	<?php submit_button( $button, 'primary', 'dropbox-sideload-button', false, array('disabled'=>$step1)); ?>
	</form>
	</div>
<?php
}

// There's no easy way to add the id and app-key so hijack the clean_url filter
add_filter('clean_url','unclean_url',10,3);
function unclean_url( $good_protocol_url, $original_url, $_context){
	
	$dropbox_api = get_option('dropbox-api');
	if (empty($dropbox_api)) return $original_url;
	if (false !== strpos($original_url, 'dropbox.com')){
		remove_filter('clean_url','unclean_url',10,3);
      $url_parts = parse_url($good_protocol_url);
      return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . "' id='dropboxjs' data-app-key='".$dropbox_api;
	}
	return $good_protocol_url;
}

//Add scripts & styles
function dropbox_sideload_add_styles() {	
	if ( 'media_upload_server' == current_filter() )
			wp_enqueue_style('media');
			
	wp_enqueue_script('dropboxjs' );
	wp_enqueue_script('dropbox-sideload');
	wp_enqueue_style('dropbox-sideload');
}


// Handle the sideloading. Returns attachment url
function dropbox_sideload_handle_sideload($url){
	
	$tmp = download_url( $url );
	$file_array = array(
		'name' => basename( $url ),
		'tmp_name' => $tmp
	);
	
	// Check for download errors
	if ( is_wp_error( $tmp ) ) {
		@unlink( $file_array[ 'tmp_name' ] );
		return false;
	}
	$id = media_handle_sideload( $file_array, 0);
	
	// Check for handle sideload errors.
	if ( is_wp_error( $id ) ) {
		@unlink( $file_array['tmp_name'] );
		return false;
	}
	$attachment_url = wp_get_attachment_url( $id );
		
	return $attachment_url;
}

/*
function dropbox_sideload_form () {
	global $pagenow;
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	$url = admin_url('media-upload.php?tab=dropbox');

	if ( $post_id )
		$url = add_query_arg('post_id', $post_id, $url);
<<<<<<< HEAD
	
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
=======
	if ( isset( $_REQUEST['dropbox-file']) ){
		$dpurl = $_REQUEST['dropbox-file'];
		$tmp = download_url( $dpurl );
		$file_array = array(
			'name' => basename( $dpurl ),
			'tmp_name' => $tmp
		);

		// Check for download errors
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array[ 'tmp_name' ] );
			return $tmp;
		}
		$id = media_handle_sideload( $file_array, $post_id );
		// Check for handle sideload errors.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}
		$attachment_url = wp_get_attachment_url( $id );
		echo '<script>jQuery(document).readd(function($){
			parent.wp.media.editor.insert('.$attachment_url.');
			return false;
			</script>';
	} else {
		media_upload_header();
		dropbox_sideload_scripts();
>>>>>>> origin/master
	
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



*/
?>
