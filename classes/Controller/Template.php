<?php
// application/classes/Controller/Template.php
class Controller_Template extends Kohana_Controller_Template {
	
	
	// В Kohana_Controller_Template определено:
//public $template = 'template'; // имя файла шаблона по умолчанию
    
   protected $is_admin = false;
   public $db = 'fb';

   /**
    * Флаг: flash-сообщение уже прочитано и передано в шаблон.
    * Нужен потому, что View::__isset() возвращает FALSE для NULL,
    * и повторный вызов _prepareTemplateData() снова дёрнул бы сессию.
    */
   protected $_flash_processed = FALSE;

	/**
     * Переопределяем before() для автоматической подготовки данных
     */
     public function before() {
        parent::before();
      I18n::lang('ru-ru');  
        // Инициализируем is_admin один раз в базовом контроллере
        $modules = Kohana::modules();
			if (isset($modules['auth']) AND class_exists('Auth'))
			{
				$this->is_admin = Auth::instance()->logged_in('admin');
			}
			else
			{
				$this->is_admin = FALSE;
			}
        View::bind_global('is_admin', $this->is_admin);
        
        if (!is_object($this->template)) {
            return;
        }
    
        $config = Kohana::$config->load('artonitcity_config');
        $this->_prepareTemplateData($config);
    }
    
    /**
     * Подготовка данных для шаблона
     */
    protected function _prepareTemplateData($config) {
		
        // Проверяем, установлены ли уже данные (чтобы не перезаписывать)
        // В Kohana View нет метода as_array(), используем прямой доступ к переменным
        $has_site = isset($this->template->site);
        $has_menu = isset($this->template->menu);
        $has_auth = isset($this->template->auth);
        $has_version = isset($this->template->version);
        // Флаг «flash уже обработан»: View::__isset() возвращает FALSE для NULL,
        // поэтому одного isset($this->template->flash) недостаточно.
        $has_flash = $this->_flash_processed OR isset($this->template->flash);
        $has_odbc = isset($this->template->odbc);
		$has_module = isset($this->template->module_info);
     
        // Подготавливаем данные только для отсутствующих ключей
        if (!$has_site) {
            $this->template->set('site', array(
                'city_name' => Arr::get($config, 'city_name', ''),
                'title' => $this->_getPageTitle(),
                'full_width' => isset($this->full_width) ? $this->full_width : false,
            ));
        }
        
        if (!$has_menu) {
            $this->template->set('menu', array(
                'menu_html' => Menu_Renderermenu::render('menu', 'nav navbar-nav'),
                'adm_html' => Menu_Renderermenu::render('adm', 'nav navbar-nav'),
            ));
        }
        
        // auth нужен всем: форма логина выводится и для неавторизованных,
        // а _getAuthData() теперь не читает login_errors (см. ниже).
        if (!$has_auth) {
            $this->template->set('auth', $this->_getAuthData());
        }
        
        if (!$has_version) {
            $this->template->set('version', $this->_getVersionData($config));
        }
        
        if (!$has_flash) {
            $this->template->set('flash', $this->_getFlashMessage());
            $this->_flash_processed = TRUE;
        }
	
		if (!$has_odbc) {
            $this->template->set('odbc', $this->_getODBC());
        }
		
		if (!$has_module) {
        $module_info = $this->_getCurrentModuleInfo();
        $this->template->set('module_info', $module_info);
		}
		
    }
    
/**
 * Получение DSN из конфигурации базы данных
 */
protected function _getODBC() {
    $config = Kohana::$config->load('database');
	$result=array(
		
		'dsn'=> isset($config['fb']['connection']['dsn'])?  $config['fb']['connection']['dsn'] : '---',
	
	
	);
   
    
    return $result;
}
    
    /**
     * Получение заголовка страницы
     */
    protected function _getPageTitle() {
        return isset($this->title) ? $this->title : '';
    }
    
    /**
     * Получение данных авторизации
     *
     * Ошибки формы логина здесь НЕ читаются: они обрабатываются
     * в _getFlashMessage() единообразно с общим flash-каналом и
     * передаются в шаблон через $flash['login_errors'].
     */
    protected function _getAuthData() {
        $auth = Auth::instance();
        
        return array(
            'logged_in' => $auth->logged_in(),
            'username'  => $auth->logged_in() ? $auth->get_user() : '',
            'csrf_token' => $this->_getCsrfToken(),
            'post_data' => array(
                'username' => Arr::get($_POST, 'username', ''),
                'remember' => Arr::get($_POST, 'remember', false),
            ),
        );
    }
    
    /**
     * Получение CSRF-токена
     */
    protected function _getCsrfToken() {
        if (class_exists('Security') && method_exists('Security', 'token')) {
            return Security::token();
        }
        return null;
    }
    
    /**
     * Получение данных о версии
     */
    protected function _getVersionData($config) {
        $result = array(
            'text' => '',
            'color' => '',
            //'ver' => Arr::get($config, 'ver', ''),
            'ver' => BASIS_VERSION,
            'timeUpdate' => Arr::get($config, 'timeUpdate', null),
        );
        
        if (empty($result['ver'])) {
            return $result;
        }
        
        $lightVerDay = Arr::get($config, 'lightVerDay', 3);
        $timeUpdate = $result['timeUpdate'];
        
        if ($timeUpdate) {
            try {
                $current_date = new DateTime();
                $update_date = new DateTime($timeUpdate);
                $interval = $current_date->diff($update_date);
                $days_diff = $interval->days;
                
                if ($days_diff < $lightVerDay) {
                    $result['color'] = 'label-success';
                    $result['text'] = __('<span class="label :color">Версия :ver обновление :timeUpdate</span>', array(
                        ':ver' => HTML::chars($result['ver']),
                        ':timeUpdate' => HTML::chars($timeUpdate),
                        ':color' => $result['color'],
                    ));
                } else {
                    $result['text'] = __('Версия :ver обновление :timeUpdate', array(
                        ':ver' => HTML::chars($result['ver']),
                        ':timeUpdate' => HTML::chars($timeUpdate),
                    ));
                }
            } catch (Exception $e) {
                Kohana::$log->add(Log::ERROR, 'Invalid date format in config: :date', [
                    ':date' => $timeUpdate
                ]);
                $result['text'] = __('Версия :ver', array(':ver' => HTML::chars($result['ver'])));
            }
        } else {
            $result['text'] = __('Версия :ver', array(':ver' => HTML::chars($result['ver'])));
        }
        
        return $result;
    }
    
    /**
     * Получение flash-сообщения.
     *
     * Обрабатывает два независимых канала:
     *   1. flash_message  — общее уведомление (успех/ошибка операции),
     *                       рендерится в template.php;
     *   2. login_errors   — ошибки формы логина,
     *                       рендерится в top_menu.php у формы входа.
     *
     * Оба читаются и удаляются из сессии — это и делает их «flash».
     *
     * Возвращает:
     *   - NULL                       — сообщений нет;
     *   - массив с ключами type/text/class
     *                                — есть общий flash (может содержать
     *                                  и login_errors);
     *   - массив с type='login_error',
     *     пустыми text/class и ключом
     *     login_errors               — есть только ошибки формы;
     *                                template.php такой «псевдо-flash»
     *                                не выводит (см. проверку class).
     */
    protected function _getFlashMessage() {
        $session = Session::instance();

        $flash = NULL;

        // 1. Общий flash-канал (успех/ошибка операции)
        $message = $session->get('flash_message');
        if ($message) {
            $session->delete('flash_message');

            $type = Arr::get($message, 'type', 'info');

            $flash = array(
                'type'  => $type,
                'text'  => Arr::get($message, 'text', ''),
                'class' => $this->_getAlertClass($type),
            );
        }

        // 2. Ошибки формы логина — отдельный канал, рендерится прямо у формы.
        //    Читаем через get_once() (самоочищающийся) независимо от того,
        //    авторизован пользователь или нет.
        $login_errors = $session->get_once('login_errors', array());

        if ($flash !== NULL) {
            $flash['login_errors'] = $login_errors;
        } elseif (!empty($login_errors)) {
            // Есть только ошибки формы — отдаём их как «псевдо-flash»,
            // чтобы top_menu их увидел, но в общий поток страницы
            // (template.php) они не попали.
            $flash = array(
                'type'         => 'login_error',
                'text'         => '',
                'class'        => '',
                'login_errors' => $login_errors,
            );
        }

        return $flash;
    }
    
    /**
     * Получение класса для alert.
     *
     * ВНИМАНИЕ: классы соответствуют Bootstrap 3. При обновлении
     * до Bootstrap 4/5 потребуется адаптация (alert-* сохранены,
     * но анимация `fade in` → `fade show` и т.д.).
     */
    protected function _getAlertClass($type) {
        $map = array(
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
        );
        
        return Arr::get($map, $type, 'alert-info');
    }
    
    /**
     * Установка заголовка страницы.
     *
     * Вызывать можно и до parent::before(), и после: в первом случае заголовок
     * подхватит _prepareTemplateData(), во втором метод обновит site в шаблоне.
     */
    protected function set_title($title) {
        $this->title = $title;
        
        // Если шаблон уже существует, обновляем данные
        if (isset($this->template) && is_object($this->template)) {
            // В Kohana View нет метода get(), используем прямой доступ к свойству
            if (isset($this->template->site)) {
                $site = $this->template->site;
                if (is_array($site)) {
                    $site['title'] = $title;
                    $this->template->set('site', $site);
                }
            }
        }
    }
    
/**
 * Установка full-width режима
 */
protected function set_full_width($enabled = true) {
    $this->full_width = $enabled;
    
    // Если шаблон уже существует, обновляем данные
    if (isset($this->template) && is_object($this->template)) {
        // В Kohana View нет метода get(), используем прямой доступ к свойству
        if (isset($this->template->site)) {
            $site = $this->template->site;  // ← вместо $this->template->get('site')
            if ($site !== null && is_array($site)) {
                $site['full_width'] = $enabled;
                $this->template->set('site', $site);
            }
        }
    }
}



			/**
			 * Определение текущего модуля и его версии
			 */
			protected function _getCurrentModuleInfo() {
				$result = array(
					'name' => '',
					'version' => '',
					'full_info' => ''
				);
				
				// Модуль определяем по файлу, из которого загружен контроллер,
				// а не по совпадению имён: "devices" содержит "dev",
				// "identifiertypref" содержит "identifier".
				$file = FALSE;
				try {
					$reflection = new ReflectionClass(get_class($this));
					$file = $reflection->getFileName();
				} catch (Exception $e) {
					$file = FALSE;
				}
				
				if ($file) {
					$file = str_replace('\\', '/', $file);
					$best = -1;
					foreach (Kohana::modules() as $module_name => $module_path) {
						$module_path = str_replace('\\', '/', $module_path);
						// Побеждает модуль с самым длинным совпавшим путём: пути
						// заканчиваются разделителем, поэтому "...\bas\" не
						// совпадёт с "...\baseref\...".
						if (strpos($file, $module_path) === 0 AND strlen($module_path) > $best) {
							$best = strlen($module_path);
							$result['name'] = $module_name;
						}
					}
				}
				
				// Версия модуля — по принятому соглашению: константа
				// <МОДУЛЬ>_VERSION определена в init.php каждого модуля.
				if ($result['name'] !== '') {
					$version_constant = strtoupper($result['name']) . '_VERSION';
					if (defined($version_constant)) {
						$result['version'] = constant($version_constant);
					}
				}

				// Наличие справки: config/userguide.php в каталоге модуля
				$result['has_guide'] = $this->_hasUserguideConfig($result['name']);

				// Формируем полную информацию
				if ($result['name'] !== '' AND $result['version'] !== '') {
					$result['full_info'] = __('Модуль: :module, Версия: :version', array(
						':module' => HTML::chars($result['name']),
						':version' => HTML::chars($result['version'])
					));
				}

				return $result;
			}

		/**
		 * Проверяет, есть ли у модуля конфиг userguide.php,
		 * то есть зарегистрирована ли для него справка.
		 *
		 * @param   string  $module_name  имя модуля (ключ в Kohana::modules())
		 * @return  boolean
		 */
		protected function _hasUserguideConfig($module_name)
		{
			if ($module_name === '')
			{
				return FALSE;
			}

			$modules = Kohana::modules();

			if ( ! isset($modules[$module_name]))
			{
				return FALSE;
			}

			$config_file = rtrim($modules[$module_name], '/\\')
						 . DIRECTORY_SEPARATOR . 'config'
						 . DIRECTORY_SEPARATOR . 'userguide.php';

			return is_file($config_file);
		}


}
