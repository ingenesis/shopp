<?php
/**
 * Asset API
 *
 * Developer api calls for adding file assets programmattically to your site.
 *
 * @copyright Ingenesis Limited, June 23, 2011
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Asset
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * shopp_add_image
 *
 * Add an image by filename, and associate with a user defined context.  Requires full path to the file on your filesystem, or a correct relative path for the scripts current working directory.
 *
 * @api
 * @since 1.2
 *
 * @param int $id Object id to attach the image asset to.
 * @param string $context the object type the image asset will be attached to.  This can be product or category
 * @param string $file Full or correct relative path to the image file.
 * @return mixed false on failure, int image asset id on success.
 **/
function shopp_add_image ( $id, $context, $file ) {
	if ( empty($id) || empty($context) || empty($file) ) {
		shopp_debug(__FUNCTION__ . " failed: One or more missing parameters.");
		return false;
	}

	if ( ! is_file($file) || ! is_readable($file) ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: File missing or unreadable.");
	}

	if ( 'product' == $context ) {
		$Object = new ShoppProduct($id);
		$Image = new ProductImage();
	} else if ( 'category' == $context ) {
		$Object = new ProductCategory($id);
		$Image = new CategoryImage();
	} else {
		shopp_debug(__FUNCTION__ . " failed for file $file: Invalid context $context.");
		return false;
	}

	if ( empty($Object->id) ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: Unable to find a $context with id $id.");
		return false;
	}

	$Image->parent = $id;
	$Image->type = "image";
	$Image->name = "original";
	$Image->filename = basename($file);
	list($Image->width, $Image->height, $Image->mime, $Image->attr) = getimagesize($file);
	$Image->mime = image_type_to_mime_type($Image->mime);
	$Image->size = filesize($file);

	if ( ! $Image->unique() ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: Too many images exist with this name.");
		return false;
	}

	$Image->store( $file, 'file' );
	$Image->save();

	if ( $Image->id ) return $Image->id;

	shopp_debug(__FUNCTION__ . " failed for file $file.");
	return false;
}

/**
 * shopp_rmv_image
 *
 * remove an image record from the database
 *
 * @api
 * @since 1.2
 *
 * @param int $image the image id
 * @param string $context the object type the image asset is attached to.  This can be product or category
 * @return mixed false on failure, int image asset id on success.
 **/
function shopp_rmv_image ( $image, $context ) {
	if ( empty($image) || empty($context) ) {
		shopp_debug(__FUNCTION__ . " failed: Missing parameters");
		return false;
	}

	if ( 'product' == $context ) {
		$Image = new ProductImage($image);
	} else if ( 'category' == $context ) {
		$Image = new CategoryImage($image);
	} else {
		shopp_debug(__FUNCTION__ . " failed: Invalid context $context.");
		return false;
	}

	if ( empty($Image->id) ) {
		shopp_debug(__FUNCTION__ . " failed: No such $context image with id $image");
		return false;
	}
	return $Image->delete();

}

/**
 * shopp_add_product_image
 *
 * Add a product image by filename.  Requires full path to the file on your filesystem, or a correct relative path for the scripts current working directory.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $product Product id to attach the image asset to.
 * @param string $file Full or correct relative path to the image file.
 * @return mixed false on failure, int image asset id on success.
 **/
function shopp_add_product_image ( $product, $file ) {
	return shopp_add_image($product, 'product', $file);
}

/**
 * shopp_rmv_product_image
 *
 * Remove a product image by image asset id.
 *
 * @api
 * @since 1.2
 *
 * @param int $image image id to remove from product.
 * @return bool false on failure, true on success.
 **/
function shopp_rmv_product_image ( $image ) {
	return shopp_rmv_image($image, 'product');
}

/**
 * shopp_rmv_category_image
 *
 * Remove a category image by image asset id.
 *
 * @api
 * @since 1.2
 *
 * @param int $image image id to remove from product category.
 * @return bool false on failure, true on success.
 **/
function shopp_rmv_category_image ( $image ) {
	return shopp_rmv_image($image, 'category');
}

/**
 * shopp_add_category_image
 *
 * Add a category image by filename.  Requires full path to the file on your filesystem, or a correct relative path for the scripts current working directory.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $category Category id to attach the image asset to.
 * @param string $file Full or correct relative path to the image file.
 * @return mixed false on failure, int image asset id on success.
 **/
function shopp_add_category_image ( $category, $file ) {
	return shopp_add_image($category, 'category', $file);
}

/**
 * shopp_add_product_download
 *
 * Add product download file to a product/variation.
 *
 * @api
 * @since 1.2
 *
 * @param int $product id of the product the download asset will be added to
 * @param string $file full or correct relative path to the download file asset.
 * @param int $variant id of the variant the download asset will be attached to.  For products with variants, this is a required parameter.
 * @return mixed false of failure, the new download asset id on success
 **/
function shopp_add_product_download ( $product, $file, $variant = false ) {
	if ( empty($product) || empty($file) ) {
		shopp_debug(__FUNCTION__.' failed: One or more missing parameters.');
		return false;
	}

	$File = new ProductDownload();
	$instore = $File->found($file);
	if( ! $instore && ( ! is_file($file) || ! is_readable($file) ) ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: File missing or unreadable.");
		return false;
	}

	$Product = new ShoppProduct($product);
	if ( empty($Product->id) ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: No such product with id $product.");
		return false;
	}
	$Product->load_data(array('summary', 'prices'));
	if ( "on" == $Product->variants && false === $variant ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: You must specify the variant id parameter for product $product.");
		return false;
	}

	$Price = reset($Product->prices);
	if ( empty($Price->id) ) {
		shopp_debug(__FUNCTION__ . " failed for file $file: Failed to load product variants.");
		return false;
	}

	if ( $variant ) {
		$Price = false;
		foreach ( $Product->prices as $Price ) {
			if ( $variant == $Price->id ) break;
		}
		if ( false === $Price ) {
			shopp_debug(__FUNCTION__ . " failed for file $file: You must specify a valid variant id parameter for product $product.");
			return false;
		}
	}

	// Save the uploaded file
	$File->load(array('type'=>'download', 'parent'=> $Price->id));
	$File->parent = $Price->id;
	$File->context = "price";
	$File->type = "download";
	$File->name = basename($file);
	$File->filename = $File->name;
	if ( ! $instore ) {
		$File->mime = file_mimetype($file,$File->name);
		$File->size = filesize($file);
		$File->store($file,'file');
	} else {
		$File->uri = $file;
		$File->readmeta();
	}
	$File->save();

	if ( $File->id ) return $File->id;

	shopp_debug(__FUNCTION__ . " failed for file $file");
	return false;
}

/**
 * shopp_rmv_product_download
 *
 * Remove a product download asset
 *
 * @api
 * @since 1.2
 *
 * @param int $download the product asset id
 * @return bool true on success, false on failure
 **/
function shopp_rmv_product_download ( $download ) {
	if ( empty($download) ) {
		shopp_debug(__FUNCTION__.' failed: download parameter required.');
		return false;
	}

	$File = new ProductDownload($download);
	if ( empty($File->id) ) {
		shopp_debug(__FUNCTION__ . " failed: No such product download with id $download.");
		return false;
	}

	return $File->delete();
}