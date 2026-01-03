<?php
namespace Daedelus\Foundation\Console\Commands\Wp;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Open /wp-admin/ in a browser
 */
#[AsCommand(name: 'wp:admin')]
class AdminCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'wp:admin';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Open /wp-admin/ in a browser';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$exec = match ( strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
			'DAR' => 'open',
			'WIN' => 'start ""',
			default => 'xdg-open',
		};

		passthru( $exec . ' ' . escapeshellarg( admin_url() ) );
	}
}