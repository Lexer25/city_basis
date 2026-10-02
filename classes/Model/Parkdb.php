<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Model_Parkdb — сведения о подключённой базе данных СКУД (Firebird).
 *
 * Единственный потребитель — Controller_Dashboard (метод aboutDB()).
 *
 * Ранее файл содержал копию установщика другого продукта (ParkResident/Setup):
 * методы addTable/delTable/addProcedure/addTrigger/addServer/addDevice и прочие,
 * работавшие с жёсткими путями C:\xampp\htdocs\parkresident\... и с учётной
 * записью sysdba/temp. Ни один из них не вызывался ни из одной точки проекта,
 * поэтому они удалены.
 */
class Model_Parkdb extends Model {

    /** Соединение с базой СКУД (см. application/config/database.php) */
    const DB_CONNECTION = 'fb';

    /** Имя соединения, используемое по умолчанию */
    public $connectName = self::DB_CONNECTION;

    public function __construct($connectName = self::DB_CONNECTION)
    {
        $this->connectName = $connectName;
    }

    /**
     * Сведения о подключённой базе данных.
     *
     * Строки возвращаются в исходной кодировке (путь к файлу БД — cp866,
     * остальные — cp1251): приведение к UTF-8 выполняет представление
     * (modules/dashboard/views/dashboard/dashboard.php).
     *
     * @param   string  $sourcename  имя соединения; по умолчанию — текущее
     * @return  array   connectName, dsn, pathDB, Server, countTable, minEventDate
     */
    public function aboutDB($sourcename = NULL)
    {
        if ($sourcename === NULL) {
            $sourcename = $this->connectName;
        }

        $config     = Kohana::$config->load('database')->$sourcename;
        $connection = Arr::get($config, 'connection');
        $dsn        = Arr::get($connection, 'dsn');

        // "odbc:ilove" -> "ilove" — имя DSN в реестре ODBC
        $dsn_name = Arr::get(explode(':', (string) $dsn), 1);

        return array(
            'connectName'  => $sourcename,
            'dsn'          => $dsn,
            'pathDB'       => $this->registry_value($dsn_name, 'Database'),
            'Server'       => $this->registry_value($dsn_name, 'Server'),
            'countTable'   => $this->table_record_counts(),
            'minEventDate' => $this->min_event_date(),
        );
    }

    /**
     * Значение параметра ODBC-DSN из реестра Windows (32-битная ветка).
     *
     * @param   string  $dsn_name  имя DSN, например "ilove"
     * @param   string  $param     имя параметра: Database, Server
     * @return  string  значение или '' — если получить не удалось
     */
    protected function registry_value($dsn_name, $param)
    {
        if ($dsn_name === '' OR ! function_exists('shell_exec')) {
            return '';
        }

        $command = 'C:\Windows\system32\reg.exe query '
                 . '"HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\ODBC\ODBC.INI\\'.$dsn_name.'"'
                 .' /v "'.$param.'"';

        $output = @shell_exec($command);

        if ( ! is_string($output)) {
            Log::instance()->add(Log::WARNING,
                'Parkdb: не удалось прочитать параметр :param для DSN :dsn', array(
                    ':param' => $param,
                    ':dsn'   => $dsn_name,
                ));
            return '';
        }

        // reg.exe печатает строку вида: "    Database    REG_SZ    C:\...\base.gdb"
        return trim(Arr::get(explode('REG_SZ', $output), 1));
    }

    /**
     * Приблизительное количество записей по таблицам
     * (оценка Firebird по статистике первичных индексов).
     *
     * @return  array  имя таблицы => количество записей
     */
    protected function table_record_counts()
    {
        $sql = 'SELECT 
				I.RDB$RELATION_NAME AS RELATION,
				CAST(1 / I.RDB$STATISTICS AS INTEGER) AS RECORD_COUNT
			FROM 
				RDB$INDICES I
				JOIN RDB$RELATION_CONSTRAINTS C 
					ON C.RDB$INDEX_NAME = I.RDB$INDEX_NAME
			WHERE 
				C.RDB$CONSTRAINT_TYPE = \'PRIMARY KEY\'
				AND I.RDB$STATISTICS > CAST(0 AS DOUBLE PRECISION)';

        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance(self::DB_CONNECTION))
            ->as_array();

        $res = array();
        foreach ($query as $value) {
            $res[trim(Arr::get($value, 'RELATION'))] = Arr::get($value, 'RECORD_COUNT');
        }

        return $res;
    }

    /**
     * Дата самого раннего события в базе.
     *
     * @return  string
     */
    protected function min_event_date()
    {
        return DB::query(Database::SELECT, 'select min(e.datetime) from events e')
            ->execute(Database::instance(self::DB_CONNECTION))
            ->get('MIN');
    }
}
