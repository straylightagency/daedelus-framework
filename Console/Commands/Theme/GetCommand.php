<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:get')]
class GetCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:get {theme} {--fields=name,title,version}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gets details about a theme.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$arg = trim( $this->argument('theme' ) );

		$theme = $this->findOne( $arg );

		$errors = $theme->errors();
		if ( is_wp_error( $errors ) ) {
			$message = $errors->get_error_message();
			$this->error( $message );

			return;
		}

		// WP_Theme object employs magic getter, unfortunately.
		$theme_vars = [
			'name',
			'title',
			'version',
			'status',
			'parent_theme',
			'template_dir',
			'stylesheet_dir',
			'template',
			'stylesheet',
			'screenshot',
			'description',
			'author',
			'tags',
			'theme_root',
			'theme_root_uri',
		];

		$theme_obj = new stdClass();

		foreach ( $theme_vars as $var ) {
			$theme_obj->$var = $theme->$var;
		}

		$theme_obj->status = $this->getStatus( $theme );
		$theme_obj->description = wordwrap( $theme_obj->description );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = $theme_vars;
		}

		$formatter = $this->get_formatter( $assoc_args );

		$formatter->display_item( $theme_obj );
	}
}