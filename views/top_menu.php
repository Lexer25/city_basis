<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<nav class="navbar navbar-default navbar-fixed-top disable" role="navigation">
    <div class="container-fluid">
        
        <!-- Меню пользователя, выводится без авторизации-->
        <div class="navbar-collapse collapse">
            <?php echo isset($menu_html) ? $menu_html : ''; ?>
        </div>
 
        <!-- Меню администратора, выводится после авторизации-->
        <div class="navbar-collapse collapse">
            <?php echo ($is_admin) ? $adm_html : ''; ?>
        </div>
        
        <!-- Левая часть: версия, ODBC, время, модуль -->
        <div style="float: left; padding: 5px 0;">
            <!-- Версия и сборка - первая строка -->
            <div style="font-size: 12px; color: #777; white-space: nowrap;">
                <?php 
                if (!empty($version['text'])){ 
                    echo $version['text']; 
                } 
                ?>
                <?php if (defined('CITY_BUILD')): ?>
                    <span style="margin-left: 5px;">Сборка <?php echo CITY_BUILD; ?></span>
                <?php endif; ?>
            </div>
            
            <!-- ODBC - вторая строка -->
            <?php if (!empty($odbc['dsn'])): ?>
                <div style="font-size: 12px; color: #777; white-space: nowrap;">
                    <?php echo __('ODBC :odbc', array(':odbc'=>$odbc['dsn'])); ?>
                </div>
            <?php endif; ?>
            
            <!-- Время - третья строка -->
            <div style="font-size: 12px; color: #777; white-space: nowrap;">
                <?php echo __('timerefresh', array('tr' => date("d.m.Y H:i", time()))); ?>
            </div>
            
            <!-- Информация о модуле - четвертая строка -->
            <?php if (!empty($module_info) && !empty($module_info['full_info'])): ?>
                <div style="padding: 2px 0;">
                    <span class="label label-primary">
                        <?php echo $module_info['full_info']; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Правая часть: авторизация -->
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
                    <?php echo Form::open('login', array('method' => 'post', 'class' => 'navbar-form form-inline')); ?>
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
                    
					                    <!-- Ошибки формы логина -->
                    <?php
                    $login_errors = !empty($flash['login_errors']) ? $flash['login_errors'] : array();
                    ?>
                    <?php if (!empty($login_errors)): ?>
                        <div class="alert alert-danger alert-dismissible" style="margin-top: 5px;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <?php foreach ($login_errors as $error): ?>
                                <p style="margin: 0;"><?php echo HTML::chars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        </ul>
        
    </div>
</nav>