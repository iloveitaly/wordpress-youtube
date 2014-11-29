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

	return "http://your-generator.com/?quality=hq&play=true&input={$matches[1]}";
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
		// TODO should request a minified version of the YT image from the server

		$page = get_post($post_id);
		// TODO need to pull this function out into this plugin
		$cover_url = iloveitaly_extract_youtube_image($page->post_content);
	}

	return $cover_url;
}