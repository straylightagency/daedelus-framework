<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

/**
 *
 */
trait CanRunMySqlCommand
{
	/**
	 * @param string $cmd
	 * @param array $assoc_args
	 * @param bool $send_to_shell
	 * @param bool $interactive
	 *
	 * @return array
	 */
	protected function runMySqlCommand(string $cmd, array $assoc_args, bool $send_to_shell = true, bool $interactive = false ): array
	{
		$this->checkProcAvailable( 'run_mysql_command' );

		$descriptors = ( $interactive || $send_to_shell ) ?
			[
				0 => STDIN,
				1 => STDOUT,
				2 => STDERR,
			] :
			[
				0 => STDIN,
				1 => [ 'pipe', 'w' ],
				2 => [ 'pipe', 'w' ],
			];

		$stdout = $stderr = '';
		$pipes = [];

		if ( isset( $assoc_args['host'] ) ) {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_host_to_cli_args -- Misidentified as PHP native MySQL function.
			$assoc_args = array_merge( $assoc_args, mysql_host_to_cli_args( $assoc_args['host'] ) );
		}

		if ( isset( $assoc_args['pass'] ) ) {
			$old_password = getenv( 'MYSQL_PWD' );
			putenv( 'MYSQL_PWD=' . $assoc_args['pass'] );
			unset( $assoc_args['pass'] );
		}

		$final_cmd = $this->forceEnvOnUnixSystems( $cmd ) . assoc_args_to_str( $assoc_args );

		WP_CLI::debug( 'Final MySQL command: ' . $final_cmd, 'db' );
		$process = $this->procOpen( $final_cmd, $descriptors, $pipes );

		if ( isset( $old_password ) ) {
			putenv( 'MYSQL_PWD=' . $old_password );
		}

		if ( ! $process ) {
			WP_CLI::debug( 'Failed to create a valid process using proc_open_compat()', 'db' );
			exit( 1 );
		}

		if ( is_resource( $process ) && ! $send_to_shell && ! $interactive ) {
			$stdout = stream_get_contents( $pipes[1] );
			$stderr = stream_get_contents( $pipes[2] );

			fclose( $pipes[1] );
			fclose( $pipes[2] );
		}

		$exit_code = proc_close( $process );

		if ( $exit_code && ( $send_to_shell || $interactive ) ) {
			exit( $exit_code );
		}

		return [
			$stdout,
			$stderr,
			$exit_code,
		];
	}

	/**
	 * Check that `proc_open()` and `proc_close()` haven't been disabled.
	 *
	 * @param string|null $context Optional. If set will appear in error message. Default null.
	 * @param bool $return Optional. If set will return false rather than error out. Default false.
	 *
	 * @return bool
	 */
	protected function checkProcAvailable(?string $context = null, bool $return = false ): bool
	{
		if ( ! function_exists( 'proc_open' ) || ! function_exists( 'proc_close' ) ) {
			if ( $return ) {
				return false;
			}

			$msg = 'The PHP functions `proc_open()` and/or `proc_close()` are disabled. Please check your PHP ini directive `disable_functions` or suhosin settings.';

			if ( $context ) {
				$this->error( sprintf( "Cannot do '%s': %s", $context, $msg ) );
			} else {
				$this->error( $msg );
			}
		}
		return true;
	}

	/**
	 * Windows compatible `proc_open()`.
	 * Works around bug in PHP, and also deals with *nix-like `ENV_VAR=blah cmd` environment variable prefixes.
	 *
	 * @param string $cmd Command to execute.
	 * @param array<int, string> $descriptors Indexed array of descriptor numbers and their values.
	 * @param array<int, string>    &$pipes Indexed array of file pointers that correspond to PHP's end of any pipes that are created.
	 * @param string|null $cwd Initial working directory for the command.
	 * @param array<string, string> $env Array of environment variables.
	 * @param array<string> $other_options Array of additional options (Windows only).
	 *
	 * @return resource Command stripped of any environment variable settings.
	 */
	protected function procOpen(string $cmd, array $descriptors, array &$pipes, ?string $cwd = null, ?array $env = null, ?array $other_options = null )
	{
		if ( $this->isWindows() ) {
			$cmd = $this->procOpenOnWindows( $cmd, $env );
		}

		return proc_open( $cmd, $descriptors, $pipes, $cwd, $env, $other_options );
	}

	/**
	 * For use by `procOpen()` only. Separated out for ease of testing. Windows only.
	 * Turns *nix-like `ENV_VAR=blah command` environment variable prefixes into stripped `cmd` with prefixed environment variables added to passed in environment array.
	 *
	 * @param string $cmd  Command to execute.
	 * @param array<string, string> &$env Array of existing environment variables. Will be modified if any settings in command.
	 * @return string Command stripped of any environment variable settings.
	 */
	protected function procOpenOnWindows(string $cmd, array &$env): string
	{
		if ( str_contains( $cmd, '=' ) ) {
			while ( preg_match( '/^([A-Za-z_][A-Za-z0-9_]*)=("[^"]*"|[^ ]*) /', $cmd, $matches ) ) {
				$cmd = substr( $cmd, strlen( $matches[0] ) );

				if ( null === $env ) {
					$env = [];
				}

				$env[ $matches[1] ] = isset( $matches[2][0] ) && '"' === $matches[2][0] ? substr( $matches[2], 1, -1 ) : $matches[2];
			}
		}

		return $cmd;
	}

	/**
	 * Check if we're running in a Windows environment (cmd.exe).
	 *
	 * @return bool
	 */
	protected function isWindows(): bool
	{
		return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
	}

	/**
	 * Maybe prefix command string with "/usr/bin/env".
	 * Removes (if there) if Windows, adds (if not there) if not.
	 *
	 * @param string $command
	 * @return string
	 */
	protected function forceEnvOnUnixSystems(string $command ):string
	{
		$env_prefix = '/usr/bin/env ';
		$env_prefix_len = strlen( $env_prefix );

		if ( $this->isWindows() ) {
			if ( 0 === strncmp( $command, $env_prefix, $env_prefix_len ) ) {
				$command = substr( $command, $env_prefix_len );
			}
		} elseif ( 0 !== strncmp( $command, $env_prefix, $env_prefix_len ) ) {
			$command = $env_prefix . $command;
		}

		return $command;
	}
}