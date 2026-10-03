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

        $session = Session::instance();

        if (Auth::instance()->login($username, $password, $remember)) {
            $session->delete('login_errors');

            // Успешный вход
            $session->set('flash_message', array(
                'type' => 'success',
                'text' => __('Вы успешно авторизованы как :username', array(
                    ':username' => $username,
                )),
            ));
        } else {
            // Ошибка входа — и в форму, и в flash
            $session->set('login_errors', array(__('Неверный логин или пароль')));
            $session->set('flash_message', array(
                'type' => 'error',
                'text' => __('Неверный логин или пароль'),
            ));
        }

        $this->redirect($this->request->referrer() ?: '/');
    }
}