<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Login extends Controller {

    public function action_index()
    {
        // Залогиненному тут делать нечего
        if (Auth::instance()->logged_in()) {
            $this->redirect('/');
        }

        // GET на /login — отдельной страницы нет, возвращаем на referrer
        if ($this->request->method() !== HTTP_Request::POST) {
            $this->redirect($this->request->referrer() ?: '/');
        }

        $username = Arr::get($_POST, 'username', '');
        $password = Arr::get($_POST, 'password', '');
        $remember = (bool) Arr::get($_POST, 'remember', false);

        if (Auth::instance()->login($username, $password, $remember)) {
            Session::instance()->delete('login_errors');
        } else {
            // прочитает Controller_Template::_getAuthData() через get_once()
            Session::instance()->set('login_errors', array(__('Неверный логин или пароль')));
        }

        $this->redirect($this->request->referrer() ?: '/');
    }
}