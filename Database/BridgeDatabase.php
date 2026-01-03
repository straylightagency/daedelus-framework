<?php

namespace Daedelus\Foundation\Database;

use Daedelus\Support\Filters;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDO;
use WP_Error;

/**
 * Alternative to WordPress native DB class "WPDB" using Laravel's Database package
 */
class BridgeDatabase
{
	/** @var Connection */
	protected Connection $connection;

	/** @var int */
	protected int $blogid = 0;

	/** @var string  */
	protected string $prefix = 'wp_';

	/** @var string */
	protected string $base_prefix = '';

	/** @var string[] */
	protected array $incompatible_modes = [
		'NO_ZERO_DATE',
		'ONLY_FULL_GROUP_BY',
		'STRICT_TRANS_TABLES',
		'STRICT_ALL_TABLES',
		'TRADITIONAL',
		'ANSI',
	];

	/** @var string[] */
	protected array $tables = [
		'posts',
		'comments',
		'links',
		'options',
		'postmeta',
		'terms',
		'term_taxonomy',
		'term_relationships',
		'termmeta',
		'commentmeta',
	];

	/** @var string[] */
	protected array $global_tables = [ 'users', 'usermeta' ];

	/** @var string[] */
	protected array $old_tables = [ 'categories', 'post2cat', 'link2cat' ];

	/** @var string[] */
	protected array $ms_global_tables = [
		'blogs',
		'blogmeta',
		'signups',
		'site',
		'sitemeta',
		'registration_log',
	];

	/** @var string[] */
	protected array $old_ms_global_tables = [ 'sitecategories' ];

	/** @var array */
	protected array $col_meta = [];

	/** @var array|string[] */
	protected array $table_charset = [];

    /** @var bool */
    protected bool $suppressErrors = false;

	/** @var int */
	public int $insert_id = 0;

	/** @var bool */
	public readonly bool $is_mysql;

	/** @var string */
	public readonly string $charset;

	/** @var string */
	public readonly string $collate;

	/** @var string */
	public readonly string $dbuser;

	/** @var string */
	public readonly string $dbname;

	/** @var string */
	public readonly string $dbhost;

	/**
	 * Database constructor.
	 *
	 * Define if it's a MySQL connection and set the SQL mode.
	 */
	public function __construct()
	{
		$this->connection = DB::connection();

		$config = $this->connection->getConfig();

		$this->dbuser = data_get( $config, 'username' );
		$this->dbname = data_get( $config, 'database' );
		$this->dbhost = data_get( $config, 'host' );

		$this->is_mysql = $this->connection->getPdo()->getAttribute( PDO::ATTR_DRIVER_NAME ) === 'mysql';

		$this->set_sql_mode();

		$this->charset = data_get( $config, 'charset', fn () => $this->connection->scalar( 'SELECT CHARSET("")' ) );

		$this->collate = data_get( $config, 'collation', fn () => $this->connection->scalar( 'SELECT COLLATION("")' ) );
	}

	/**
	 * @param string $prefix
	 * @param bool $set_table_names
	 *
	 * @return string|WP_Error
	 */
	public function set_prefix(string $prefix, bool $set_table_names = true): string|WP_Error
	{
		if ( preg_match( '|[^a-z0-9_]|i', $prefix ) ) {
			return new WP_Error( 'invalid_db_prefix', 'Invalid database prefix' );
		}

		$old_prefix = is_multisite() ? '' : $prefix;

		if ( isset( $this->base_prefix ) ) {
			$old_prefix = $this->base_prefix;
		}

		$this->base_prefix = $prefix;

		if ( $set_table_names ) {
			foreach ( $this->tables( 'global' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			if ( is_multisite() && empty( $this->blogid ) ) {
				return $old_prefix;
			}

			$this->prefix = $this->get_blog_prefix();

			foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}

			foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
				$this->$table = $prefixed_table;
			}
		}

		return $old_prefix;
	}

	/**
	 * @param array $modes
	 *
	 * @return void
	 */
	public function set_sql_mode(array $modes = []):void
	{
		if ( $this->is_mysql ) {
			$incompatible_modes = (array) Filters::apply( 'incompatible_sql_modes', $this->incompatible_modes );

			$modes = array_merge( $modes, $this->connection->getConfig( 'modes' ) ?? [] );
			$modes = array_filter( $modes, fn ($mode) => !in_array( $mode, $incompatible_modes, true ) );
			$modes = array_values( $modes );

			$this->query( "SET sql_mode = '" . implode( ',', $modes ) . "'" );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return string|object
	 */
	public function __get(string $key): string|object
	{
		$allowed = array_merge( $this->tables, $this->old_tables, $this->global_tables, $this->ms_global_tables, $this->old_ms_global_tables );

		if ( in_array( $key, $allowed ) ) {
			return $this->prefix . $key;
		}

		if ( $key === 'prefix' ) {
			return $this->prefix;
		}

		if ( $key === 'base_prefix' ) {
			return $this->base_prefix;
		}

		if ( $key === 'dbh' ) {
			return $this;
		}

		if ( $key === 'client_info' ) {
			return $this->db_client_info();
		}

		return '';
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function __isset(string $key):bool
	{
		$allowed = array_merge( $this->tables, $this->old_tables, $this->global_tables, $this->ms_global_tables, $this->old_ms_global_tables );

		return in_array( $key, $allowed );
	}

	/**
	 * @param string $scope
	 * @param bool $prefix
	 * @param int $blog_id
	 *
	 * @return array|string|string[]
	 */
	public function tables(string $scope = 'all', bool $prefix = true, int $blog_id = 0): array|string {
		switch ( $scope ) {
			case 'all':
				$tables = array_merge( $this->global_tables, $this->tables );
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'blog':
				$tables = $this->tables;
				break;
			case 'global':
				$tables = $this->global_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}
				break;
			case 'ms_global':
				$tables = $this->ms_global_tables;
				break;
			case 'old':
				$tables = $this->old_tables;
				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->old_ms_global_tables );
				}
				break;
			default:
				return [];
		}

		if ( $prefix ) {
			if ( ! $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_prefix = $this->get_blog_prefix( $blog_id );
			$base_prefix = $this->base_prefix;
			$global_tables = array_merge( $this->global_tables, $this->ms_global_tables );

			foreach ( $tables as $k => $table ) {
				if ( in_array( $table, $global_tables, true ) ) {
					$tables[ $table ] = $base_prefix . $table;
				} else {
					$tables[ $table ] = $blog_prefix . $table;
				}
				unset( $tables[ $k ] );
			}

			if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) ) {
				$tables['users'] = CUSTOM_USER_TABLE;
			}

			if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) ) {
				$tables['usermeta'] = CUSTOM_USER_META_TABLE;
			}
		}

		return $tables;
	}

    /**
     * @param bool $suppress
     * @return bool
     */
	public function suppress_errors(bool $suppress = true): bool
    {
        $errors = $this->suppressErrors;

        $this->suppressErrors = $suppress;

        return $errors;
	}

	/**
	 * @param string $query
	 *
	 * @return int|bool
	 */
	public function query(string $query):int|bool
	{
		$query = Filters::apply( 'query', $query );

		if ( preg_match( '/^\s*(create|truncate|drop)\s/i', $query ) ) {
			$this->connection->statement( $query );

			return $this->connection->hasModifiedRecords();
		}

		if ( preg_match( '/^\s*(alter)\s/i', $query ) ) {
			try {
				$this->connection->statement( $query );

				return $this->connection->hasModifiedRecords();
			} catch (QueryException $exception) {
				/**
				 * Something, alter queries can throw an exception during database update.
				 */
				return false;
			}
		}

		if ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
            try {
                $result = $this->connection->affectingStatement( $query );
            } catch (QueryException $exception) {
                if ( !$this->suppressErrors ) {
                    throw $exception;
                }
            }

			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = $this->connection->getPdo()->lastInsertId();
			}

			return $result;
		}

		$rows = $this->connection->select( $query );

		return count( $rows );
	}

	/**
	 * @param string|null $query
	 * @param string $output
	 *
	 * @return object|array|null
	 * @throws Exception
	 */
	public function get_results(string $query = null, string $output = OBJECT): object|array|null
	{
		if ( is_null( $query ) ) {
			return null;
		}

        try {
            $results = $this->connection->select( $query );
        } catch (QueryException $exception) {
            if ( !$this->suppressErrors ) {
                throw $exception;
            }
        }

		if ( empty( $results ) ) {
			return [];
		}

		if ( OBJECT === $output ) {
			return $results;
		}

		if ( OBJECT_K === $output ) {
			$new_array = [];

			foreach ( $results as $row ) {
				$var_by_ref = (array) $row;
				$key = array_shift( $var_by_ref );
				if ( ! isset( $new_array[ $key ] ) ) {
					$new_array[ $key ] = $row;
				}
			}

			return $new_array;
		}

		if ( ARRAY_A === $output ) {
			return array_map( fn ($item) => (array) $item, $results );
		}

		if ( ARRAY_N === $output ) {
			return array_values( array_map( fn ($item) => array_values( (array) $item ), $results ) );
		}

		throw new Exception( ' $db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N' );
	}

	/**
	 * @param string|null $query
	 * @param string $output
	 * @param int $y
	 *
	 * @return array|object|null
	 * @throws Exception
	 */
	public function get_row(string $query = null, string $output = OBJECT, int $y = 0): array|object|null
	{
        try {
            $results = $this->connection->select( $query );
        } catch (QueryException $exception) {
            if ( !$this->suppressErrors ) {
                throw $exception;
            }
        }

		if ( empty( $results[ $y ] ) ) {
			return null;
		}

		$results = (array) $results[ $y ];

		if ( $output === OBJECT ) {
			return (object) $results;
		}

		if ( ARRAY_A === $output ) {
			return array_map( fn ($item) => (array) $item, $results );
		}

		if ( ARRAY_N === $output ) {
			return array_values( array_map( fn ($item) => (array) $item, $results ) );
		}

		throw new Exception( '$wpdb->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N' );
	}

	/**
	 * @param $query
	 * @param int $x
	 *
	 * @return array
	 */
	public function get_col( $query = null, int $x = 0 ): array
	{
		if ( is_null( $query ) ) {
			return [];
		}

        try {
            $results = $this->connection->select( $query );
        } catch (QueryException $exception) {
            if ( !$this->suppressErrors ) {
                throw $exception;
            }
        }

		if ( empty( $results ) ) {
			return [];
		}

		$new_array = [];

		for ( $i = 0, $j = count( $results ); $i < $j; $i++ ) {
			$values = array_values( (array) $results[ $i ] );
			$new_array[ $i ] = $values[ $x ];
		}

		return $new_array;
	}

	/**
	 * @param string $table
	 * @param string $column
	 *
	 * @return string
	 * @throws Exception
	 */
	public function get_col_charset(string $table, string $column): string
	{
		/**
		 * Filters the column charset value before the DB is checked.
		 *
		 * Passing a non-null value to the filter will short-circuit
		 * checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string|null|false|WP_Error $charset The character set to use. Default null.
		 * @param string                     $table   The name of the table being checked.
		 * @param string                     $column  The name of the column being checked.
		 */
		$charset = Filters::apply( 'pre_get_col_charset', null, $table, $column );
		if ( null !== $charset ) {
			return $charset;
		}

		// Skip this entirely if this isn't a MySQL database.
		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		$tablekey  = strtolower( $table );
		$columnkey = strtolower( $column );

		if ( empty( $this->table_charset[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		// If still no column information, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// If this column doesn't exist, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// Return false when it's not a string column.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ]->Collation ) ) {
			return false;
		}

		list( $charset ) = explode( '_', $this->col_meta[ $tablekey ][ $columnkey ]->Collation );

		return $charset;
	}

    /**
     * @param null $query
     * @param int $x
     * @param int $y
     * @return mixed|void|null
     */
	public function get_var($query = null, int $x = 0, int $y = 0 )
	{
		if ( is_null( $query ) ) {
			return null;
		}

        try {
            return $this->connection->scalar( $query );
        } catch (QueryException $exception) {
            if ( !$this->suppressErrors ) {
                throw $exception;
            }
        }
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param array|string|null $format
	 *
	 * @return int|false
	 * @throws Exception
	 */
	public function insert(string $table, array $data, array|string|null $format = null ): int|false
	{
		if ( empty( $data ) ) {
			return false;
		}

		$this->connection->table( $table )->insert( $data );

		$this->insert_id = $this->connection->getPdo()->lastInsertId();

		return $this->connection->hasModifiedRecords();
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param array|string|null $format
	 *
	 * @return int|false
	 * @throws Exception
	 */
	public function replace(string $table, array $data, array|string|null $format = null): int|false
	{
		if ( empty( $data ) ) {
			return false;
		}

		$query = $this->connection->table( $table );

		$driver = $query->connection->getDriverName();

		if ( $driver === 'mysql' || $driver === 'sqlite' ) {
			if ( ! is_array( reset($data ) ) ) {
				$data = [ $data ];
			} else {
				$data = Arr::mapWithKeys( $data, function ($value, $key) {
					ksort($value);
					return [ $key => $value ];
				} );
			}

			$sql = $query->grammar->compileInsert( $query, $data );
			$sql = preg_replace('/^\s*insert\s+into\b/i','replace into', $sql );

			$query->connection->insert(
				$sql,
				$query->cleanBindings( Arr::flatten( $data,1 ) )
			);

			$this->insert_id = $this->connection->getPdo()->lastInsertId();
		}

		return $this->connection->hasModifiedRecords();
	}

	/**
	 * @param $table
	 * @param $data
	 * @param $where
	 * @param $format
	 * @param $where_format
	 *
	 * @return bool
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ): bool
	{
		return $this->connection->table( $table )->where( $where )->update( $data ) > 0;
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param array|string|null $where_format
	 *
	 * @return bool
	 */
	public function delete(string $table, array $where, array|string $where_format = null ): bool
	{
		return $this->connection->table( $table )->where( $where )->delete() > 0;
	}

	/**
	 * @param string|null $query
	 * @param ...$args
	 *
	 * @return string
	 */
	public function prepare(?string $query, ...$args): string
	{
		if ( is_null( $query ) ) {
			return '';
		}

		if ( ! str_contains( $query, '%' ) ) {
			wp_load_translations_early();
			_doing_it_wrong(
				'wpdb::prepare',
				sprintf(
				/* translators: %s: wpdb::prepare() */
					__( 'The query argument of %s must have a placeholder.' ),
					'wpdb::prepare()'
				),
				'3.9.0'
			);
		}

		/*
		 * Specify the formatting allowed in a placeholder. The following are allowed:
		 *
		 * - Sign specifier, e.g. $+d
		 * - Numbered placeholders, e.g. %1$s
		 * - Padding specifier, including custom padding characters, e.g. %05s, %'#5s
		 * - Alignment specifier, e.g. %05-s
		 * - Precision specifier, e.g. %.2f
		 */
		$allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

		/*
		 * If a %s placeholder already has quotes around it, removing the existing quotes
		 * and re-inserting them ensures the quotes are consistent.
		 *
		 * For backward compatibility, this is only applied to %s, and not to placeholders like %1$s,
		 * which are frequently used in the middle of longer strings, or as table name placeholders.
		 */

		$query = str_replace(
			[
				"'%s'", '"%s"'
			],
			'%s',
			$query
		);

		// Escape any unescaped percents (i.e. anything unrecognised).
		$query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdfFi]))/", '%%\\1', $query );

		// Extract placeholders from the query.
		$split_query = preg_split( "/(^|[^%]|(?:%%)+)(%(?:$allowed_format)?[sdfFi])/", $query, -1, PREG_SPLIT_DELIM_CAPTURE );

		$split_query_count = count( $split_query );

		/*
		 * Split always returns with 1 value before the first placeholder (even with $query = "%s"),
		 * then 3 additional values per placeholder.
		 */
		$placeholder_count = ( ( $split_query_count - 1 ) / 3 );

		// If args were passed as an array, as in vsprintf(), move them up.
		$passed_as_array = ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) );
		if ( $passed_as_array ) {
			$args = $args[0];
		}

		$new_query = '';
		$key = 2; // Keys 0 and 1 in $split_query contain values before the first placeholder.
		$arg_id = 0;
		$arg_identifiers = $arg_strings = [];

		while ( $key < $split_query_count ) {
			$placeholder = $split_query[ $key ];

			$format = substr( $placeholder, 1, -1 );
			$type   = substr( $placeholder, -1 );

			// Force floats to be locale-unaware.
			if ( 'f' === $type ) {
				$type        = 'F';
				$placeholder = '%' . $format . $type;
			}

			if ( 'i' === $type ) {
				$placeholder = '`%' . $format . 's`';
				// Using a simple strpos() due to previous checking (e.g. $allowed_format).
				$argnum_pos = strpos( $format, '$' );

				if ( false !== $argnum_pos ) {
					// sprintf() argnum starts at 1, $arg_id from 0.
					$arg_identifiers[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
				} else {
					$arg_identifiers[] = $arg_id;
				}
			} elseif ( 'd' !== $type && 'F' !== $type ) {
				/*
				 * i.e. ( 's' === $type ), where 'd' and 'F' keeps $placeholder unchanged,
				 * and we ensure string escaping is used as a safe default (e.g. even if 'x').
				 */
				$argnum_pos = strpos( $format, '$' );

				if ( false !== $argnum_pos ) {
					$arg_strings[] = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
				} else {
					$arg_strings[] = $arg_id;
				}

				/*
				 * Unquoted strings for backward compatibility (dangerous).
				 * First, "numbered or formatted string placeholders (eg, %1$s, %5s)".
				 * Second, if "%s" has a "%" before it, even if it's unrelated (e.g. "LIKE '%%%s%%'").
				 */
				if ( ( '' === $format && ! str_ends_with( $split_query[ $key - 1 ], '%' ) )
				) {
					$placeholder = "'%" . $format . "s'";
				}
			}

			// Glue (-2), any leading characters (-1), then the new $placeholder.
			$new_query .= $split_query[ $key - 2 ] . $split_query[ $key - 1 ] . $placeholder;

			$key += 3;
			++$arg_id;
		}

		// Replace $query; and add remaining $query characters, or index 0 if there were no placeholders.
		$query = $new_query . $split_query[ $key - 2 ];

		$dual_use = array_intersect( $arg_identifiers, $arg_strings );

		if ( count( $dual_use ) > 0 ) {
			wp_load_translations_early();

			$used_placeholders = [];

			$key    = 2;
			$arg_id = 0;
			// Parse again (only used when there is an error).
			while ( $key < $split_query_count ) {
				$placeholder = $split_query[ $key ];

				$format = substr( $placeholder, 1, -1 );

				$argnum_pos = strpos( $format, '$' );

				if ( false !== $argnum_pos ) {
					$arg_pos = ( ( (int) substr( $format, 0, $argnum_pos ) ) - 1 );
				} else {
					$arg_pos = $arg_id;
				}

				$used_placeholders[ $arg_pos ][] = $placeholder;

				$key += 3;
				++$arg_id;
			}

			$conflicts = [];
			foreach ( $dual_use as $arg_pos ) {
				$conflicts[] = implode( ' and ', $used_placeholders[ $arg_pos ] );
			}

			_doing_it_wrong(
				'wpdb::prepare',
				sprintf(
				/* translators: %s: A list of placeholders found to be a problem. */
					__( 'Arguments cannot be prepared as both an Identifier and Value. Found the following conflicts: %s' ),
					implode( ', ', $conflicts )
				),
				'6.2.0'
			);

			return '';
		}

		$args_count = count( $args );

		if ( $args_count !== $placeholder_count ) {
			wp_load_translations_early();

			if ( 1 === $placeholder_count && $passed_as_array ) {
				/*
				 * If the passed query only expected one argument,
				 * but the wrong number of arguments was sent as an array, bail.
				 */
				_doing_it_wrong(
					'wpdb::prepare',
					__( 'The query only expected one placeholder, but an array of multiple placeholders was sent.' ),
					'4.9.0'
				);

				return '';
			} else {
				/*
				 * If we don't have the right number of placeholders,
				 * but they were passed as individual arguments,
				 * or we were expecting multiple arguments in an array, throw a warning.
				 */
				_doing_it_wrong(
					'wpdb::prepare',
					sprintf(
					/* translators: 1: Number of placeholders, 2: Number of arguments passed. */
						__( 'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).' ),
						$placeholder_count,
						$args_count
					),
					'4.8.3'
				);

				/*
				 * If we don't have enough arguments to match the placeholders,
				 * return an empty string to avoid a fatal error on PHP 8.
				 */
				if ( $args_count < $placeholder_count ) {
					$max_numbered_placeholder = 0;

					for ( $i = 2, $l = $split_query_count; $i < $l; $i += 3 ) {
						// Assume a leading number is for a numbered placeholder, e.g. '%3$s'.
						$argnum = (int) substr( $split_query[ $i ], 1 );

						if ( $max_numbered_placeholder < $argnum ) {
							$max_numbered_placeholder = $argnum;
						}
					}

					if ( ! $max_numbered_placeholder || $args_count < $max_numbered_placeholder ) {
						return '';
					}
				}
			}
		}

		$args_escaped = [];

		foreach ( $args as $i => $value ) {
			if ( in_array( $i, $arg_identifiers, true ) ) {
				$args_escaped[] = str_replace( '`', '``', $value );
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$args_escaped[] = $value;
			} else {
				if ( ! is_scalar( $value ) && ! is_null( $value ) ) {
					wp_load_translations_early();
					_doing_it_wrong(
						'wpdb::prepare',
						sprintf(
						/* translators: %s: Value type. */
							__( 'Unsupported value type (%s).' ),
							gettype( $value )
						),
						'4.8.2'
					);

					// Preserving old behavior, where values are escaped as strings.
					$value = '';
				}

				$args_escaped[] = $this->real_escape( $value );
			}
		}

		$query = vsprintf( $query, $args_escaped );

		return $this->add_placeholder_escape( $query );
	}

	/**
	 * Adds a placeholder escape string, to escape anything that resembles a printf() placeholder.
	 *
	 * @since 4.8.3
	 *
	 * @param string $query The query to escape.
	 * @return string The query with the placeholder escape string inserted where necessary.
	 */
	public function add_placeholder_escape(string $query): string
	{
		/*
		 * To prevent returning anything that even vaguely resembles a placeholder,
		 * we clobber every % we can find.
		 */
		return str_replace( '%', $this->placeholder_escape(), $query );
	}

	/**
	 * Generates and returns a placeholder escape string for use in queries returned by ::prepare().
	 *
	 * @since 4.8.3
	 *
	 * @return string String to escape placeholders.
	 */
	public function placeholder_escape(): string
	{
		static $placeholder;

		if ( ! $placeholder ) {
			// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
			// Old WP installs may not have AUTH_SALT defined.
			$salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : (string) rand();

			$placeholder = '{' . hash_hmac( $algo, uniqid( $salt, true ), $salt ) . '}';
		}

		/*
		 * Add the filter to remove the placeholder escaper. Uses priority 0, so that anything
		 * else attached to this filter will receive the query with the placeholder string removed.
		 */
		if ( false === Filters::has( 'query', [$this, 'remove_placeholder_escape']) ) {
			Filters::add( 'query', [$this, 'remove_placeholder_escape'], 0 );
		}

		return $placeholder;
	}

	/**
	 * Removes the placeholder escape strings from a query.
	 *
	 * @since 4.8.3
	 *
	 * @param string $query The query from which the placeholder will be removed.
	 * @return string The query with the placeholder removed.
	 */
	public function remove_placeholder_escape(string $query): string {
		return str_replace( $this->placeholder_escape(), '%', $query );
	}

	/**
	 * @param string|array $data
	 *
	 * @return string|array
	 */
	public function _escape(string|array $data): string|array
	{
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->_escape( $v );
				} else {
					$data[ $k ] = $this->real_escape( $v );
				}
			}
		} else {
			$data = $this->real_escape( $data );
		}

		return $data;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function real_escape(mixed $value): string
	{
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return $this->add_placeholder_escape( $value );
	}

	/**
	 * Gets blog prefix.
	 *
	 * @param int|null $blog_id Optional. Blog ID to retrieve the table prefix for.
	 *                     Defaults to the current blog ID.
	 *
	 * @return string Blog prefix.
	 */
	public function get_blog_prefix(?int $blog_id = null): string
	{
		if ( is_multisite() ) {
			if ( null === $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_id = (int) $blog_id;

			if ( defined( 'MULTISITE' ) && ( 0 === $blog_id || 1 === $blog_id ) ) {
				return $this->base_prefix;
			} else {
				return $this->base_prefix . $blog_id . '_';
			}
		} else {
			return $this->base_prefix;
		}
	}

	/**
	 * Retrieves the character set for the given table.
	 *
	 * @param string $table Table name.
	 *
	 * @return WP_Error|false|string Table character set, WP_Error object if it couldn't be found.
	 * @throws Exception
	 */
	protected function get_table_charset(string $table): WP_Error|false|string
	{
		/**
		 * Filters the table charset value before the DB is checked.
		 *
		 * Returning a non-null value from the filter will effectively short-circuit
		 * checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string|WP_Error|null $charset The character set to use, WP_Error object
		 *                                      if it couldn't be found. Default null.
		 * @param string               $table   The name of the table being checked.
		 */
		$charset = Filters::apply( 'pre_get_table_charset', null, $table );

		if ( null !== $charset ) {
			return $charset;
		}

		$tablekey = strtolower( $table );

		if ( isset( $this->table_charset[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		$charsets = $columns = [];

		$table_parts = explode( '.', $table );
		$table = '`' . implode( '`.`', $table_parts ) . '`';
		$results = $this->get_results( "SHOW FULL COLUMNS FROM $table" );

		if ( ! $results ) {
			return new WP_Error( 'wpdb_get_table_charset_failure', __( 'Could not retrieve table charset.' ) );
		}

		foreach ( $results as $column ) {
			$columns[ strtolower( $column->Field ) ] = $column;
		}

		$this->col_meta[ $tablekey ] = $columns;

		foreach ( $columns as $column ) {
			if ( ! empty( $column->Collation ) ) {
				list( $charset ) = explode( '_', $column->Collation );

				$charsets[ strtolower( $charset ) ] = true;
			}

			list( $type ) = explode( '(', $column->Type );

			// A binary/blob means the whole query gets treated like this.
			if ( in_array( strtoupper( $type ), [ 'BINARY', 'VARBINARY', 'TINYBLOB', 'MEDIUMBLOB', 'BLOB', 'LONGBLOB' ], true ) ) {
				$this->table_charset[ $tablekey ] = 'binary';
				return 'binary';
			}
		}

		// utf8mb3 is an alias for utf8.
		if ( isset( $charsets['utf8mb3'] ) ) {
			$charsets['utf8'] = true;
			unset( $charsets['utf8mb3'] );
		}

		// Check if we have more than one charset in play.
		$count = count( $charsets );
		if ( 1 === $count ) {
			$charset = key( $charsets );
		} elseif ( 0 === $count ) {
			// No charsets, assume this table can store whatever.
			$charset = false;
		} else {
			// More than one charset. Remove latin1 if present and recalculate.
			unset( $charsets['latin1'] );
			$count = count( $charsets );
			if ( 1 === $count ) {
				// Only one charset (besides latin1).
				$charset = key( $charsets );
			} elseif ( 2 === $count && isset( $charsets['utf8'], $charsets['utf8mb4'] ) ) {
				// Two charsets, but they're utf8 and utf8mb4, use utf8.
				$charset = 'utf8';
			} else {
				// Two mixed character sets. ascii.
				$charset = 'ascii';
			}
		}

		$this->table_charset[ $tablekey ] = $charset;

		return $charset;
	}

	/**
	 * Retrieves the maximum string length allowed in a given column.
	 *
	 * The length may either be specified as a byte length or a character length.
	 *
	 * @param string $table Table name.
	 * @param string $column Column name.
	 *
	 * @return array|false|WP_Error {
	 *     Array of column length information, false if the column has no length (for
	 *     example, numeric column), WP_Error object if there was an error.
	 *
	 * @type string $type One of 'byte' or 'char'.
	 * @type int $length The column length.
	 * @throws Exception
	 */
	public function get_col_length(string $table, string $column): WP_Error|false|array
	{
		$tablekey = strtolower( $table );
		$columnkey = strtolower( $column );

		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return false;
		}

		$typeinfo = explode( '(', $this->col_meta[ $tablekey ][ $columnkey ]->Type );

		$type = strtolower( $typeinfo[0] );
		if ( ! empty( $typeinfo[1] ) ) {
			$length = trim( $typeinfo[1], ')' );
		} else {
			$length = false;
		}

		return match ( $type ) {
			'char', 'varchar' => [
				'type'   => 'char',
				'length' => (int) $length,
			],
			'binary', 'varbinary' => [
				'type'   => 'byte',
				'length' => (int) $length,
			],
			'tinyblob', 'tinytext' => [
				'type'   => 'byte',
				'length' => 255,        // 2^8 - 1
			],
			'blob', 'text' => [
				'type'   => 'byte',
				'length' => 65535,      // 2^16 - 1
			],
			'mediumblob', 'mediumtext' => [
				'type'   => 'byte',
				'length' => 16777215,   // 2^24 - 1
			],
			'longblob', 'longtext' => [
				'type'   => 'byte',
				'length' => 4294967295, // 2^32 - 1
			],
			default => false,
		};
	}

	/**
	 * Strips any invalid characters from the string for a given table and column.
	 *
	 * @param string $table Table name.
	 * @param string $column Column name.
	 * @param string $value The text to check.
	 *
	 * @return string|WP_Error The converted string, or a WP_Error object if the conversion fails.
	 *
	 * @throws Exception
	 */
	public function strip_invalid_text_for_column(string $table, string $column, string $value): WP_Error|string
	{
		$charset = $this->get_col_charset( $table, $column );

		if ( ! $charset ) {
			// Not a string column.
			return $value;
		}

		if ( is_wp_error( $charset ) ) {
			// Bail on real errors.
			return $charset;
		}

		$data = [
			$column => [
				'value'   => $value,
				'charset' => $charset,
				'length'  => $this->get_col_length( $table, $column ),
			],
		];

		$data = $this->strip_invalid_text( $data );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data[ $column ]['value'];
	}

	/**
	 * Determines whether MySQL database is at least the required minimum version.
	 *
	 * @global string $required_mysql_version The required MySQL version string.
	 * @return ?WP_Error
	 */
	public function check_database_version():?WP_Error
	{
		global $required_mysql_version;
		$wp_version = wp_get_wp_version();

		// Make sure the server has the required MySQL version.
		if ( version_compare( $this->db_version(), $required_mysql_version, '<' ) ) {
			/* translators: 1: WordPress version number, 2: Minimum required MySQL version number. */
			return new WP_Error( 'database_version', sprintf( __( '<strong>Error:</strong> WordPress %1$s requires MySQL %2$s or higher' ), $wp_version, $required_mysql_version ) );
		}

		return null;
	}

	/**
	 * Retrieves the database character collate.
	 *
	 * @return string The database character collate.
	 */
	public function get_charset_collate(): string
	{
		$charset_collate = '';

		if ( ! empty( $this->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $this->charset";
		}
		if ( ! empty( $this->collate ) ) {
			$charset_collate .= " COLLATE $this->collate";
		}

		return $charset_collate;
	}

	/**
	 * Determines whether the database or WPDB supports a particular feature.
	 *
	 * Capability sniffs for the database server and current version of WPDB.
	 *
	 * Database sniffs are based on the version of MySQL the site is using.
	 *
	 * WPDB sniffs are added as new features are introduced to allow theme and plugin
	 * developers to determine feature support. This is to account for drop-ins which may
	 * introduce feature support at a different time to WordPress.
	 *
	 * @see wpdb::db_version()
	 *
	 * @param string $db_cap The feature to check for. Accepts 'collation', 'group_concat',
	 *                       'subqueries', 'set_charset', 'utf8mb4', 'utf8mb4_520',
	 *                       or 'identifier_placeholders'.
	 * @return bool True when the database feature is supported, false otherwise.
	 */
	public function has_cap(string $db_cap): bool
	{
		$db_version = $this->db_version();
		$db_server_info = $this->db_server_info();

		/*
		 * Account for MariaDB version being prefixed with '5.5.5-' on older PHP versions.
		 */
		if ( '5.5.5' === $db_version
		     && str_contains( $db_server_info, 'MariaDB' )
		     && PHP_VERSION_ID < 80016 // PHP 8.0.15 or older.
		) {
			// Strip the '5.5.5-' prefix and set the version to the correct value.
			$db_server_info = preg_replace( '/^5\.5\.5-(.*)/', '$1', $db_server_info );
			$db_version = preg_replace( '/[^0-9.].*/', '', $db_server_info );
		}

		return match ( strtolower( $db_cap ) ) {
			'collation', 'group_concat', 'subqueries' => version_compare( $db_version, '4.1', '>=' ),
			'set_charset' => version_compare( $db_version, '5.0.7', '>=' ),
			'utf8mb4', 'identifier_placeholders' => true,
			'utf8mb4_520' => version_compare( $db_version, '5.6', '>=' ),
			default => false,
		};

	}

	/**
	 * Retrieves the database server version.
	 *
	 * @return string Version number on success, null on failure.
	 */
	public function db_version(): string
	{
		return preg_replace( '/[^0-9.].*/', '', $this->db_server_info() );
	}

	/**
	 * Returns the version of the database server.
	 *
	 * @return string Server version as a string.
	 */
	public function db_server_info(): string
	{
		return $this->connection->getServerVersion();
	}

	/**
	 * Returns the version of the client server.
	 *
	 * @return string Server version as a string.
	 */
	public function db_client_info(): string
	{
		return $this->connection->getPdo()->getAttribute( PDO::ATTR_CLIENT_VERSION );
	}

	/**
	 * First half of escaping for `LIKE` special characters `%` and `_` before preparing for SQL.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function esc_like(string $text ): string
	{
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Strips any invalid characters based on value/charset pairs.
	 *
	 * @param array $data Array of value arrays. Each value array has the keys 'value', 'charset', and 'length'.
	 *                    An optional 'ascii' key can be set to false to avoid redundant ASCII checks.
	 *
	 * @return array|WP_Error The $data parameter, with invalid characters removed from each value.
	 *                        This works as a passthrough: any additional keys such as 'field' are
	 *                        retained in each value array. If we cannot remove invalid characters,
	 *                        a WP_Error object is returned.
	 * @throws Exception
	 */
	protected function strip_invalid_text(array $data): WP_Error|array {
		$db_check_string = false;

		foreach ( $data as &$value ) {
			$charset = $value['charset'];

			if ( is_array( $value['length'] ) ) {
				$length                  = $value['length']['length'];
				$truncate_by_byte_length = 'byte' === $value['length']['type'];
			} else {
				$length = false;
				/*
				 * Since we have no length, we'll never truncate. Initialize the variable to false.
				 * True would take us through an unnecessary (for this case) codepath below.
				 */
				$truncate_by_byte_length = false;
			}

			// There's no charset to work with.
			if ( false === $charset ) {
				continue;
			}

			// Column isn't a string.
			if ( ! is_string( $value['value'] ) ) {
				continue;
			}

			$needs_validation = true;
			if (
				// latin1 can store any byte sequence.
				'latin1' === $charset
				||
				// ASCII is always OK.
				( ! isset( $value['ascii'] ) && $this->check_ascii( $value['value'] ) )
			) {
				$truncate_by_byte_length = true;
				$needs_validation = false;
			}

			if ( $truncate_by_byte_length ) {
				mbstring_binary_safe_encoding();
				if ( false !== $length && strlen( $value['value'] ) > $length ) {
					$value['value'] = substr( $value['value'], 0, $length );
				}
				reset_mbstring_encoding();

				if ( ! $needs_validation ) {
					continue;
				}
			}

			// utf8 can be handled by regex, which is a bunch faster than a DB lookup.
			if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset ) ) {
				$regex = '/
					(
						(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
						|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
						|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}';

				if ( 'utf8mb4' === $charset ) {
					$regex .= '
						|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
						|    [\xF1-\xF3][\x80-\xBF]{3}
						|    \xF4[\x80-\x8F][\x80-\xBF]{2}
					';
				}

				$regex         .= '){1,40}                          # ...one or more times
					)
					| .                                  # anything else
					/x';
				$value['value'] = preg_replace( $regex, '$1', $value['value'] );

				if ( false !== $length && mb_strlen( $value['value'], 'UTF-8' ) > $length ) {
					$value['value'] = mb_substr( $value['value'], 0, $length, 'UTF-8' );
				}

				continue;
			}

			// We couldn't use any local conversions, send it to the DB.
			$value['db'] = $db_check_string = true;
		}
		unset( $value ); // Remove by reference.

		if ( $db_check_string ) {
			$queries = [];

			foreach ( $data as $col => $value ) {
				if ( ! empty( $value['db'] ) ) {
					// We're going to need to truncate by characters or bytes, depending on the length value we have.
					if ( isset( $value['length']['type'] ) && 'byte' === $value['length']['type'] ) {
						// Using binary causes LEFT() to truncate by bytes.
						$charset = 'binary';
					} else {
						$charset = $value['charset'];
					}

					$connection_charset = $this->charset;

					if ( is_array( $value['length'] ) ) {
						$length          = sprintf( '%.0f', $value['length']['length'] );
						$queries[ $col ] = $this->prepare( "CONVERT( LEFT( CONVERT( %s USING $charset ), $length ) USING $connection_charset )", $value['value'] );
					} elseif ( 'binary' !== $charset ) {
						// If we don't have a length, there's no need to convert binary - it will always return the same result.
						$queries[ $col ] = $this->prepare( "CONVERT( CONVERT( %s USING $charset ) USING $connection_charset )", $value['value'] );
					}

					unset( $data[ $col ]['db'] );
				}
			}

			$sql = [];
			foreach ( $queries as $column => $query ) {
				if ( ! $query ) {
					continue;
				}

				$sql[] = $query . " AS x_$column";
			}

			$row = $this->get_row( 'SELECT ' . implode( ', ', $sql ), ARRAY_A );

			if ( ! $row ) {
				return new WP_Error( 'wpdb_strip_invalid_text_failure', __( 'Could not strip invalid text.' ) );
			}

			foreach ( array_keys( $data ) as $column ) {
				if ( isset( $row[ "x_$column" ] ) ) {
					$data[ $column ]['value'] = $row[ "x_$column" ];
				}
			}
		}

		return $data;
	}

	/**
	 * Checks if a string is ASCII.
	 *
	 * The negative regex is faster for non-ASCII strings, as it allows
	 * the search to finish as soon as it encounters a non-ASCII character.
	 *
	 * @param string $input_string String to check.
	 *
	 * @return bool True if ASCII, false if not.
	 */
	protected function check_ascii(string $input_string): bool
	{
		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( mb_check_encoding( $input_string, 'ASCII' ) ) {
				return true;
			}
		} elseif ( ! preg_match( '/[^\x00-\x7F]/', $input_string ) ) {
			return true;
		}

		return false;
	}
}