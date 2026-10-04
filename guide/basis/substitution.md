## Замены классов ядра

Модуль `basis` зарегистрирован в `application/bootstrap.php` раньше модуля `database`, поэтому его файлы в `classes/Kohana/` перекрывают системные.

Переопределения классов фреймворка размещаются в этом модуле, а не в пакетах фреймворка (`system`, `modules/database` и других): исходники фреймворка не изменяются.

- **`classes/Kohana/Database/PDO.php`** — `Database_PDO::query()` для запросов `Database::INSERT` возвращает **только количество вставленных строк** (`array($rowCount)`), без идентификатора вставки. Причина: драйвер Gemini InterBase ODBC не поддерживает `lastInsertId()`, из-за чего вставки в базу СКУД падали с ошибкой «driver does not support lastInsertId()».

  Оригинал `modules/database/classes/Kohana/Database/PDO.php` остаётся нетронутым и служит эталоном для сравнения.

**Следствие:** идентификатор новой записи нельзя получить из результата вставки. В проекте для этого используется генератор Firebird:

~~~
$newId = DB::query(Database::SELECT, 'SELECT GEN_ID(GEN_XXX_ID, 1) as gen FROM RDB$DATABASE')
    ->execute(Database::instance('fb'))
    ->get('GEN');
~~~

---