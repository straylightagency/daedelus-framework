<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Filters;

class RelativeUrls extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
	    $filters = [
			'bloginfo_url',
			'the_permalink',
			'wp_list_pages',
			'wp_list_categories',
			'wp_get_attachment_url',
			'the_content_more_link',
			'the_tags',
			'get_pagenum_link',
			'get_comment_link',
			'month_link',
			'day_link',
			'year_link',
			'term_link',
			'the_author_posts_link',
			'script_loader_src',
			'style_loader_src',
			'theme_file_uri',
			'parent_theme_file_uri',
		];

		Filters::add( $filters, fn (string $url) => $this->relativeUrl( $url ) );

	    Filters::add('wp_calculate_image_srcset', fn (array $sources) => array_map( fn ( $source ) => [
		    ...$source,
		    'url' => $this->relativeUrl( $source['url'] )
	    ], $sources ) );
    }

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	protected function relativeUrl(string $url): string
	{
		if ( is_feed() ) {
			return $url;
		}

		if ( $this->compareBaseUrl( network_home_url(), $url ) ) {
			return wp_make_link_relative( $url );
		}

		return $url;
	}

	/**
	 * @param string $base_url
	 * @param string $input_url
	 * @param bool $strict_scheme
	 *
	 * @return bool
	 */
	protected function compareBaseUrl(string $base_url, string $input_url, bool $strict_scheme = true):bool
	{
		$base_url = trailingslashit( $base_url );
		$input_url = trailingslashit( $input_url );

		if ( $base_url === $input_url ) {
			return true;
		}

		$input_url = wp_parse_url( $input_url );

		if ( !isset( $input_url['host'] ) ) {
			return true;
		}

		$base_url = wp_parse_url( $base_url );

		if ( !isset( $base_url['host'] ) ) {
			return false;
		}

		if ( !$strict_scheme || !isset( $input_url['scheme'] ) || !isset( $base_url['scheme'] ) ) {
			$input_url['scheme'] = $base_url['scheme'] = 'soil';
		}

		if ( $base_url['scheme'] !== $input_url['scheme'] ) {
			return false;
		}

		if ( $base_url['host'] !== $input_url['host'] ) {
			return false;
		}

		if ( isset( $base_url['port'] ) || isset( $input_url['port'] ) ) {
			return isset( $base_url['port'], $input_url['port'] ) && $base_url['port'] === $input_url['port'];
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function condition(): bool
	{
		return !is_admin() || wp_doing_ajax();
	}
}