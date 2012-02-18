<?php

LL::Require_file('PDO/PDOExceptions.php');

class PDOFactory {

	const CONFIG_NAME_DEFAULT = 'default';

	static $Cached_connections = array();

	public static function Instantiate( $config_name = null, $options = array() ) {
		
		try {
			
			if ( !$config_name ) {
				$config_name = Config::Is_set('db.default_config_name') ? Config::Get('db.default_config_name') : self::CONFIG_NAME_DEFAULT;
			}
			
			$cache_name = $config_name;
			$config_data = self::Get_connection_data_for_config( $config_name, $options );
			$is_writable = false;
			
			if ( isset($options['for_write']) && $options['for_write'] ) {
				if ( !isset($config_data['write']) || !$config_data['write'] ) {
					throw new InvalidParameterException( 'Write operation called on database, but no write-enabled configuration is available. Check your database configuration file.' );
				}
				
				$use_config = $config_data['write'];
				$cache_name .= '_write';
			}
			else {
				$use_config = $config_data['read'];
				$cache_name .= '_read';
			}

			if ( 
				 (!isset($options['use_cache']) || $options['use_cache'])
				 &&
				 (isset(self::$Cached_connections[$cache_name]) && self::$Cached_connections[$cache_name] )
				 )
			{
				return self::$Cached_connections[$cache_name];
			}
			else {
	
				if ( isset($config_data['rw_separated']) && $config_data['rw_separated'] ) {
					if ( isset($options['for_write']) && $options['for_write'] ) {
						$is_writable = true;
					}
				}
				else {
					$is_writable = true;
				}
	
				$driver_options = isset($use_config['driver_options']) && $use_config['driver_options'] ? $use_config['driver_options'] : array();
	
				$class_name = 'PDO_' . strtolower($use_config['driver']);				
				
				if ( !LL::Include_class("PDO/{$class_name}") ) {
					throw new MissingPDODriverException();
				}
				
				LL::Require_class("PDO/{$class_name}");
				
				$pdo = new $class_name( $use_config['dsn'], $use_config['username'], $use_config['password'], $driver_options );
				$pdo->is_writable = $is_writable;
				$pdo->config_name = $config_name;
				$pdo->config_data = $config_data;
				
				self::$Cached_connections[$cache_name] = $pdo;

				Debug::Show('Database Connection Instantiate using ' . $config_name, Debug::VERBOSITY_LEVEL_BASIC);

				$relevant_hook = ( $is_writable ) ? 'after_connect_w' : 'after_connect_r';

				if ( LL::Hook_exists('DB', $relevant_hook) ) {
					LL::Call_hook('DB', $relevant_hook, $pdo);
				}
				else {
					LL::Call_hook('DB', 'after_connect', $pdo);
				}
					
				return $pdo;
			}
			
		}
		catch( Exception $e ) {
			
			throw $e;
		}
		
	}
	
	/**
	 * @returns an array containing the keys 'dsn', 'username', 'password', and 'driver_options'
	 * 
	 */
	public static function Get_connection_data_for_config( $config_name, $options = array() ) {
		
		$default_port = 3306;
		
		LL::Include_config( 'db' );
		
		if ( $config_name != self::CONFIG_NAME_DEFAULT ) {
			$config_key = "db.{$config_name}";
		}
		else {
			if ( Config::Is_set('db.' . self::CONFIG_NAME_DEFAULT) ) {
				$config_key = 'db.' . self::CONFIG_NAME_DEFAULT;
			}
			else {
				$config_key = 'db';
			}
		}
		
		if ( Config::Is_set($config_key) ) {
			$setup = Config::Get($config_key);	
		}
		else {
			$setup = self::Load_legacy_db_config();
		}
		
		
		if ( !$setup || !is_array($setup) ) {
			throw new DBConnException( "No DB Configuration Found");
		}

		$default_setup['driver'] = 'mysql';
		$default_setup['port'] = '3306';
		$default_setup['host'] = 'localhost';
				
		$ret = $setup;	
		$ret['rw_separated'] = false;
		$ret['config_key'] = $config_key;
		
		if ( array_key_exists('read', $setup) ) {
			$ret['rw_separated'] = true;
			$ret['read'] = array_merge($default_setup, $setup['read']);
			$ret['read']['dsn'] = self::DSN_from_db_config(array_merge($default_setup, $setup, $ret['read']));
		}
		else {
			$ret['read'] = array_merge($default_setup, $setup);
			$ret['read']['dsn'] = self::DSN_from_db_config(array_merge($default_setup, $setup));
		}

		if ( array_key_exists('write', $setup) ) {
			$ret['rw_separated'] = true;
			$ret['write'] = array_merge($default_setup, $setup['write']);
			$ret['write']['dsn'] = self::DSN_from_db_config(array_merge($default_setup, $setup, $ret['write']));
		}
		else {
			//$ret['write'] = array_merge($default_setup, $setup);
			//$ret['write']['dsn'] = self::DSN_from_db_config(array_merge($default_setup, $setup));
			
		}

		return $ret;	
	}

	public static function DSN_from_db_config( $config ) {
		
		$dsn = "{$config['driver']}:dbname={$config['db_name']}";
		
		if ( isset($config['host']) && $config['host'] ) {
			$dsn .= ";host={$config['host']};port={$config['port']}";		
		}

		if ( isset($config['unix_socket']) && $config['unix_socket'] ) {
			$dsn .= ";unix_socket={$config['unix_socket']}";		
		}

		return $dsn;
		
	}


	public static function Load_Legacy_db_config() {
		
		$config = array();
		
		if ( defined('FUSE_DB_USERNAME') ) {
			$config['username'] = constant('FUSE_DB_USERNAME');
		}
		else if ( defined('DB_USERNAME') ) {
			$config['username'] = constant('DB_USERNAME');
		}

		if ( defined('FUSE_DB_PASSWORD') ) {
			$config['password'] = constant('FUSE_DB_PASSWORD');
		}
		else if ( defined('DB_PASSWORD') ) {
			$config['password'] = constant('DB_PASSWORD');
		}


		if ( defined('FUSE_DB_HOSTNAME') ) {
			$config['host'] = constant('FUSE_DB_HOSTNAME');
		}
		else if ( defined('DB_HOSTNAME') ) {
			$config['host'] = constant('DB_HOSTNAME');
		}

		if ( defined('FUSE_DB_NAME') ) {
			$config['db_name'] = constant('FUSE_DB_NAME');
		}
		else if ( defined('DB_NAME') ) {
			$config['db_name'] = constant('DB_NAME');
		}


		if ( defined('FUSE_DB_DRIVER') ) {
			$config['driver'] = constant('FUSE_DB_DRIVER');
		}
		else if ( defined('DB_DRIVER') ) {
			$config['driver'] = constant('DB_DRIVER');
		}


		if ( defined('FUSE_DB_PORT') ) {
			$config['port'] = constant('FUSE_DB_PORT');
		}
		else if ( defined('DB_PORT') ) {
			$config['port'] = constant('DB_PORT');
		}

		if ( defined('DB_UNIX_SOCKET') ) {
			$config['unix_socket'] = constant('DB_UNIX_SOCKET');
		}

		return $config;
		
	}

}
?>