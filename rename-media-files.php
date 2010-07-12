<?php
/*
Plugin Name: Rename Media Files
Plugin URI: http://blog.milandinic.com/wordpress/plugins/rename-media-files/
Description: Rename names of media files.
Author: Milan Dinić
Version: 0.1
Author URI: http://blog.milandinic.com/
*/


/**
 * Uses code from:
 * Enable Media Replace
 * Media Tags
 * WP Smush.it
 * AJAX Thumbnail Rebuild
 */
 
/**
 * Yes, we're localizing the plugin.  This partly makes sure non-English
 * users can use it too.  To translate into your language use the
 * rename-media-files.pot file in /languages folder.  Poedit is a good tool to for translating.
 * @link http://poedit.net
 *
 * @since 0.5
 */
function rename_media_files_init() {
	load_plugin_textdomain( 'rename-media-files', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'rename_media_files_init' );
 
/**
 * Add filters for adding field on media edit screen
 * and function that processes field value
 */

add_filter('attachment_fields_to_edit', 'rename_media_files_fields_to_edit', 11, 2);
add_filter('attachment_fields_to_save', 'rename_media_files_attachment_fields_to_save', 11, 2);

/**
 * Function that adds field on media edit screen
 */
function rename_media_files_fields_to_edit($form_fields, $post) {
	$orig_file = get_attached_file( $post->ID );
	$orig_filename = basename($orig_file);
	$post_rename_media_files_fields = "<br />".sprintf(__('Enter a new file name in the field above. (current filename is <strong>%1$s</strong>)', 'rename-media-files'), $orig_filename);
	
    $form_fields['rename_media_files'] = array(
       	'label' => __('New file name:', 'rename-media-files'),
   		'input' => 'html',
   		'html' => "<input type='text' name='attachments[$post->ID][rename_media_files_input]' 
			id='attachments[$post->ID][rename_media_files_input]'
       		size='50' value='' />
		$post_rename_media_files_fields "
	);
	
    return $form_fields;
}

/**
 * Function that processes value from field on media edit screen
 */
function rename_media_files_attachment_fields_to_save($post, $attachment) {

	global $wpdb;

	if ($attachment['rename_media_files_input']) {
		// get original filename
		$orig_file = get_attached_file( $post['ID'] );
		$orig_filename = basename($orig_file);
		// get original path
		$orig_dir_path = substr($orig_file, 0, (strrpos($orig_file, "/")));
		
		if (wp_attachment_is_image( $post['ID'] )) {
			// get URLs to original pictures
			$orig_image_thumbnail_url = wp_get_attachment_image_src( $post['ID'], 'thumbnail' );
			$orig_image_medium_url = wp_get_attachment_image_src( $post['ID'], 'medium' );
			$orig_image_large_url = wp_get_attachment_image_src( $post['ID'], 'large' );
			$orig_image_full_url = wp_get_attachment_image_src( $post['ID'], 'full' );
			} else {
				// get URL to original file that is not image
				$orig_attachment_url = wp_get_attachment_url( $post['ID'] );
		}
		
		// make new filename and path
		$new_filename= wp_unique_filename( $orig_dir_path, $attachment['rename_media_files_input'] );
		$new_file = $orig_dir_path . "/" . $new_filename;
		
		// make new file with desired name
		copy( $orig_file, $new_file );
		
		// update file location in database
		update_attached_file( $post['ID'], $new_file );

		// update guid for attachment
		$post_for_guid = get_post( $post['ID'] );
		$guid = str_replace($orig_filename, $new_filename, $post_for_guid->guid);

		wp_update_post( array('ID' => $post['ID'],
							  'guid' => $guid) );
							
		// update attachment's metadata
		wp_update_attachment_metadata( $post['ID'], wp_generate_attachment_metadata( $post['ID'], $new_file) );
		
		// get URLs to new pictures and update posts with old URLs
		if (wp_attachment_is_image( $post['ID'] )) {
			$new_image_thumbnail_url = wp_get_attachment_image_src( $post['ID'], 'thumbnail' );
			$new_image_medium_url = wp_get_attachment_image_src( $post['ID'], 'medium' );
			$new_image_large_url = wp_get_attachment_image_src( $post['ID'], 'large' );
			$new_image_full_url = wp_get_attachment_image_src( $post['ID'], 'full' );
			
				
			$wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_image_thumbnail_url', '$new_image_thumbnail_url');");
			$wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_image_medium_url', '$new_image_medium_url');");
			$wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_image_large_url', '$new_image_large_url');");
			$wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_image_full_url', '$new_image_full_url');");
			} else {
				// get URL to original file that is not image and update posts with old URL
				$new_attachment_url = wp_get_attachment_url( $post['ID'] );
				
				$wpdb->query("UPDATE $wpdb->posts SET post_content = REPLACE(post_content, '$orig_attachment_url', '$new_attachment_url');");
		}
	
	}

    return $post;
}

