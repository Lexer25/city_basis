<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo HTML::chars($title) ?> | <?php echo __('User Guide') ?></title>

<?php foreach ($styles as $style => $media) echo HTML::style($style, array('media' => $media), NULL, TRUE), "\n" ?>

<?php foreach ($scripts as $script) echo HTML::script($script, NULL, NULL, TRUE), "\n" ?>

</head>
<body>

	<div id="kodoc-header">
		<div class="container">
			<a href="<?php echo URL::site('dashboard', TRUE) ?>" id="kodoc-logo">
				<img src="<?php echo Route::url('docs/media', array('file' => 'img/Artonit_logo.png')) ?>" />
			</a>

			<div id="kodoc-menu">
				<ul>
					<li class="first">
						<a href="<?php echo URL::site('dashboard', TRUE) ?>">Артонит Сити</a>
					</li>

					<li class="guide"
						style="border-left:1px solid #ccc; padding-left:15px; margin-left:15px;">
						<a href="<?php echo Route::url('docs/guide') ?>">User Guide</a>
					</li>

					<?php if (Kohana::$config->load('userguide.api_browser')): ?>
					<li class="api">
						<a href="<?php echo Route::url('docs/api') ?>">API Browser</a>
					</li>
					<?php endif ?>
				</ul>
			</div>
		</div>
	</div>

	<div id="kodoc-content">
		<div class="wrapper">
			<div class="container">
				<?php if (isset($breadcrumb) && is_array($breadcrumb) && count($breadcrumb) > 1): ?>
				<div class="span-22 prefix-1 suffix-1">
					<ul id="kodoc-breadcrumb">
						<?php foreach ($breadcrumb as $link => $title): ?>
							<?php if (is_string($link)): ?>
							<li><?php echo HTML::anchor($link, $title, NULL, NULL, TRUE) ?></li>
							<?php else: ?>
							<li class="last"><?php echo HTML::chars($title) ?></li>
							<?php endif ?>
						<?php endforeach ?>
					</ul>
				</div>
				<?php endif ?>
				<div class="span-6 prefix-1">
					<div id="kodoc-topics">
						<?php echo $menu ?>
					</div>
				</div>
				<div id="kodoc-body" class="span-16 suffix-1 last">
					<?php echo $content ?>
				</div>
			</div>
		</div>
	</div>

	<div id="kodoc-footer">
		<div class="container">
			<div class="span-12">
			<?php if (isset($copyright)): ?>
				<p><?php echo HTML::chars($copyright) ?></p>
			<?php else: ?>
				&nbsp;
			<?php endif ?>
			</div>
			<div class="span-12 last right">
			<p>Powered by Kohana v<?php echo Kohana::VERSION ?></p>
			</div>
		</div>
	</div>

</body>
</html>