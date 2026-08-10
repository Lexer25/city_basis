<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * File Auth driver.
 * [!!] this Auth driver does not support roles nor autologin.
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 * 
 * Этот файл сформировал Бухаров А.В. 12 авг 2017 г. Пароль и логин пользователя берутся из БД СКУД.
 */
class Auth_File extends Auth {
	
	// User list
	protected $_users;
	
	/**
	 * Constructor loads the user list into the class.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		// Load user list from config first
		$this->_users = Arr::get($config, 'users', array());
		
		//Бухаров А.В. 15 авг 2018 г. Логин и пароль берутся из БД СКУД.
		try {
			$sql='select p.login, p.pswd from people p
				where p.pswd<>\'\'';
			$query = DB::query(Database::SELECT, iconv('UTF-8','windows-1251',$sql))
			->execute(Database::instance('fb'))
			->as_array();
			$res=array();
			foreach ($query as $key=>$value)
			{
				$res[Arr::get($value, 'LOGIN')]=$this->hash(Arr::get($value, 'PSWD'));
			}
			$this->_users = $res;
		} catch (Exception $e) {
			// Log the error but don't crash the application
			Log::instance()->add(Log::ERROR, 'Auth_File: Failed to load users from database: ' . $e->getMessage());
			// Keep the users from config (if any) or empty array
			// This allows the application to continue running
		}
		
	}
	
	/**
	 * Logs a user in.
	 *
	 * @param   string   $username  Username
	 * @param   string   $password  Password
	 * @param   boolean  $remember  Enable autologin (not supported)
	 * @return  boolean
	 */
	protected function _login($username, $password, $remember)
	{
		if (is_string($password))
		{
			// Create a hashed password
			$password = $this->hash($password);
		}
		
		if (isset($this->_users[$username]) AND $this->_users[$username] === $password)
		{
			// Complete the login
			return $this->complete_login($username);
		}
		
		// Login failed
		return FALSE;
	}
	
	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    $username  Username
	 * @return  boolean
	 */
	public function force_login($username)
	{
		// Complete the login
		return $this->complete_login($username);
	}
	
	/**
	 * Get the stored password for a username.
	 *
	 * @param   mixed   $username  Username
	 * @return  string
	 */
	public function password($username)
	{
		return Arr::get($this->_users, $username, FALSE);
	}
	
	/**
	 * Compare password with original (plain text). Works for current (logged in) user
	 *
	 * @param   string   $password  Password
	 * @return  boolean
	 */
	public function check_password($password)
	{
		$username = $this->get_user();
		
		if ($username === FALSE)
		{
			return FALSE;
		}
		
		return ($password === $this->password($username));
	}
	
} // End Auth File
