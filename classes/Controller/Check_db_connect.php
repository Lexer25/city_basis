<?php
//проверка подключения к базе данных. Если проверка не выполняется, то переходить на страницу ошибок.
			// Проверяем наличие модуля database
if (isset(Kohana::modules()['database']) AND class_exists('Database'))
{
    try
    {
        $db = Database::instance('fb')->connect();
        
        // Проверяем, что соединение установлено
        if ($db AND $db->is_connected())
        {
            // Соединение успешно
            echo 'Connected to database "fb"';
        }
        else
        {
            throw new Exception('Failed to connect to database "fb"');
        }
    }
    catch (Database_Exception $e)
    {
        // Ошибка базы данных
        $db = NULL;
        Kohana::$log->add(Log::ERROR, 'Database error: :message', array(
            ':message' => $e->getMessage()
        ));
    }
    catch (Exception $e)
    {
        // Общая ошибка
        $db = NULL;
        Kohana::$log->add(Log::ERROR, 'Error: :message', array(
            ':message' => $e->getMessage()
        ));
    }
}
else
{
    $db = NULL;
    Kohana::$log->add(Log::WARNING, 'Database module is not loaded');
}