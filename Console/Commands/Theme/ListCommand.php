<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:list')]
class ListCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:list {--fields=name,title,version}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gets a list of themes.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$skipUpdateCheck = $this->hasOption('skip-update-check') && $this->option('skip-update-check');
		$recentlyActive = $this->hasOption('recently-active') && $this->option('recently-active');

		// Force WordPress to check for updates if `--skip-update-check` is not passed.
		if ( false === $skipUpdateCheck ) {
			call_user_func( 'wp_update_themes' );
		}

		$all_items = $this->findAll();

		if ( false !== $recentlyActive ) {
			$all_items = array_filter( $all_items,
				fn ( $value ) => isset( $value['recently_active'] ) && true === $value['recently_active']
			);
		}

		if ( empty( $all_items ) ) {
			$this->error( "No themes found." );
			return;
		}

		foreach ( $all_items as $key => &$item ) {
			if ( empty( $item['version'] ) ) {
				$item['version'] = '';
			}

			if ( empty( $item['update_version'] ) ) {
				$item['update_version'] = '';
			}

			foreach ( $item as $field => &$value ) {
				if ( 'update' === $field ) {
					if ( true === $value ) {
						$value = 'available';
					} elseif ( false === $value ) {
						$value = 'none';
					}
				} elseif ( 'auto_update' === $field ) {
					if ( true === $value ) {
						$value = 'on';
					} elseif ( false === $value ) {
						$value = 'off';
					}
				}
			}

			$objFields = [
				'name',
				'status',
				'update',
				'version',
				'update_version',
				'auto_update',
			];

			$fields = $this->option( 'fields' );

			foreach ( $objFields as $field ) {
				if ( !array_key_exists( $field, $fields ) ) {
					continue;
				}

				// This can be either a value to filter by or a comma-separated list of values.
				// Also, it is not forbidden for a value to contain a comma (in which case we can filter only by one).
				$field_filter = $fields[ $field ];
				if (
					$item[ $field ] !== $field_filter
					&& ! in_array( $item[ $field ], array_map( 'trim', explode( ',', $field_filter ) ), true )
				) {
					unset( $all_items[ $key ] );
				}
			}
		}


	}
}