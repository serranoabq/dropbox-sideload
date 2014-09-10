<?php
/*
    Plugin Name: Dropbox Sideloader
    Description: Plugin to enable sideloading media from Dropbox. 
    Version: 0.9
    Author: Justin R. Serrano
*/

require_once( sprintf( "%s/dropbox-sideload-class.php", dirname(__FILE__) ) );

if( class_exists( 'DropboxSideload' ) ) {
	new DropboxSideload();
}

?>
