<?php defined('SYSPATH') OR die('No direct access allowed.');

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
	
	
	
	public function _date_stat()//получение даты и времени выбора статистики
	{
		$sql='select min (std.time_insert), max (std.time_insert) from st_data std';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		//echo Debug::vars('12',$query ); exit;
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res['min'] = Arr::get($value, 'MIN', 'not');
			$res['max'] = Arr::get($value, 'MAX', 'not');
		}
		return $res;
		
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
			 //echo Debug::vars('31', $keycode); exit;	
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
				 //echo Debug::vars('60', $keycode); exit;
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
		
		//echo Debug::vars('101',$key, dechex ($key), $key_arr, $result2); exit;
		

		return $result2;
	}
	
	public function decCommaTo001A($key)// преобразование числа вида 123,34567  к формату 001A
	{
		
		$temp=Arr::get(explode( ',', $key), 0)*pow(2,16)+Arr::get(explode( ',', $key), 1);
		return $this->decDigitTo001A($temp);
	}
	
	
	
	
	public function delete_stat_data()// очистка таблицы st_data
	{
		$query = DB::delete('st_data')
		->execute(Database::instance('fb'));
		
	}

	
	public function fixKeyOnDBCount()// 28.02.2020 процедура делает расчет количества карт по базе данных для каждой точки прохода и заносит эти данные в таблицу ST_DATA как параметр 8 KeyCountDB_door
	{
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
				//echo Debug::vars('556', $query2); exit;
		Log::instance()->add(Log::NOTICE, $query2);
		foreach($query2 as $key=>$value){
				$sql='insert into st_data (id_dev, id_agent, id_param, facts) values ('.Arr::get($value, 'ID_DEV').', 12, 8,' .Arr::get($value, 'COUNT').')';
		//echo Debug::vars('23', $sql); exit;
	try
			{
			$query = DB::query(Database::INSERT, $sql)
			->execute(Database::instance('fb'));
			} catch (Exception $e) {
			}
		}
	}
	
	
	public function fixKeyOnCardidx()// 15.08.2024 сколько карт в контроллеры по таблице cardidx
	{
		$sql='select  cdx.id_dev, count(distinct cdx.id_card) from cardidx cdx
			group by cdx.id_dev';
		
	
		
		$query2 = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
				//echo Debug::vars('556', $query2); exit;
		Log::instance()->add(Log::NOTICE, $query2);
		foreach($query2 as $key=>$value){
				$sql='insert into st_data (id_dev, id_agent, id_param, facts) values ('.Arr::get($value, 'ID_DEV').', 12, 8,' .Arr::get($value, 'COUNT').')';
		//echo Debug::vars('23', $sql); exit;
	try
			{
			$query = DB::query(Database::INSERT, $sql)
			->execute(Database::instance('fb'));
			} catch (Exception $e) {
			}
		}
	}
	
	
	/* 16.04.2024
	надо иметь возмжность фиксировать данные для конкретной точки прохода/ Точки прохода передаются как массив, их может быть мног.
	*/
	public function fixKeyOnDBCountForDoors($id_door)//  процедура делает расчет количества карт по базе данных для каждой точки прохода и заносит эти данные в таблицу ST_DATA как параметр 8 KeyCountDB_door
	{
		//echo Debug::vars('185', implode(",",$id_door)); exit;
		$sql='select  ac.id_dev, count(distinct c.id_card) from ss_accessuser ssu
        join card c on ssu.id_pep=c.id_pep
        join access ac on ssu.id_accessname=ac.id_accessname
        where
        c."ACTIVE">0
        and (c.timeend>\'NOW\' or c.timeend is null)
		and c.id_cardtype in (1,2)
		and ac.id_dev in('.implode(",",$id_door).')
        group by ac.id_dev';
		
		$sql2='select  ac.id_dev, count(distinct c.id_card) from ss_accessuser ssu
        join card c on ssu.id_pep=c.id_pep
        join access ac on ssu.id_accessname=ac.id_accessname
        where
        c."ACTIVE">0
        and c.id_cardtype in (1,2)
		and ac.id_dev in('.implode(",",$id_door).')
        group by ac.id_dev';
		
		$query2 = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		//echo Debug::vars('556', $query2); exit;
		Log::instance()->add(Log::NOTICE, $query2);
		foreach($query2 as $key=>$value){
				$sql='insert into st_data (id_dev, id_agent, id_param, facts) values ('.Arr::get($value, 'ID_DEV').', 12, 8,' .Arr::get($value, 'COUNT').')';
		//echo Debug::vars('23', $sql); exit;
	try
			{
			$query = DB::query(Database::INSERT, $sql)
			->execute(Database::instance('fb'));
			} catch (Exception $e) {
			}
		}
	}
	
	/*
	14.09.2023
	Карты с просроченным сроком действий, но находящиеся в таблице cardidx
	*/
	public function fixOverTimeKeyOnDBCount()// 28.02.2020 процедура делает расчет количества карт по базе данных для каждой точки прохода и заносит эти данные в таблицу ST_DATA как параметр 11
	{
		$sql='SELECT cd.id_dev, count(cd.id_card) from cardidx cd
				join card c on c.id_card=cd.id_card
				where c.timeend<\'now\'
				group by cd.id_dev
				order by cd.id_dev';
		
		
		$query2 = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		//		echo Debug::vars('556', $query2); exit;
		foreach($query2 as $key=>$value){
				$sql='insert into st_data (id_dev, id_agent, id_param, facts) values ('.Arr::get($value, 'ID_DEV').', 12, 11,' .Arr::get($value, 'COUNT').')';
		//echo Debug::vars('23', $sql); exit;
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
	
	public function del_queue($id_dev)
	{
		if (!empty($id_dev))
		{		$dev=implode(",",array_keys($id_dev));
		$sql='delete from cardindev cd where cd.id_dev in ('.$dev.')';
		$query = DB::query(Database::DELETE, $sql)
		->execute(Database::instance('fb'));
		}
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
		//echo Debug::vars('31', $file_name, $fp); exit;
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
	public function Get_unActiveCard()
	{
		$sql='select distinct p.id_pep, p.name, p.surname, p.patronymic, p.note,  o.name as org_name, o2.name as parent2, o3.name as parent3, o4.name as parent4, o.id_org, c.id_card, c.timeend, c."ACTIVE" as isactive from people p
        join card c on c.id_pep=p.id_pep
        join organization o on o.id_org=p.id_org
        left join organization o2 on o.id_parent=o2.id_org
        left join organization o3 on o2.id_parent=o3.id_org
        left join organization o4 on o3.id_parent=o4.id_org
		where c."ACTIVE"<1
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
	
	public function get_org_parent($id_org)
	{
		
		$result='';
		return $result;
				
		$sql='select o.id_parent, o.name from organization o where o.id_org='.$id_org;
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		// рекурсия до корня
		$id_parent=$query[0]['ID_PARENT'];
		$result[]=iconv('windows-1251','UTF-8',$query[0]['NAME']);
		// рекурсия до корня
		while ($id_parent> 1)
		{
			$sql='select o.id_parent, o.name from organization o where o.id_org='.$id_parent;
			$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();
			$id_parent=$query[0]['ID_PARENT'];
			$result[]=iconv('windows-1251','UTF-8',$query[0]['NAME']);
		}
		//echo Debug::vars('129', implode("/", $result)); exit;
		
		return implode("/ ", array_reverse($result));
	}
	
	
	
	public function repeat_load($id_dev)
	{
		if (!empty($id_dev))
		{		$dev=implode(",",array_keys($id_dev));
		$sql='update cardindev cd set cd.attempts=0 where cd.id_dev in ('.$dev.')';
		$query = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
		}
	}
	
	
	public function detect_change_device_count()
	{
		$stat_day_befor = isset(Kohana::$config->load('artonitcity_config')->stat_day_befor)? Kohana::$config->load('artonitcity_config')->stat_day_befor : 1;
		//Kohana::$config->write('artonitcity_config', 'test_config', 'data_config');
		
		
		$sql='select std.id_order, sto.timestart, count(std.id_param) from st_order sto
				left join st_data std on std.id_order=sto.id
				where sto.timestart>\''.date("d.m.Y H:i:s",strtotime("-".$stat_day_befor." days")).'\'
				and std.id_param=2
				group by std.id_order, sto.timestart';
		
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		$curr_count=0;
		$res=array();
		foreach ($query as $key =>$data)
		{
			$id_order=$data['ID_ORDER'];
			if ($data['COUNT'] <> $curr_count )
			{
				$res[$id_order]['date']=$data['TIMESTART'];
				$res[$id_order]['old_count']=$curr_count;
				$res[$id_order]['new_count']=$data['COUNT'];
				$curr_count=$data['COUNT'];
			}
		}
		
		return $res;
	}
	
	
	public function stop_load($id_dev)
	{
		if (!empty($id_dev))
		{
			$dev=implode(",",array_keys($id_dev));
			$sql='update cardindev cd set cd.attempts='.$this->getmaxAttempts().' where cd.id_dev in ('.$dev.')';
			$query = DB::query(Database::UPDATE, $sql)
			->execute(Database::instance('fb'));
		}
		
	}
	
	public function getmaxAttempts()
	{
		$reg=shell_exec('C:\Windows\system32\reg.exe query "HKEY_LOCAL_MACHINE\SOFTWARE\Shelni\Access Server " /v "Max Attempts"');
		$st=substr(trim($reg),strlen($reg)-4);
		$st = ($st)? hexdec($st) : 100;
		//return $st;
		return 2;
	}
	
	public function GetOrder($id=FALSE)// получение нового ордера для статистики
	{
		$sql='delete from st_data std where std.id_dev in ('.$id.')';
		$query = DB::query(Database::DELETE, $sql)
		->execute(Database::instance('fb'));
		
		$sql='insert into st_order (id_service, ID_TS) values (1, '.$id.')';
		try
		{
			$query = DB::query(Database::INSERT, $sql)
			->execute(Database::instance('fb'));
		} catch (Exception $e) {
			//echo Debug::vars('38');
			
			
		}
		$sql='select gen_id(gen_st_order_id, 0) from RDB$DATABASE';
		$id = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->get('GEN_ID');
		
		return $id;
	}
	
	public function CloseOrder($id)// завершение ордера
	{
		$sql='update st_order set timeend=\'NOW\' where id='.$id;
		$id = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
		
		return $id+1;
	}
	
	public function ClearStat () //удаление данных более заданного периода
	{
		$stat_day_befor=Kohana::$config->load('artonitcity_config')->stat_day_befor;
		$sql='delete from st_order sto where sto.timestart <\''.date("d.m.Y H:i:s",strtotime("-".$stat_day_befor." days")).'\'';
		$id = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
		
		//echo Debug::vars('117', $sql);
		
		$sql='delete from st_data std where std.time_insert <\''.date("d.m.Y H:i:s",strtotime("-".$stat_day_befor." days")).'\'';
		$id = DB::query(Database::UPDATE, $sql)
		->execute(Database::instance('fb'));
		//echo Debug::vars('122', $sql);
	}
	
	public function device_list()// получение списка устройства
	{
		$sql='select d2.id_dev as id1, d2.name as name1, ac.id_dev as id2, d.name as name2, d.id_reader, d2.id_server, s.name as s_name, s.ip, s.port, count( distinct c.id_card) as cc from ss_accessuser su
				join access ac on su.id_accessname=ac.id_accessname
				join device d on d.id_dev=ac.id_dev
				join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
				join server s on s.id_server=d2.id_server
				join card c on c.id_pep=su.id_pep
				group by d2.id_dev, d2.name, ac.id_dev, d.name, d.id_reader, d2.id_server, s.name, s.ip, s.port';
		
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$value['ID2']]['ID_DOOR']=Arr::get($value, 'ID2');
			$res[$value['ID2']]['ID_DEV']=Arr::get($value, 'ID1');
			$res[$value['ID2']]['ID_TS']=Arr::get($value, 'ID_SERVER');
			$res[$value['ID2']]['SERVER_NAME']=iconv('windows-1251','UTF-8', Arr::get($value, 'S_NAME'));
			$res[$value['ID2']]['SERVER_IP']=$this->IntToIP(Arr::get($value, 'IP'));
			$res[$value['ID2']]['SERVER_PORT']=Arr::get($value, 'PORT');
			$res[$value['ID2']][ 'DEVICE_NAME']=iconv('windows-1251','UTF-8',Arr::get($value, 'NAME1'));//win->utf
			$res[$value['ID2']][ 'DOOR_NAME']=iconv('windows-1251','UTF-8',Arr::get($value, 'NAME2'));
			$res[$value['ID2']]['BASE_COUNT']=Arr::get($value, 'CC');
			$res[$value['ID2']]['ID_READER']=Arr::get($value, 'ID_READER');
			
		}
		//echo Debug::vars('34', $res); exit;
		return $res;
	}
	
	
	public function last_stat_order()// получение id последнего завершенного цилка опроса
	{		$sql='select max(id) from st_order std where std.timeend is not null';
	$id_order = DB::query(Database::SELECT, $sql)
	->execute(Database::instance('fb'))
	->get('MAX');
	return $id_order;
	}
	
	public function stat_version_device ()//получение версий устройств по результатам последного опроса
	{
		$sql='select distinct std.facts, count(*) from st_data std
			join st_order sto on sto.id=std.id_order
			where sto.id ='.$this->last_stat_order().'
			and std.id_param=1
			group by std.facts';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
		
		foreach ($query as $key=>$value)
		{
			$res[]=$value['FACT'].' '.$value['COUNT'];
		}
		return $res;
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
	
	
	
	public function load_order()// вывод очереди карт на загрузку
	{
		$sql='select distinct cd.operation, cd.id_dev, d.name, d2.name as device, s.name as server, count (*) from cardindev cd
 join device d on d.id_dev=cd.id_dev
 join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
 join server s on d2.id_server=s.id_server
 where d."ACTIVE">0 and d2."ACTIVE">0 and cd.attempts<'.$this->getmaxAttempts().'
  and d2.id_devtype in (1,2, 6)
 group by cd.operation , cd.id_dev, d.name , d2.name, s.name';
 
	//22.12.2025 при использовании ТС4 нет ограничений на количество попыток
 	$sql='select distinct cd.operation, cd.id_dev, d.name, d2.name as device, s.name as server, count (*) from cardindev cd
 join device d on d.id_dev=cd.id_dev
 join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
 join server s on d2.id_server=s.id_server
 where d."ACTIVE">0 and d2."ACTIVE">0 
  and d2.id_devtype in (1,2, 6)
 group by cd.operation , cd.id_dev, d.name , d2.name, s.name';
 
 //echo Debug::vars(); exit;
 
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$value['ID_DEV']]['ID_DEV']=Arr::get($value, 'ID_DEV');
			$res[$value['ID_DEV']]['NAME']=iconv('windows-1251','UTF-8',Arr::get($value, 'NAME'));
			$res[$value['ID_DEV']]['DEVICE']=iconv('windows-1251','UTF-8',Arr::get($value, 'DEVICE'));
			$res[$value['ID_DEV']]['SERVER']=iconv('windows-1251','UTF-8',Arr::get($value, 'SERVER'));
			if (Arr::get($value, 'OPERATION')==1) $res[$value['ID_DEV']]['COUNT_WRITE']=Arr::get($value, 'COUNT');
			if (Arr::get($value, 'OPERATION')==2) $res[$value['ID_DEV']]['COUNT_DELETE']=Arr::get($value, 'COUNT');
		}
		
		return $res;
	}
	
	
	public function count_order_for_notactive()// вывод очереди карт на загрузку
	{
		$sql='select cd.operation, cd.id_dev, d.name from cardindev cd
 join device d on d.id_dev=cd.id_dev
 where d."ACTIVE"=0';
		
		$res = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		
		->count();
		
		
		
		return $res;
	}
	
	
	public function load_order_overcount()// вывод очереди карт на загрузку с превышенным количеством попыток
	{
		$sql='select distinct cd.operation, cd.id_dev, d.name, d2.name as device, s.name as server, count (*) from cardindev cd
 join device d on d.id_dev=cd.id_dev
 join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
 join server s on d2.id_server=s.id_server
 where d."ACTIVE">0 and d2."ACTIVE">0 and cd.attempts>='.$this->getmaxAttempts().'
 and cd.attempts<200
 group by cd.operation , cd.id_dev, d.name , d2.name, s.name';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$value['ID_DEV']]['ID_DEV']=Arr::get($value, 'ID_DEV');
			$res[$value['ID_DEV']]['NAME']=iconv('windows-1251','UTF-8',Arr::get($value, 'NAME'));
			$res[$value['ID_DEV']]['DEVICE']=iconv('windows-1251','UTF-8',Arr::get($value, 'DEVICE'));
			$res[$value['ID_DEV']]['SERVER']=iconv('windows-1251','UTF-8',Arr::get($value, 'SERVER'));
			if (Arr::get($value, 'OPERATION')==1) $res[$value['ID_DEV']]['COUNT_WRITE']=Arr::get($value, 'COUNT');
			if (Arr::get($value, 'OPERATION')==2) $res[$value['ID_DEV']]['COUNT_DELETE']=Arr::get($value, 'COUNT');
		}
		
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
			
		//echo Debug::vars('897', $sql); exit;	
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		//echo Debug::vars('594',microtime(1)-$t1,$sql,  $query); exit;
		
		// $bb выборка idколичества карт в контроллерах по данным статистики/
	
		//$bb=$this->	GetKeyCountStat_arr();// получили список данных из статистики
		//echo Debug::vars('595', $bb); exit;
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
		//		echo Debug::vars('556', $query2); exit;
		foreach($query2 as $key=>$value){
			$device_count[$value['ID_DEV']]=$value['COUNT'];//количество карт по базе данных
		}
		
		$md=array();
		
		//echo Debug::vars('680', $device_count ); exit;
		$t2=microtime(1);
		//echo Debug::vars('616',$t2-$t1, $query); exit;
		$res=array();
		
		foreach ($query as $key=>$value)
		{	//echo Debug::vars('937', $value); exit;
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
		//echo Debug::vars('565', microtime(1) - $t1, $res);
		return $res;
	}
	
	public function GetKeyCountStat_arr()// получение данных об устройстве из таблицы статистики. 
	{
		$res=array();
	
		$sql='select std.id, std.id_dev,  std.facts, std.id_param, std.time_insert, d2.id_reader,  d2.id_dev as door_id from st_data std
                join device d on d.id_dev=std.id_dev
                left join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader=std.id_param-3
				 order by std.id_dev';
			$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();
			//echo Debug::vars('633', $query); exit;
			foreach ($query as $key=> $value)
			{
				//echo Debug::vars('314',$key, $value); exit;
				if(!is_null(Arr::get($value, 'ID_READER')))
				{
					//заявляю пустые значения массивов
					$res[$value['DOOR_ID']]['LINE'] = 'no_data';
					$res[$value['DOOR_ID']]['VER'] = 'no_data';
					$res[$value['DOOR_ID']]['COUNT'] = 'no_data';
					$res[$value['DOOR_ID']]['BASE_COUNT_AT_TIME'] = -1;
					$res[$value['DOOR_ID']]['TEST_MODE'] = 'no_data';
					$res[$value['DOOR_ID']]['ID_READER'] =-1;
					$res[$value['DOOR_ID']]['ID_READER'] =-1;
					// ==================
					
					if(Arr::get($value, 'ID_PARAM') == 2) $res[$value['DOOR_ID']]['LINE'] = str_replace("\r\n","", trim(Arr::get($value, 'FACTS')));// version
					if(Arr::get($value, 'ID_PARAM') == 1) $res[$value['DOOR_ID']]['VER'] = str_replace("\r\n","", trim(Arr::get($value, 'FACTS')));// reportstatus
					if(Arr::get($value, 'ID_PARAM') == 7 ) $res[$value['DOOR_ID']]['COUNT'] = trim(Arr::get($value, 'FACTS'));// key count in device
					if((Arr::get($value, 'ID_PARAM') == 3) or (Arr::get($value, 'ID_PARAM') == 4) ) $res[$value['DOOR_ID']]['COUNT'] = trim(Arr::get($value, 'FACTS'));// key count in device
					if(Arr::get($value, 'ID_PARAM') == 8 ) $res[$value['DOOR_ID']]['BASE_COUNT_AT_TIME'] = trim(Arr::get($value, 'FACTS'));// key count in db in read time
					
					if(Arr::get($value, 'ID_PARAM') == 9 ) $res[$value['DOOR_ID']]['TEST_MODE'] = trim(Arr::get($value, 'FACTS'));// test mode in db in read time
					
					$res[$value['DOOR_ID']]['ID_READER'] = trim(Arr::get($value, 'ID_READER'));
					$res[$value['DOOR_ID']]['TIME_INSERT'] = Arr::get($value, 'TIME_INSERT');
					
				}
			}

		//echo Debug::vars('219', $res);exit;
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
	
	
	
	
	public function GetKeyCountStat($id_dev, $id_order)// получение данных об устройстве из таблицы статистики. Выбираются данные последного завершенного опроса
	{
		
		$sql='select std.facts, std.id_param, std.time_insert, d.id_reader from device d
		join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
		join st_data std on std.id_dev=d2.id_dev
		where std.id_order = '.$id_order.' and d.id_dev='.$id_dev;
		//echo Debug::vars('232', $sql);
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		$res=array();
		foreach ($query as $key=> $value)
		{
			if(Arr::get($value, 'ID_PARAM') == 2) $res['LINE'] = trim(Arr::get($value, 'FACTS'));
			if(Arr::get($value, 'ID_PARAM') == 1) $res['VER'] = trim(Arr::get($value, 'FACTS'));
			if(Arr::get($value, 'ID_PARAM') == 3) $res['DOOR0'] = trim(Arr::get($value, 'FACTS'));
			if(Arr::get($value, 'ID_PARAM') == 4) $res['DOOR1'] = trim(Arr::get($value, 'FACTS'));
			$res['ID_READER'] = trim(Arr::get($value, 'ID_READER'));
			$res['TIME_INSERT'] = Arr::get($value, 'TIME_INSERT');
		}
		//echo Debug::vars('219', $res);exit;
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
	
	public function GetKeyCountDevice ($ip, $port, $name, $chanel)//получение количеста карту у указанного устройства
	{
		$server = '127.0.0.1';
		$port = 5666;
		$smes = 'r55 login name="3", password="3"';
		$smes1 = 'r55 enumdevices';
		$smes2 = 'r55 exec device="'.iconv('windows-1251','UTF-8', $name).'", command="getkeycount door='.$chanel.'"';
		$smes3 = 'r55 exec device="'.iconv('windows-1251','UTF-8', $name).'", command="reportstatus"';
		$smes4 = 'r55 exec device="'.iconv('windows-1251','UTF-8', $name).'", command="getversion"';
		$smes5 = 'r55 exec device="'.iconv('windows-1251','UTF-8', $name).'", command="getconfig"';
		$line_ok='Yes';
		$reply='';
		
		//создаем сокет для подключения ТСП
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		
		// создаем соединение с сервером
		$connection = socket_connect($socket, $server, $port);
		//if ($connection === false) die("Cannot connect to server {$server}:{$port}");
		if ($connection === false) $this->redirect('errorpage?err=Cannot connect to server {$server}:{$port}');
		$reply = socket_read($socket,4096);
		//echo Debug::vars('49', $reply);
		
		//авторизация
		socket_write($socket, $smes."\r\n", strlen($smes."\r\n"));
		//получаем ответ
		$reply = socket_read($socket,4096);
		//echo Debug::vars('125', $reply);
		
		//проверки связи
		socket_write($socket, iconv('UTF-8','windows-1251',$smes3)."\r\n", strlen(iconv('UTF-8','windows-1251',$smes3)."\r\n"));
		//получаем ответ
		$reply = socket_read($socket,4096);
		
		if(stripos($reply, $line_ok ))
		{
			//читаю версию контроллера.
			socket_write($socket, iconv('UTF-8','windows-1251',$smes4)."\r\n", strlen(iconv('UTF-8','windows-1251',$smes4)."\r\n"));
			
			//получаем ответ
			$reply = iconv('windows-1251','UTF-8', socket_read($socket,4096));
			$res['ver']=socket_read($socket,4096);
			/*
			//читаю конфигурацию.
			socket_write($socket, iconv('UTF-8','windows-1251',$smes4)."\r\n", strlen(iconv('UTF-8','windows-1251',$smes4)."\r\n"));
			
			//получаем ответ
			$reply = iconv('windows-1251','UTF-8', socket_read($socket,4096));
			$res['config']=socket_read($socket,4096);
			
			*/
			//количество карт по двери 0.
			socket_write($socket, iconv('UTF-8','windows-1251',$smes2)."\r\n", strlen(iconv('UTF-8','windows-1251',$smes2)."\r\n"));
			
			//получаем ответ
			$reply = iconv('windows-1251','UTF-8', socket_read($socket,4096));
			$reply=substr($reply, stripos($reply, 'OK KeyCount')+12, strlen($reply)-stripos($reply, 'OK KeyCount')-15);
			
		} else {
			$reply='Err';
			
		}
		
		socket_close($socket);
		$res['count']=$reply;
		
		//return $reply;
		return $res;
	}
	
	public function getVersion($id_dev)// получить версию контроллера из статистических данных ST_DATA
	{
		$sql='select * from st_data std where std.id_param=1
			and std.id_dev='.$id_dev;
			
		$sql='select std.facts from device d
			join device d2 on d2.id_ctrl=d.id_ctrl and d.id_reader is null
			left join st_data std on std.id_dev=d.id_dev and std.id_param=1
			where d2.id_dev='.$id_dev;
			
			
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		if(count($query)>0)
		{
		$ver_ademant='66.';
		$ver_artonit='www.artonit.ru';
		$res='now_version';
		if(substr_count(Arr::get($query[0], 'FACTS', ''), $ver_ademant)>0) $res='ademant';
		if(substr_count(Arr::get($query[0], 'FACTS', ''), $ver_artonit)>0) $res='artonit';
		} else {
			$res='no_data';
		}
		
		return $res;
	}

		public function getAnalitic_for_Test_mode_ademant($id_dev)// аналитика для выяснения: а работает ли Адемант в режиме ТЕСТ/ Входной параметр - id точки прохода
		{
			$sql='select count(*) from events e where e.datetime>\''.date("d.m.Y H:i:s",strtotime("-1 days")).'\'
				and e.id_eventtype=50
				and e.id_dev='.$id_dev;
			$count_50 = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->get('COUNT');
			//echo Debug::vars('939', $id_dev, $count_50, $sql);
			$sql='select count(*) from events e where e.datetime>\''.date("d.m.Y H:i:s",strtotime("-1 days")).'\'
				and e.id_eventtype=46
				and e.id_dev='.$id_dev;
			$count_46 = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->get('COUNT');
				
			$sql='select count(*) from events e where e.datetime>\''.date("d.m.Y H:i:s",strtotime("-1 days")).'\'
				and e.id_eventtype=65
				and e.id_dev='.$id_dev;
			$count_65 = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->get('COUNT');
			
			echo Debug::vars('817',$id_dev, $count_50, $count_46, $count_65); exit;
			$mode='TEST_OFF';
			if($count_50 == 0 and $count_46 == 0 and $count_65!=0) $mode='TEST_ON';
			//echo Debug::vars('817',$id_dev, $count_50, $count_46, $count_65, $mode);
			return $mode;
		}
		
		public function getAnalitic_for_Test_mode_artonit($id_dev)// аналитика для Артонит: а работает ли Артонит в режиме ТЕСТ
		{
			$mode='TEST_OFF';
			$sql='select count(*) from events e where e.datetime>\''.date("d.m.Y H:i:s",strtotime("-1 days")).'\'
				and e.id_eventtype=145
				and e.id_dev='.$id_dev;
			$count_145 = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->get('COUNT');
			//echo Debug::vars('831', $count_145); exit;
			if($count_145 > 0 ) $mode='TEST_ON';
			return $mode;
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
			//	echo Debug::vars('1245', $sql);exit;
			
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
		
		
		public function getAnyDataFromStdata($id_param)
		{
			$res=array();
			$sql='select std.id_dev, std.facts from st_data std
				where std.id_param='.$id_param;
			$result = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();
			foreach($result as $key=>$value)
			{
				
				$res[Arr::get($value, 'ID_DEV')] =  Arr::get($value, 'FACTS');
				
			}
			return $res;
		}
		
		
		
}
