<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use WP_Theme;

/**
 *
 */
trait ManageThemes
{
	/**
	 * @param string $name
	 *
	 * @return false|WP_Theme
	 */
	protected function findOne(string $name): false|WP_Theme
	{
		// Workaround to equalize folder naming conventions across Win/Mac/Linux.
		// Returns false if theme stylesheet doesn't exactly match existing themes.
		$existing_themes = wp_get_themes( [ 'errors' => null ] );
		$existing_stylesheets = array_keys( $existing_themes );

		if ( !in_array( $name, $existing_stylesheets, true ) ) {
			$inexact_match = $this->resolveInexactMatch( $name, $existing_themes );

			if ( false !== $inexact_match ) {
				$this->ask( "Did you mean '%s' ?", $inexact_match );
			}

			return false;
		}

		return $existing_themes[ $name ];
	}

	/**
	 * @param array $themes
	 *
	 * @return array
	 */
	protected function findMany(array $themes = []):array
	{
		$items = [];

		foreach ( $themes as $theme ) {
			$item = $this->findOne( $theme );

			if ( $item ) {
				$items[] = $item;
			} else {
				$this->warn( sprintf( "The '%s' theme could not be found.", $theme ) );
			}
		}

		return $items;
	}

	/**
	 * @return array
	 */
	protected function findAll():array
	{
		$items = [];
		$theme_version_info = [];

		if ( is_multisite() ) {
			$site_enabled = get_option( 'allowedthemes' );

			if ( empty( $site_enabled ) ) {
				$site_enabled = [];
			}

			$network_enabled = get_site_option( 'allowedthemes' );

			if ( empty( $network_enabled ) ) {
				$network_enabled = [];
			}
		}

		$all_update_info = $this->getUpdateInfo();

		$checked_themes  = $all_update_info->checked ?? [];

		if ( !empty( $checked_themes ) ) {
			foreach ( $checked_themes as $slug => $version ) {
				$theme_version_info[ $slug ] = $this->isThemeVersionValid( $slug, $version );
			}
		}

		$auto_updates = get_site_option( 'auto_update_themes' );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		foreach ( wp_get_themes() as $key => $theme ) {
			$stylesheet = $theme->get_stylesheet();

			$update_info = ( isset( $all_update_info->response[ $stylesheet ] ) && null !== $all_update_info->response[ $theme->get_stylesheet() ] ) ? (array) $all_update_info->response[ $theme->get_stylesheet() ] : null;

			$items[ $stylesheet ] = [
				'name'           => $key,
				'status'         => $this->getStatus( $theme ),
				'update'         => (bool) $update_info,
				'update_version' => $update_info['new_version'] ?? null,
				'update_package' => $update_info['package'] ?? null,
				'version'        => $theme->get( 'Version' ),
				'update_id'      => $stylesheet,
				'title'          => $theme->get( 'Name' ),
				'description'    => wordwrap( $theme->get( 'Description' ) ),
				'author'         => $theme->get( 'Author' ),
				'auto_update'    => in_array( $stylesheet, $auto_updates, true ),
			];

			// Compare version and update information in theme list.
			if ( isset( $theme_version_info[ $key ] ) && false === $theme_version_info[ $key ] ) {
				$items[ $stylesheet ]['update'] = 'version higher than expected';
			}

			if ( is_multisite() ) {
				if ( !empty( $site_enabled[ $key ] ) && !empty( $network_enabled[ $key ] ) ) {
					$items[ $stylesheet ]['enabled'] = 'network,site';
				} elseif ( !empty( $network_enabled[ $key ] ) ) {
					$items[ $stylesheet ]['enabled'] = 'network';
				} elseif ( !empty( $site_enabled[ $key ] ) ) {
					$items[ $stylesheet ]['enabled'] = 'site';
				} else {
					$items[ $stylesheet ]['enabled'] = 'no';
				}
			}
		}

		return $items;
	}

	/**
	 * @param string $name
	 * @param array $existing_themes
	 *
	 * @return false|string
	 */
	protected function resolveInexactMatch( string $name, array $existing_themes ): false|string
	{
		$target = strtolower( $name );
		$themes = array_map( 'strtolower', array_keys( $existing_themes ) );

		if ( in_array( $target, $themes, true ) ) {
			return $target;
		}

		$suggestion = $this->suggest( $target, $themes );

		if ( '' !== $suggestion ) {
			return $suggestion;
		}

		return false;
	}

	/**
	 * @param string $target
	 * @param array $options
	 * @param int $threshold
	 *
	 * @return string
	 */
	protected function suggest(string $target, array $options, int $threshold = 2): string
	{
		if ( empty( $options ) ) {
			return '';
		}

		foreach ( $options as $option ) {
			$distance = levenshtein( $option, $target );
			$levenshtein[ $option ] = $distance;
		}

		// Sort known command strings by distance to user entry.
		asort( $levenshtein );

		$suggestion = key( $levenshtein );

		// Only return a suggestion if below a given threshold.
		return $levenshtein[ $suggestion ] <= $threshold && $suggestion !== $target
			? (string) $suggestion
			: '';
	}

	/**
	 * Get the status for a given theme.
	 *
	 * @param WP_Theme $theme Theme to get the status for.
	 *
	 * @return string Status of the theme.
	 */
	protected function getStatus( WP_Theme $theme ): string
	{
		if ( $this->isActiveTheme( $theme ) ) {
			return 'active';
		}

		if ( $theme->get_stylesheet_directory() === get_template_directory() ) {
			return 'parent';
		}

		return 'inactive';
	}

	/**
	 * Check whether a given theme is the active theme.
	 *
	 * @param WP_Theme $theme Theme to check.
	 *
	 * @return bool Whether the provided theme is the active theme.
	 */
	protected function isActiveTheme( WP_Theme $theme ): bool
	{
		return $theme->get_stylesheet_directory() === get_stylesheet_directory();
	}

	/**
	 * Check whether a given theme is the active theme parent.
	 *
	 * @param WP_Theme $theme Theme to check.
	 *
	 * @return bool Whether the provided theme is the active theme.
	 */
	protected function isActiveParentTheme( WP_Theme $theme ): bool
	{
		return $theme->get_stylesheet_directory() === get_template_directory();
	}

	/**
	 * Check if current version of the theme is higher than the one available at WP.org.
	 *
	 * @param string $slug Theme slug.
	 * @param string $version Theme current version.
	 *
	 * @return bool|string
	 */
	protected function isThemeVersionValid( string $slug, string $version ): bool|string
	{
		// Get Theme Info.
		$theme_info = themes_api( 'theme_information', array( 'slug' => $slug ) );

		// Return empty string for themes not on WP.org.
		if ( is_wp_error( $theme_info ) ) {
			return '';
		}

		// Compare theme version info.
		return !version_compare( $version, $theme_info->version, '>' );
	}

	/**
	 * Get the available update info.
	 *
	 * @return mixed Available update info.
	 */
	protected function getUpdateInfo(): mixed
	{
		return get_site_transient( 'update_themes' );
	}
}