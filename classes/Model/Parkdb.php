<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * @package    ParkResident/Setup
 * @category   Base
 * @author     Artonit
 * @copyright  (c) 2025 Artonit Team
 * @license    http://artonit/ru

 */



class Model_Parkdb extends Model {

    /*11.04.2025 информация по подключенной базе данных
    */

    //путь к базе данных
    public $_connectName='fb';
    public $db_path;
    public $serverIP;
    public $serverPort;
    public $mess;

    public function __construct($_connectName='fb')
    {


        $this->db_path = iconv('cp866','UTF-8//IGNORE', Arr::get($this->aboutDB($_connectName), 'pathDB'));
        $this->serverIP = iconv('cp866','UTF-8//IGNORE', Arr::get($this->aboutDB($_connectName), 'Server'));

        //echo Debug::vars('29', $this->aboutDB()); exit;
        $this->serverPort=3050;
        //echo Debug::vars('35', $_connectName, $this); exit;
    }


    public function aboutDB($sourcename)
    {
        $_fbinfo=Kohana::$config->load('database')->$sourcename;
        $_connection=Arr::get($_fbinfo, 'connection');
        $_dsn=Arr::get($_connection, 'dsn');
        //echo Debug::vars('13', Arr::get(explode(":", $_dsn), 1));

        //$reg=shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\ODBC\ODBC.INI\SDuo" /v "Database"');
        $reg=shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\ODBC\ODBC.INI\\'.Arr::get(explode(":", $_dsn), 1).'" /v "Database"');
//		echo Debug::vars('43', shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\ODBC\ODBC.INI\\'.Arr::get(explode(":", $_dsn), 1)));exit;
        $_aaa=explode("REG_SZ", $reg);

        $reg=shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\ODBC\ODBC.INI\\'.Arr::get(explode(":", $_dsn), 1).'" /v "Server"');
        $_ip=explode("REG_SZ", $reg);
		
		
		//подсчет количества записей
		$sql='SELECT RDB$INDICES.rdb$relation_name RELATION,
		  cast(1/I.RDB$STATISTICS as integer) RECORD_COUNT
		  FROM RDB$INDICES I
		  JOIN RDB$RELATION_CONSTRAINTS C
		  ON (C.RDB$INDEX_NAME = I.RDB$INDEX_NAME)
		  AND (C.RDB$CONSTRAINT_TYPE = \'PRIMARY KEY\')
		  AND (I.RDB$STATISTICS > cast(0 as double precision))';
		  
		  
		$sql='SELECT 
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
            ->execute(Database::instance('fb'))
            ->as_array();
			$res=array();
			foreach($query as $ley=>$value){
				
				$res[trim(Arr::get($value, 'RELATION'))] = Arr::get($value, 'RECORD_COUNT');
			}
			
			//минимальная дата события
			$sql='select min(e.datetime) from events e';
			$query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->get('MIN');

//		echo Debug::vars('44', $_aaa, $_ip);exit;
        return array('connectName'=>$sourcename,
            'dsn'=>$_dsn,
            'pathDB'=>trim(Arr::get($_aaa, 1)),
            'Server'=>trim(Arr::get($_ip, 1)),
            'countTable'=>$res,
            'minEventDate'=>$query,
        );

    }

    /*
    20.03.2025 Проверка наличия указанных таблицы

    */
    public function checkTableIsPresent($table)
    {
        $res=array();
        $sql='select distinct RDB$RELATION_NAME
				from RDB$RELATION_FIELDS
				where RDB$RELATION_NAME=\''.$table.'\'';
        //echo Debug::vars('12', $sql); exit;

        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        if($query) return true;
        return false;

    }


    /*
    20.03.2025 Проверка наличия указанных процедур

    */
    public function checkProcedureIsPresent($name)
    {
        $res=array();

        $sql='select distinct * from  RDB$PROCEDURES
			where RDB$PROCEDURE_name = \''.$name.'\'';

        //echo Debug::vars('12', $sql); exit;

        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        if($query) return true;
        return false;
    }

    /*
    20.03.2025 Проверка наличия указанных процедур

    */
    public function checkGeneratorIsPresent($name)
    {
        $res=array();

        $sql='select distinct * from rdb$GENERATORS
    where rdb$GENERATOR_name=\'GEN_'.$name.'_ID\'';

        //echo Debug::vars('12', $sql); exit;

        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        if($query) return true;
        return false;
    }


    /**
    20.03.2025 Проверка наличия указанных процедур

     */
    public function checkTriggerIsPresent($name)
    {
        $res=array();

        $sql='select distinct * from rdb$triggers
    where rdb$trigger_name=\''.$name.'\'';

        //echo Debug::vars('123', $sql); exit;

        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        if($query) return true;
        return false;
    }




    public function makeQuery($query)
    {
        Log::instance()->add(Log::DEBUG, '173 makeQuery выполняется запрос: ' . $query);
        try {
            $db = Database::instance('fb');
            $this->mess = $db->query(NULL, $query); // Выполняем запрос
            Log::instance()->add(Log::DEBUG, '108 makeQuery выполнен успешно. Результат: ' . print_r($this->mess, true));
            return true;
        } catch (Exception $e) {
            $this->mess = $e->getMessage();
            Log::instance()->add(Log::ERROR, '113 makeQuery выполнен с ошибкой: ' . $this->mess);
            return false;
        }
    }

    //2.05.2025 единая процедура выполнения exec с анализом ответа.
    // ответ 0 - команда выполнена успешно.
    // ответ НЕ 0 - ошибка.
    public function makeExec($query)
    {
        Log::instance()->add(Log::DEBUG, Debug::vars('124 makeExec выполняет запрос  :', $query));

        $retval = null;
        $output = null;
        $result=exec(iconv('UTF-8', 'CP1251', $query), $retval, $output);

        if($output==0)
        {
            Log::instance()->add(Log::DEBUG, '132 makeExec выполнен успешно. Результат выполнения '. $output);
            return true;
        } else {
            Log::instance()->add(Log::DEBUG, '135 makeExec выполнен с ошибкой. Результат выполнения '. $output);
            return false;
        }

    }

    public function aboutTable($tableName)
    {

        $sql='select Rdb$Description from Rdb$Relations
			where Rdb$Relation_Name=\''.$tableName.'\'';
        //	echo Debug::vars('87', $sql);exit;
        //$this->makeQuery($sql);

        $query = Arr::flatten(DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array());
        return $query;


    }

    public function delTable($tableName)
    {

        return $this->makeQuery('DROP TABLE '. $tableName);

    }




    public function delTableData($tableName)
    {
        $this->delGenerator($tableName);
        $this->makeQuery('delete from '. $tableName);

    }

    //Добавление данных в указанную таблицу
    public function addTableData($name)
    {
        //echo Debug::vars('99', $name.'.sql');exit;
        $ttt='"C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe" "'.$this->serverIP.'/'.$this->serverPort.':'.$this->db_path.'" -user sysdba -pass temp -i "C:\xampp\htdocs\parkresident\modules\setup\config\sql\data\\'.$name.'.sql"';
		exec(iconv('UTF-8', 'CP1251', $ttt));

    }



    //31.03.2025 Добавление таблицы сводится к выполнению нескольких sql запросов, взятых из файла конфигурации.
    public function addTable($tableName)
    {
        $retval=null;
        $output=null;
        $ttt='"C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe" "'.$this->serverIP.'/'.$this->serverPort.':'.$this->db_path.'  -user sysdba -pass temp -i C:\xampp\htdocs\parkresident\modules\setup\config\sql\\'.$tableName.'.sql';

        Log::instance()->add(Log::DEBUG, Debug::vars('158 выполняю команду добавления таблицы :', iconv('UTF-8', 'CP1251', $ttt)));
        $result=exec(iconv('UTF-8', 'CP1251', $ttt), $retval, $output);
        /* echo Debug::vars('181', $result, $output);exit;
        Log::instance()->add(Log::DEBUG, '159 результат добавления таблицы : '. Debug::vars($result));	*/
        Log::instance()->add(Log::DEBUG, '159-1 результат добавления таблицы : '. Debug::vars($output));
        if($output==0) return true;
        return false;
        // echo Debug::vars('159 результат добавления таблицы :', exec(iconv('UTF-8', 'CP1251', $ttt)));
    }

    public function delGenerator($name)
    {

        return $this->makeQuery('DROP GENERATOR GEN_'. $name.'_ID');



    }

    public function delProcedure($name)
    {

        return $this->makeQuery('DROP PROCEDURE '. $name);
    }


    //31.03.2025 ДОбавление процедуры сводится к выполнению скрипта, взятого из файлов.
    public function addProcedure($name)
    {
        $ttt='"C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe" "'.$this->serverIP.'/'.$this->serverPort.':'.$this->db_path.'  -user sysdba -pass temp -i C:\xampp\htdocs\parkresident\modules\setup\config\sql\\'.$name.'.sql';

        Log::instance()->add(Log::DEBUG, Debug::vars('226 выполняю команду добавления процедуры :', $name));


        return $this->makeExec($ttt);
    }

    //31.03.2025 ДОбавление процедуры сводится к выполнению скрипта, взятого из файлов.
    public function addTrigger($name)
    {
        return $this->addProcedure($name);
    }

    public function delTrigger($name)
    {

        return $this->makeQuery('DROP TRIGGER '. $name);
    }

    public function getServers()
    {
        $sql = 'SELECT * FROM SERVER';
        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        return $query;
    }

    public function getServerById($id_server)
    {
        $sql = 'SELECT * FROM SERVER WHERE ID_SERVER = ' . (int)$id_server;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);

        try {
            $db = Database::instance('fb');
            $db->begin();
            $result = $db->query(Database::SELECT, $sql)->as_array();
            $db->commit();
            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            $this->mess = $e->getMessage();
            Log::instance()->add(Log::ERROR, 'Ошибка при получении сервера с ID ' . $id_server . ': ' . $this->mess);
            return null;
        }
    }

    public function addServer($data)
    {
        $sql = 'INSERT INTO SERVER (ID_DB, NAME, IP, PORT, "ACTIVE") VALUES (' .
            (int)$data['ID_DB'] . ', ' .
            '\'' . addslashes($data['NAME']) . '\', ' .
            (int)$data['IP'] . ', ' .
            (int)$data['PORT'] . ', ' .
            (int)$data['ACTIVE'] . ')';
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        return $this->makeQuery($sql);
    }

    public function updateServer($id, $data)
    {
        $sql = 'UPDATE SERVER SET ' .
            'ID_DB = ' . (int)$data['ID_DB'] . ', ' .
            'NAME = \'' . addslashes($data['NAME']) . '\', ' .
            'IP = ' . (int)$data['IP'] . ', ' .
            'PORT = ' . (int)$data['PORT'] . ', ' .
            '"ACTIVE" = ' . (int)$data['ACTIVE'] . ' ' .
            'WHERE ID_SERVER = ' . (int)$id;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        return $this->makeQuery($sql);
    }

    public function deleteServer($id_server)
    {
        $sql = 'DELETE FROM SERVER WHERE ID_SERVER = ' . (int)$id_server;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        return $this->makeQuery($sql);
    }


    public function addDevice($data) {
        $netaddr = isset($data['NETADDR']) ? iconv('UTF-8', 'windows-1251//TRANSLIT//IGNORE', $data['NETADDR']) : '';
        $name = isset($data['NAME']) ? iconv('UTF-8', 'windows-1251//TRANSLIT//IGNORE', $data['NAME']) : '';

        $sql = 'INSERT INTO DEVICE (ID_SERVER, NETADDR, NAME, "ACTIVE") VALUES (' .
            (int)$data['ID_SERVER'] . ', ' .
            '\'' . addslashes($netaddr) . '\', ' .
            '\'' . addslashes($name) . '\', ' .
            (int)$data['ACTIVE'] . ')';
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL для контроллера: ' . $sql);
        $result = $this->makeQuery($sql);

        if ($result) {
            Log::instance()->add(Log::DEBUG, 'Контроллер успешно добавлен');
            // Получаем последний ID_DEV
            $sql_max = 'SELECT MAX(ID_DEV) AS last_id FROM DEVICE';
            Log::instance()->add(Log::DEBUG, 'Запрос для получения последнего ID_DEV: ' . $sql_max);
            $query = DB::query(Database::SELECT, $sql_max)
                ->execute(Database::instance('fb'))
                ->as_array();
            Log::instance()->add(Log::DEBUG, 'Результат запроса MAX(ID_DEV): ' . print_r($query, true));

            $lastId = null;
            if (!empty($query) && isset($query[0])) {
                $lastId = isset($query[0]['last_id']) ? $query[0]['last_id'] :
                    (isset($query[0]['LAST_ID']) ? $query[0]['LAST_ID'] :
                        (isset($query[0]['MAX(ID_DEV)']) ? $query[0]['MAX(ID_DEV)'] : null));
            }

            if ($lastId !== null && $lastId > 0) {
                Log::instance()->add(Log::DEBUG, 'Добавление дверей начато для ID_DEV: ' . $lastId);
                $door1_name = 'Дверь ' . $lastId;
                $door1_sql = 'INSERT INTO DEVICE (ID_SERVER, NETADDR, NAME, "ACTIVE") VALUES (' .
                    (int)$data['ID_SERVER'] . ', ' .
                    '\'' . addslashes($netaddr) . '\', ' .
                    '\'' . addslashes(iconv('UTF-8', 'windows-1251//TRANSLIT//IGNORE', $door1_name)) . '\', ' .
                    (int)$data['ACTIVE'] . ')';
                Log::instance()->add(Log::DEBUG, 'Сформированный SQL для первой двери: ' . $door1_sql);
                $door1_result = $this->makeQuery($door1_sql);
                if ($door1_result) {
                    Log::instance()->add(Log::DEBUG, 'Первая дверь успешно добавлена');
                } else {
                    Log::instance()->add(Log::ERROR, 'Ошибка при добавлении первой двери: ' . $this->mess);
                }

                $door2_sql = 'INSERT INTO DEVICE (ID_SERVER, NETADDR, NAME, "ACTIVE") VALUES (' .
                    (int)$data['ID_SERVER'] . ', ' .
                    '\'' . addslashes($netaddr) . '\', ' .
                    '\'' . addslashes(iconv('UTF-8', 'windows-1251//TRANSLIT//IGNORE', $door1_name)) . '\', ' .
                    (int)$data['ACTIVE'] . ')';
                Log::instance()->add(Log::DEBUG, 'Сформированный SQL для второй двери: ' . $door2_sql);
                $door2_result = $this->makeQuery($door2_sql);
                if ($door2_result) {
                    Log::instance()->add(Log::DEBUG, 'Вторая дверь успешно добавлена');
                } else {
                    Log::instance()->add(Log::ERROR, 'Ошибка при добавлении второй двери: ' . $this->mess);
                }
            } else {
                Log::instance()->add(Log::ERROR, 'Не удалось определить последний ID_DEV: ' . print_r($query, true));
                $result = false;
            }
        } else {
            Log::instance()->add(Log::ERROR, 'Ошибка при добавлении контроллера: ' . $this->mess);
        }
        return $result;
    }

    public function updateDevice($id, $data) {
        $sql = 'UPDATE DEVICE SET ' .
            'ID_SERVER = ' . (int)$data['ID_SERVER'] . ', ' .
            'NETADDR = \'' . addslashes($data['NETADDR']) . '\', ' .
            'NAME = \'' . addslashes($data['NAME']) . '\', ' .
            'INTERVAL = ' . (int)$data['INTERVAL'] . ', ' .
            'DSS1 = ' . (int)$data['DSS1'] . ', ' .
            'DSS2 = ' . (int)$data['DSS2'] . ', ' .
            'FLAG = ' . (int)$data['FLAG'] . ', ' .
            'ID_PLAN = ' . (int)$data['ID_PLAN'] . ', ' .
            'POS_X = ' . (int)$data['POS_X'] . ', ' .
            'POS_Y = ' . (int)$data['POS_Y'] . ', ' .
            'PSW = \'' . addslashes($data['PSW']) . '\', ' .
            'ACTIVE = ' . (int)$data['ACTIVE'] . ', ' .
            'CONFIG = \'' . addslashes($data['CONFIG']) . '\', ' .
            'PARAM = \'' . addslashes($data['PARAM']) . '\', ' .
            'TAGNAME = \'' . addslashes($data['TAGNAME']) . '\', ' .
            'ID_READER = NULL ' .
            'WHERE ID_DEV = ' . (int)$id;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        return $this->makeQuery($sql);
    }

    public function getDeviceById($id_dev) {
        $sql = 'SELECT ID_DEV, ID_SERVER, NETADDR, NAME, INTERVAL, DSS1, DSS2, FLAG, ID_PLAN, POS_X, POS_Y, PSW, ACTIVE, CONFIG, PARAM, TAGNAME, ID_READER FROM DEVICE WHERE ID_DEV = ' . (int)$id_dev;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        try {
            $db = Database::instance('fb');
            $db->begin();
            $result = $db->query(Database::SELECT, $sql)->as_array();
            $db->commit();
            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            $this->mess = $e->getMessage();
            Log::instance()->add(Log::ERROR, 'Ошибка при получении устройства с ID ' . $id_dev . ': ' . $this->mess);
            return null;
        }
    }

    public function deleteDevice($id_dev) {
        $sql = 'DELETE FROM DEVICE WHERE ID_DEV = ' . (int)$id_dev;
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        return $this->makeQuery($sql);
    }

    public function getDevices() {
        $sql = 'SELECT ID_DEV, ID_SERVER, NETADDR, NAME, "ACTIVE" FROM DEVICE';
        Log::instance()->add(Log::DEBUG, 'Сформированный SQL: ' . $sql);
        try {
            $query = DB::query(Database::SELECT, $sql)
                ->execute(Database::instance('fb'))
                ->as_array();
            return $query;
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'Ошибка выполнения запроса: ' . $e->getMessage());
            return array();
        }
    }



}
