<?php

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'load_bootstrap.inc.php' );
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'manage_common.inc.php' );

$action = $argv[1];

if ( !$action ) {
	echo "\nUsage: {$argv[0]} action [options]\n";
	exit;
}

if ( !admin_user_exists() ) {
	create_admin_user();
}
else {
	require_admin_login();
}

switch( $action ) {
	
	case 'create':
	
		$username = $argv[2];
		$password = $argv[3];
		$groupname = $argv[4];
	
		if ( !$username || !$password || !$groupname ) {
			echo "Usage: {$argv[0]} create username password groupname";
			exit;
		}
	
		create_user( $username, $password, $groupname );
	
		break;
	case 'usergrant':
		$username = $argv[2];
		$priv_type_key = $argv[3];
		$priv_val = ( isset($argv[4]) ) ? $argv[4] : null;
	
		if ( !$username || !$priv_type_key ) {
			echo "Usage: {$argv[0]} usergrant username priv_type [priv_val]";
			exit;
		}
	
		$options['priv_val'] = $priv_val;
	
		grant_user_privilege( $username, $priv_type_key, $options );
	
		break;		
	case 'userrevoke':
		$username = $argv[2];
		$priv_type_key = $argv[3];
		$priv_val = ( isset($argv[4]) ) ? $argv[4] : null;
	
		if ( !$username || !$priv_type_key ) {
			echo "Usage: {$argv[0]} userrevoke username priv_type [priv_val]";
			exit;
		}
	
		$options['priv_val'] = $priv_val;
	
		revoke_user_privilege( $username, $priv_type_key, $options );
	
		break;			
	case 'groupgrant':
		$groupname = $argv[2];
		$priv_type_key = $argv[3];
		$priv_val = ( isset($argv[4]) ) ? $argv[4] : null;
	
		if ( !$groupname || !$priv_type_key ) {
			echo "Usage: {$argv[0]} groupgrant groupname priv_type [priv_val]";
			exit;
		}
	
		$options['priv_val'] = $priv_val;
	
		grant_group_privilege( $groupname, $priv_type_key, $options );
	
		break;
	case 'grouprevoke':
		$groupname = $argv[2];
		$priv_type_key = $argv[3];
		$priv_val = ( isset($argv[4]) ) ? $argv[4] : null;
	
		if ( !$groupname || !$priv_type_key ) {
			echo "Usage: {$argv[0]} grouprevoke groupname priv_type [priv_val]";
			exit;
		}
	
		$options['priv_val'] = $priv_val;
	
		revoke_group_privilege( $groupname, $priv_type_key, $options );
	
		break;							
	default:
		echo "\nUnknown action: {$action}\n";
}

function grant_user_privilege( $username, $priv_type, $options = array() ) {

	LL::Require_class('Auth/AuthLoader');
	LL::Require_class('Auth/UserPrivilege');
	
	$user = AuthLoader::Load_user_object();
	$user->name = $username;
	
	if ( !$user->record_exists() ) {
		echo "Nonexistent user: {$username}\n";
		exit;
	}		
	
	$priv_obj = new UserPrivilege();
	$priv_type_obj = check_privilege_type( $priv_type, $options );
	$priv_val = isset($options['priv_val']) ? $options['priv_val'] : 1;

	$query_obj = $priv_obj->db->new_query_obj();
	$query_obj->where( "{$priv_obj->table_name}.priv_type_id = {$priv_type_obj->id}");
	$query_obj->where( "{$priv_obj->table_name}.user_id = {$user->id}");
	$query_obj->where( "{$priv_obj->table_name}.user_priv_val = '{$priv_val}'");
	
	if ( $priv_obj->fetch_single( array('query_obj' => $query_obj) ) ) {
		echo "User {$username} already has privilege {$priv_type}\n";
		exit;
	}	

	$priv_obj->user_id = $user->id;
	$priv_obj->priv_type_id = $priv_type_obj->id;
	$priv_obj->user_priv_val = $priv_val;
	$priv_obj->save();
	
	echo "Privilege {$priv_type} added for user {$username}.\n";
	
}

function grant_group_privilege( $groupname, $priv_type, $options = array() ) {

	LL::Require_class('Auth/AuthLoader');
	LL::Require_class('Auth/UserGroup');
	LL::Require_class('Auth/GroupPrivilege');
	
	$group = new UserGroup();
	$group->name = $groupname;
	
	if ( !$group->record_exists() ) {
		echo "Nonexistent group: {$groupname}\n";
		exit;
	}		

	$priv_obj = new GroupPrivilege();
	$priv_type_obj = check_privilege_type( $priv_type, $options );
	$priv_val = isset($options['priv_val']) ? $options['priv_val'] : 1;

	$query_obj = $priv_obj->db->new_query_obj();
	$query_obj->where( "{$priv_obj->table_name}.priv_type_id = {$priv_type_obj->id}");
	$query_obj->where( "{$priv_obj->table_name}.group_id = {$group->id}");
	$query_obj->where( "{$priv_obj->table_name}.group_priv_val = '{$priv_val}'");
	
	if ( $priv_obj->fetch_single( array('query_obj' => $query_obj) ) ) {
		echo "Group {$groupname} already has privilege {$priv_type}\n";
		exit;
	}	
	
	$priv_obj->group_id = $group->id;
	$priv_obj->priv_type_id = $priv_type_obj->id;
	$priv_obj->group_priv_val = $priv_val;
	$priv_obj->save();
	
	echo "Privilege {$priv_type} added for group {$groupname}.\n";
	
}

function revoke_user_privilege( $username, $priv_type, $options = array() ) {

	LL::Require_class('Auth/AuthLoader');
	LL::Require_class('Auth/UserPrivilege');
	
	$user = AuthLoader::Load_user_object();
	$user->name = $username;
	
	if ( !$user->record_exists() ) {
		echo "Nonexistent user: {$username}\n";
		exit;
	}		
	
	$priv_obj = new UserPrivilege();
	$priv_type_obj = check_privilege_type($priv_type, array('require_priv_type' => true) );
	$priv_val = isset($options['priv_val']) ? $options['priv_val'] : 1;

	$query_obj = $priv_obj->db->new_query_obj();
	$query_obj->where( "{$priv_obj->table_name}.priv_type_id = {$priv_type_obj->id}");
	$query_obj->where( "{$priv_obj->table_name}.user_id = {$user->id}");
	$query_obj->where( "{$priv_obj->table_name}.user_priv_val = '{$priv_val}'");
	
	if ( $priv_obj = $priv_obj->fetch_single( array('query_obj' => $query_obj) ) ) {
		$priv_obj->delete();
	}	

	echo "Privilege {$priv_type} removed for user {$username}.\n";
	
}

function revoke_group_privilege( $groupname, $priv_type, $options = array() ) {

	LL::Require_class('Auth/UserGroup');
	LL::Require_class('Auth/GroupPrivilege');
	
	$group = new UserGroup;
	$group->name = $groupname;
	
	if ( !$group->record_exists() ) {
		echo "Nonexistent group: {$groupname}\n";
		exit;
	}		
	
	$priv_obj = new GroupPrivilege();
	$priv_type_obj = check_privilege_type($priv_type, array('require_priv_type' => true) );
	$priv_val = isset($options['priv_val']) ? $options['priv_val'] : 1;

	$query_obj = $priv_obj->db->new_query_obj();
	$query_obj->where( "{$priv_obj->table_name}.priv_type_id = {$priv_type_obj->id}");
	$query_obj->where( "{$priv_obj->table_name}.group_id = {$group->id}");
	$query_obj->where( "{$priv_obj->table_name}.group_priv_val = '{$priv_val}'");
	
	if ( $priv_obj = $priv_obj->fetch_single( array('query_obj' => $query_obj) ) ) {
		$priv_obj->delete();
	}	

	echo "Privilege {$priv_type} removed for group {$groupname}.\n";
	
}

function check_privilege_type( $priv_type, $options = array() ) {
	
	LL::Require_class('Auth/UserPrivilegeType');
		
	$priv_type_obj = new UserPrivilegeType;
	$priv_type_obj->key = $priv_type;
	
	if ( !$priv_type_obj->record_exists() ) {
	
		if ( isset($options['require_priv_type']) && $options['require_priv_type'] ) {
			echo "Nonexistent Privilege type.\n";
			exit;
		}
		else {	
			echo "Privilege type: {$priv_type} does not exist. Create it? ";
			if ( user_response_is_yes() ) {
				$priv_type_obj->save();
			}
			else {
				echo "Cannot continue. Create privilege manually.";
				exit;
			}
		}
	}
	
	return $priv_type_obj;
	
}

function create_user( $username, $password, $groupname, $options = array() ) {
	
	LL::Require_class('Auth/AuthLoader');
	LL::Require_class('Auth/AuthValidator');
	LL::Require_model('Auth/UserGroup');
		
	$user = AuthLoader::Load_user_object();
	
	if ( !AuthValidator::Username_has_valid_format($username) ) {
		echo "Invalid username: {$username}\n";
		exit;
	}
	
	if ( !AuthValidator::Password_has_valid_format($password) ) {
		echo "Invalid password - does not meet format criteria\n";
		exit;
	}

	$group = new UserGroup();
	$group->name = $groupname;
	
	if ( !$group->record_exists() ) {
		if ( !isset($options['create_group']) ) {
			echo "Group {$groupname} does not exist. Create it?";
			$create_group = ( user_response_is_yes()  ) ? true : false;
		}
		else {
			$create_group = $options['create_group'];
		}
			
		if ( !$create_group ) {
			echo "Nonexistent group: {$groupname}\n";
			exit;
		}
		else {
			$group->save();
		}
		
	} 

	$user->name = $username;
	$user->group_id = $group->id;
	
	if ( Config::Get('auth.enable_clear_passwords') ) {
		$user->password = $password;
	}

	if ( Config::Get('auth.enable_encrypted_passwords') ) {
		$user->password_encrypted = $user->encrypt_password($password);
	}

	$user->save();
	echo "\nUser {$username} successfully added\n";

	
}

function require_admin_login() {
	
	LL::Require_class('Auth/AuthLoader');
	
	echo "Enter a valid administrator username: ";
	$username = read_user_response();
	
	echo "Enter the password for {$username}: ";
	$password = read_user_response();
	
	$user_obj = AuthLoader::Load_user_object();
	
	$user_obj->name = $username;
	
	if ( $user_obj->record_exists() && $user_obj->validate_password($password) && $user_obj->is_administrator() ) {
		return true;
	}
	
	echo "Invalid username or password.";
	exit;
	
}

function admin_user_exists() {
	
	LL::Require_class('Auth/AuthLoader');
	$user_obj = AuthLoader::Load_user_object();
	$user_obj->name = Config::Get_required('auth.administrator_username');
	
	Config::Get_required('auth.administrator_username');
	
	if ( $user_obj->record_exists() ) {
		return true; 
	}

	return false;	
	
}

function create_admin_user() {

	LL::Require_class('Auth/AuthLoader');
	$user_obj = AuthLoader::Load_user_object();
	
	$user_obj->name = Config::Get_required('auth.administrator_username');
	
	echo "Enter the new Administrator password:";
	$admin_password = read_user_response();
	
	$options['create_group'] = true;
	
	create_user(Config::Get_required('auth.administrator_username'), $admin_password, Config::Get_required('auth.administrators_groupname'), $options );
	
	
}

?>
