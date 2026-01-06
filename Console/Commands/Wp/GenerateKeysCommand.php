<?php
namespace Daedelus\Framework\Console\Commands\Wp;

use BadFunctionCallException;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generate WordPress keys on .env file
 */
#[AsCommand(name: 'wp:generate-keys')]
class GenerateKeysCommand extends Command
{
    use ConfirmableTrait;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'wp:generate-keys
                    {--show : Display the keys instead of modifying files}
                    {--force : Force the operation to run when in production}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate WordPress keys on .env file';

    /** @var string */
    const string VALID_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []<>~`+=,.;:/?|';

    /** @var array */
    const array KEYS = [
        "AUTH_KEY", "SECURE_AUTH_KEY", "LOGGED_IN_KEY",
        "NONCE_KEY", "AUTH_SALT", "SECURE_AUTH_SALT",
        "LOGGED_IN_SALT", "NONCE_SALT",
    ];

    /**
     * @return void
     * @throws RandomException
     */
	public function handle():void
	{
        $keys = [];

        foreach ( self::KEYS as $name ) {
            $keys[ $name ] = $this->generateRandomKey();
        }

        if ( $this->option('show') ) {
            foreach ( $keys as $name => $key ) {
                $this->line('<comment>' . $name . '="' . $key . '"</comment>');
            }
            return;
        }

        if ( ! $this->confirmToProceed() ) {
            return;
        }

        if ( ! $this->writeNewEnvironmentFileWith( $keys ) ) {
            return;
        }

        $this->components->info('WordPress keys set successfully.');
	}

    /**
     * @return string
     * @throws RandomException
     */
    protected function generateRandomKey():string
    {
        if ( ! function_exists( 'random_int' ) ) {
            throw new BadFunctionCallException( "'random_int' does not exist" );
        }

        $chars = self::VALID_CHARACTERS;
        $key   = '';

        for ( $i = 0; $i < 64; $i++ ) {
            $key .= substr( $chars, random_int( 0, strlen( $chars ) - 1 ), 1 );
        }

        return $key;
    }

    /**
     * Write the new generated keys in the .env file
     *
     * @param array $keys
     * @return bool
     */
    protected function writeNewEnvironmentFileWith(array $keys):bool
    {
        $input = file_get_contents( $this->laravel->environmentFilePath() );

        $replaced = $input;

        foreach ( $keys as $name => $key ) {
            $replaced = preg_replace(
                $this->keyReplacementPattern( $name ),
                $name . '="' . $key . '"',
                $replaced,
            );

            if ( $replaced === $input || $replaced === null ) {
                $this->error('Unable to set application key. No ' . $name . ' variable was found in the .env file.');

                return false;
            }
        }

        file_put_contents( $this->laravel->environmentFilePath(), $replaced );

        return true;
    }

    /**
     * Get a regex pattern that will match env with the $key_name with any random key string.
     *
     * @param string $key_name
     * @return string
     */
    protected function keyReplacementPattern(string $key_name): string
    {
        $escaped = preg_quote('="' . $this->laravel['config'][ 'wordpress.' . strtolower( $key_name ) ] . '"', '/');

        return "/^{$key_name}{$escaped}/m";
    }
}