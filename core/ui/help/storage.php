<?php
get_current_screen()->add_help_tab( array(
	'id'      => 'storage',
	'title'   => Shopp::__('Storage Engines'),
	'content' => Shopp::_mx(

'### Storage Engines

Shopp includes two built-in storage engines: DB Storage and File System. Each have benefits and drawbacks that should be considered when determining which to use.

### Database Storage

> If you plan to upload files larger than 2 MB, it would be better to use the File System storage engine.

By default, Shopp is set up to store both storefront images and product downloads in the database using the DB Storage engine. Database storage has no set up steps to get it working making it the easiest storage engine to use. It also has some major drawbacks that may not make it the best choice in some cases. Storing assets in the database limits the maximum file size that can be stored to 2 MB files.

### File System Storage

The file system storage enables storage and retrieval in any directory on the host server’s file system. File system storage avoids the file size limits of database storage and can support very large files. Overall it also provides faster downloads for customers. It also provides support for resumable downloads so that should the download be interrupted or paused (by browsers that support it), the customer can resume downloading where they left off, rather than needing to restart the download from the beginning. The biggest drawback of file system storage is that it has a more difficult set up process.

With file system storage you can maintain all of your storefront images and/or product downloads on the file system in a directory any where on the web server file system. Using another upload system (one other than Shopp’s file uploading), such as an FTP client, allows you to use extremely large files (up to about 2 GB) as a product download file.

#### To set up file system storage for either storefront images or product downloads:

* Create a new **directory** on the file system. This can be done using command-line tools, and FTP client or a file manager that your web host provides.
* Ensure the web server has access to read and write information to that directory. File permissions are a very technical subject, if you need to know more about it, search online for information about **file permissions**.
* Login to your WordPress admin and using the menus navigate to **Shopp** &rarr; **Setup** &rarr; **System**.
* For either the Product Images or Downloads setting choose the **File System** storage engine.
* Enter the full path (absolute path) to the file storage directory. You can also use a partial path (also known as a relative path) starting from the `wp-content` subdirectory in your WordPress installation. For instance, if you have **products** and **images** subdirectories located in your WordPress installation, under the path `/website/wp-content/`, you can enter the partial paths **products** and **images**.
',

'Storage help tab')
) );

get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Storage help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . '/downloads-images',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));
