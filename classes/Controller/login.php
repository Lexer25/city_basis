<?php defined('SYSPATH') or die('No direct script access.');

    class Controller_Login extends Controller {
			
	public function before()
	{
			  if (Auth::instance()->logged_in()) {
					$this->redirect('/');
				}
	}
	

        public function action_index() {
			
			
					if (!empty($_POST)) {
             	$username = Arr::get($_POST, 'username');
                $password = Arr::get($_POST, 'password');
			
                if (Auth::instance()->login($username, $password)) {
                $user = Auth::instance()->get_user();
				}
			}
			//echo Debug::vars('15', $_POST);exit;
			echo Debug::vars('15', Auth::instance()->logged_in('admin'));exit;
		
               
			$this->redirect($this->request->referrer());
           
        }

    }

