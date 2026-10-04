## Механизм alert-сообщений (flash)

Все уведомления пользователю — «Вы успешно авторизованы», «Вы вышли из
системы», «Неверный логин или пароль», результат произвольной операции —
выводятся через единый механизм **flash-сообщений**, построенный на сессии
Kohana.

### Что такое flash и почему именно так

**Flash-сообщение** (от англ. *flash* — «вспышка», «мелькнуть») — это
сообщение, которое:

1. **живёт ровно один показ** — от момента записи в сессию до момента
   первого чтения;
2. **переживает ровно один redirect** — кладётся в сессию *до* редиректа,
   читается *после*;
3. **автоматически удаляется** при чтении, чтобы не «залипнуть» и не
   показаться повторно при перезагрузке страницы.

Приставка «flash» описывает не *что* передаётся, а *как*: комбинацию
«сессия + одноразовость + паттерн POST-redirect-GET».

Зачем это нужно. Классический сценарий — обработка формы:

~~~php
// POST /login
if (Auth::instance()->login(...)) {
    Session::instance()->set('flash_message', array('type' => 'success', ...));
    $this->redirect('/');   // ← редирект
}
~~~

Редирект после POST нужен, чтобы пользователь не мог нажать F5 и повторно
отправить форму (проблема *double submit*). Но редирект убивает все
переменные текущего запроса — включая то самое сообщение «Вы успешно
авторизованы». Обычная переменная view до следующего запроса не доживёт.

Сессия редирект переживает, но если положить сообщение в сессию и не
удалять — оно будет показываться **всегда**, пока кто-нибудь его не уберёт.
Получится «залипшее» уведомление на каждой странице.

**Flash — это компромисс:** «живи в сессии, переживи редирект, но умри
при первом чтении».

В Kohana нет встроенного API flash (в отличие от Laravel с
`session()->flash()` или Rails с `flash[:notice]`) — это соглашение,
реализуемое вручную: связка `Session::get()` + `Session::delete()` или
готовый `Session::get_once()`.

### Как это работает в проекте

Цепочка из трёх звеньев:

1. **Контроллер** кладёт в сессию массив с ключом `flash_message`:

   ~~~php
   $session = Session::instance();
   $session->set('flash_message', array(
       'type' => 'success',
       'text' => __('Текст сообщения'),
   ));
   ~~~

2. **Базовый контроллер** `Controller_Template::_getFlashMessage()` при
   следующем рендере страницы **читает сообщение из сессии и сразу его
   удаляет** (`get` + `delete`) — поэтому сообщение показывается ровно
   один раз. Метод `_getAlertClass()` превращает `type` в CSS-класс
   Bootstrap 3, результат кладётся в шаблон как переменная `$flash`:

   ~~~php
   array(
       'type'  => 'success',
       'text'  => '...',
       'class' => 'alert-success',
   )
   ~~~

3. **Шаблон** `views/template.php` рендерит alert, если у `$flash`
   заполнен `class` (это отсекает «псевдо-flash» от ошибок формы —
   см. ниже):

   ~~~php
   <?php if (!empty($flash) && !empty($flash['class'])): ?>
       <div class="alert <?php echo $flash['class']; ?> alert-dismissible fade in" role="alert">
           <button type="button" class="close" data-dismiss="alert" aria-label="Close">
               <span aria-hidden="true">&times;</span>
           </button>
           <?php echo htmlspecialchars($flash['text']); ?>
       </div>
   <?php endif; ?>
   ~~~

### Два независимых канала

В `_getFlashMessage()` обрабатываются два канала уведомлений:

| Канал | Ключ в сессии | Где рендерится | Назначение |
|---|---|---|---|
| Общий flash | `flash_message` | `views/template.php`, внутри `.container`, сверху страницы | Успех/ошибка операции, редирект на другую страницу |
| Ошибки формы логина | `login_errors` | `views/top_menu.php`, прямо у формы входа | Ошибки входа, когда пользователь остаётся на форме |

Оба канала читаются **безусловно** и **самоочищаются** (`get`+`delete`
и `get_once()` соответственно). Ошибки формы передаются в `top_menu`
через `$flash['login_errors']` — даже если общего flash-сообщения нет,
`_getFlashMessage()` вернёт «псевдо-flash» с `type='login_error'` и
пустым `class`. Шаблон `template.php` такой псевдо-flash не выводит
(проверка `!empty($flash['class'])`), а `top_menu.php` — выводит.

Разделение нужно, чтобы:

- flash-сообщение об ошибке входа видел пользователь на **любой**
  странице после редиректа (в том числе не на странице логина);
- ошибки в форме логина отображались непосредственно рядом с полями
  ввода и содержали контекст «что именно не так с формой».

### Типы сообщений

| `type` в сессии | CSS-класс         | Смысл                       |
|-----------------|-------------------|-----------------------------|
| `success`       | `alert-success`   | Операция выполнена успешно  |
| `error`         | `alert-danger`    | Ошибка                      |
| `warning`       | `alert-warning`   | Предупреждение              |
| `info`          | `alert-info`      | Информационное сообщение    |
| *(любое иное)*  | `alert-info`      | Значение по умолчанию       |

Классы соответствуют **Bootstrap 3**. При обновлении до Bootstrap 4/5
потребуется заменить `fade in` на `fade show`, а `panel panel-primary` —
на `card border-primary` и т.п. Точки в коде, которые это затрагивает:
`views/template.php`, `views/top_menu.php`,
`Controller_Template::_getAlertClass()`.

### Защита от повторного чтения

В `Controller_Template` есть свойство `protected $_flash_processed`.
Оно поднимается в `TRUE` после первого вызова `_getFlashMessage()`.
Проверка `$has_flash` выглядит так:

~~~php
$has_flash = $this->_flash_processed OR isset($this->template->flash);
~~~

Почему не просто `isset($this->template->flash)`: `View::__isset()`
возвращает `FALSE` для `NULL`, а `_getFlashMessage()` как раз возвращает
`NULL`, когда сообщений нет. Если бы метод `_prepareTemplateData()`
вызвался повторно, сессия была бы прочитана второй раз. Сейчас
`_prepareTemplateData()` вызывается один раз из `before()`, но флаг
делает поведение устойчивым к рефакторингу.

### Пример: вывод alert после успешного входа

Из `basis/classes/Controller/login.php`:

~~~php
class Controller_Login extends Controller {

    public function action_index()
    {
        if (Auth::instance()->logged_in()) {
            $this->redirect('/');
        }

        if ($this->request->method() !== HTTP_Request::POST) {
            $this->redirect($this->request->referrer() ?: '/');
        }

        $username = Arr::get($_POST, 'username', '');
        $password = Arr::get($_POST, 'password', '');
        $remember = (bool) Arr::get($_POST, 'remember', false);

        $session = Session::instance();

        if (Auth::instance()->login($username, $password, $remember)) {
            $session->delete('login_errors');
            $session->set('flash_message', array(
                'type' => 'success',
                'text' => __('Вы успешно авторизованы как :username', array(
                    ':username' => $username,
                )),
            ));
        } else {
            $session->set('login_errors', array(__('Неверный логин или пароль')));
            $session->set('flash_message', array(
                'type' => 'error',
                'text' => __('Не удалось войти. Проверьте логин и пароль.'),
            ));
        }

        $this->redirect($this->request->referrer() ?: '/');
    }
}
~~~

При неудачном входе пользователь увидит **два разных текста**:
короткий «Неверный логин или пароль» — у формы (рендерится
`top_menu.php`), развёрнутый «Не удалось войти...» — сверху страницы
(рендерится `template.php`). Если пользователь уйдёт на другую страницу,
общий alert его догонит, а ошибка формы — нет (она рендерится только
на форме логина).

### Пример: alert из произвольного контроллера

Чтобы показать уведомление после любой операции, достаточно положить
`flash_message` в сессию и сделать `redirect()`:

~~~php
class Controller_People extends Controller_Template {

    public function action_delete()
    {
        $id = (int) $this->request->param('id');
        // ... удаление пользователя ...

        Session::instance()->set('flash_message', array(
            'type' => 'success',
            'text' => __('Пользователь :id удалён', array(':id' => $id)),
        ));

        $this->redirect('people');
    }

    public function action_import()
    {
        try {
            // ... импорт ...
            Session::instance()->set('flash_message', array(
                'type' => 'info',
                'text' => __('Импорт завершён'),
            ));
        } catch (Exception $e) {
            Session::instance()->set('flash_message', array(
                'type' => 'warning',
                'text' => __('Импорт выполнен с замечаниями: :err', array(
                    ':err' => $e->getMessage(),
                )),
            ));
        }

        $this->redirect('people');
    }
}
~~~

### Рекомендации при добавлении новых уведомлений

- Всегда делайте `redirect()` после установки `flash_message` — иначе
  сообщение может быть прочитано и удалено ещё в рамках текущего
  запроса и не дойдёт до пользователя.
- Текст пропускайте через `__()` — базовый словарь `basis/i18n/ru.php`
  уже содержит ряд сообщений (`Вы вышли из системы`,
  `Неверный логин или пароль` и т.д.).
- Используйте `error` (`alert-danger`) для ошибок, `warning` — для
  предупреждений, `success` — для успешных операций, `info` — для
  нейтральных уведомлений.
- Не полагайтесь на HTML в `text`: шаблон выводит его через
  `htmlspecialchars()`. Если нужна разметка — расширяйте шаблон
  или используйте отдельный ключ.
- Не кладите в `flash_message` одновременно `login_errors` — это
  разные каналы с разной областью применения.
- Если пишете свой контроллер, не наследующий `Controller_Template`,
  помните: механизм flash в нём работать не будет, потому что чтение
  из сессии выполняется именно в `Controller_Template::_getFlashMessage()`.

### История исправлений (версия 4.0.7)

До версии 4.0.7 механизм работал с ошибками:

- **`login_errors` не доходил до неавторизованного пользователя.**
  Метод `_getAuthData()` вызывался только при `is_admin === TRUE`,
  а `login_errors` читался именно в нём. В результате ошибки формы
  логина накапливались в сессии и не показывались.
- **Проверка `isset($this->template->flash)` давала `FALSE` для `NULL`** —
  хрупкое место, грозившее повторным чтением сессии при рефакторинге.
- **Alert в `template.php` стоял вне `.container`** — растягивался
  на всю ширину окна и не выравнивался с контентом.

В версии 4.0.7:

- `login_errors` читается в `_getFlashMessage()` безусловно, вне
  зависимости от `is_admin`;
- `_getAuthData()` больше не читает `login_errors`;
- добавлен флаг `_flash_processed`;
- alert перемещён внутрь `.container`;
- `auth` устанавливается в шаблон всегда, а не только для администраторов —
  теперь форма логина рендерится и для неавторизованных пользователей.

Подробности — в `README.md` модуля, раздел «Исправления механизма flash».

---