<?php
//echo Debug::vars('2', $config_windows);//exit;
//echo Debug::vars('3', $list_windows1);//exit;
?>
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title"><?php echo __('Панель управления')?></h3>
  </div>
  <div class="panel-body">

	<?php
	
	echo __('Пнаель управления СКУД Артонит Сити');
	
	
	?>
   
    </div>
<?php
//вывод номера сборки
    echo 'City version ' . (defined('CITY_VERSION') ? CITY_VERSION : 'unknown');
?> 
</div>

