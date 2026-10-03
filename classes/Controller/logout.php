<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Logout extends Controller {

    public function action_index()
    {
        Auth::instance()->logout();

        $session = Session::instance();
        $session->delete('username');
        $session->delete('res');

        $session->set('flash_message', array(
            'type' => 'info',
            'text' => __('Вы вышли из системы'),
        ));

        $this->redirect('/');
    }
}