Dropbox Sideload (Plugin)
=========================

WordPress plugin to sideload files directly from Dropbox.

Description
-----------

Dropbox Sideload allows the backend user to select a file and sideload it directly from Dropbox. This allows a file that resides in Dropbox to be addedd to the Media Library without having first to be downloaded locally and then uploaded to the WordPress site.

Installation
------------

Please see [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) in the WordPress Codex for general installation instructions.

The plugin uses the Dropbox Chooser API to select the file from a Dropbox folder. This API requires the user to have an API key. Go to the (Dropbox Developers)[https://www.dropbox.com/developers/apps/create?app_type_checked=dropins] site to create a Drop-in app and receive an API key (requires a Dropbox account). Only one API key is needed generated by one account. However, once the API key is set, any Dropbox account can be used to sideload files.

Setting
-------

1. Go to `Settings > Dropbox Sideload Options` and enter your Dropbox API key. 
2. If you desire to remiain logged in after choosing a file, check the corresponding box. If unchecked, the Dropbox acount used will be logged out after selecting the file.

Usage
-----
1. To sideload a file, go to `Media > Dropbox Sideload` from the Admin menu or in the post editing screen click the `Add Media` button and go to the `Dropbox Sideload` tab.
2. Click the `Choose from Dropbox` button. A Dropbox file selection window will pop up. If you are not logged into Dropbox, enter your credentials. Select the file and press `Choose`. 
3. Press the `Sideload button. It might take some time to sideload the file. During this time no progress is shown. 

Once the sideloading is complete, the file is in the Media Library. If using the `Add Media` dialog, click on the `Insert Media` tab and select the file. 

Errors
------

Sideloading depends heavily on the server capabilities. Some server configurations will not be able to sideload the requested file. This is not an issue of the plugin, but of the server environment. When an error occurs during sideloading, the user is notified. The error will also be logged to the error log, if enabled in the WordPress configuration. Some common types of errors include:
* cURL is not properly installed or configured 
* incorrect or insufficient PHP upload settings (i.e., file too big)
* script timeout during upload (i.e., file too big so it takes too long)
* invalid or incorrect SSL certificates for cURL access (Dropbox file URL is an https URL)

Notes
-------

1. The API key allows Dropbox Sideload to use the Dropbox Chooser Drop-in to select the file. This retreives the dowload URL of the selected file in the same way one can share the file directly from Dropbox. The API key DOES NOT provide access to any file within a Dropbox account other than the one selected by the user. 
2. If a user is logged into the Dropbox site on the browser being used to access the WordPress site, that account will automatically be used. Depending on the browsing environment, this can be an undesired behavior. For this reason, Dropbox Sideload will log out the current Dropbox user after the selection is made. This way, another user on the same browser cannot access the files. If you would rather remain logged in, please change the appropriate setting.

Changelog
---------

* 0.1 - Initial version
* 0.2 - Get filename from Dropbox. 
* 0.4 - Name change
* 0.75 - New UI
* 0.8 - Clean up UI. Add settings page.
