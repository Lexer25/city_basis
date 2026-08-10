<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Artonit City - панель управления СКУД Артонит">
    <meta name="author" content="www.artonit.ru">
    <link rel="shortcut icon" href="/city/favicon.ico">

    <title>
        Artonit City 
        <?php 
        if (!empty($site['city_name'])) {
            echo HTML::chars($site['city_name']);
        }
        if (!empty($site['title'])) {
            echo ' - ' . HTML::chars($site['title']);
        }
        ?>
    </title>

    <!-- CSS -->
    <link rel="stylesheet" href="/city/static/css/bootstrap.min.css">
    <link rel="stylesheet" href="/city/static/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="/city/static/css/modal.css">
    <link rel="stylesheet" href="/city/static/css/city.css">
    <link rel="stylesheet" href="/city/static/css/2.31.3/theme.blue.min.css">
    <link rel="stylesheet" href="/city/static/css/2.31.3/jquery.tablesorter.pager.min.css">
	
	<!-- ДОБАВЬТЕ jQuery UI CSS -->
    
    <link rel="stylesheet" href="/city/static/css/jquery-ui.css">
	
	
    
    <!-- JavaScript -->
    <script src="/city/static/js/jquery-2.2.4.js"></script>
	
	<script src="/city/static/js/jquery-ui.min.js"></script>
	
    <script src="/city/static/js/moment-with-locales.min.js"></script>
    <script src="/city/static/js/bootstrap.min.js"></script>
    <script src="/city/static/js/bootstrap-datetimepicker.min.js"></script>
    <script src="/city/static/js/2.31.3/jquery.tablesorter.min.js"></script>
    <script src="/city/static/js/2.31.3/jquery.tablesorter.widgets.min.js"></script>
    <script src="/city/static/js/2.31.3/jquery.tablesorter.pager.min.js"></script>
	
	
</head>

<body>
    <!-- Flash-сообщения -->
    <?php if (!empty($flash)): ?>
        <div class="alert <?php echo $flash['class']; ?> alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php echo htmlspecialchars($flash['text']); ?>
        </div>
    <?php endif; ?>

    <!-- Контейнер -->
    <div class="<?php echo (!empty($site['full_width'])) ? 'container-fluid' : 'container'; ?>">
        <span id="time-top"></span>
        
        <!-- Меню -->
        <?php 
        // Заменяем оператор ?? на тернарный оператор с isset()
        $menu_html = isset($menu['menu_html']) ? $menu['menu_html'] : '';
        $adm_html = isset($menu['adm_html']) ? $menu['adm_html'] : '';
        $auth_data = isset($auth) ? $auth : array();
        $version_data = isset($version) ? $version : array();
        $odbc_data = isset($odbc) ? $odbc : array();
      
        echo View::factory('top_menu', array(
            'menu_html' => $menu_html,
            'adm_html' => $adm_html,
            'auth' => $auth_data,
            'version' => $version_data,
            'odbc' => $odbc_data,
        ))->render(); 
        ?>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <!-- Контент -->
        <?php echo isset($content) ? $content : ''; ?>
        
        <!-- Кнопка "Наверх" -->
        <button onclick="topFunction()" id="myBtn" title="<?php echo __('top'); ?>">
            <?php echo __('top'); ?>
        </button>
    </div>

    <!-- Время -->
    <span id="time-bottom" style="display:none;">
       <?php if (defined('START_TIME')): ?>
Страница подготовлена за <?php echo round(microtime(TRUE) - START_TIME, 3); ?> сек.
<?php endif; ?>
    </span>
    
    <!-- Скрипты -->
    <script>
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        var btn = document.getElementById("myBtn");
        if (!btn) return;
        btn.style.display = (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) 
            ? "block" : "none";
    }

    function topFunction() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var bottomTime = document.getElementById('time-bottom');
        var topTime = document.getElementById('time-top');
        if (bottomTime && topTime) {
            topTime.textContent = bottomTime.textContent;
        }
    });
    </script>

    <script>
    $(document).ready(function() {
        $('.dropdown-toggle').dropdown();
    });
    </script>
</body>
</html>