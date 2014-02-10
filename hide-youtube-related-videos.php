<?php /*
**************************************************************************
Plugin Name: Hide YouTube Related Videos
Plugin URI: http://wordpress.org/extend/plugins/hide-youtube-related-videos/
Description: This is a simple plugin to keep the YouTube oEmbed from showing related videos.
Author: SparkWeb Interactive, Inc.
Version: 1.3
Author URI: http://www.soapboxdave.com/

**************************************************************************

Copyright (C) 2012 SparkWeb Interactive, Inc.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

**************************************************************************/

//The Filter That Does the Work
add_filter('oembed_result', 'hide_youtube_related_videos', 10, 3);
function hide_youtube_related_videos($data, $url, $args = array()) {
	$data = preg_replace('/(youtube\.com.*)(\?feature=oembed)(.*)/', '$1?' . apply_filters("hyrv_extra_querystring_parameters", "wmode=transparent&amp;") . 'rel=0$3', $data);
	return $data;
}

//Disable the Jetpack
function hyrv_remove_jetpack_shortcode_function( $shortcodes ) {
    $jetpack_shortcodes_dir = WP_CONTENT_DIR . '/plugins/jetpack/modules/shortcodes/';
    $shortcodes_to_unload = array('youtube.php');
    foreach ( $shortcodes_to_unload as $shortcode ) {
        if ( $key = array_search( $jetpack_shortcodes_dir . $shortcode, $shortcodes ) ) {
            unset( $shortcodes[$key] );
        }
    }
    return $shortcodes;
}
add_filter('jetpack_shortcodes_to_include', 'hyrv_remove_jetpack_shortcode_function');

//On Activation, all oembed caches are cleared
register_activation_hook(__FILE__, 'hide_youtube_related_videos_activation');
function hide_youtube_related_videos_activation() {
	global $wpdb;

	$post_ids = $wpdb->get_col( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'" );
 	if ( $post_ids ) {
 		$postmetaids = $wpdb->get_col( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'" );
 		$in = implode( ',', array_fill( 1, count($postmetaids), '%d' ) );
 		do_action( 'delete_postmeta', $postmetaids );
 		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_id IN($in)", $postmetaids ) );
 		do_action( 'deleted_postmeta', $postmetaids );
 		foreach ( $post_ids as $post_id )
 			wp_cache_delete( $post_id, 'post_meta' );
 		return true;
 	}

}
