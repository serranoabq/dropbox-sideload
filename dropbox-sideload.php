<?php
/*
    Plugin Name: Dropbox Sideloader
    Description: Plugin to enable sideloading media from Dropbox. 
    Version: 0.75
    Author: Justin R. Serrano
		Known issues: 
			1. Sideloading is fully dependent on the WP server configuration.
			2. The server must be able to download files from Dropbox. Some installations  
				 have problems accessing https sites, and will not be able to download the 
				 file locally.
			3. Depending on the size of the file, the server operation (downloading) might
				 time out. 
*/

// Initialize plugin
add_action( 'admin_init', 'dropbox_sideload_admin_init' );
function dropbox_sideload_admin_init(){
	// NEEDS: dropbox_sideload_add_styles()
	// NEEDS: dropbox_sideload_menu()
	// NEEDS: dropbox_sideload_tab_handler()
	
	//Register JS & CSS
	wp_register_script ( 'dropboxjs', 
		'https://www.dropbox.com/static/api/2/dropins.js' );
	wp_register_script( 'dropbox-sideload', 
		plugins_url( 'dropbox-sideload.js', __FILE__ ), array( 'dropboxjs' ) );
	wp_register_style( 'dropbox-sideload', 
		plugins_url('dropbox-sideload.css', __FILE__ ) );
		
	// Add Dropbox Sideload to Media Upload pages
	add_action( 'load-media_page_dropbox-sideload', 'dropbox_sideload_add_styles' );
	add_action( 'media_upload_dropbox', 'dropbox_sideload_add_styles' );

	// Add a Dropbox Sideload tab to the media uploader
	add_filter( 'media_upload_tabs', 'dropbox_sideload_menu' );
	
	// Add Dropbox Sideload tab handler
	add_action( 'media_upload_dropbox',  'dropbox_sideload_tab_handler' );
	
	// Add Dropbox API setting
	register_setting( 'dropbox-sideload', 'dropbox-api' );
	register_setting( 'dropbox-sideload', 'dropbox-stay-logged-in' );
}

// Assign a tab to the media uploader
function dropbox_sideload_menu( $tabs ) {
	$tabs['dropbox'] = 'Dropbox Sideload';
	return $tabs;
}

// Handle Dropbox Sideload tab
function dropbox_sideload_tab_handler() {
	// NEEDS: dropbox_sideload_main_content()
	
	// Set the body ID
	$GLOBALS['body_id'] = 'media-upload';
	
	// Do an IFrame header
	iframe_header( __('Dropbox Sideload', 'dropbox-sideload') );
	
	// Add the Media buttons	
	media_upload_header();

	// Do the content
	dropbox_sideload_main_content();

	// Do a footer
	iframe_footer();
}

// Add Dropbox Sideload to the admin menus
add_action( 'admin_menu', 'dropbox_sideload_admin_menu' );
function dropbox_sideload_admin_menu() {
	// NEEDS: dropbox_sideload_menu_page()
	
	if ( ! function_exists('submit_button') ) return;
	
	if ( current_user_can('upload_files') )
		add_media_page( 
			__('Dropbox Sideload', 'dropbox-sideload'),	// Page title
			__('Dropbox Sideload', 'dropbox-sideload'),	// Menu title
			'read', 																		// Capability
			'dropbox-sideload', 												// Menu slug
			'dropbox_sideload_menu_page' 								// Function callback
		);
	}

// Handle the menu page
function dropbox_sideload_menu_page(){
	// NEEDS: dropbox_sideload_main_content()

	if( ! current_user_can( 'upload_files' ) ) return;
	
	echo '<div class="wrap"><h2>' . __('Dropbox Sideload', 'dropbox-sideload') . '</h2>';

	//Do the content
	dropbox_sideload_main_content();
		
	echo '</div>';
}

// Create Dropbox Sideload interface
function dropbox_sideload_main_content(){
	// NEEDS: dropbox_sideload_handle_sideload()

	
	global $pagenow;
	global $wp_version;
	
	// Handle request parameters
	$post_id 						= isset( $_REQUEST['post_id'] ) ? intval($_REQUEST['post_id']) : 0;
	$dropbox_file 			= isset( $_REQUEST['dropbox-file'] ) ? $_REQUEST['dropbox-file'] : '';
	$dropbox_api 				= isset( $_REQUEST['dropbox-api'] ) ? $_REQUEST['dropbox-api'] : '';
	$dropbox_staylog 		= isset( $_REQUEST['dropbox-stay-logged-in'] );
	$dropbox_delete_api = isset( $_REQUEST['dropbox-delete-api'] ) ;
	
	// Get options
	$dropbox_api_option = get_option( 'dropbox-api', '' );
	$dropbox_staylog = $dropbox_staylog || get_option( 'dropbox-stay-logged-in', false );
	
	if ( empty( $dropbox_api_option ) ) {
		$dropbox_api_option = $dropbox_api; 
		update_option( 'dropbox-api', $dropbox_api_option );
	} else {
		$dropbox_api = $dropbox_api_option;
	}
	
	// Clear the API Key
	if ( $dropbox_delete_api ) {
		$dropbox_api = '';
		update_option( 'dropbox-api', $dropbox_api );
	}
	
	// Step 1 is completed when the API setting is entered
	$step1  = ! empty($dropbox_api);
	$class  = $step1 ? 'step2' : 'step1';
	$button = __( 'Next', 'dropbox-sideload' );
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

	if( $step1 && ! empty( $dropbox_file ) ){
		// Step 1 & 2 completed, so handle sideload
		$attachment_url = dropbox_sideload_handle_sideload( $dropbox_file );
		$button = __( 'Complete', 'dropbox-sideload' );
		$class = 'step3';
		
		// If successful, point the user to the Media Library
		if ($attachment_url){
			$status = sprintf( __('The file was susccesfully sideloaded, go to the <a href="%s">Media Library</a> to add it to your post.', 'dropbox-sideload'), $library_url ) ;
		} else { 
			$status = __('Error sideloading the file from Dropbox. Check your logs for details.', 'dropbox-sideload');
			$class .= ' error';
		}
	}
	?>
	
	<div class="dropbox_sideload_wrap">
		<?php if ( ! $step1 ) { ?>
		<div class="updated"><p><strong>NOTE: </strong> The API key allows Dropbox Sideload to utilize the Dropbox Chooser Drop-in, but it does not provide access to any file within a Dropbox account. By selecting a file to sideload, the user grants <strong>Dropbox Sideload</strong> permission to download that file similarly as if the file were shared using Dropbox sharing features. As an added security measure, <strong>Dropbox Sideload</strong> will sign out the user of from their Dropbox account by default, unless the <code>Keep me logged-in</code> option is selected. </p></div>
		<p>To use <strong>Dropbox Sideload</strong> you must first create a Dropbox Drop-in app. Go to the <a href="https://www.dropbox.com/developers/apps/create?app_type_checked=dropins" title="Create Dropbox Drop-in" target="_blank">Dropbox Developers site</a> to create the app. Supply a name that meets the guidelines of Dropbox. Once completed, enter your API key in the box below.</p>
		
		<?php } ?>
		<form method="post" action="<?php echo $url ?>">
		<table class="form-table <?php echo $class; ?>" id="dropbox-sideload-steps">
			<tbody>
				<!-- Step 1 --> 
				<tr id="table-step1">
					<th scope="row">
						Dropbox API key
					</th>
					<td>
						<input type="text" name="dropbox-api" id="dropbox-api" value="<?php echo $dropbox_api; ?>" <?php echo $step1 ? 'readonly' : ''; ?>/>
						<?php if ($step1) { 
							submit_button( __( 'Delete Key', 'dropbox-sideload' ), 'delete', 'dropbox-delete-api', false, !$step1 ? array('disabled'=>'1'): '' ); 
						} else {
							submit_button( __( 'Next', 'dropbox-sideload' ), 'primary', 'dropbox-continue', false, array() ); 
						} ?>
							<br/>
							<span class="description"><?php _e( 'Choose a file from the Dropbox window and press Sideload', ' dropbox-sideload' ); ?> </span>
					</td>
				</tr>
				<!-- Step 2 -->
				<tr id="table-step2a">
					<th scope="row">
						Stay logged into Dropbox?
					</th>
					<td>
						<label for="dropbox-stay-logged-in">
							<input type="checkbox" name="dropbox-stay-logged-in" id="dropbox-stay-logged-in" <?php echo $dropbox_staylog ? 'checked' : '' ; ?> > Keep me logged-in after choosing file
						</label>
					</td>
				</tr>
				<tr id="table-step2b">
					<th scope="row">
						Choose sideload file
					</th>
					<td>
						<input type="text" name="dropbox-file" id="dropbox-file" value="<?php echo $dropbox_file; ?>" readonly /> 
						<a href="#" id="dropbox-choose" class="button <?php echo ! $step1 ? 'disabled' : ''; ?>">Choose from Dropbox</a>
						<?php submit_button( __( 'Sideload', 'dropbox-sideload' ), 'primary', 'dropbox-sideload-button', false, array('disabled' => 'disabled') ); ?>
						<br/>
						<span class="description"><?php _e( 'Enter your Dropbox API Key and press Next', ' dropbox-sideload' ); ?> </span>
					</td>
				</tr>
				<!-- Step 3? -->
				<tr id="table-step3">
					<th scope="row">
						Sideload stauts
					</th>
					<td>
						<?php echo $status; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php /*
		<ol id="dropbox-sideload-steps">
			<li id="step1" class="<?php echo $step1?'done':'required';?>">
				<strong>Enter your Dropbox Drop-ins API key.</strong></br>
				
			</li>
			<li id="step2" class="<?php echo $step1?'required':'disabled';?>">
				<strong>Choose from Dropbox</strong></br>
				<label for="dropbox-stay-logged-in">
					<input type="checkbox" name="dropbox-stay-logged-in" id="dropbox-stay-logged-in" <?php echo $dropbox_staylog ? 'checked' : '' ; ?> > Keep me logged-in after choosing file
				</label><br/>
				
			</li>
			<li id="step3" class="<?php echo $step3?'done':'disabled'?>">
				Status: <?php echo $status; ?>
			</li>
		</ol>
		*/?>
		<br class="clear" />
		<?php //submit_button( $button, 'primary', 'dropbox-sideload-button', false, array( 'disabled' => $step1 ) ); ?>
		</form>
	</div>
<?php
}

// Add scripts & styles
function dropbox_sideload_add_styles() {	
	if ( 'media_upload_server' == current_filter() )
			wp_enqueue_style('media');
			
	wp_enqueue_script( 'dropboxjs' );
	wp_enqueue_script( 'dropbox-sideload' );
	wp_enqueue_style( 'dropbox-sideload' );
}


// Handle the sideloading. Returns attachment url on success, FALSE on failure
function dropbox_sideload_handle_sideload($url){
	// Download file to temporary location
	$tmp = download_url( $url );
	$file_array = array(
		'name' => basename( $url ),
		'tmp_name' => $tmp
	);
	
	// Check for download errors
	if ( is_wp_error( $tmp ) ) {
		error_log( $tmp -> get_error_message() );
		@unlink( $file_array[ 'tmp_name' ] );
		return false;
	}
	
	// Do the sideloading into WP
	$id = media_handle_sideload( $file_array, 0);
	
	// Check for handle sideload errors.
	if ( is_wp_error( $id ) ) {
		error_log( $id -> get_error_message() );
		@unlink( $file_array['tmp_name'] );
		return false;
	}
	
	// Success! So get the attachment url as a sign of success
	$attachment_url = wp_get_attachment_url( $id );
		
	return $attachment_url;
}

// Dropbox Chooser requires the API key to be added to the script call
add_filter('clean_url','unclean_url',10,3);
function unclean_url( $good_protocol_url, $original_url, $_context){
	
	$dropbox_api = get_option( 'dropbox-api' , '' );
	if ( empty( $dropbox_api ) ) return $original_url;
	
	if (false !== strpos( $original_url, 'dropbox.com' ) ) {
		remove_filter( 'clean_url' , 'unclean_url' , 10, 3 );
		$url_parts = parse_url( $good_protocol_url );
		return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . "' id='dropboxjs' data-app-key='".$dropbox_api;
	}
	return $good_protocol_url;
}

?>
