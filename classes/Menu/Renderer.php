<?php defined('SYSPATH') or die('No direct script access.');

class Menu_Renderer {
    
    // application/classes/Menu/Renderer.php
    // Заменяем метод should_display() на этот:

    private static function should_display($item)
    {
        // ЯВНЫЙ ЗАПРЕТ (имеет высший приоритет)
        if (isset($item['disabled']) && $item['disabled'] === true) {
            return false;
        }
        
        // Если нет условий - всегда показываем
        if (!isset($item['show'])) {
            return true;
        }
        
        $show_config = $item['show'];
        
        // Проверка авторизации
        if (isset($show_config['logged_in'])) {
            $logged_in = Auth::instance()->logged_in();
            
            if ($show_config['logged_in'] === true && !$logged_in) {
                return false;
            }
            
            if ($show_config['logged_in'] === false && $logged_in) {
                return false;
            }
        }
        
        // Проверка роли
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
        
        // Проверка по callable функции
        if (isset($show_config['callback']) && is_callable($show_config['callback'])) {
            if (!call_user_func($show_config['callback'], $item)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Получить все пункты меню с учетом условий отображения
     * @return array
     */
    public static function get_visible_items($name)
    {
        $all_items = Kohana::$config->load($name)->as_array();
        $visible_items = array();
        
        foreach ($all_items as $key => $item) {
            if (self::should_display($item)) {
                // Рекурсивно фильтруем дочерние пункты
                if (isset($item['children']) && !empty($item['children'])) {
                    $visible_children = array();
                    foreach ($item['children'] as $child_key => $child) {
                        if (self::should_display($child)) {
                            $visible_children[$child_key] = $child;
                        }
                    }
                    $item['children'] = $visible_children;
                    
                    // Если у родителя нет видимых дочерних пунктов, скрываем и его
                    if (empty($visible_children)) {
                        continue;
                    }
                }
                
                $visible_items[$key] = $item;
            }
        }
        
        // Сортируем
        uasort($visible_items, function($a, $b) {
            $order_a = isset($a['order']) ? $a['order'] : 999;
            $order_b = isset($b['order']) ? $b['order'] : 999;
            return $order_a - $order_b;
        });
        
        return $visible_items;
    }
    
    // application/classes/Menu/Renderer.php
    /**
     * Получить URL пункта меню
     */
    private static function get_url($item)
    {
        if (isset($item['route'])) {
            $params = isset($item['params']) ? $item['params'] : array();
            // URL::site() добавит базовый путь
            return URL::site(Route::get($item['route'])->uri($params));
            
        } elseif (isset($item['url'])) {
            $url = $item['url'];
            
            // Обработка специальных ссылок
            if (empty($url)) {
                return '#';
            }
            
            // Внешние ссылки не трогаем
            if (strpos($url, 'http://') === 0 || 
                strpos($url, 'https://') === 0 || 
                strpos($url, '//') === 0) {
                return $url;
            }
            
            // Якоря и javascript
            if (strpos($url, '#') === 0 || strpos($url, 'javascript:') === 0) {
                return $url;
            }
            
            // Нормализация: добавляем ведущий слеш если его нет
            if ($url[0] !== '/') {
                $url = '/' . $url;
            }
            
            // КЛЮЧЕВОЙ МОМЕНТ: добавляем базовый путь Kohana
            return URL::site($url);
        }
        return '#';
    }
    
    /**
     * Проверить, активен ли пункт меню
     */
    private static function is_active($item, $current_uri = null)
    {
        if ($current_uri === null) {
            $current_uri = Request::current()->uri();
        }
        
        $item_url = self::get_url($item);
        
        // Точное совпадение
        if ($current_uri === $item_url) {
            return true;
        }
        
        // Для корневого URL
        if ($item_url === '/' && $current_uri === '') {
            return true;
        }
        
        // Для вложенных URL
        if ($item_url !== '/' && strpos($current_uri, $item_url) === 0) {
            return true;
        }
        
        // Ручное указание активных URL
        if (isset($item['active_for'])) {
            $active_for = (array) $item['active_for'];
            foreach ($active_for as $pattern) {
                if (strpos($current_uri, $pattern) === 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Рекурсивно отрендерить пункты меню
     */
    private static function render_items($items, $current_uri, $depth = 0)
    {
        $html = '';
        
        foreach ($items as $key => $item) {
            $is_active = self::is_active($item, $current_uri);
            $has_children = isset($item['children']) && !empty($item['children']);
            
            // Формируем классы
            $li_classes = array();
            if ($is_active) {
                $li_classes[] = 'active';
            }
            if ($has_children) {
                $li_classes[] = 'dropdown';
            }
            $li_class_attr = !empty($li_classes) ? ' class="' . implode(' ', $li_classes) . '"' : '';
            
            $html .= '<li' . $li_class_attr . '>';
            
            // Ссылка
            $url = self::get_url($item);
            $icon_html = isset($item['icon']) ? '<i class="' . $item['icon'] . '"></i> ' : '';
            
            $a_classes = array();
            if ($has_children) {
                $a_classes[] = 'dropdown-toggle';
            }
            $a_class_attr = !empty($a_classes) ? ' class="' . implode(' ', $a_classes) . '"' : '';
            
            $data_attr = $has_children ? ' data-toggle="dropdown"' : '';
            
            // Добавляем data-атрибуты для подсказки
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
            
            // Вложенные пункты
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
    
    /**
     * Отрендерить меню
     */
    public static function render($name='menu', $ul_class = 'nav')
    {
        $items = self::get_visible_items($name);
        $current_uri = Request::current()->uri();
        
        if (empty($items)) {
            return '';
        }
        
        $class_attr = $ul_class ? ' class="' . $ul_class . '"' : '';
        $html = '<ul' . $class_attr . '>';
        $html .= self::render_items($items, $current_uri);
        $html .= '</ul>';
        
        // Добавляем CSS и JS для подсказок (только один раз)
        $html .= self::get_tooltip_assets();
        
        return $html;
    }
    
    /**
     * Получить CSS и JS для подсказок
     */
    private static function get_tooltip_assets()
    {
        static $loaded = false;
        
        if ($loaded) {
            return '';
        }
        
        $loaded = true;
        
        return '
        <style>
            /* Стили для всплывающей подсказки */
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
                    
                    // Позиционирование
                    const rect = currentTarget.getBoundingClientRect();
                    let top = rect.bottom + 10;
                    let left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2);
                    
                    // Проверяем границы
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
                    
                    // Если уже есть таймер - отменяем
                    if (tooltipTimer) {
                        clearTimeout(tooltipTimer);
                    }
                    
                    // Запускаем таймер на 1 секунды
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
                
                // Инициализация после загрузки DOM
                if (document.readyState === \'loading\') {
                    document.addEventListener(\'DOMContentLoaded\', initTooltips);
                } else {
                    initTooltips();
                }
                
                function initTooltips() {
                    const menuItems = document.querySelectorAll(\'.nav li > a, .dropdown-menu li > a\');
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