<?php defined('SYSPATH') or die('No direct script access.');

class Menu_Renderermenu {
    
    private static function should_display($item)
    {
        if (isset($item['disabled']) && $item['disabled'] === true) {
            return false;
        }
        
        if (!isset($item['show'])) {
            return true;
        }
        
        $show_config = $item['show'];
        
        if (isset($show_config['logged_in'])) {
            $logged_in = Auth::instance()->logged_in();
            
            if ($show_config['logged_in'] === true && !$logged_in) {
                return false;
            }
            
            if ($show_config['logged_in'] === false && $logged_in) {
                return false;
            }
        }
        
        if (isset($show_config['roles'])) {
            $roles = (array) $show_config['roles'];
            $has_role = false;
            
            foreach ($roles as $role) {
                if (Auth::instance()->logged_in($role)) {
                    $has_role = true;
                    break;
                }
            }
            
            if (!$has_role) {
                return false;
            }
        }
        
        if (isset($show_config['callback']) && is_callable($show_config['callback'])) {
            if (!call_user_func($show_config['callback'], $item)) {
                return false;
            }
        }
        
        return true;
    }
    
    public static function get_visible_items($name)
    {
        $all_items = Kohana::$config->load($name)->as_array();
        $visible_items = array();
        
        foreach ($all_items as $key => $item) {
            if (self::should_display($item)) {
                if (isset($item['children']) && !empty($item['children'])) {
                    $visible_children = array();
                    foreach ($item['children'] as $child_key => $child) {
                        if (self::should_display($child)) {
                            $visible_children[$child_key] = $child;
                        }
                    }
                    $item['children'] = $visible_children;
                    
                    if (empty($visible_children)) {
                        continue;
                    }
                }
                
                $visible_items[$key] = $item;
            }
        }
        
        uasort($visible_items, function($a, $b) {
            $order_a = isset($a['order']) ? $a['order'] : 999;
            $order_b = isset($b['order']) ? $b['order'] : 999;
            return $order_a - $order_b;
        });
        
        return $visible_items;
    }
    
    private static function get_url($item)
    {
        if (isset($item['route'])) {
            $params = isset($item['params']) ? $item['params'] : array();
            return URL::site(Route::get($item['route'])->uri($params));
            
        } elseif (isset($item['url'])) {
            $url = $item['url'];
            
            if (empty($url)) {
                return '#';
            }
            
            if (strpos($url, 'http://') === 0 || 
                strpos($url, 'https://') === 0 || 
                strpos($url, '//') === 0) {
                return $url;
            }
            
            if (strpos($url, '#') === 0 || strpos($url, 'javascript:') === 0) {
                return $url;
            }
            
            if ($url[0] !== '/') {
                $url = '/' . $url;
            }
            
            return URL::site($url);
        }
        return '#';
    }
    
    /**
     * URI пункта меню для сравнения с текущим адресом.
     *
     * Отличается от get_url() тем, что возвращает «голый» URI без базового
     * пути приложения: "dashboard", "people/peopleinfo", "dev/load_order".
     * Именно в таком виде адрес отдаёт Request::current()->uri(), поэтому
     * сравнивать нужно с ним, а не с URL::site() — тот добавляет "/city/".
     *
     * @param   array   $item  пункт меню
     * @return  string  URI без обрамляющих слэшей; '' — если пункт не является
     *                  внутренней ссылкой (внешняя ссылка, '#', javascript:)
     */
    private static function get_uri($item)
    {
        if (isset($item['route'])) {
            $params = isset($item['params']) ? $item['params'] : array();
            return trim(Route::get($item['route'])->uri($params), '/');
        }

        if ( ! isset($item['url'])) {
            return '';
        }

        $url = (string) $item['url'];

        // Внешние ссылки, заглушки и пустой адрес с текущим URI не сравниваем
        if ($url === ''
            || strpos($url, 'http://') === 0
            || strpos($url, 'https://') === 0
            || strpos($url, '//') === 0
            || strpos($url, '#') === 0
            || strpos($url, 'javascript:') === 0) {
            return '';
        }

        return trim($url, '/');
    }

    private static function is_active($item, $current_uri = null)
    {
        if ($current_uri === null) {
            $current_uri = Request::current()->uri();
        }
        
        $current_uri = trim((string) $current_uri, '/');

        // Корень приложения отдан маршруту 'default' (см. application/bootstrap.php),
        // поэтому /city/ и /city/dashboard — одна и та же страница.
        if ($current_uri === '') {
            $default_route = Arr::get(Route::all(), 'default');
            if ($default_route instanceof Route) {
                $current_uri = trim((string) Arr::get($default_route->defaults(), 'controller'), '/');
            }
        }

        $item_uri = self::get_uri($item);

        if ($item_uri !== '') {
            // Пункт ведёт ровно на текущую страницу
            if ($item_uri === $current_uri) {
                return true;
            }

            // Пункт-раздел активен и на вложенных страницах: "people" — для
            // "people/peopleinfo", но "dev/load" — не для "dev/load_order"
            if (strpos($current_uri, $item_uri.'/') === 0) {
                return true;
            }
        }

        if (isset($item['active_for'])) {
            $active_for = (array) $item['active_for'];
            foreach ($active_for as $pattern) {
                $pattern = trim((string) $pattern, '/');
                if ($pattern !== '' && ($current_uri === $pattern || strpos($current_uri, $pattern.'/') === 0)) {
                    return true;
                }
            }
        }

        return false;
    }
    
    private static function render_items($items, $current_uri, $depth = 0)
    {
        $html = '';
        
        foreach ($items as $key => $item) {
            $is_active = self::is_active($item, $current_uri);
            $has_children = isset($item['children']) && !empty($item['children']);
            
            $li_classes = array();
            if ($is_active) {
                $li_classes[] = 'active';
            }
            if ($has_children) {
                $li_classes[] = 'dropdown';
            }
            $li_class_attr = !empty($li_classes) ? ' class="' . implode(' ', $li_classes) . '"' : '';
            
            $html .= '<li' . $li_class_attr . '>';
            
            $url = self::get_url($item);
            $icon_html = isset($item['icon']) ? '<i class="' . $item['icon'] . '"></i> ' : '';
            
            $a_classes = array();
            if ($has_children) {
                $a_classes[] = 'dropdown-toggle';
            }
            $a_class_attr = !empty($a_classes) ? ' class="' . implode(' ', $a_classes) . '"' : '';
            
            $data_attr = $has_children ? ' data-toggle="dropdown"' : '';
            
            $order = isset($item['order']) ? $item['order'] : 999;
            $data_attr .= ' data-menu-title="' . HTML::chars($item['title']) . '"';
            $data_attr .= ' data-menu-order="' . $order . '"';
            $data_attr .= ' data-menu-url="' . $url . '"';
            
            $html .= '<a href="' . $url . '"' . $a_class_attr . $data_attr . '>';
            $html .= $icon_html . HTML::chars($item['title']);
            if ($has_children) {
                $html .= ' <b class="caret"></b>';
            }
            $html .= '</a>';
            
            if ($has_children && !empty($item['children'])) {
                $html .= '<ul class="dropdown-menu">';
                foreach ($item['children'] as $child_key => $child) {
                    $child_url = self::get_url($child);
                    $child_icon = isset($child['icon']) ? '<i class="' . $child['icon'] . '"></i> ' : '';
                    $child_active = self::is_active($child, $current_uri);
                    $child_class = $child_active ? ' class="active"' : '';
                    
                    $order = isset($child['order']) ? $child['order'] : 999;
                    
                    $html .= '<li' . $child_class . '>';
                    $html .= '<a href="' . $child_url . '"';
                    $html .= ' data-menu-title="' . HTML::chars($child['title']) . '"';
                    $html .= ' data-menu-order="' . $order . '"';
                    $html .= ' data-menu-url="' . $child_url . '"';
                    $html .= '>';
                    $html .= $child_icon . HTML::chars($child['title']);
                    $html .= '</a>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
            
            $html .= '</li>';
        }
        
        return $html;
    }
    
    public static function render($name='menu', $ul_class = 'nav')
    {
        $items = self::get_visible_items($name);
        $current_uri = Request::current()->uri();
        
        if (empty($items)) {
            return '';
        }
        
        // Добавляем класс 'nav-inline' для горизонтального меню
        $class_attr = $ul_class ? ' class="' . $ul_class . ' nav-inline"' : ' class="nav-inline"';
        $html = '<ul' . $class_attr . '>';
        $html .= self::render_items($items, $current_uri);
        $html .= '</ul>';
        
        $html .= self::get_tooltip_assets();
        
        return $html;
    }
    
    private static function get_tooltip_assets()
    {
        static $loaded = false;
        
        if ($loaded) {
            return '';
        }
        
        $loaded = true;
        
        return '
        <style>
            /* Горизонтальное меню - ВСЕ В ОДНУ СТРОКУ */
            .nav-inline {
                display: flex !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                justify-content: flex-start !important;
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            
            .nav-inline > li {
                display: flex !important;
                flex: 0 1 auto !important;
                min-width: 0 !important;
                position: relative !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .nav-inline > li > a {
                display: flex !important;
                align-items: center !important;
                padding: 8px 10px !important;
                white-space: nowrap !important;
                font-size: 13px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                min-width: 0 !important;
            }
            
            /* Уменьшаем отступы для компактности */
            .nav-inline > li:first-child > a {
                padding-left: 0 !important;
            }
            
            .nav-inline > li:last-child > a {
                padding-right: 0 !important;
            }
            
            /* Иконки */
            .nav-inline > li > a i {
                margin-right: 4px !important;
                flex-shrink: 0 !important;
            }
            
            /* Выпадающие меню */
            .nav-inline .dropdown-menu {
                position: absolute !important;
                top: 100% !important;
                left: 0 !important;
                z-index: 1000 !important;
                display: none !important;
                min-width: 180px !important;
                padding: 5px 0 !important;
                margin: 2px 0 0 !important;
                background-color: #fff !important;
                border: 1px solid #ccc !important;
                border-radius: 4px !important;
                box-shadow: 0 6px 12px rgba(0,0,0,.175) !important;
                white-space: nowrap !important;
            }
            
            .nav-inline .dropdown-menu > li {
                display: block !important;
            }
            
            .nav-inline .dropdown-menu > li > a {
                display: block !important;
                padding: 3px 20px !important;
                color: #333 !important;
                white-space: nowrap !important;
            }
            
            .nav-inline .dropdown:hover .dropdown-menu {
                display: block !important;
            }
            
            .nav-inline .dropdown.open .dropdown-menu {
                display: block !important;
            }
            
            /* Автоматическое сжатие при нехватке места */
            @media (max-width: 1200px) {
                .nav-inline > li > a {
                    padding: 6px 8px !important;
                    font-size: 12px !important;
                }
                
                .nav-inline > li > a i {
                    margin-right: 3px !important;
                }
            }
            
            @media (max-width: 992px) {
                .nav-inline > li > a {
                    padding: 5px 6px !important;
                    font-size: 11px !important;
                }
                
                .nav-inline > li > a i {
                    margin-right: 2px !important;
                    font-size: 12px !important;
                }
            }
            
            @media (max-width: 768px) {
                .nav-inline > li > a {
                    padding: 4px 5px !important;
                    font-size: 10px !important;
                }
                
                .nav-inline > li > a i {
                    margin-right: 2px !important;
                    font-size: 11px !important;
                }
            }
            
            /* Стили для подсказок */
            .menu-tooltip {
                position: fixed;
                background: rgba(0, 0, 0, 0.9);
                color: #fff;
                padding: 10px 15px;
                border-radius: 6px;
                font-size: 13px;
                z-index: 99999;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
                box-shadow: 0 4px 12px rgba(0,0,0,0.4);
                max-width: 320px;
                font-family: Arial, sans-serif;
                border: 1px solid rgba(255,255,255,0.1);
                white-space: nowrap;
            }
            
            .menu-tooltip.visible {
                opacity: 1;
            }
            
            .menu-tooltip .tooltip-title {
                font-weight: bold;
                color: #fff;
                font-size: 14px;
                margin-bottom: 3px;
            }
            
            .menu-tooltip .tooltip-order {
                color: #ffd700;
                font-size: 12px;
                margin-top: 2px;
            }
            
            .menu-tooltip .tooltip-url {
                color: #66ccff;
                font-size: 11px;
                margin-top: 2px;
                word-break: break-all;
                opacity: 0.8;
            }
            
            .menu-tooltip::after {
                content: \'\';
                position: absolute;
                bottom: -8px;
                left: 50%;
                transform: translateX(-50%);
                border-left: 8px solid transparent;
                border-right: 8px solid transparent;
                border-top: 8px solid rgba(0, 0, 0, 0.9);
            }
            
            .menu-tooltip.top::after {
                bottom: -8px;
                border-top: 8px solid rgba(0, 0, 0, 0.9);
            }
            
            .menu-tooltip.bottom::after {
                top: -8px;
                bottom: auto;
                border-top: none;
                border-bottom: 8px solid rgba(0, 0, 0, 0.9);
            }
            
            /* Обертка для скролла если совсем не помещается */
            .nav-inline-wrapper {
                overflow-x: auto !important;
                overflow-y: visible !important;
                -webkit-overflow-scrolling: touch !important;
                scrollbar-width: thin !important;
            }
            
            .nav-inline-wrapper::-webkit-scrollbar {
                height: 4px !important;
            }
            
            .nav-inline-wrapper::-webkit-scrollbar-thumb {
                background: #888 !important;
                border-radius: 2px !important;
            }
            
            .nav-inline-wrapper::-webkit-scrollbar-thumb:hover {
                background: #555 !important;
            }
        </style>
        
        <script>
            (function() {
                let tooltipTimer = null;
                let tooltipElement = null;
                let currentTarget = null;
                let isTooltipVisible = false;
                
                function createTooltip() {
                    if (!tooltipElement) {
                        tooltipElement = document.createElement(\'div\');
                        tooltipElement.className = \'menu-tooltip\';
                        tooltipElement.innerHTML = \'<div class="tooltip-title"></div><div class="tooltip-order"></div><div class="tooltip-url"></div>\';
                        document.body.appendChild(tooltipElement);
                    }
                    return tooltipElement;
                }
                
                function showTooltip(event, data) {
                    const tooltip = createTooltip();
                    
                    tooltip.querySelector(\'.tooltip-title\').textContent = \'📌 \' + data.title;
                    tooltip.querySelector(\'.tooltip-order\').textContent = \'Порядок: \' + data.order;
                    tooltip.querySelector(\'.tooltip-url\').textContent = \'URL: \' + data.url;
                    
                    const rect = currentTarget.getBoundingClientRect();
                    let top = rect.bottom + 10;
                    let left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2);
                    
                    const tooltipRect = tooltip.getBoundingClientRect();
                    
                    if (top + tooltipRect.height > window.innerHeight - 10) {
                        top = rect.top - tooltipRect.height - 10;
                        tooltip.className = \'menu-tooltip bottom\';
                    } else {
                        tooltip.className = \'menu-tooltip top\';
                    }
                    
                    if (left < 10) left = 10;
                    if (left + tooltipRect.width > window.innerWidth - 10) {
                        left = window.innerWidth - tooltipRect.width - 10;
                    }
                    
                    tooltip.style.top = top + \'px\';
                    tooltip.style.left = left + \'px\';
                    tooltip.classList.add(\'visible\');
                    isTooltipVisible = true;
                }
                
                function hideTooltip() {
                    if (tooltipElement) {
                        tooltipElement.classList.remove(\'visible\');
                        isTooltipVisible = false;
                    }
                    if (tooltipTimer) {
                        clearTimeout(tooltipTimer);
                        tooltipTimer = null;
                    }
                    currentTarget = null;
                }
                
                function handleMouseEnter(event) {
                    const target = event.currentTarget;
                    const title = target.getAttribute(\'data-menu-title\');
                    const order = target.getAttribute(\'data-menu-order\');
                    const url = target.getAttribute(\'data-menu-url\');
                    
                    if (!title) return;
                    
                    currentTarget = target;
                    
                    if (tooltipTimer) {
                        clearTimeout(tooltipTimer);
                    }
                    
                    tooltipTimer = setTimeout(function() {
                        showTooltip(event, {
                            title: title,
                            order: order,
                            url: url
                        });
                    }, 1000);
                }
                
                function handleMouseLeave(event) {
                    hideTooltip();
                }
                
                function handleMouseMove(event) {
                    if (isTooltipVisible && tooltipElement && currentTarget) {
                        const rect = currentTarget.getBoundingClientRect();
                        let top = rect.bottom + 10;
                        let left = rect.left + (rect.width / 2) - (tooltipElement.offsetWidth / 2);
                        
                        const tooltipRect = tooltipElement.getBoundingClientRect();
                        
                        if (top + tooltipRect.height > window.innerHeight - 10) {
                            top = rect.top - tooltipRect.height - 10;
                        }
                        
                        if (left < 10) left = 10;
                        if (left + tooltipRect.width > window.innerWidth - 10) {
                            left = window.innerWidth - tooltipRect.width - 10;
                        }
                        
                        tooltipElement.style.top = top + \'px\';
                        tooltipElement.style.left = left + \'px\';
                    }
                }
                
                if (document.readyState === \'loading\') {
                    document.addEventListener(\'DOMContentLoaded\', initTooltips);
                } else {
                    initTooltips();
                }
                
                function initTooltips() {
                    const menuItems = document.querySelectorAll(\'.nav-inline li > a, .dropdown-menu li > a\');
                    menuItems.forEach(function(item) {
                        item.addEventListener(\'mouseenter\', handleMouseEnter);
                        item.addEventListener(\'mouseleave\', handleMouseLeave);
                        item.addEventListener(\'mousemove\', handleMouseMove);
                    });
                }
            })();
        </script>
        ';
    }
}