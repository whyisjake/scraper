<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://jakespurlock.com
 * @since             1.0.0
 * @package           Scraper
 *
 * @wordpress-plugin
 * Plugin Name:       Scraper
 * Plugin URI:        https://jakespurlock.com/scraper
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Jake Spurlock
 * Author URI:        https://jakespurlock.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       scraper
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require 'vendor/autoload.php';

use Fusonic\OpenGraph\Consumer;

add_filter(
	'the_content',
	function( $content ) {
		global $post;
		$url = get_post_meta( $post->ID, 'url', true );

		if ( $url ) {
			// We have this in the cache?
			$open_graph = get_post_meta( $post->ID, 'open_graph', true );

			// Nope, let's get it.
			if ( ! $open_graph ) {
				$consumer   = new Consumer();
				$open_graph = $consumer->loadUrl( $url );

				// At some point, let's save the image as the thumbnail.
				update_post_meta( $post->ID, 'open_graph', $open_graph );
			}

			$content .= '<div class="card">';

			// Build an image.
			$image    = $open_graph->images[0];
			$content .= sprintf( '<img src="%s" alt="%s" class="card-img-top">', esc_url( $image->url ), esc_attr( $open_graph->title ) );

			$content .= '<div class="card-body">';

			// Dek
			$content .= sprintf( '<div class="card-text">%s</div>', wpautop( wp_trim_words( $open_graph->description, 35 ) ) );

			// Audio Player
			$audio_url = ( ! empty( $open_graph->audios[0]->url ) ) ? $open_graph->audios[0]->url : false;
			if ( $audio_url ) {
				$content .= audio_player( $audio_url );
			}

			$content .= '</div>';

			// Buttons
			$buttons  = share_button( $open_graph->url, $open_graph->siteName, false, '🔗' );
			$buttons .= share_button( get_comments_link( $post->ID ), 'Comment', get_comments_number( $post->ID ), '🗣' );
			$buttons .= share_button( '#', 'Like', false, '♡' );

			// Link
			$content .= sprintf( '<div class="card-footer text-muted">%s</div>', $buttons );

			$content .= '</div>';
		}
		return $content;
	}
);

/**
 * Share Button Function
 *
 * @param string $text
 * @param mixed $count
 * @param string $icon
 * @return string
 */
function share_button( $url = '', $text = '', $count = '', $icon = '' ) {
	if ( $count > 0 ) {
		$count = sprintf( '(%d)', $count );
	}
	return sprintf(
		' <a href="%s" class="badge badge-primary">%s %s %s</a>',
		esc_url( $url ),
		$icon,
		$text,
		$count
	);
}

function understrap_entry_footer() {
	return false;
}

function audio_player( $audio_url ) {
	return sprintf( '<audio controls><source src="%s" type="audio/mpeg"></audio>', esc_url( $audio_url ) );
}
