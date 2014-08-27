// Javascript
jQuery(document).ready(function (){ 
	var db_options = {
			// Required. Called when a user selects an item in the Chooser.
			success: function(files) {
				// each element in the files array is a file object, defined as
				/* file = {
					// Name of the file.
					name: "filename.txt",
					// URL to access the file, which varies depending on the linkType specified when the
					// Chooser was triggered.
					link: "https://...",
					// Size of the file in bytes.
					bytes: 464,
					// URL to a 64x64px icon for the file based on the file's extension.
					icon: "https://...",
					// A thumbnail URL generated when the user selects images and videos.
					// If the user didn't select an image or video, no thumbnail will be included.
					thumbnailLink: "https://...?bounding_box=75&mode=fit",
				} */
					jQuery('#dropbox-file').val(files[0].link);
					jQuery('#step2').attr('class','done');
					jQuery('#step3').attr('class','done');
					jQuery('#sideload').attr('disabled','false');
					jQuery('#sideload').prop('value','Sideload');
					//alert("Here's the file link: " + files[0].link)
			},

			// Optional. Called when the user closes the dialog without selecting a file
			// and does not include any parameters.
			cancel: function() {},

			// Optional. "preview" (default) is a preview link to the document for sharing,
			// "direct" is an expiring link to download the contents of the file. For more
			// information about link types, see Link types below.
			linkType: "direct",

			// Optional. This is a list of file extensions. If specified, the user will
			// only be able to select files with these extensions. You may also specify
			// file types, such as "video" or "images" in the list. For more information,
			// see File types below. By default, all extensions are allowed.
		}
	
	jQuery('#dropbox-choose').click(function(){
		Dropbox.choose(db_options);
		return false;
	});
	
	//document.getElementById("dropbox-sideload-form").appendChild(button);
});