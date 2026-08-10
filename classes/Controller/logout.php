<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Logout extends Controller {

	public function action_index()
	{
		echo Debug::vars('6', Session::instance()); //exit;
		Auth::instance()->logout();
		Session::instance()->delete('username');
		Session::instance()->delete('res');
		$this->redirect('/');
	}

}

