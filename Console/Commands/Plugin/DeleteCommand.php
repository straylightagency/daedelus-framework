<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:delete')]
class DeleteCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:delete {plugins?*} {--all}';

	/** @var string */
	protected $description = 'Deletes plugin files without deactivating or uninstalling.';

	/**
	 * @param Filesystem $files
	 */
	public function __construct(protected Filesystem $files)
	{
		parent::__construct();
	}

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->argument('plugins');
		$all = $this->hasOption('all') && $this->option('all');

		// Check if plugin names or --all is passed.
		if ( empty( $args ) && !$all ) {
			return;
		}

		$successes = 0;
		$errors = [];

		$plugins = $this->plugins();

		if ( !empty( $args ) ) {
			$plugins = $plugins->filter(function ($plugin, $name) use ($args) {
				return in_array( $name, $args );
			} );

			if ( $plugins->isEmpty() ) {
				$this->error('No plugins found.');
				return;
			}
		}

		$count = $plugins->count();

		foreach ( $plugins as $plugin ) {
			if ( $this->doDelete( $plugin ) ) {
				$this->line( "Deleted `{$plugin->Name}` plugin." );
				++$successes;
			} else {
				$this->warn( "The `{$plugin->Name}` plugin could not be deleted." );
				$errors[] = $plugin->FileName;
			}
		}

		$current = get_site_transient( 'update_plugins' );

		if ( $current ) {
			// Don't remove the plugins that weren't deleted.
			$deleted = array_diff( $plugins->pluck('FileName')->toArray(), $errors );

			foreach ( $deleted as $plugin_file ) {
				unset( $current->response[ $plugin_file ] );
			}

			set_site_transient( 'update_plugins', $current );
		}

		$message = $count > 1 ? 'plugins' : 'plugin';
		$this->info( "$successes/$count $message deleted." );
	}

	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	protected function doDelete( $plugin ): bool
	{
		if ( is_uninstallable_plugin( $plugin->FilePath ) ) {
			uninstall_plugin( $plugin->FilePath );
		}

		do_action( 'delete_plugin', $plugin->FilePath );

		$plugins_dir = app()->pluginsPath();

		$full_file_path = app()->pluginsPath( $plugin->FilePath );
		$full_dir_path = dirname( $full_file_path );

		if ( strpos( $plugin->FilePath, '/' ) && $full_dir_path !== $plugins_dir ) {
			$deleted = $this->files->deleteDirectory( $full_dir_path );
		} else {
			$deleted = $this->files->delete( $full_file_path );
		}

		do_action( 'deleted_plugin', $plugin->FilePath, $deleted );

		$plugin_slug = dirname( $plugin->FilePath );

		if ( 'hello.php' === $plugin->FilePath ) {
			$plugin_slug = 'hello-dolly';
		}

		$plugin_translations = wp_get_installed_translations( 'plugins' );

		if ( '.' !== $plugin_slug && ! empty( $plugin_translations[ $plugin_slug ] ) ) {
			$translations = $plugin_translations[ $plugin_slug ];

			foreach ( $translations as $translation => $data ) {
				$po_file = app()->langPath( '/plugins/' . $plugin_slug . '-' . $translation . '.po' );
				$mo_file = app()->langPath( '/plugins/' . $plugin_slug . '-' . $translation . '.mo' );
				$l10n_file = app()->langPath( '/plugins/' . $plugin_slug . '-' . $translation . '.l10n' );

				$this->files->exists( $po_file ) ?? $this->files->delete( $po_file );
				$this->files->exists( $mo_file ) ?? $this->files->delete( $mo_file );
				$this->files->exists( $l10n_file ) ?? $this->files->delete( $l10n_file );

				$json_translation_files = $this->files->glob( app()->langPath( '/plugins/' . $plugin_slug . '-' . $translation . '-*.json' ) );

				if ( $json_translation_files ) {
					array_map( [ $this->files, 'delete' ], $json_translation_files );
				}
			}
		}

		return $deleted;
	}
}