<?php
/*
Plugin Name: WS5 WP Helper
Plugin URI: 
Description: A few simple tweaks such as loading jQuery and Font-Awesome from CDN, open Visit Site link in new window, etc; etc;
Version: 1.0
Author URI: http://wsfive.com/
*/

/* open visit site link in new window */
function ws5_tweak_admin_bar( $wp_admin_bar ) {
	//Get a reference to the view-site node to modify.
	$node = $wp_admin_bar->get_node('view-site');
	//Change target
	$node->meta['target'] = '_blank';
	//Update Node.
	$wp_admin_bar->add_node($node);
}
add_action( 'admin_bar_menu', 'ws5_tweak_admin_bar', 80 );

/* Load jQuery from a CDN using the exact version current WordPress install - Credit: Zach Schnackel http://zslabs.com */
function ws5_cdn_src() {

	global $wp_version;

	if ( !is_admin() ) :

		wp_enqueue_script('jquery');

	// Get current version of jQuery from WordPress core
	$wp_jquery_ver = $GLOBALS['wp_scripts']->registered['jquery-core']->ver;
	$wp_jquery_migrate_ver = $GLOBALS['wp_scripts']->registered['jquery-migrate']->ver;

	// We may choose cloadfare or google for main jQuery
	$jquery_cdn_url = '//cdnjs.cloudflare.com/ajax/libs/jquery/'. $wp_jquery_ver .'/jquery.min.js';
	//$jquery_cdn_url = '//ajax.googleapis.com/ajax/libs/jquery/'. $wp_jquery_ver .'/jquery.min.js';
	$jquery_migrate_cdn_url = '//cdnjs.cloudflare.com/ajax/libs/jquery-migrate/'. $wp_jquery_migrate_ver .'/jquery-migrate.min.js';

	// Deregister jQuery and jQuery Migrate
	wp_deregister_script('jquery-core');
	wp_deregister_script('jquery-migrate');

	// Register jQuery with CDN URL
	wp_register_script('jquery-core', $jquery_cdn_url, '', null, false );
	// Register jQuery Migrate with CDN URL
	wp_register_script('jquery-migrate', $jquery_migrate_cdn_url, array( 'jquery-core' ), null, false );

	endif;
}
add_action( 'wp_enqueue_scripts', 'ws5_cdn_src' );

/* Add local fallback for jQuery if CDN is down or not accessible */
function ws5_local_fallback( $src, $handle = null ) {

	if ( !is_admin() ) :

		static $add_jquery_fallback = false;
	static $add_jquery_migrate_fallback = false;

	if ( $add_jquery_fallback ) :
		echo '<script>window.jQuery || document.write(\'<script src="' . includes_url('js/jquery/jquery.js') . '"><\/script>\')</script>' . "\n";
	$add_jquery_fallback = false;
	endif;

	if ( $add_jquery_migrate_fallback ) :
		echo '<script>window.jQuery.migrateMute || document.write(\'<script src="' . includes_url('js/jquery/jquery-migrate.min.js') . '"><\/script>\')</script>' . "\n";
	$add_jquery_migrate_fallback = false;
	endif;

	if ( $handle === 'jquery-core')
		$add_jquery_fallback = true;

	if ( $handle === 'jquery-migrate')
		$add_jquery_migrate_fallback = true;

	return $src;

	endif;

	return $src;
}
add_filter( 'script_loader_src', 'ws5_local_fallback', 10, 2 );

/* Remove extra Font Awesome stylesheets if Font Awesome is already used in your theme - Credit: Tiguan http://themeforest.net/user/Tiguan */
function ws5_redo_fontawesome() {

	global $wp_styles;
	// use preg_match to find only the following patterns as exact matches, to prevent other plugin stylesheets that contain font-awesome expression to be also dequeued
	$patterns = array( 'font-awesome.' );
	$regex = '/(' .implode('|', $patterns) .')/i';

	foreach ( $wp_styles -> registered as $registered ) {
		if ( preg_match( $regex, $registered->src) ) {
			wp_dequeue_style( $registered->handle );
			// FA was dequeued, so here we need to enqueue it again from CDN
			wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.2/css/font-awesome.min.css' );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'ws5_redo_fontawesome', 999 );

/* Remove query strings from static resources - Credit: Zendy Web Studio https://kauai.zendy.net */
function ws5_remove_query_strings( $uri ) {

	// Strip version number from resource (ver param first on GET params list)
	if ( strpos( $uri, '?ver' ) !== false ) {
		$uri_parts = explode( '?ver', $uri );
		$stripped_uri = $uri_parts[0];

		// Strip version number from resource (ver param *not* first on GET params list)
	}elseif ( strpos( $uri, '&ver' ) !== false ) {
		$uri_parts = explode( '&ver', $uri );
		$stripped_uri = $uri_parts[0];
	}

	// If stripped URI has been set, return it
	// Otherwise, just return the full URI
	return $stripped_uri ? $stripped_uri : $uri;
}
if ( ! is_admin() ) {
	// Filter JS
	add_filter( 'script_loader_src', 'ws5_remove_query_strings', 15, 1 );
	// Filter CSS
	add_filter( 'style_loader_src', 'ws5_remove_query_strings', 15, 1 );
}

/* Remove inline styles and other coding junk added - Chris Ferdinandi - http://gomakethings.com */

function ws5_clean_post_content($content) {
	// Remove inline styling
	// $content = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $content);
	// Remove font tag
	$content = preg_replace('/<font[^>]+>/', '', $content);
	// Remove empty tags
	$post_cleaners = array('<p></p>' => '', '<p> </p>' => '', '<p>&nbsp;</p>' => '', '<span></span>' => '', '<span> </span>' => '', '<span>&nbsp;</span>' => '', '<span>' => '', '</span>' => '', '<font>' => '', '</font>' => '');
	$content = strtr($content, $post_cleaners);
	return $content;
}

add_filter( 'the_content', 'ws5_clean_post_content' );

/*  Disable Emojis - Credit: Ryan Hellyer https://geek.hellyer.kiwi/plugins/disable-emojis/ */

function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );	
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
}
add_action( 'init', 'disable_emojis' );

/**
 * Filter function used to remove the tinymce emoji plugin.
 * 
 * @param    array  $plugins  
 * @return   array             Difference betwen the two arrays
 */
function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	} else {
		return array();
	}
}
