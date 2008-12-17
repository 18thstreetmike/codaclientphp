<?php
/**
 * The PHP CodaServer Driver
 * 
 * Copyright 2008, 18th Street Software, LLC
 * 
 * This driver makes it possible to access the CodaServer Business Rules Engine via the PHP language.
 * 
 * It deliberately uses a syntax similar to other PHP database drivers as to be more familiar to PHP developers.  There is certainly 
 * potential to upgrade this if the community deems it useful to do so.
 * 
 * This driver is made available under the terms of the GNU GPLv2.  For details please visit http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * 
 * @package codaserver
 * @author Mike Arace <mike@18thstreetsoftware.com>
 * @copyright Copyright (c) 2008, 18th Street Software, LLC
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GNU Public License, Version 2
 */

require_once('HessianPHP/dist/HessianClient.php');

$__CODASERVER_LAST_ERRORS = array();

/**
 * Class CodaServerConnection
 * 
 * This class represents a connection to a CodaServer instance, and is the resource that must be passed to most 
 * functions in this library.  It has two publicly accessable member variables, $session_key and $url.  The former
 * is this connection's unique handle on the server and the latter is the URL of the server.
 * 
 * This class is generally not instantiated directly.  An instance of it is returned by a successful codaserver_connect() 
 * call.
 *
 * @package codaserver
 */
class CodaServerConnection {
	public $session_key;
	public $url;
	
	function __construct($session_key, $url) {
		$this->session_key = $session_key;
		$this->url = $url;
	}
}

/**
 * Connects to an instance of CodaServer
 *
 * @param string $hostname
 * @param int $port
 * @param string $username
 * @param string $password
 * @return CodaServerConnection on success or Boolean false on failure
 */
function codaserver_connect($hostname = 'localhost', $port = '3407', $username = '', $password = '') {
	$url = 'http://'.$hostname.':'.$port;
	
	$proxy = &new HessianClient($url);
	$session_key = $proxy->login($username, $password, null, null, null);

	if (!empty($session_key)) {
		return new CodaServerConnection($session_key, $url);
	} else {
		return false;
	}
}

/**
 * This function runs a query, update statement, or other Coda language command on the provided connection.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $query
 * @return Boolean false on failure, Boolean true on successful update, resultset array on successful query.
 */
function codaserver_query($resource_identifier, $query) {	
	// if no connection is specified exit
	if (!isset($resource_identifier) || is_null($resource_identifier)) {
		throw new Exception('Invalid CodaServer connection specified');
	}
	
	// establish the proxy
	$proxy = &new HessianClient($resource_identifier->url);
	
	// run the query
	$response = $proxy->execute($resource_identifier->session_key, $query);
	
	if ($response['errorstatus'] == true) {
		global $__CODASERVER_LAST_ERRORS;
		$__CODASERVER_LAST_ERRORS = $response['errors'];
		return false;
	} else if (!is_array($response['data'])) {
		return true;
	} else {
		$__CODASERVER_LAST_ERRORS = array();
		return $response['data'];
	}
}

/**
 * Fetches an array of errors from the last codaserver_query call.
 *
 * @return Array of errors, or an empty array if the last call was successful
 */
function codaserver_errors() {
	global $__CODASERVER_LAST_ERRORS;
	return $__CODASERVER_LAST_ERRORS;
}

/**
 * Fetches a stdClass object representing the next row in the provided CodaServer resultset
 *
 * @param resultset array $result
 * @return stdClass object
 */
function codaserver_fetch_object(&$result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		if (!array_key_exists('row_counter', $result)) {
			$result['row_counter'] = 0;
		}
		if (count($result['data']) <= $result['row_counter'] ) {
			return false;
		} else {
			$retval = new stdClass();
			$i = 0;
			$temp = $result['data'][$result['row_counter']];
			foreach($row['columns'] as $column) {
				$retval->$column['column_name'] = $temp[$i];
				$i++;
			}
			$result['row_counter']++;
			return $retval;
		}
	}
}
/**
 * Fetches a numerically-indexed array representing the next row in the provided CodaServer resultset
 *
 * @param resultset array $result
 * @return numerically-indexed array
 */
function codaserver_fetch_row(&$result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		if (!array_key_exists('row_counter', $result)) {
			$result['row_counter'] = 0;
		}
		if (count($result['data']) <= $result['row_counter'] ) {
			return false;
		} else {
			$retval = $result['data'][$result['row_counter']];
			$result['row_counter']++;
			return $retval;
		}
	}
}
/**
 * Fetches a column name-indexed array representing the next row in the provided CodaServer resultset
 *
 * @param resultset array $result
 * @return name-indexed array
 */
function codaserver_fetch_assoc(&$result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		if (!array_key_exists('row_counter', $result)) {
			$result['row_counter'] = 0;
		}
		if (count($result['data']) <= $result['row_counter'] ) {
			return false;
		} else {
			$retval = array();
			$i = 0;
			$temp = $result['data'][$result['row_counter']];
			foreach($row['columns'] as $column) {
				$retval[$column['column_name']] = $temp[$i];
				$i++;
			}
			$result['row_counter']++;
			return $retval;
		}
	}
}

/**
 * Fetches a column name- and numerically-indexed array representing the next row in the provided CodaServer resultset
 *
 * @param resultset array $result
 * @return array
 */
function codaserver_fetch_array(&$result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		if (!array_key_exists('row_counter', $result)) {
			$result['row_counter'] = 0;
		}
		if (count($result['data']) <= $result['row_counter'] ) {
			return false;
		} else {
			$i = 0;
			$retval = $result['data'][$result['row_counter']];
			foreach($row['columns'] as $column) {
				$retval[$column['column_name']] = $retval[$i];
				$i++;
			}
			$result['row_counter']++;
			return $retval;
		}
	}
}

/**
 * Resets the current row pointer of the result set to the first row.
 *
 * @param resultset array $result
 */
function codaserver_reset_resultset_pointer(&$result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		$result['row_counter'] = 0;
	}
}

/**
 * Fetches an array of the column headers for a resultset
 *
 * @param resultset array $result
 * @return array of column headers
 */
function codaserver_fetch_fields($result) {
	if (!array_key_exists('columns', $result)) {
		throw new Exception('Value provided not a single resultset');
	} else {
		return $result['columns'];
	}
}

/**
 * Sets the application name, active environment, and optional group name for the given
 * CodaServer connection.  Many commands on CodaServer require that this information be 
 * present.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $application_name
 * @param string $environment
 * @param string $group_name
 * @return boolean
 */
function codaserver_set_application($resource_identifier, $application_name, $environment = 'dev', $group_name = null) {
	if (empty($application_name)) {
		throw new Exception('No application specified');
	} else if (empty($environment) || !in_array(strtolower($environment), array('dev', 'test', 'prod'))) {
		throw new Exception('Invalid environment');
	}
	return codaserver_query( $resource_identifier, 'SET APPLICATION '.$application_name.'.'.$environment.(!empty($group_name) ? ' IN GROUP '.$group_name : ''));
}

/**
 * Returns a list of CodaServer users that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $group_name
 * @param string $application_name
 * @param string $environment
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_users ($resource_identifier, $group_name = null, $application_name = null, $environment = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($application_name) && !in_array(strtolower($environment), array('dev', 'test', 'prod')) ) {
		throw new Exception('Application must have an environment specified');
	} else if (!empty($environment) && empty($application_name)) {
		throw new Exception('Environment requires an application be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW USERS '.(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($application_name) ? ' FOR APPLICATION '.$application_name.'.'.$environment : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns a list of CodaServer groups that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $application_name
 * @param string $environment
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_groups($resource_identifier, $user_name = null, $application_name = null, $environment = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($application_name) && !in_array(strtolower($environment), array('dev', 'test', 'prod')) ) {
		throw new Exception('Application must have an environment specified');
	} else if (!empty($environment) && empty($application_name)) {
		throw new Exception('Environment requires an application be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW GROUPS '.(!empty($user_name) ? 'OF USER '.$user_name : '').' '.(!empty($application_name) ? ' FOR APPLICATION '.$application_name.'.'.$environment : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns a list of CodaServer types that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_types($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW TYPES '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns a list of datasources that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_datasources($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW DATASOURCES '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns a list of sessions that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_sessions($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW SESSIONS '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns a list of applications that match the specified criteria. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $group_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_applications ($resource_identifier, $group_name = null, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW APPLICATIONS '.(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the CodaServer server permissions for the specified user. 
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_server_permissions ($resource_identifier, $user_name, $where_clause = null, $order_by_clause = null) {
	if (empty($user_name)) {
		throw new Exception('Username must be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW SERVER PERMISSIONS FOR USER '.$user_name.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the application permissions for the specified user in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_application_permissions ($resource_identifier, $user_name, $group_name = null, $where_clause = null, $order_by_clause = null) {
	if (empty($user_name)) {
		throw new Exception('Username must be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW APPLICATION PERMISSIONS FOR USER '.$user_name.(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the tables in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_tables($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW TABLES '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the forms in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_forms($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW FORMS '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the procedures in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_procedures($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW PROCEDURES '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the triggers for the specified table in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $table_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_table_triggers ($resource_identifier, $table_name, $where_clause = null, $order_by_clause = null) {
	if (empty($table_name)) {
		throw new Exception('Table name must be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW TRIGGERS FOR TABLE '.$table_name.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the triggers for the specified form in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $form_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_form_triggers ($resource_identifier, $form_name, $where_clause = null, $order_by_clause = null) {
	if (empty($form_name)) {
		throw new Exception('Form name must be specified');
	}
	return codaserver_query($resource_identifier, 'SHOW TRIGGERS FOR FORM '.$table_name.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the indexes in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_indexes($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW INDEXES '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the crons in the session's application.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_crons($resource_identifier, $where_clause = null, $order_by_clause = null) {
	return codaserver_query($resource_identifier, 'SHOW CRONS '.(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the roles for the session's application.  Can have an optional username and group name specified to return only the
 * roles belonging to that user or user/group combination.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_roles($resource_identifier, $user_name = null, $group_name = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($group_name) && empty($user_name)) {
		throw new Exception('Cannot specify a group name without a username');
	}
	return codaserver_query($resource_identifier, 'SHOW ROLES '.(!empty($user_name) ? ' FOR USER '.$user_name : '').(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the ACL-style permissions within the session's application for either a user or user/group combination, or a role.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $role_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_permissions($resource_identifier, $user_name = null, $group_name = null, $role_name = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($group_name) && empty($user_name)) {
		throw new Exception('Cannot specify a group name without a username');
	}
	if (!empty($role_name) && !empty($user_name)) {
		throw new Exception('Cannot specify both a username and a role name');
	} else if (empty($role_name) && empty($user_name)) {
		throw new Exception('Must specify either a username or a role name');
	}
	return codaserver_query($resource_identifier, 'SHOW PERMISSIONS '.(!empty($role_name) ? ' FOR ROLE '.$role_name : '').(!empty($user_name) ? ' FOR USER '.$user_name : '').(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the table permissions within the session's application for either a user or user/group combination, or a role.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $role_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_table_permissions($resource_identifier, $user_name = null, $group_name = null, $role_name = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($group_name) && empty($user_name)) {
		throw new Exception('Cannot specify a group name without a username');
	}
	if (!empty($role_name) && !empty($user_name)) {
		throw new Exception('Cannot specify both a username and a role name');
	}
	return codaserver_query($resource_identifier, 'SHOW TABLE PERMISSIONS '.(!empty($role_name) ? ' FOR ROLE '.$role_name : '').(!empty($user_name) ? ' FOR USER '.$user_name : '').(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the form permissions within the session's application for either a user or user/group combination, or a role.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $role_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_form_permissions($resource_identifier, $user_name = null, $group_name = null, $role_name = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($group_name) && empty($user_name)) {
		throw new Exception('Cannot specify a group name without a username');
	}
	if (!empty($role_name) && !empty($user_name)) {
		throw new Exception('Cannot specify both a username and a role name');
	}
	return codaserver_query($resource_identifier, 'SHOW FORM PERMISSIONS '.(!empty($role_name) ? ' FOR ROLE '.$role_name : '').(!empty($user_name) ? ' FOR USER '.$user_name : '').(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns the procedure permissions within the session's application for either a user or user/group combination, or a role.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @param string $group_name
 * @param string $role_name
 * @param string $where_clause
 * @param string $order_by_clause
 * @return resultset array
 */
function codaserver_show_procedure_permissions($resource_identifier, $user_name = null, $group_name = null, $role_name = null, $where_clause = null, $order_by_clause = null) {
	if (!empty($group_name) && empty($user_name)) {
		throw new Exception('Cannot specify a group name without a username');
	}
	if (!empty($role_name) && !empty($user_name)) {
		throw new Exception('Cannot specify both a username and a role name');
	}
	return codaserver_query($resource_identifier, 'SHOW PROCEDURE PERMISSIONS '.(!empty($role_name) ? ' FOR ROLE '.$role_name : '').(!empty($user_name) ? ' FOR USER '.$user_name : '').(!empty($group_name) ? ' IN GROUP '.$group_name : '').(!empty($where_clause) ? ' WHERE '.$where_clause : '').(!empty($order_by_clause) ? ' ORDER BY '.$order_by_clause : ''));
}

/**
 * Returns information about the specified user.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $user_name
 * @return resultset array
 */
function codaserver_describe_user($resource_identifier, $user_name) {
	if (empty($user_name)) {
		throw new Exception('Username must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE USER '.$user_name);
}

/**
 * Returns information about the specified group.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $group_name
 * @return resultset array
 */
function codaserver_describe_group($resource_identifier, $group_name) {
	if (empty($group_name)) {
		throw new Exception('Group name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE GROUP '.$group_name);
}

/**
 * Returns information about the specified type.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $type_name
 * @return resultset array
 */
function codaserver_describe_type($resource_identifier, $type_name) {
	if (empty($type_name)) {
		throw new Exception('Type name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE TYPE '.$type_name);
}

/**
 * Returns information about the specified datasource.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $datasource_name
 * @return resultset array
 */
function codaserver_describe_datasource($resource_identifier, $datasource_name) {
	if (empty($datasource_name)) {
		throw new Exception('Datasource name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE DATASOURCE '.$datasource_name);
}

/**
 * Returns information about the specified application name.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $application_name
 * @return resultset array
 */
function codaserver_describe_application($resource_identifier, $application_name) {
	if (empty($application_name)) {
		throw new Exception('Application name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE APPLICATION '.$application_name);
}

/**
 * Returns information about the specified table.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $table_name
 * @return resultset array
 */
function codaserver_describe_table($resource_identifier, $table_name) {
	if (empty($table_name)) {
		throw new Exception('Table name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE TABLE '.$table_name);
}

/**
 * Returns information about the specified table's columns.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $table_name
 * @return resultset array
 */
function codaserver_describe_table_columns($resource_identifier, $table_name) {
	if (empty($table_name)) {
		throw new Exception('Table name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE TABLE '.$table_name.' COLUMNS');
}

/**
 * Returns information about the specified form.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $form_name
 * @return resultset array
 */
function codaserver_describe_form($resource_identifier, $form_name) {
	if (empty($form_name)) {
		throw new Exception('Form name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE FORM '.$form_name);
}

/**
 * Returns information about the fields of the specified form.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $form_name
 * @return resultset array
 */
function codaserver_describe_form_fields($resource_identifier, $form_name) {
	if (empty($form_name)) {
		throw new Exception('Form name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE FORM '.$form_name.' FIELDS');
}

/**
 * Returns information about statuses of the specified form.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $form_name
 * @return resultset array
 */
function codaserver_describe_form_statuses($resource_identifier, $form_name) {
	if (empty($form_name)) {
		throw new Exception('Form name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE FORM '.$form_name.' STATUSES');
}

/**
 * Returns information about status relationships of the specified form.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $form_name
 * @return resultset array
 */
function codaserver_describe_form_status_relationships($resource_identifier, $form_name) {
	if (empty($form_name)) {
		throw new Exception('Form name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE FORM '.$form_name.' STATUS RELATIONSHIPS');
}

/**
 * Returns information about the specified index.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $index_name
 * @return resultset array
 */
function codaserver_describe_index($resource_identifier, $index_name) {
	if (empty($index_name)) {
		throw new Exception('Index name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE INDEX '.$index_name);
}

/**
 * Returns information about the columns of the specified index.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $index_name
 * @return resultset array
 */
function codaserver_describe_index_columns($resource_identifier, $index_name) {
	if (empty($index_name)) {
		throw new Exception('Index name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE INDEX '.$index_name.' COLUMNS');
}

/**
 * Returns information about the specified procedure.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $procedure_name
 * @return resultset array
 */
function codaserver_describe_procedure($resource_identifier, $procedure_name) {
	if (empty($procedure_name)) {
		throw new Exception('Procedure name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE PROCEDURE '.$procedure_name);
}

/**
 * Returns information about the parameters of the specified procedure.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $procedure_name
 * @return resultset array
 */
function codaserver_describe_procedure_parameters($resource_identifier, $procedure_name) {
	if (empty($procedure_name)) {
		throw new Exception('Procedure name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE PROCEDURE '.$procedure_name.' PARAMETERS');
}

/**
 * Returns information about the specified trigger.  The operation must be fully specified, for instance 'BEFORE INSERT'.  Either a 
 * table name or form name can be specified.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $table_name
 * @return resultset array
 */
function codaserver_describe_trigger($resource_identifier, $table_name, $operation) {
	if (empty($table_name)) {
		throw new Exception('Table name must be specified');
	}
	if (empty($operation)) {
		throw new Exception('Operation must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE TRIGGER '.$table_name.' '.$operation);
}

/**
 * Returns information about the parameters of the specified cron.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $cron_name
 * @return resultset array
 */
function codaserver_describe_cron($resource_identifier, $cron_name) {
	if (empty($cron_name)) {
		throw new Exception('Cron name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE CRON '.$cron_name);
}

/**
 * Returns information about the parameters of the specified cron's procedure parameters.
 *
 * @param CodaServerConnection $resource_identifier
 * @param string $cron_name
 * @return resultset array
 */
function codaserver_describe_cron_parameters($resource_identifier, $cron_name) {
	if (empty($cron_name)) {
		throw new Exception('Cron name must be specified');
	}
	return codaserver_query($resource_identifier, 'DESCRIBE CRON '.$cron_name.' PARAMETERS');
}