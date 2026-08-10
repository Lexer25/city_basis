<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Basis extends Controller_Template {

   public $template = 'template';
   //Широкий шаблон
   //для использьвания необходимо указать 
   //$this->template = View::factory($this->template_width);
   public $template_width = 'template_width';
   
  	
	public function before()
	{
			
			parent::before();
			$session = Session::instance();

	}
	
	
	public function action_index()
	{	
			
		
		$content = View::factory('basis/basis', array(
			
			));
		
		$this->template->content = $content;
		//echo View::factory('profiler/stats');
		
	}
	
	
	
	
}
