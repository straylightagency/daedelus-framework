<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:list')]
class ListCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:list {--fields=name,status,update,version,update_version,auto_update} {--skip-update-check}';

	/** @var string */
	protected $description = 'Gets a list of plugins.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$fields = $this->option('fields');
		$skip_update_check = $this->hasOption('skip-update-check') && $this->option('skip-update-check');
		$recently_active = $this->hasOption('recently-active') && $this->option('recently-active');

		$status = false;
		$last_updated = false;
		$tested_up_to = false;

		if ( !empty( $fields ) ) {
			$fields = explode( ',', $fields );

			$status = in_array( 'wporg_status', $fields, true );
			$last_updated = in_array( 'wporg_last_updated', $fields, true );
			$tested_up_to = in_array( 'tested_up_to', $fields, true );
		}

		$field = $this->hasOption('field') ? $this->option('field') : '';

		if ( 'wporg_status' === $field ) {
			$status = true;
		}

		if ( 'wporg_last_updated' === $field ) {
			$last_updated = true;
		}

		$tested_up_to = 'tested_up_to' === $field || $tested_up_to;

		// Force WordPress to check for updates if `--skip-update-check` is not passed.
		if ( false === $skip_update_check ) {
			wp_update_plugins();
		}

		$all_items = $this->findAll();

		if ( false !== $recently_active ) {
			$all_items = array_filter(
				$all_items,
				fn ( $value ) => isset( $value['recently_active'] ) && true === $value['recently_active']
			);
		}

		if ( !is_array( $all_items ) ) {
			$this->error( "No plugins found." );
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
					$value = $value ? 'available' : 'none';
				}

				if ( 'auto_update' === $field ) {
					$value = $value ? 'on' : 'off';
				}
			}

			$obj_fields = [
				'name',
				'status',
				'update',
				'version',
				'update_version',
				'auto_update',
			];

			foreach ( $obj_fields as $field ) {
				if ( !array_key_exists( $field, $fields ) ) {
					continue;
				}

				// This can be either a value to filter by or a comma-separated list of values.
				// Also, it is not forbidden for a value to contain a comma (in which case we can filter only by one).
				$field_filter = $fields[ $field ];

				if ( $item[ $field ] !== $field_filter
					&& !in_array( $item[ $field ], array_map( 'trim', explode( ',', $field_filter ) ), true )
				) {
					unset( $all_items[ $key ] );
				}
			}
		}

//		$formatter = $this->get_formatter( $fields );
//		$formatter->display_items( $all_items );
	}
}