<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Model_Stat — статистика опроса контроллеров СКУД.
 *
 * Удалены методы, не вызываемые ни из одной точки проекта
 * (проверено сплошным поиском по всем *.php):
 * __date_stat, __decCommaTo001A, __delete_stat_data, __fixKeyOnDBCount,
 * fixKeyOnDBCountForDoors, __fixOverTimeKeyOnDBCount, del_queue,
 * Get_unActiveCard, get_org_parent, repeat_load, __detect_change_device_count,
 * stop_load, __GetOrder, __CloseOrder, device_list, last_stat_order,
 * __stat_version_device, load_order, __count_order_for_notactive,
 * load_order_overcount, GetKeyCountStat_arr, GetKeyCountStat,
 * GetKeyCountDevice, getVersion, getAnalitic_for_Test_mode_ademant,
 * getAnalitic_for_Test_mode_artonit, getAnyDataFromStdata.
 *
 * ВНИМАНИЕ: одноимённые методы есть в Model_Dev (date_stat, load_order)
 * и Model_People (Get_unActiveCard) — там они и используются.
 */
class Model_Stat extends Model
{
	public function authmode($authmode)
	{
		$authmode_desc=array('Нет данных о режиме авторизаци',
					'Только RFID карта (тип 1)',
					'Только FaceID (тип 2)',
					'Строгое соответствие RFID и FaceID (тип 3)',
					'Не строгое соответствие RFID и FaceID (тип 4)',
					'Проход по любому идентификатору (тип 5)');
		return Arr::get($authmode_desc, $authmode, 0);
		
	}
	
	public function authmodeList()
	{
		$authmode_desc=array('0'=>'Нет данных о режиме авторизаци',
					'1'=>'Только RFID карта (тип 1)',
					'2'=>'Только FaceID (тип 2)',
					'3'=>'Строгое соответствие RFID и FaceID (тип 3)',
					'4'=>'Не строгое соответствие RFID и FaceID (тип 4)',
					'5'=>'Проход по любому идентификатору (тип 5)');
		return $authmode_desc;
		
	}
	
	public function reviewKeyCode($keycode)// преобразование кода 001А к цифрам
	{
		//$keycode='7627DE001A';
		//$keycode='No_card';
		$post=Validation::factory(array('key'=>trim($keycode)));
		$post->rule('key', 'not_empty')
			->rule('key', 'regex', array(':value', '/[A-F0-9]+/'));//'/[a-fA-F0-9]++$/iD'
		if($post->check())
		{
			$key=substr(Arr::get($post,'key'),0, 6);
			
			$key_arr=str_split ($key);
			
			$numReverse2=array('0', '8','4','C','2','A','6','E','1','9','5','D','3','B','7','F');
			
			$result1 = hexdec(Arr::get($numReverse2, hexdec(Arr::get($key_arr, 5)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr,4))))
						.','. str_pad(hexdec(Arr::get($numReverse2,hexdec(Arr::get($key_arr, 3)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 2)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 1)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 
		0)))), 5, '0', STR_PAD_LEFT);
			
			$result2 = str_pad(hexdec(Arr::get($numReverse2, 
					hexdec(Arr::get($key_arr, 5)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 4)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 3)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 2)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 1)))
						.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 
		0)))), 10, '0', STR_PAD_LEFT);
			$result=$result2.', '.$result1;
			
		} else {
			$result='--';
			
		}
		
		return $result;
	}
	
	public function decDigitTo001A($key)// преобразование длинного десятичного числа к формату 001A
	{
		//7627DE 001A	123,58478	0008119406 ->hex 7be46e
		//
		//получаю 7627DE
		
		//$key='0008119406';
		
		$key_arr=str_split(str_pad(dechex ($key), 6, "0", STR_PAD_LEFT));
		$numReverse2=array('0', '8','4','C','2','A','6','E','1','9','5','D','3','B','7','F');
		
		$result2 = str_pad(
					Arr::get($numReverse2,hexdec(Arr::get($key_arr, 5)))
					.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 4)))
					.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 3)))
					.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 2)))
					.Arr::get($numReverse2, hexdec(Arr::get($key_arr, 1)))
					.Arr::get($numReverse2, hexdec(Arr::get($key_arr,0))),
					6, '0', STR_PAD_LEFT).'001A';
		
		return $result2;
	}
	
	public function fixKeyOnCardidx()// 15.08.2024 сколько карт в контроллеры по таблице cardidx
	{
		$sql='select  cdx.id_dev, count(distinct cdx.id_card) from cardidx cdx
			group by cdx.id_dev';
		
		$query2 = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		Log::instance()->add(Log::NOTICE, $query2);
		foreach($query2 as $key=>$value){
				$sql='insert into st_data (id_dev, id_agent, id_param, facts) values ('.Arr::get($value, 'ID_DEV').', 12, 8,' .Arr::get($value, 'COUNT').')';
	try
			{
			$query = DB::query(Database::INSERT, $sql)
			->execute(Database::instance('fb'));
			} catch (Exception $e) {
			}
		}
	}
	
	public function analyt_result()// 26.02.2020 процедура получает данные по аналитике
	{
		$sql='select distinct e.analit, count (*) from events e
			where e.analit is not null
			and e.datetime> CURRENT_TIMESTAMP - 1
			and e.analit>0
			group by e.analit';
		$res = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		return $res;
	}
	
	public function card_late_next_week_save_to_file()
	{
		$count_day_befor_end_time=Kohana::$config->load('artonitcity_config')->count_day_befor_end_time;
		$sql='select distinct p.id_pep, p.name, p.surname, p.patronymic, p.note,  o.name as org_name, c.id_card, c.timeend from people p
		join card c on c.id_pep=p.id_pep
		join organization o on o.id_org=p.id_org
		where c.timeend>\'now\' and c.timeend < \''. date("d.m.Y H:i:s",strtotime("$count_day_befor_end_time days")).'\'';
		$res = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
		

		$file_name="Late_card_befor_".date("d-m-Y",strtotime("$count_day_befor_end_time days")).".csv";
		$fp = fopen($file_name, "w"); // Открываем файл в режиме записи
		$mytext ="id_pep;name;surname;patronymic;note;org_name;id_card; timeend\r\n"; // строка данных
		$test = fwrite($fp, $mytext); // Запись в файл
		foreach ($res as $key=>$value)
		{
			fwrite($fp, implode(";",$value)."\r\n");
		}
		fclose($fp); //Закрытие файла
		return;
	}
	
	public function card_late_save_to_file()
	{
		$sql='select distinct p.id_pep, p.name, p.surname, p.patronymic, p.note,  o.name as org_name, c.id_card, c.timeend from people p
		join card c on c.id_pep=p.id_pep
		join organization o on o.id_org=p.id_org
		where c.timeend<\'now\'';
		$res = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
		
		$file_name="Late_card_befor_".date('Y-m-d_H_i_s').".csv";
		$file_name="application/downloads/Late_card_befor".".csv";
		$fp = fopen($file_name, "w"); // Открываем файл в режиме записи
		$mytext ="id_pep;name;surname;patronymic;note;org_name;id_card; timeend\r\n"; // строка данных
		$test = fwrite($fp, $mytext); // Запись в файл
		foreach ($res as $key=>$value)
		{
			fwrite($fp, implode(";",$value)."\r\n");
		}
		fclose($fp); //Закрытие файла
		return;
	}
	
	public function Get_people_late_next_week()
	{
		$count_day_befor_end_time=Kohana::$config->load('artonitcity_config')->count_day_befor_end_time;
		$sql='select distinct p.id_pep, p.name, p.surname, p.patronymic, p.note,  o.name as org_name, o2.name as parent2, o3.name as parent3, o4.name as parent4, o.id_org, c.id_card, c.timeend from people p
		join card c on c.id_pep=p.id_pep
		join organization o on o.id_org=p.id_org
		left join organization o2 on o.id_parent=o2.id_org
        left join organization o3 on o2.id_parent=o3.id_org
        left join organization o4 on o3.id_parent=o4.id_org
		where c.timeend>\'now\' and c.timeend < \''. date("d.m.Y H:i:s",strtotime("$count_day_befor_end_time days")).'\'';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
	
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$key]=$value;
			$res[$key]['NAME']=iconv('windows-1251','UTF-8',$value['NAME']);
			$res[$key]['PATRONYMIC']=iconv('windows-1251','UTF-8',$value['PATRONYMIC']);
			$res[$key]['SURNAME']=iconv('windows-1251','UTF-8',$value['SURNAME']);
			$res[$key]['ORG_NAME']=iconv('windows-1251','UTF-8',$value['ORG_NAME']);
			$res[$key]['NOTE']=iconv('windows-1251','UTF-8',$value['NOTE']);
			$res[$key]['MAX']=Arr::get($value, 'MAX');
			$res[$key]['ORG_PARENT']= '..\\'
					.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT4', '..')).'\\'
							.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT3', '..')).'\\'
									.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT2', '..')).'\\'
											.iconv('windows-1251','UTF-8', Arr::get($value, 'ORG_NAME', '..'));
		}
		return $res;
	}
	
	public function Get_people_late()
	{
		$sql='select distinct p.id_pep, p.name, p.surname, p.patronymic, p.note,  o.name as org_name, o2.name as parent2, o3.name as parent3, o4.name as parent4, o.id_org, c.id_card, c.timeend, c."ACTIVE" as isactive from people p
        join card c on c.id_pep=p.id_pep
        join organization o on o.id_org=p.id_org
        left join organization o2 on o.id_parent=o2.id_org
        left join organization o3 on o2.id_parent=o3.id_org
        left join organization o4 on o3.id_parent=o4.id_org
		where c.timeend<\'now\'
		and c."ACTIVE">0
		order by c.timeend';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
		
		
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$key]=$value;
			$res[$key]['NAME']=iconv('windows-1251','UTF-8',$value['NAME']);
			$res[$key]['PATRONYMIC']=iconv('windows-1251','UTF-8',$value['PATRONYMIC']);
			$res[$key]['SURNAME']=iconv('windows-1251','UTF-8',$value['SURNAME']);
			$res[$key]['ORG_NAME']=iconv('windows-1251','UTF-8',$value['ORG_NAME']);
			$res[$key]['NOTE']=iconv('windows-1251','UTF-8',$value['NOTE']);
			$res[$key]['MAX']=Arr::get($value, 'MAX');
			//$res[$key]['ORG_PARENT']= $this->get_org_parent(Arr::get($value, 'ID_ORG')).' '.iconv('windows-1251','UTF-8',$value['ORG_NAME']);
			$res[$key]['ORG_PARENT']= '..\\'
					.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT4', '..')).'\\'
							.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT3', '..')).'\\'
									.iconv('windows-1251','UTF-8', Arr::get($value, 'PARENT2', '..')).'\\'
											.iconv('windows-1251','UTF-8', Arr::get($value, 'ORG_NAME', '..'));
											
		}
		
		return $res;
		
	}
	
	public function getmaxAttempts()
	{
		$reg=shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Shelni\Access Server " /v "Max Attempts"');
		$st=substr(trim($reg),strlen($reg)-4);
		$st = ($st)? hexdec($st) : 100;
		//return $st;
		return 2;
	}
	
	public function ClearStat () //удаление данных более заданного периода
	{
		$stat_day_befor=Kohana::$config->load('artonitcity_config')->stat_day_befor;
		$sql='delete from st_order sto where sto.timestart <\''.date("d.m.Y H:i:s",strtotime("-".$stat_day_befor." days")).'\'';
		$id = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
		
		
		$sql='delete from st_data std where std.time_insert <\''.date("d.m.Y H:i:s",strtotime("-".$stat_day_befor." days")).'\'';
		$id = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
	}
	
	/** 11.03.2026 набор данных для окна 2 Оборудование
	*/
	public function getEquipment()
	{
		$res=array();
		//подсчет количества транспортных серверов
		$res['device'][3]['name']=__('ts_count');
		$res['device'][3]['count']=DB::query(Database::SELECT, 'select count(*) from server')
		->execute(Database::instance('fb'))
		->get('COUNT');
		
		//подсчет количества контроллеров
		$res['device'][4]['name']=__('device_count');
		$res['device'][4]['count']=DB::query(Database::SELECT, 'select count(*) from device d where d.id_reader is null')
		->execute(Database::instance('fb'))
		->get('COUNT');
		return $res;
		
	}
	
	/** 11.03.2026 набор данных для окна 3 Очередь загрузок
	*/
	public function getLoadOrder()
	{
		$res=array();
		$res['order']['card_for_load']['name']=__('loading_card_rfid');
		
			
		$sql='select count(*) from cardindev cd
			join devtype_cardtype dc on dc.id_cardtype=cd.id_cardtype
			join device d on d.id_dev=cd.id_dev and d.id_devtype=dc.id_devtype
			join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null and d2.id_devtype=dc.id_devtype
			join DEVTYPE dt on dt.id_devtype=dc.id_devtype
			where d."ACTIVE">0
			and d2."ACTIVE">0
			and dt.standalone in (0, 1)
			and cd.id_cardtype=1
			';
			
			

		$res['order']['card_for_load']['count']=DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->get('COUNT');
		
		return $res;
		
	}
	
	public function IntToIP ($intIP)// преобразование IP адреса
	{
		$mm= explode (".", long2ip($intIP));
		$tt=$mm[3].'.'.$mm[2].'.'.$mm[1].'.'.$mm[0];
		
		return $tt;
	}
	
	public function load_table($id_dev=FALSE, $a=FALSE)// подготовка данных по каждому контроллеру *количество карт по базе для указаного устройства и т.п.) либо для всех устройств
	{
		// $id_dev если указан, то выборка будет сделана только для указанного устройства, если не указан, то выборка будет сделана для всех устройств
		//выборка количества карт по базе данных.
		$t1=microtime(1);
		
		//подготовка списка точек прохода и данных о версии контроллерах, состоянии линии связи и кол-ва загруженных карт из таблицы st_data
		
		
	$sql='select d2.id_devtype,  d2.flag as db_controller_config, std6.facts as read_controller_config, d2.id_dev as id_dev ,
    d2.name as dev_name , d.id_dev as id_door , d.name as door_name, d.id_reader, d2.id_server, s.name as s_name,  s.ip, s.port,
    std.facts as ver, std2.facts as line, std3.facts as mode,
    std4.facts as keycount, std4.time_insert as keycountTime,
    std5.facts as fixkeyOnDB, std5.time_insert as DBkeycountTime,
    std7.facts as fixOverTimekeyOnDB, std7.time_insert as fixOverTimekeyOnDBTime,
    std8.facts as doorstate,
    std112.facts as deviceInfo

    from device d
            join device d2 on d2.id_ctrl=d.id_ctrl   and d2.id_reader is null
            join server s on d2.id_server=s.id_server
            left join st_data std on std.id_dev= d2.id_dev and std.id_param=1
            left join st_data std2 on std2.id_dev= d2.id_dev and std2.id_param=2
            left join st_data std3 on std3.id_dev= d.id_dev and std3.id_param=9
            left join st_data std4 on std4.id_dev= d2.id_dev and std4.id_param=d.id_reader+3
            left join st_data std5 on std5.id_dev= d.id_dev and std5.id_param=8
            left join st_data std7 on std7.id_dev= d.id_dev and std7.id_param=11
            left join st_data std6 on std6.id_dev=d2.id_dev and std6.id_param=10
            left join st_data std8 on std8.id_dev=d.id_dev and std8.id_param=111                             
            left join st_data std112 on std112.id_dev=d.id_dev and std112.id_param=112
            where d.id_reader is not null
            order by d.id_dev';	
			
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		
		// $bb выборка idколичества карт в контроллерах по данным статистики/
	
		//$bb=$this->	GetKeyCountStat_arr();// получили список данных из статистики
		$device_count=array();
		
		
		//расчет количества карт по каждой точке прохода сколько должно быть, причем именно на момент просмотра данных. Эти данные нет смысла сопоставлять со статистикой из таблицы st_data
				
		$sql='select  ac.id_dev, count(distinct c.id_card) from ss_accessuser ssu
        join card c on ssu.id_pep=c.id_pep
        join access ac on ssu.id_accessname=ac.id_accessname
        where
        c."ACTIVE">0
        and (c.timeend>\'NOW\' or c.timeend is null)
		and c.id_cardtype in (1,2)
        group by ac.id_dev';
		
		$query2 = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		foreach($query2 as $key=>$value){
			$device_count[$value['ID_DEV']]=$value['COUNT'];//количество карт по базе данных
		}
		
		$md=array();
		
		$t2=microtime(1);
		$res=array();
		
		foreach ($query as $key=>$value)
		{
			$res[$value['ID_DOOR']]['ID_DEVTYPE']=Arr::get($value, 'ID_DEVTYPE');//тип устройства
			$res[$value['ID_DOOR']]['ID_DOOR']=Arr::get($value, 'ID_DOOR');//id точки прохода
			$res[$value['ID_DOOR']]['SERVER_NAME']=iconv('windows-1251','UTF-8', Arr::get($value, 'S_NAME'));// название транспортного сервера
			$res[$value['ID_DOOR']]['SERVER_IP']=$this->IntToIP(Arr::get($value, 'IP'));//IP адрес транспортного сервера
			$res[$value['ID_DOOR']]['SERVER_PORT']=Arr::get($value, 'PORT');// порт транспортного сервера
			$res[$value['ID_DOOR']]['DEVICE_ID']=Arr::get($value, 'ID_DEV');// id_devконтроллера
			$res[$value['ID_DOOR']]['DEVICE_NAME']=Arr::get($value, 'ID_DEV').';'.iconv('windows-1251','UTF-8',Arr::get($value, 'DEV_NAME'));//win->utf название контоллера
			$res[$value['ID_DOOR']]['DOOR_NAME']=Arr::get($value, 'ID_DOOR').' '.iconv('windows-1251','UTF-8',Arr::get($value, 'DOOR_NAME'));//название точки прохода
			$res[$value['ID_DOOR']]['BASE_COUNT']=Arr::get($device_count, Arr::get($value, 'ID_DOOR'), '--');//количество карт по базе данных
			$res[$value['ID_DOOR']]['BASE_COUNT_READ']=date('Y-m-d H:i:s');//текущее время формирования отчета
			$res[$value['ID_DOOR']]['ID_READER']=Arr::get($value, 'ID_READER');// ID точки прохода
			$res[$value['ID_DOOR']]['DEVICE_VERSION']=$this->parser_2(iconv('windows-1251','UTF-8',Arr::get($value, 'VER', 'no')));// выделение версии контроллера из строки
			$res[$value['ID_DOOR']]['DEVICE_COUNT']=$this->parser_2(iconv('windows-1251','UTF-8',Arr::get($value, 'KEYCOUNT', 'no'))) ;//выделение количества карт из строки
			$res[$value['ID_DOOR']]['KEYCOUNTTIME']=Arr::get($value, 'KEYCOUNTTIME', 'no') ;//выделение количества карт из строки
			$res[$value['ID_DOOR']]['TEST_MODE']=Arr::get($value, 'MODE', 'no');
			$res[$value['ID_DOOR']]['BASE_COUNT_AT_TIME']=Arr::get($value, 'FIXKEYONDB', 'no');//количество карт в базе данных на момент сбора статистики
			$res[$value['ID_DOOR']]['DBKEYCOUNTTIME']=Arr::get($value, 'DBKEYCOUNTTIME', 'no');//дата записи количества карт таблиц статистики.
			$res[$value['ID_DOOR']]['FIXOVERTIMEKEYONDB']=Arr::get($value, 'FIXOVERTIMEKEYONDB', 0);//количество просроченных карт
			$res[$value['ID_DOOR']]['TR_COLOR']=$this->GetTRColor($res[$value['ID_DOOR']]['BASE_COUNT_AT_TIME'],$res[$value['ID_DOOR']]['DEVICE_COUNT']);//подготовка фона строки. Происходит сравнение цифр и делается вывод о фоне строки.
			$res[$value['ID_DOOR']]['COMMENT']=$res[$value['ID_DOOR']]['TR_COLOR'];
			$res[$value['ID_DOOR']]['DB_COMMON_LIST']=Arr::get($value, 'DB_CONTROLLER_CONFIG', 'no') & 1;
			$res[$value['ID_DOOR']]['DOORSTATE']=Arr::get($value, 'DOORSTATE', 'no');
			$res[$value['ID_DOOR']]['DEVICEINFO']=Arr::get($value, 'DEVICEINFO');;
			
			$readCommonList = -1;//режим работы "Единый список". 0- единый список выключен, 1 - единый список включен, 100 - режим не определен
			//if(!is_null($value['READ_CONTROLLER_CONFIG'])) $readCommonList=substr (Model::Factory('Stat')->parser_2($value['READ_CONTROLLER_CONFIG']), 7, 1);
			if(strpos($value['READ_CONTROLLER_CONFIG'], 'OK Config')) $readCommonList=substr (Model::Factory('Stat')->parser_2($value['READ_CONTROLLER_CONFIG']), 7, 1);
			if($readCommonList>0) $readCommonList=$readCommonList & 1;// выляю последний бит из слова конфигурации, прочитанного из контроллера. 0 - нет Единого списка, 1 - есть единый список.
			$res[$value['ID_DOOR']]['READ_COMMON_LIST']=$readCommonList;

		}
		return $res;
	}
	
	public function parser_2($str)// прасер данных двупроходный
	{
		if(empty($str)) return '';
		$res='';
		$aa=trim($str);
		parse_str($aa, $bb);
		foreach ($bb as $key=>$value)
		{
			$str=$value;
		}
		
		$aa=trim($str);
		parse_str($aa, $bb);
		
		foreach ($bb as $key=>$value)
		{
			$res=$value;
		}
		$res=str_replace ('"', '', $res);
		return $res;
	}
	
	public function parser_1($str)// парсер данных однопроходный. На выходе массив параметр = значение.
	{
		$aa=trim($str);
		parse_str($aa, $bb);// разбор строки в массив
		foreach ($bb as $key=>$value)
		{
			$res=$value;
		}
		$res=str_replace ('"', '', $res);
		return $res;
	}
	
	public function GetTRColor ($a, $b)//формирование цвета строки в таблице данных
	{
		//http://itchief.ru/lessons/bootstrap-3/30-bootstrap-3-tables
		//class="active"-серый, "success" - зеленый, "info" - голубой, "warning" - желтый, "danger" - красный
		if ($a==$b) $res="success";
		if ($a < $b) $res="warning";
		if ($a > $b) $res="danger";
		return $res;
	}
	
	public function getDeviceInTestMode() // 20.04.2019 /Вывод id_dev, работающих в режиме TEST
	{
		$sql='select std.id_dev from st_data std where std.id_param=9 and std.facts = \'TEST_ON\'';
		$query_test_on = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		return $query_test_on;
	}
	
	public function getDeviceStatData($id_dev) // 22.08.2024 выборка данных из таблицы st_data
	{
		$sql='select std.facts from st_data std
			where std.id_param=113
			and std.id_dev='.$id_dev;
		
		try
		{
		$result = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->get('FACTS');
		return $result;
		} catch (Exception $e) {
			return '';
		}
	
	}
	
}
