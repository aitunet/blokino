<?php
/**
 * Blokino — uninstall (E-07).
 *
 * Runs only when the plugin is deleted from the Plugins screen. Removes the
 * plugin's own options and transients, on every site of a network.
 *
 * Kept on purpose (they are the user's content, not the plugin's settings):
 * posts of the `project` type, media attachments, the site logo (custom_logo)
 * and the blocks already saved in posts — effects are injected at render time,
 * so saved markup stays valid without the plugin.
 *
 * @package Blokino
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete the plugin's options and transients on the current site.
 */
function blokino_uninstall_site() {
	$options = array(
		'blokino_settings',
		'blokino_demo_record',
		'blokino_welcome_seen',
		'blokino_content_signature',
		'blokino_portfolio',
		'blokino_db_version',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}
	delete_transient( 'blokino_themes_catalog' );
	delete_transient( 'blokino_themes_catalog_fail' );
}

if ( is_multisite() ) {
	$blokino_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $blokino_sites as $blokino_site_id ) {
		switch_to_blog( (int) $blokino_site_id );
		blokino_uninstall_site();
		restore_current_blog();
	}
} else {
	blokino_uninstall_site();
}
