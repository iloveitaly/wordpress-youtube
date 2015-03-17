<?php

/*
Plugin Name: YouTube Embed & Image Extraction
Description: YouTube embed shortcodes and thumbnail extraction
Version: 1.0
License: MIT

Author: Michael Bianco
Author URI: http://cliffsidemedia.com/
Plugin URI: https://github.com/iloveitaly/wordpress-youtube
*/

define('ILOVEITALY_YOUTUBE_REGEX', '#([a-zA-Z0-9_-]{11})#i');
define('ILOVEITALY_YOUTUBE_IFRAME_REGEX', '#<iframe.+?src="http://www.youtube.com/embed/([a-zA-Z0-9_-]{11})["?][^>]+?></iframe>#i');

add_action('init', 'iloveitaly_youtube_shortcodes');
function iloveitaly_youtube_shortcodes() {
	add_shortcode('youtube', 'iloveitaly_youtube_embed');
}

function iloveitaly_iframe_to_image($content) {
	return preg_replace(ILOVEITALY_YOUTUBE_IFRAME_REGEX, "<a target='_blank' href='".get_permalink().'\'><img src="' . iloveitaly_extract_youtube_image($content) . '" /></a>', $content);
}

function iloveitaly_extract_youtube_image($content) {
	preg_match(ILOVEITALY_YOUTUBE_IFRAME_REGEX, $content, $matches);

	if(empty($matches[1])) {
		return "";
	}

	return "http://youtube-thumbnail-generator.herokuapp.com/?quality=hq&play=true&input={$matches[1]}";
}

function iloveitaly_youtube_embed($attrs) {
	if(!is_single()) return "";

	preg_match(ILOVEITALY_YOUTUBE_REGEX, $attrs['id'], $matches);
	$youtube_id = $matches[1];

	return "<iframe src=\"http://www.youtube.com/embed/{$youtube_id}?rel=0&showinfo=0&modestbranding=1&wmode=opaque&theme=dark\" height=\"315\" width=\"560\" frameborder=\"0\"></iframe>";
}

function iloveitaly_post_cover_image($post_id, $thumbnail_size = 'full') {
	$cover = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), $thumbnail_size);
	$cover_url = $cover['0'];

	if(empty($cover_url)) {
		$cover_url = iloveitaly_extract_first_image_in_post($post_id, $thumbnail_size);
	}

	if(empty($cover_url)) {
		// TODO should request a minified version of the YT image from the server

		$page = get_post($post_id);
		// TODO need to pull this function out into this plugin
		$cover_url = iloveitaly_extract_youtube_image($page->post_content);
	}

	return $cover_url;
}

function iloveitaly_extract_first_image_in_post($post_id, $thumbnail_size = 'full') {
	$first_image = "";

	// http://wordpress.stackexchange.com/questions/65502/getting-first-image-from-post
	$post_attachments = get_children('post_parent='.$post_id.'&post_type=attachment&post_mime_type=image&order=asc');

	if(!empty($post_attachments)) {
		$files_keys = array_reverse(array_keys($post_attachments));
		$first_attachment_in_post = wp_get_attachment_image_src($files_keys[0], $thumbnail_size);
		$first_image = $first_attachment_in_post['0'];
	}

	if(empty($first_image)) {
		$post = get_post($post_id);

		// sometimes post_parent is nil; we have to resort to regex
		// http://wordpress.stackexchange.com/questions/26958/extract-post-image-to-be-featured-images
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
		$image_url = $matches[1][0];

		// TODO try to extract the attachment reference from this and then pull the exact thumbnail size
		
		$first_image = $image_url;
	}

	return $first_image;
}

// TODO consider handling post orphans
// http://wordpress.stackexchange.com/questions/114768/import-attachments-not-attaching-to-post-parent