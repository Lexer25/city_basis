<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_dbskud extends Model 
{
	/** 01.09.2024 Проверка таблицы card на правильность заполения номеров RFID.
	* все номера должны быть строго цифры и большие буквы ABCDEF (иначе сверка карт работает некорректно)
	*@input 
	*@output список "неправильных" номеров карт
	*/
	
	public function checkRfidKeyFormat()
	{
		$sql='select c.id_card from card c
		where (c.id_card like \'%a%\'
		or c.id_card like \'%b%\'
		or c.id_card like \'%c%\'
		or c.id_card like \'%d%\'
		or c.id_card like \'%e%\'
		or c.id_card like \'%f%\')
		and c.id_cardtype=1
		';
		
		$query_db = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array();	
		return $query_db;
		
	}
	
	
}



