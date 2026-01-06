<?php
namespace Daedelus\Framework;

use PDO;
use Env\Env;
use Exception;
use PDOException;
use Dotenv\Dotenv;
use Composer\Composer;
use Composer\Script\Event;
use Composer\IO\IOInterface;

/**
 *
 *
 * @author Anthony Pauwels <anthony@straylightagency.be>
 * @package Daedelus
 */
class ComposerScripts
{
    /** @var Composer */
    protected static Composer $composer;

    /** @var IOInterface */
    protected static IOInterface $io;

    /** @var string */
    protected static string $root;

    /** @var string[] */
    protected static array $plugins = [
        'classic-editor',
        'codepress-admin-columns',
        'disable-comments',
        'duplicate-page',
        'post-types-order',
        'regenerate-thumbnails',
        'svg-support',
        'tinymce-advanced',
        'toolbar-publish-button',
    ];

    /**
     * @param Event $event
     * @return void
     * @throws Exception
     */
    public static function install(Event $event):void
    {
        self::$composer = $event->getComposer();
        self::$io = $event->getIO();

        $vendor = self::$composer->getConfig()->get('vendor-dir');

        self::$root = dirname( $vendor );

        require_once $vendor . '/autoload.php';

        if ( !env('WP_HOME') ) {
            copy('.env.example', '.env');
        }

        self::loadEnv();

        /** Setup basic information about installation */
        if ( self::$io->askConfirmation('<info>Setup .env ?</info> [<comment>Y,n</comment>]? ', true ) ) {
            self::setupEnv();

            /** Reload env variables */
            self::loadEnv();
        }

        /** We create a database from .env information */
        if ( self::$io->askConfirmation('<info>Create database ?</info> [<comment>Y,n</comment>]? ', true ) ) {
            self::createDatabase();
        }

        /** If WordPress core directories do not exist, we ask to download WordPress */
        if ( !file_exists( self::$root . '/public/wp-includes' ) && !file_exists( self::$root . '/public/wp-admin' ) ) {
            if ( self::$io->askConfirmation( '<info>Download WordPress ?</info> [<comment>Y,n</comment>]? ', true ) ) {
                /** Download WordPress without themes and plugins */
                self::$io->write( exec('wp core download --path=public --skip-content'));

                /** Delete the wp-content folder because we already have the content folder */
                self::$io->write( exec('rm -r ./public/wp-content'));
            }
        }

        $parsed_url = parse_url( env('APP_URL') );

        $home = $parsed_url['host'];

        $title = env('APP_NAME');
        $user = env('WP_USER');
        $pass = env('WP_PASSWORD');
        $email = env('WP_EMAIL');

        /** Ask to activate base plugins */
        if ( self::$io->askConfirmation( '<info>Install WordPress and activate plugins ?</info> [<comment>Y,n</comment>]? ', true ) ) {
            /** Activate WordPress */
            self::$io->write( exec('cd ./public && wp core install --url=' . $home . ' --title="' . $title . '" --admin_user=' . $user . ' --admin_password="' . $pass . '" --admin_email=' . $email));

            /** Install and activate plugins */
            foreach ( static::$plugins as $plugin_name ) {
                self::$io->write( exec('cd ./public && wp plugin install ' . $plugin_name . ' --activate'));
            }
        }

        /** Ask to activate the theme */
        if ( self::$io->askConfirmation( '<info>Setup theme and activate ?</info> [<comment>Y,n</comment>]? ', true ) ) {
            /** Build assets */
            self::$io->write( exec('cd ./public/content/themes/hyron') );

            /** Activate the theme */
            self::$io->write( exec('cd ./public && wp theme activate Hyron') );
        }

        $full_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

        $admin = $full_url . '/wp-admin';

        /** Display admin info */
        self::$io->write( '-' );
        self::$io->write( '<info>User:        </info>' . $user );
        self::$io->write( '<info>Password:    </info>' . $pass );
        self::$io->write( '<info>Email:       </info>' . $email );
        self::$io->write( '<info>Admin URL:   </info>' . $admin );

        @exec('open ' . $admin );
    }

    /**
     * @return void
     */
    protected static function loadEnv():void
    {
        $dotenv = Dotenv::createUnsafeMutable( self::$root, ['.env'], false );

        if ( file_exists( self::$root . '/.env') ) {
            $dotenv->load();
        }

        Env::$options = Env::CONVERT_BOOL | Env::CONVERT_NULL | Env::CONVERT_INT;
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function setupEnv():void
    {
        $defaults = [
            'WP_TITLE' => env('WP_TITLE') ?? 'Daedelus',
            'WP_HOME' => env('WP_HOME') ?? 'http://majestic.local.host/',
            'WP_USER' => env('WP_USER') ?? 'private',
            'WP_PASSWORD' => str_replace( ['"', "'"] , '', env('WP_PASSWORD') ?? self::generateRandomString( 16, false ) ),
            'WP_EMAIL' => env('WP_EMAIL') ?? 'private@anthonypauwels.be',
            'DB_NAME' => env('DB_NAME') ?? 'majestic',
        ];

        $title = self::$io->ask('What is the site title ? (default: "' . $defaults['WP_TITLE'] . '")', $defaults['WP_TITLE'] );
        $home = self::$io->ask('What is the site home URL ? (default: "' . $defaults['WP_HOME'] . '")', $defaults['WP_HOME'] );
        $user = self::$io->ask('What is the site username ? (default: "' . $defaults['WP_USER'] . '")', $defaults['WP_USER'] );
        $pass = self::$io->ask('What is the site password ? (default: "' . $defaults['WP_PASSWORD'] . '")', $defaults['WP_PASSWORD'] );
        $email = self::$io->ask('What is the site email ? (default: "' . $defaults['WP_EMAIL'] . '")', $defaults['WP_EMAIL'] );
        $database = self::$io->ask('What is the database name ? (default: "' . $defaults['DB_NAME'] . '")', $defaults['DB_NAME'] );

        $parsed_url = parse_url( $home );

        $home = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/';

        $variables = [
            'WP_TITLE' => $title,
            'WP_HOME' => $home,
            'WP_USER' => $user,
            'WP_PASSWORD' => $pass,
            'WP_EMAIL' => $email,
            'DB_NAME' => $database,
        ];

        $file = file(self::$root . '/.env');

        /** replace line if exists in file */
        $file = array_map( function ( $line ) use ( &$variables ) {
            foreach ( $variables as $key => $value ) {
                if ( stristr( $line, $key ) ) {
                    unset( $variables[ $key ] );

                    return "$key='$value'\n";
                }
            }

            return $line;
        }, $file );

        file_put_contents(self::$root . '/.env', implode('', $file));

        if ( count( $variables ) ) {
            file_put_contents(self::$root . '/.env', "\n", FILE_APPEND | LOCK_EX);

            /** add missing variables */
            foreach ( $variables as $key => $value ) {
                file_put_contents(self::$root . '/.env', "\n$key='$value'", FILE_APPEND | LOCK_EX);
            }
        }

        if ( !env('AUTH_KEY') ) {
            self::generateSalts();
        }
    }

    /**
     * @return void
     */
    protected static function createDatabase():void
    {
        $db_name = env('DB_NAME') ?? 'majestic';
        $db_host = env('DB_HOST') ?? 'localhost';
        $db_user = env('DB_USER') ?? 'root';
        $db_password = env('DB_PASSWORD') ?? 'root';
        $db_charset = env('DB_CHARSET') ?? 'utf8';

        try {
            $pdo = new PDO( "mysql:host=$db_host", $db_user, $db_password );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET $db_charset")
            or die( print_r( $pdo->errorInfo(), true ) );

        } catch ( PDOException $e ) {
            self::$io->write( "DB ERROR: {$e->getMessage()}" );
        }
    }

    /**
     * Ask to generate salts then do it.
     *
     * @return void
     * @throws Exception
     */
    protected static function generateSalts():void
    {
        if ( !self::$io->isInteractive() ) {
            $generate_salts = self::$composer->getConfig()->get('generate-salts');
        } else {
            $generate_salts = self::$io->askConfirmation('<info>Generate salts and append to .env file?</info> [<comment>Y,n</comment>]? ', true);
        }

        if ( $generate_salts ) {
            $salts_keys = [
                'AUTH_KEY',
                'SECURE_AUTH_KEY',
                'LOGGED_IN_KEY',
                'NONCE_KEY',
                'AUTH_SALT',
                'SECURE_AUTH_SALT',
                'LOGGED_IN_SALT',
                'NONCE_SALT',
            ];

            $salts = array_map( function ( $key ) {
                return sprintf("%s='%s'", $key, self::generateRandomString() );
            }, $salts_keys );

            file_put_contents(self::$root . '/.env', "\n\n" . implode("\n", $salts) . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Slightly modified/simpler version of wp_generate_password
     * https://github.com/WordPress/WordPress/blob/cd8cedc40d768e9e1d5a5f5a08f1bd677c804cb9/wp-includes/pluggable.php#L1575
     *
     * @param int $length
     * @param bool $special_chars
     * @return string
     * @throws Exception
     */
    private static function generateRandomString(int $length = 64, bool $special_chars = true):string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        if ( $special_chars ) {
            $chars .= '!@#$%^&*()';
            $chars .= '-_ []{}<>~`+=,.;:/?|';
        }

        $string = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $string .= substr( $chars, random_int( 0, mb_strlen( $chars ) - 1), 1 );
        }

        return $string;
    }
}