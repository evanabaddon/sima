<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
//{{{ util: safely open PDO class.
class SafePDO extends PDO
{
	public static function exception_handler($exception)
	{
		// Output the exception details
		die('Uncaught exception: '. $exception->getMessage());
	}

	public function __construct($dsn, $username='', $password='', $driver_options=array())
	{
		// Temporarily change the PHP exception handler while we . . .
		set_exception_handler(array(__CLASS__, 'exception_handler'));

		// . . . create a PDO object
		parent::__construct($dsn, $username, $password, $driver_options);

		// Change the exception handler back to whatever it was before
		restore_exception_handler();
	}
}
//}}}

class Jaring
{
//{{{ var : constanta
	public static $MSG_SUCCESS_UPDATE	= 'Data has been updated.';
	public static $MSG_SUCCESS_CREATE	= 'New data has been created.';
	public static $MSG_SUCCESS_DESTROY	= 'Data has been deleted.';
	public static $MSG_ACCESS_FAIL		= "You don't have sufficient privilege.";
	public static $MSG_REQUEST_INVALID	= "Invalid request ";
	public static $MOD_INIT				= '/init';

	public static $ACCESS_NO		= 0;
	public static $ACCESS_READ		= 1;
	public static $ACCESS_CREATE	= 2;
	public static $ACCESS_UPDATE	= 3;
	public static $ACCESS_DELETE	= 4;
//}}}
//{{{ var : static
	public static $_ext				= '.php';
	public static $_title			= 'Jaring Framework';
	public static $_name			= 'jaring';
	public static $_path			= '/';
	public static $_path_mod		= 'module';
	public static $_mod_init		= '';
	public static $_content_type	= 0;
	public static $_menu_mode		= 1;
	public static $_paging_size		= 50;
	public static $_media_dir		= 'media';
	public static $_db_class		= '';
	public static $_db_url			= '';
	public static $_db_user			= '';
	public static $_db_pass			= '';
	public static $_db_pool_min		= 0;
	public static $_db_pool_max		= 100;
	public static $_db				= null;
	public static $_db_ps			= null;

	//	Module configuration. Set by each modules index.
	public static $_mod	= [
							"db_table"	=> [
								"name"		=> ""
							,	"id"		=> ["id"]
							,	"read"		=> []
							,	"search"	=> []
							,	"create"	=> []
							,	"generate_id" => null
							,	"update"	=> []
							,	"order"		=> []
							]
						];

	public static $_out	= [
							'success'	=> false
						,	'data'		=> ''
						,	'total'		=> 0
						];
	/*
		Cookies values.
		Variables that will be instantiated when calling cookies_get.
	*/
	public static $_c_uid			= 0;
	public static $_c_username		= 'Anonymous';
//}}}

//{{{ db : check user access to module
	public static function check_user_access ($mod, $uid, $access)
	{
		$q	="
				select	GM.permission
				from	_user			U
				,		_group			G
				,		_user_group		UG
				,		_menu			M
				,		_group_menu		GM
				where	GM._group_id	= G.id
				and		GM._menu_id		= M.id
				and		UG._group_id	= G.id
				and		UG._user_id		= U.id
				and		M.module		= '". $mod ."'
				and		U.id			= ". $uid ."
				order by GM.permission desc
				limit	0,1
			";

		try {
			self::$_db_ps = self::$_db->prepare($q);
			self::$_db_ps->execute ();
			$rs = self::$_db_ps->fetchAll (PDO::FETCH_ASSOC);
			self::$_db_ps->closeCursor ();

			if (count ($rs) <= 0) {
				throw new Exception (self::$MSG_ACCESS_FAIL);
			}
			if (((int) $rs[0]["permission"]) >= $access) {
				return true;
			}
		} catch (Exception $e) {
			throw $e;
		}

		throw new Exception (self::$MSG_ACCESS_FAIL);
	}
//}}}
//{{{ db : initialize sqlite database.
	public static function db_init_sqlite ()
	{
		/* Check if sqlite is file or memory. */
		$a = explode(":", self::$_db_url);

		/* sqlite is file based */
		if (count ($a) === 2) {
			$a[1] = $f_db	= APP_PATH . $a[1];
			self::$_db_url	= implode (":", $a);

			if (file_exists ($f_db)) {
				self::$_db = new SafePDO (self::$_db_url);
				self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				return;
			}
		}

		self::$_db = new SafePDO (self::$_db_url);
		self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		/* Populate new database file. */
		$f_sql		= APP_PATH ."/db/init.ddl.sql";
		$f_sql_v	= file_get_contents($f_sql);
		$queries	= explode (";", $f_sql_v);

		foreach ($queries as $q) {
			$q	.= ";";
			self::$_db->exec ($q);
		}

		$f_sql		= APP_PATH ."/db/init.dml.sql";
		$f_sql_v	= file_get_contents($f_sql);
		$queries	= explode (";", $f_sql_v);

		foreach ($queries as $q) {
			$q	.= ";";
			self::$_db->exec ($q);
		}

		$f_sql = APP_PATH ."/db/app.sql";
		if (file_exists ($f_sql)) {
			$f_sql_v	= file_get_contents($f_sql);
			$queries	= explode (";", $f_sql_v);

			foreach ($queries as $q) {
				$q	.= ";";
				self::$_db->exec ($q);
			}
		}
	}
//}}}
//{{{ db : initialize database.
	public static function db_init ()
	{
		if (stristr(self::$_db_url, "sqlite") !== FALSE) {
			self::db_init_sqlite ();
		} else {
			self::$_db = new SafePDO (self::$_db_url, self::$_db_user, self::$_db_pass);
			self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	}
//}}}
//{{{ db : get connection.
	public static function db_get_connection ()
	{
		if (self::_db == null) {
			self::db_init ();
		}
	}
//}}}
//{{{ cookie : get value
	public static function cookies_get ()
	{
		$ckey = 'user_id';
		if (isset ($_COOKIE[$ckey])) {
			self::$_c_uid = $_COOKIE[$ckey];
		}

		$ckey = 'user_name';
		if (isset ($_COOKIE[$ckey])) {
			self::$_c_username = $_COOKIE[$ckey];
		}
	}
//}}}
//{{{ cookie : check if user has cookie.
	public static function cookies_check ()
	{
		$m_home	= self::$_path . self::$_path_mod ."/home/";
		$p_home	= strpos ($_SERVER['REQUEST_URI'], $m_home);

		if (0 === self::$_c_uid) {
			if (false === $p_home) {
				header ("Location:". $m_home);
				exit ();
			}
		}
	}
//}}}
//{{{ main
	public static function init ()
	{
		$f_app_conf	= APP_PATH ."/app.conf";

		if (!file_exists($f_app_conf)) {
			$f_app_conf = APP_PATH . "/app.default.conf";
		}

		$app_conf = parse_ini_file ($f_app_conf);

		self::$_title			= $app_conf['app.title'];
		self::$_name			= $app_conf['app.name'];
		self::$_ext				= $app_conf['app.extension'];
		self::$_path			= $app_conf['app.path'];
		self::$_path_mod		= $app_conf['app.module.dir'];
		self::$_mod_init		= self::$_path . self::$_path_mod . self::$MOD_INIT . self::$_ext;
		self::$_content_type	= $app_conf['app.content.type'];
		self::$_menu_mode		= $app_conf['app.menu.mode'];
		self::$_paging_size		= $app_conf['app.paging.size'];
		self::$_media_dir		= "/". $app_conf['app.media.dir'] ."/";
		self::$_db_url			= $app_conf['db.url'];
		self::$_db_user			= $app_conf['db.username'];
		self::$_db_pass			= $app_conf['db.password'];
		self::$_db_pool_min		= $app_conf['db.pool.min'];
		self::$_db_pool_max		= $app_conf['db.pool.max'];

		self::cookies_get ();
	}
//}}}

//{{{ db : execute query
	/*
		q		: query.
		bindv	: array of binding value, if query containt '?'.
		fetch	: should we fetch after execute? delete statement MUST set to false
	*/
	public static function db_execute ($q, $bindv = null, $fetch = true)
	{
		$rs = [];
		$s = true;

		self::$_db_ps = self::$_db->prepare ($q);

		if (null !== $bindv) {
			$s = self::$_db_ps->execute ($bindv);
		} else {
			$s = self::$_db_ps->execute ();
		}

		if ($s && $fetch) {
			$rs = self::$_db_ps->fetchAll (PDO::FETCH_ASSOC);

			self::$_db_ps->closeCursor ();
		}

		return $rs;
	}
//}}}
//{{{ db : prepare fields for where query
	public static function db_prepare_fields ($fields, $sep = "and", $op = "=")
	{
		$s = "";

		foreach ($fields as $k => $v) {
			if ($k > 0) {
				$s .= " $sep ";
			}
			$s .= " $v $op ? ";
		}

		return $s;
	}
//}}}
//{{{ db : get last inserted row id
	public static function get_last_insert_id ($table, $id, $fields, $bindv)
	{
		$q	="
			select	$id
			from	$table
			where ". self::db_prepare_fields ($fields);

		$rs = self::db_execute ($q, $bindv);

		if (count ($rs) > 0) {
			return $rs[0][$id];
		}

		return 0;
	}
//}}}
//{{{ db : generate uniq id using timestamp + millisecond
	public static function db_generate_id ()
	{
		return round (microtime (true) * 1000);
	}
//}}}
//{{{ db : generate ID for each data
	public static function db_prepare_id (&$data)
	{
		if (! isset (self::$_mod["db_table"]["generate_id"])) {
			return;
		}

		$id = self::$_mod["db_table"]["generate_id"];

		foreach ($data as &$d) {
			$d[$id] = self::db_generate_id ();
		}
	}
//}}}
//{{{ db : prepare insert query
	public static function db_prepare_insert ($table, $fields)
	{
		$qbind	= "";

		$nfield	= count ($fields);
		for ($i = 0; $i < $nfield; $i++) {
			if ($i > 0) {
				$qbind .= ",";
			}
			$qbind .= "?";
		}

		$q	=" insert into $table "
			." (". implode (",", $fields) .")"
			." values ( $qbind )";

		self::$_db_ps = self::$_db->prepare ($q);
	}
//}}}
//{{{ db : prepare statement for updating data
	public static function db_prepare_update ($table, $fields, $ids)
	{
		$qupdate=" update $table ";
		$qset	=" set ". self::db_prepare_fields ($fields, ",");
		$qwhere	=" where ". self::db_prepare_fields ($ids);

		$q	= $qupdate
			. $qset
			. $qwhere;

		self::$_db_ps = self::$_db->prepare ($q);
	}
//}}}
//{{{ db : prepare delete statement
	public static function db_prepare_delete ($table, $fields)
	{
		$qdelete=" delete from $table";
		$qwhere	=" where ". self::db_prepare_fields ($fields);

		self::$_db_ps	= self::$_db->prepare ($qdelete . $qwhere);
	}
//}}}

//{{{ crud -> db : handle read request
	public static function request_read ()
	{
		$query	= "'%".$_GET["query"]."%'";
		$start	= (int) $_GET["start"];
		$limit	= (int) $_GET["limit"];
		$freads		= self::$_mod["db_table"]["read"];
		$fsearch	= self::$_mod["db_table"]["search"];

		$qselect	= "	select ". implode (",", $freads);
		$qfrom		= " from ". self::$_mod["db_table"]["name"];
		$qwhere		= " where 1=1 ";
		$qorder		= " order by ". implode (",", self::$_mod["db_table"]["order"]);
		$qlimit		= "	limit ". $start .",". $limit;

		// get parameter name that has the same name with read fields,
		// and use it as the filter
		foreach ($freads as $v) {
			if (! array_key_exists ($v, $_GET)) {
				continue;
			}

			$qwhere .=" and $v = ";

			if (is_numeric ($_GET[$v])) {
				$qwhere .= $_GET[$v];
			} else {
				$qwhere .= "'". $_GET[$v] ."'";
			}
		}

		// add filter by search field
		if (count ($fsearch) > 0) {
			$qwhere .=" and (";
		}

		foreach ($fsearch as $k => $v) {
			if ($k > 0) {
				$qwhere .= " or ";
			}
			$qwhere .= " $v like $query ";
		}

		if (count ($fsearch) > 0) {
			$qwhere .= ")";
		}

		// Get total rows
		$qtotal	=" select	COUNT(". self::$_mod["db_table"]["id"][0] .") as total "
				. $qfrom
				. $qwhere;

		// Get data
		$qread	= $qselect
				. $qfrom
				. $qwhere
				. $qorder
				. $qlimit;

		self::$_out["total"]		= (int) self::db_execute ($qtotal)[0]["total"];
		self::$_out["data"]		= self::db_execute ($qread);
		self::$_out["success"]	= true;

		if (function_exists ("request_read_after")) {
			request_read_after (self::$_out["data"]);
		}
	}
//}}}
//{{{ crud -> db : handle create request
	public static function request_create ($data)
	{
		$table	= self::$_mod["db_table"]["name"];
		$fields	= self::$_mod["db_table"]["create"];

		if (function_exists ("request_create_before")) {
			request_create_before ($data);
		}

		self::db_prepare_insert ($table, $fields);
		self::db_prepare_id ($data);

		foreach ($data as $d) {
			$bindv = [];

			foreach (self::$_mod["db_table"]["create"] as $field) {
				array_push ($bindv, $d[$field]);
			}

			self::$_db_ps->execute ($bindv);
			self::$_db_ps->closeCursor ();

			unset ($bindv);
		}

		if (function_exists ("request_create_after")) {
			request_create_after ($data);
		}

		self::$_out['success']	= true;
		self::$_out['data']		= self::$MSG_SUCCESS_CREATE;
	}
//}}}
//{{{ crud -> db : handle update request
	public static function request_update ($data)
	{
		$table	= self::$_mod["db_table"]["name"];
		$fields	= self::$_mod["db_table"]["update"];
		$ids	= self::$_mod["db_table"]["id"];

		if (function_exists ("request_update_before")) {
			request_update_before ($data);
		}

		self::db_prepare_update ($table, $fields, $ids);

		foreach ($data as $d) {
			$bindv = [];

			foreach ($fields as $field) {
				array_push ($bindv, $d[$field]);
			}
			foreach ($ids as $field) {
				array_push ($bindv, $d[$field]);
			}

			self::$_db_ps->execute ($bindv);
			self::$_db_ps->closeCursor ();

			unset ($bindv);
		}

		self::$_out['success']	= true;
		self::$_out['data']		= self::$MSG_SUCCESS_UPDATE;
	}
//}}}
//{{{ crud -> db : handle delete request
	public static function request_delete ($data)
	{
		if (function_exists ("request_delete_before")) {
			request_delete_before ($data);
		}

		self::db_prepare_delete (self::$_mod["db_table"]["name"]
								,self::$_mod["db_table"]["id"]
								);

		foreach ($data as $d) {
			$bindv = [];

			foreach (self::$_mod["db_table"]["id"] as $field) {
				array_push ($bindv, $d[$field]);
			}

			self::$_db_ps->execute ($bindv);
			self::$_db_ps->closeCursor ();

			unset ($bindv);
		}

		if (function_exists ("request_delete_after")) {
			request_delete_after ($data);
		}

		self::$_out['success']	= true;
		self::$_out['data']		= self::$MSG_SUCCESS_DESTROY;
	}
//}}}

//{{{ crud : get module name based on request URI.
	public static function get_module_name ($uri)
	{
		return trim (
					str_replace (
						"/"
					,	"_"
					,	substr (
							strstr (
								$uri
							,	self::$_path_mod
							)
						,	strlen(self::$_path_mod)
						)
					)
				,	"_"
				);
	}
//}}}
//{{{ crud : get access code based on HTTP method
	public static function request_get_crud_access ($method)
	{
		switch ($method) {
		case "GET":
			return self::$ACCESS_READ;
		case "POST":
			return self::$ACCESS_CREATE;
		case "PUT":
			return self::$ACCESS_UPDATE;
		case "DELETE":
			return self::$ACCESS_DELETE;
		default:
			throw new Exception (self::$MSG_REQUEST_INVALID . $method);
		}
	}
//}}}
//{{{ crud : get access code based on GET/POST parameter.
	public static function request_get_action_access ($action)
	{
		switch ($action) {
		case "read":
			return self::$ACCESS_READ;
		case "create":
			return self::$ACCESS_CREATE;
		case "update":
			return self::$ACCESS_UPDATE;
		case "destroy":
			return self::$ACCESS_DELETE;
		default:
			throw new Exception (self::$MSG_REQUEST_INVALID
								. $action);
		}
	}
//}}}
//{{{ crud : get user access.
	public static function request_get_access ($mode)
	{
		$access	= self::$ACCESS_NO;

		if ("crud" === $mode) {
			$access = self::request_get_crud_access($_SERVER["REQUEST_METHOD"]);
		} else {
			$action	= "read";

			if ("GET" === $_SERVER["REQUEST_METHOD"]) {
				$action = $_GET["action"];
			} else {
				$action = $_POST["action"];
			}

			$access = self::request_get_action_access ($action);
		}

		return $access;
	}
//}}}
//{{{ crud : route request.
	public static function request_switch ($path, $access, $data)
	{
		switch ($access) {
		case self::$ACCESS_READ:
			$path .= "read.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_read ();
			}
			break;

		case self::$ACCESS_CREATE:
			$path .= "create.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_create ($data);
			}
			break;

		case self::$ACCESS_UPDATE:
			$path .= "update.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_update ($data);
			}
			break;

		case self::$ACCESS_DELETE:
			$path .= "delete.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_delete ($data);
			}
			break;
		}
	}
//}}}
//{{{ crud : main.
	public static function request_handle ($mode = "crud")
	{
		$i		= 1;
		$q		= "";
		$t		= 0;

		$uri	= explode ("?", $_SERVER["REQUEST_URI"])[0];
		$path	= APP_PATH.$uri;
		$module	= self::get_module_name ($uri);

		try {
			self::db_init ();

			$access = self::request_get_access ($mode);

			self::check_user_access ($module, self::$_c_uid, $access);

			if ("crud" === $mode) {
				$data = json_decode (file_get_contents('php://input'), true);
			} else {
				$data = $_POST;
			}

			/* Convert json object to array */
			if (null !== $data && ! is_array (current ($data))) {
				$data = array($data);
			}

			self::request_switch ($path, $access, $data);
		} catch (Exception $e) {
			self::$_out['data'] = addslashes ($e->getMessage ());
		}

		header('Content-Type: application/json');
		echo json_encode (self::$_out, JSON_NUMERIC_CHECK);
	}
//}}}
}
