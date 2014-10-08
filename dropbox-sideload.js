/*
    Plugin Name: Dropbox Sideloader
    Description: Plugin to enable sideloading media from Dropbox. 
    Version: 0.8
    Author: Justin R. Serrano
*/
// Javascript
jQuery(document).ready(function (){ 
	var db_options = {
			success: function(files) {
				// each element in the files array is a file object, defined as
				/* file = {
					name: "filename.txt", // Name of the file.
					link: "https://...", 	// URL to the file
					bytes: 464,						// Size of the file in bytes.
					icon: "https://...", // URL to an icon for the file
					thumbnailLink: "https://...?bounding_box=75&mode=fit", // URL for thumbnail with images and videos
				} */
					// Add file to text box
					jQuery('#dropbox-file').val(files[0].link);
					jQuery('#dropbox-sideload-button').removeAttr('disabled');
					
					// Populate the file info box
					img = files[0].icon;
					if ( files[0].thumbnailLink ) {
						img = files[0].thumbnailLink;
					}
					jQuery('#dropbox-thumb').html( '<img src="' + img + '" />');
					jQuery('#dropbox-fileinfo').html( 
						'<strong>File: </strong>' + files[0].name + '</br>' +
						'<strong>Size: </strong>' + Math.floor( files[0].bytes/1024 ) + ' kB'
					);
					jQuery('#dropbox-status').html( '<strong>Status:</strong> Ready to sideload' );
						
					// This is a little 'hackish', but it works at logging out the user
					if( jQuery('#dropbox-stay-logged-in').prop('value') == '0' )
						jQuery.getScript('http://www.dropbox.com/logout');
			},

			cancel: function() {},
			linkType: "direct",

		}
	
	jQuery('#dropbox-choose').click(function(){
		Dropbox.choose(db_options);
		return false;
	});
	
	
});