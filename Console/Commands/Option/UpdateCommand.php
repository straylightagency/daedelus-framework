<?php
namespace Daedelus\Foundation\Console\Commands\Option;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'option:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'option:update {key} {value} {--autoload=} {--format=}';

	/** @var string */
	protected $description = 'Updates an option value.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$key = $this->argument('key');
		$value = $this->argument('value');
		$autoload = $this->option('autoload');
		$format = $this->option('format') ?? 'plaintext';

		if ( ! in_array( $autoload, [ 'on', 'off', 'yes', 'no' ], true ) ) {
			$autoload = null;
		}

		// Sanitization WordPress normally performs when getting an option
		if ( in_array( $key, [ 'siteurl', 'home', 'category_base', 'tag_base' ], true ) ) {
			$value = untrailingslashit( $value );
		}

		$old_value = sanitize_option( $key, get_option( $key ) );

		if ( $value === $old_value && null === $autoload ) {
			$this->info( "Value passed for '{$key}' option is unchanged." );
		} elseif ( update_option( $key, $value, $autoload ) ) {
			$this->info( "Updated '{$key}' option." );
		} else {
			$this->error( "Could not update option '{$key}'." );
		}
	}
}