<?php defined('SYSPATH') or die('No direct script access.');

//class Controller_Errorpage extends Controller_Template {
class Controller_Errorpage extends Controller{
	
	public function action_index()
	{
		//echo Debug::vars('32',$_GET); exit;
		$err=Arr::get($_GET, 'err');
		$content = View::factory('errorpage', array('err'=>$err));
	$this->response->body($content);
		
	}
	
	
	
}

