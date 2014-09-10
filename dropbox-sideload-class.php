<?php

if ( ! class_exists( 'DropboxSideload' ) ) {
	class DropboxSideload {
		var $_ds_error;
		
		public function __construct() {
			// NEEDS: admin_init()
			// NEEDS: admin_menu()
			// NEEDS: unclean_url()
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_filter( 'clean_url', array( &$this, 'unclean_url' ) , 10, 3 );
		}
		
		// Initialize
		public function admin_init(){
			// NEEDS: add_styles()
			// NEEDS: sideload_menu()
			// NEEDS: tab_handler()
			
			//Register JS & CSS
			wp_register_script ( 'dropboxjs', 
				'https://www.dropbox.com/static/api/2/dropins.js' );
			wp_register_script( 'dropbox-sideload', 
				plugins_url( 'dropbox-sideload.js', __FILE__ ), array( 'dropboxjs' ) );
			wp_register_style( 'dropbox-sideload', 
				plugins_url('dropbox-sideload.css', __FILE__ ) );
				
			// Add Dropbox Sideload to Media Upload pages
			add_action( 'load-media_page_dropbox-sideload', array( &$this, 'add_styles' ) );
			add_action( 'media_upload_dropbox', array( &$this, 'add_styles' ) );

			// Add a Dropbox Sideload tab to the media uploader
			add_filter( 'media_upload_tabs', array( &$this, 'sideload_menu' ) );
			
			// Add Dropbox Sideload tab handler
			add_action( 'media_upload_dropbox',  array( &$this, 'tab_handler' ) );
			
			// Add Dropbox API setting
			register_setting( 'dropbox-sideload', 'dropbox-api' );
			register_setting( 'dropbox-sideload', 'dropbox-stay-logged-in' );
		}
		
		// Add Dropbox Sideload to the admin menus
		public function admin_menu() {
			// NEEDS: menu_page()
			// NEEDS: options_page()
			
			if ( ! function_exists('submit_button') ) return;
			
			if ( current_user_can('upload_files') )
				add_media_page( 
					__('Dropbox Sideload', 'dropbox-sideload'),	// Page title
					__('Dropbox Sideload', 'dropbox-sideload'),	// Menu title
					'read', 																		// Capability
					'dropbox-sideload', 												// Menu slug
					array( &$this, 'menu_page' ) 								// Function callback
				);
			
			if ( current_user_can('manage_options') )
				add_options_page( 
					__('Dropbox Sideload Options', 'dropbox-sideload'),	// Page title
					__('Dropbox Sideload Options', 'dropbox-sideload'),	// Menu title
					'read', 																						// Capability
					'dropbox-sideload', 																// Menu slug
					array( &$this, 'options_page' )	 										// Function callback
				);
		}

		// Assign a tab to the media uploader
		public function sideload_menu( $tabs ) {
			$tabs['dropbox'] = 'Dropbox Sideload';
			return $tabs;
		}
		
		// Handle the menu page
		public function menu_page(){
			// NEEDS: main_content()

			if( ! current_user_can( 'upload_files' ) ) return;
			
			echo '<div class="wrap"><h2>' . __('Dropbox Sideload', 'dropbox-sideload') . '</h2>';

			//Do the content
			$this->main_content();
				
			echo '</div>';
		}

		// Handle the options page
		public function options_page(){
			// NEEDS: setting_content()

			if( ! current_user_can( 'manage_options' ) ) return;
			
			echo '<div class="wrap"><h2>' . __('Dropbox Sideload Settings', 'dropbox-sideload') . '</h2>';

			// Do the content
			$this -> setting_content();
				
			echo '</div>';
		}

		// Add scripts & styles
		public function add_styles() {	
			if ( 'media_upload_dropbox-sideload' == current_filter() )
					wp_enqueue_style('media');
					
			wp_enqueue_script( 'dropboxjs' );
			wp_enqueue_script( 'dropbox-sideload' );
			wp_enqueue_style( 'dropbox-sideload' );
		}
		
		// Handle Dropbox Sideload tab
		public function tab_handler() {
			// NEEDS: main_content()
			
			// Set the body ID
			$GLOBALS['body_id'] = 'media-upload';
			
			// Do an IFrame header
			iframe_header( __('Dropbox Sideload', 'dropbox-sideload') );
			
			// Add the Media buttons	
			media_upload_header();

			// Do the content
			$this->main_content();

			// Do a footer
			iframe_footer();
		}

		// Create Dropbox Sideload interface
		public function main_content(){
			// NEEDS: handle_sideload()

			global $pagenow;
			
			// Handle request parameters
			$dropbox_file 			= isset( $_REQUEST['dropbox-file'] ) ? $_REQUEST['dropbox-file'] : '';
			
			// Get options
			$dropbox_api = get_option( 'dropbox-api', '' );
			$dropbox_staylog = get_option( 'dropbox-stay-logged-in', false );
			
			// Prep defaults
			$status = '';
			$class = '';
			$_ds_error = '';
			$url = admin_url('media-upload.php?tab=dropbox' );
			$library_url = admin_url( 'media-upload.php?tab=library' );
			
			if ( 'upload.php' == $pagenow ){
				$url = admin_url( 'upload.php?page=dropbox-sideload' );
				$library_url = admin_url( 'upload.php' );
			}
			
			// Step 1 is completed when the API setting is entered
			$step1  = ! empty($dropbox_api);
			
			if( $step1 && ! empty( $dropbox_file ) ){
				// Step 1 & 2 completed, so handle sideload
				$attachment_url = $this->handle_sideload( $dropbox_file );
				
				// If successful, point the user to the Media Library
				if ($attachment_url){
					$status = sprintf( __('The file was susccesfully sideloaded, go to the <a href="%s">Media Library</a> to add it to your post.', 'dropbox-sideload'), $library_url ) ;
					$dropbox_file = ''; // clear the form
				} else { 
					$status = __('Error sideloading the file from Dropbox. <br/>', 'dropbox-sideload') . $this->get_last_error();
					$class .= ' error';
				}
			}
			?>
			
			<div class="dropbox_sideload_wrap">
				<?php if ( ! $step1 ) { ?>
				<div class="error"><p>
				<?php echo sprintf( __('No Dropbox API key specified. Please add one in the <a href="%s">Settings Page</a>','dropbox-sideload'), admin_url( 'options-general.php?page=dropbox-sideload' ) );?>
				</p></div>
				<?php } ?>
				<form method="post" action="<?php echo $url ?>">
					<input type="hidden" name="dropbox-api" id="dropbox-api" value="<?php echo $dropbox_api; ?>"/>
					<input type="hidden" name="dropbox-stay-logged-in" id="dropbox-stay-logged-in" value="<?php echo $dropbox_staylog; ?>"/> 
					
					<table class="form-table <?php echo $class; ?>" id="dropbox-sideload-steps">
					<tbody>
						<tr>
							<th scope="row">
								<?php _e( 'Choose sideload file', 'dropbox-sideload' ); ?>
							</th>
							<td>
								<input type="text" name="dropbox-file" id="dropbox-file" class="regular-text" value="<?php echo $dropbox_file; ?>" readonly /> 
								<a href="#" id="dropbox-choose" class="button <?php echo ! $step1 ? 'disabled' : ''; ?>">
									<?php _e( 'Choose from Dropbox', 'dropbox-sideload' ); ?>
								</a>
								<br/>
								<span class="description"><?php _e( 'Choose a file from Dropbox and press Sideload', ' dropbox-sideload' ); ?> </span>
								<br/>
								
							</td>
						</tr>
						<tr class="<?php echo $class; ?>">
							<th scope="row">
								<?php _e( 'Sideload stauts', 'dropbox-sideload' ); ?>
							</th>
							<td>
								<div id="dropbox-file-description">
									<div id="dropbox-filename"></div>
									<div id="dropbox-thumb"></div>
								</div>
								<div id="dropbox-status"><?php echo $status; ?></div>
							</td>
						</tr>
					</tbody>
				</table>
				<br class="clear"/>
				<?php submit_button( __( 'Sideload', 'dropbox-sideload' ), 'primary', 'dropbox-sideload-button', false, array('disabled' => 'disabled') ); ?>
				</form>
			</div>
		<?php
		}
		
		// Create Dropbox Sideload Settings interface
		public function setting_content() {
			global $pagenow;
			
			// Handle request parameters
			$dropbox_api 				= isset( $_REQUEST['dropbox-api'] ) ? $_REQUEST['dropbox-api'] : '';
			$dropbox_staylog 		= isset( $_REQUEST['dropbox-stay-logged-in'] );
			$dropbox_delete_api = isset( $_REQUEST['dropbox-delete-api'] ) ;
			
			// Get options
			$dropbox_api_option = get_option( 'dropbox-api', '' );
			$dropbox_staylog = $dropbox_staylog || get_option( 'dropbox-stay-logged-in', false );
			update_option( 'dropbox-stay-logged-in', $dropbox_staylog );
			
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
			
			$step1 = ! empty($dropbox_api);
			
			if ( 'options-general.php' == $pagenow )
				$url = admin_url( 'options-general.php?page=dropbox-sideload' );
				
			?>
			
			<div class="dropbox_sideload_wrap">
				<p><?php _e( '<strong>NOTE: </strong> The API key allows Dropbox Sideload to use the Dropbox Chooser Drop-in to choose the file. It does not provide access to any file within a Dropbox account other than the one selected by the user.', ' dropbox-sideload' ); ?></p>
				
				<form method="post" action="<?php echo $url ?>">
				<table class="form-table" id="dropbox-sideload-steps">
					<tbody>
						<tr>
							<th scope="row">
								<?php _e( 'Dropbox API key', 'dropbox-sideload' ); ?>
							</th>
							<td>
								<input type="text" name="dropbox-api" id="dropbox-api" class="regular-text" value="<?php echo $dropbox_api; ?>" <?php echo $step1 ? 'readonly' : ''; ?>/>
								<?php if ($step1) { 
									submit_button( __( 'Delete Key', 'dropbox-sideload' ), 'delete', 'dropbox-delete-api', false, ! $step1 ? array('disabled' => '1'): '' ); 
								} ?>
									<br/>
									<span class="description"><?php _e( 'Enter your Dropbox API key. Go to the <a href="https://www.dropbox.com/developers/apps/create?app_type_checked=dropins" title="Dropbox Developers site" target="_blank">Dropbox Developers site</a> to create a Drop-in app and receive an API key.', ' dropbox-sideload' ); ?> </span>
							</td>
						</tr>
						<tr id="status">
							<th scope="row">
								<?php _e( 'Stay logged into Dropbox?', 'dropbox-sideload' ); ?>
							</th>
							<td>
								<label for="dropbox-stay-logged-in">
									<input type="checkbox" name="dropbox-stay-logged-in" id="dropbox-stay-logged-in" <?php echo $dropbox_staylog ? 'checked' : '' ; ?> > <?php _e( 'Keep me logged-in after choosing file', 'dropbox-sideload' ); ?>
								</label>
								<br/>
									<span class="description"><?php _e( 'By default the user is logged out of their account after the selection.', ' dropbox-sideload' ); ?> </span>
							</td>
						</tr>
					</tbody>
				</table>
				<br class="clear"/>
				<?php submit_button( __( 'Save Settings', 'dropbox-sideload' ), 'primary', 'dropbox-save-button', false, array() ); ?>
				</form>
			</div>
		<?php
		}
		
		// Handle the sideloading. Returns attachment url on success, FALSE on failure
		public function handle_sideload( $url ){
			// Download file to temporary location
			$tmp = download_url( $url );
			$file_array = array(
				'name' => basename( $url ),
				'tmp_name' => $tmp
			);
			
			// Check for download errors
			if ( is_wp_error( $tmp ) ) {
				$this->_ds_error = "DROPBOX_SIDELOAD ERROR: " . $tmp -> get_error_message();
				error_log( $_ds_error );
				@unlink( $file_array[ 'tmp_name' ] );
				return false;
			}
			
			// Do the sideloading into WP
			$id = media_handle_sideload( $file_array, 0);
			
			// Check for handle sideload errors.
			if ( is_wp_error( $id ) ) {
				$this->_ds_error = "DROPBOX_SIDELOAD ERROR: " . $id -> get_error_message();
				error_log( $_ds_error );
				@unlink( $file_array['tmp_name'] );
				return false;
			}
			
			// Success! So get the attachment url as a sign of success
			$attachment_url = wp_get_attachment_url( $id );
				
			return $attachment_url;
		}

		// Dropbox Chooser requires the API key to be added to the script call, this appends it
		public function unclean_url( $good_protocol_url, $original_url, $_context ){
			
			$dropbox_api = get_option( 'dropbox-api' , '' );
			if ( empty( $dropbox_api ) ) return $original_url;
			
			if (false !== strpos( $original_url, 'dropbox.com' ) ) {
				remove_filter( 'clean_url' , 'unclean_url' , 10, 3 );
				$url_parts = parse_url( $good_protocol_url );
				return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . "' id='dropboxjs' data-app-key='".$dropbox_api;
			}
			return $good_protocol_url;
		}
		
		public function get_last_error(){
			return $this->_ds_error;
		}
		
	}
}


?>