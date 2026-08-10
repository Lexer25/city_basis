<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../../assets/ico/favicon.png">

    <title>Artonit City <?echo  isset(Kohana::$config->load('artonitcity_config')->city_name)? Kohana::$config->load('artonitcity_config')->city_name : '';?></title>

    <!-- Bootstrap core CSS -->
    <?= HTML::style('static/css/bootstrap.css'); ?>
	<?= HTML::style('static/css/modal.css'); ?>
    <?//= HTML::style('static/css/admin.css'); ?>
	<?//= HTML::style('static/css/timesheet.css'); ?>
	<?= HTML::style('static/css/city.css'); ?>
	<?//= HTML::style('static/css/modal.css'); ?>
	<link rel="stylesheet" href="/city/static/css/themes/blue/style.css" type="text/css" media="print, projection, screen" />
	 

  </head>
    <body>
		<div class="container">
				<!-- Static navbar -->
			 <div class="navbar navbar-default">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					  <?= HTML::anchor('dashboard', __('City'),  array('class'=>'navbar-brand')) ?>
				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav">
						<li><?php echo HTML::anchor('skud', __('сводная'));?></li>
					</ul>
					<ul class="nav navbar-nav">
						<li><?php echo HTML::anchor('dbsetting', __('Настройка'));?></li>
					</ul>
					
				</div>
			</div>
			<div class="panel panel-primary">
			  <div class="panel-heading">
				<h3 class="panel-title"><?echo __('err_mess')?></h3>
			  </div>
			  <div class="panel-body">
				
				<?
				
				//	echo Debug::vars('17', mb_detect_encoding($err, ['UTF-8', 'Windows-1251', 'KOI8-R', 'ISO-8859-5']));//exit;
				echo date('Y.m.d H:m', time()). '<br>'. iconv('CP1251', 'UTF-8//IGNORE', $err);
				
				?>
				
			  </div>
			</div>
		</div>
	
  </body>
</html>

