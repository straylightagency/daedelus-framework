<?php

namespace Daedelus\Foundation\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider as BaseServiceProvider;

/**
 *
 */
class ViewServiceProvider extends BaseServiceProvider
{
	/**
	 * @return void
	 */
	public function boot(): void
	{
		$this->registerBladeDirectives();
	}

	/**
	 * @return void
	 */
	protected function registerBladeDirectives(): void
	{
        Blade::directive('vite', fn (array|string $entries) => "<?php echo vite( $entries ); ?>");

        Blade::directive('mix', fn (array|string $entries) => "<?php echo mix( $entries ); ?>");

		Blade::directive('language_attributes', fn () => "<?php language_attributes() ?>" );

		Blade::directive('wp_head', fn () => "<?php wp_head(); ?>" );

		Blade::directive('wp_footer', fn () => "<?php wp_footer(); ?>" );

		Blade::directive('body_class', fn (string $css_class = '') => "<?php body_class( $css_class ); ?>" );

	}
}