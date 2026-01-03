<?php
namespace Daedelus\Foundation\Hooks;

use Closure;
use Daedelus\Support\Actions;
use Daedelus\Support\Filters;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;

class CleanupHtml extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
		$this->obscurify();
		$this->disableEmojis();
	    $this->disableGutenbergBlockCss();
        $this->disableGlobalStyles();
		$this->disableGalleryCss();
		$this->cleanHtmlMarkup();
    }

	/**
	 * @return void
	 */
	protected function obscurify():void
	{
		Filters::add( 'get_bloginfo_rss', fn (string $value ) => !Str::is( $value, __('Just another WordPress site') ) ? $value : '' );

		Actions::add( 'the_generator', $this->noop() );

		Filters::remove('wp_robots', 'wp_robots_max_image_preview_large');

		Actions::remove('wp_head', 'rsd_link');
		Actions::remove('wp_head', 'wlwmanifest_link');
		Actions::remove('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
		Actions::remove('wp_head', 'wp_generator');
		Actions::remove('wp_head', 'wp_shortlink_wp_head', 10);
		Actions::remove('wp_head', 'rest_output_link_wp_head', 10);
		Actions::remove('wp_head', 'wp_oembed_add_discovery_links');
		Actions::remove('wp_head', 'wp_oembed_add_host_js');
		Actions::remove('wp_head', 'wp_print_auto_sizes_contain_css_fix', 1 );
	}

	/**
	 * @return void
	 */
	protected function disableEmojis():void
	{
		Actions::remove('wp_head', 'print_emoji_detection_script', 7);
		Actions::remove('admin_print_scripts', 'print_emoji_detection_script');
		Actions::remove('wp_print_styles', 'print_emoji_styles');
		Actions::remove('admin_print_styles', 'print_emoji_styles');
		Filters::remove('the_content_feed', 'wp_staticize_emoji');
		Filters::remove('comment_text_rss', 'wp_staticize_emoji');
		Filters::remove('wp_mail', 'wp_staticize_emoji_for_email');
		Filters::add( 'emoji_svg_url',  $this->noop() );
	}

	/**
	 * @return void
	 */
	protected function disableGutenbergBlockCss():void
	{
		Actions::add('wp_enqueue_scripts', function () {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-block-style' );
			wp_dequeue_style( 'storefront-gutenberg-blocks' );
			wp_dequeue_style( 'classic-theme-styles' );
			wp_dequeue_style( 'global-styles' );
		}, 200 );

		Actions::add('wp_footer', function () {
			wp_dequeue_style('core-block-supports');
		} );
	}

    /**
     * @return void
     */
    protected function disableGlobalStyles():void
    {
        Filters::add('should_load_separate_core_block_assets', $this->noop() );
    }

	/**
	 * @return void
	 */
	protected function disableGalleryCss():void
	{
		Filters::add('use_default_gallery_style', $this->noop() );
	}

	/**
	 * @return void
	 */
	protected function cleanHtmlMarkup():void
	{
		Filters::add( 'body_class', function (array $classes) {
			$remove_classes = [
				'page-template-default'
			];

			if ( is_single() || is_page() && !is_front_page() ) {
				if ( !in_array( $slug = basename( get_permalink() ), $classes, true ) ) {
					$classes[] = $slug;
				}
			}

			if ( is_front_page() ) {
				$remove_classes[] = 'page-id-' . get_option('page_on_front');
			}

			return array_values( array_diff( $classes, $remove_classes ) );
		} );

		Filters::add('language_attributes', function () {
			$attributes = [];

			if ( is_rtl() ) {
				$attributes[] = 'dir="rtl"';
			}

			$lang = esc_attr( get_bloginfo('language') );

			if ( $lang ) {
				$attributes[] = "lang=\"{$lang}\"";
			}

			return implode(' ', $attributes );
		} );

		if ( class_exists( DOMDocument::class ) ) {
			Filters::add([
				'style_loader_tag',
				'script_loader_tag',
			], function ( $html ) {
				$document = new DOMDocument();

				libxml_use_internal_errors( true );
				$document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
				libxml_clear_errors();

				foreach ( ( new DOMXPath( $document ) )->query( '//*' ) as $link ) {
					$link->removeAttribute('type');
					$link->removeAttribute('id');

					if ( ( $media = $link->getAttribute('media') ) && $media !== 'all' ) {
						continue;
					}

					$link->removeAttribute('media');
				}

				return trim( substr( $document->saveHTML(), 23 ) );
			} );
		}

		Filters::add( [
			'get_avatar', // <img />
			'post_thumbnail_html', // <img />
			'comment_id_fields', // <input />
		],  $this->removeSelfClosingTags() );

		Filters::add('site_icon_meta_tags', fn (array $meta_tags ) => array_map( $this->removeSelfClosingTags(), $meta_tags ), 20 );
	}

	/**
	 * @return Closure
	 */
	protected function removeSelfClosingTags():Closure
	{
		return fn (string $html) => str_replace(' />', '>', $html );
	}
}