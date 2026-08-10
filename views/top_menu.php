<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<nav class="navbar navbar-default navbar-fixed-top disable" role="navigation">
    <div class="container-fluid">
        
        <!-- Меню пользователя, выводится без авторизации-->
        <div class="navbar-collapse collapse">
            <?php echo isset($menu_html) ? $menu_html : ''; ?>
        </div>
        
        <!-- Меню администратора, выводится после авторизации-->
        <div class="navbar-collapse collapse">
            <?php echo isset($adm_html) ? $adm_html : ''; ?>
        </div>
        
        <!-- Авторизация -->
        <ul class="nav navbar-nav navbar-right">
            <li>
                <?php if (!empty($auth['logged_in'])): ?>
                    <div class="navbar-text" style="padding-right: 15px;">
                        <span class="glyphicon glyphicon-user" style="margin-right: 5px;"></span>
                        <span style="display: inline-block; margin-right: 10px; vertical-align: middle;">
                            <?php echo HTML::chars($auth['username']); ?>
                        </span>
                        <span style="display: inline-block; vertical-align: middle;">
                            <?php echo HTML::anchor(
                                'logout', 
                                HTML::chars(__('logout')), 
                                array(
                                    'class' => 'btn btn-xs btn-default',
                                    'onclick' => 'return confirm(\'' . HTML::chars(__('confirm.delete')) . '\')'
                                )
                            ); ?>
                        </span>
                    </div>
                <?php else: ?>
                    <!-- Форма логина -->
                    <?php echo Form::open('dashboard', array('method' => 'post', 'class' => 'navbar-form form-inline')); ?>
                        <?php if (!empty($auth['csrf_token'])): ?>
                            <?php echo Form::hidden('csrf', $auth['csrf_token']); ?>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="inputUsername" class="sr-only"><?php echo HTML::chars(__('Username')); ?></label>
                            <input type="text" class="form-control input-sm" id="inputUsername" 
                                   placeholder="<?php echo HTML::chars(__('Username')); ?>" 
                                   name="username"
                                   value="<?php echo HTML::chars(isset($auth['post_data']['username']) ? $auth['post_data']['username'] : ''); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">    
                            <label for="inputPassword" class="sr-only"><?php echo HTML::chars(__('Password')); ?></label>
                            <input type="password" class="form-control input-sm" id="inputPassword" 
                                   placeholder="<?php echo HTML::chars(__('Password')); ?>" 
                                   name="password"
                                   required>
                        </div>
                        
                        <div class="checkbox input-sm">
                            <label>
                                <input type="checkbox" name="remember" 
                                       <?php echo (!empty($auth['post_data']['remember'])) ? 'checked' : ''; ?>>
                                <?php echo HTML::chars(__('Remember me')); ?>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span class="glyphicon glyphicon-log-in"></span> 
                            <?php echo HTML::chars(__('Login')); ?>
                        </button>
                    <?php echo Form::close(); ?>
                    
                    <!-- Ошибки -->
                    <?php if (!empty($auth['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible" style="margin-top: 5px;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <?php foreach ($auth['errors'] as $error): ?>
                                <p style="margin: 0;"><?php echo HTML::chars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        </ul>

        <!-- Версия -->
        <div>
            <?php 
			if (!empty($version['text'])): ?>
                <?php echo $version['text']; ?>
            <?php endif; ?>
           
        </div>
		
		<!-- odbc имя -->
        <div>
		
            <?php if (!empty($odbc['dsn'])): ?>
                <?php echo __('ODBC :odbc', array(':odbc'=>$odbc['dsn'])); ?>
            <?php endif; ?>
            <br>
            <?php echo __('timerefresh', array('tr' => date("d.m.Y H:i", time()))); ?>
        </div>
		
		
    </div>
</nav>