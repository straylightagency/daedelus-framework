<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;

/**
 *
 */
class DisableDashboardWidgets extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		Actions::add( 'wp_dashboard_setup', function () {
			global $wp_meta_boxes;

			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
			unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['yoast_db_widget'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
		} );
	}
}