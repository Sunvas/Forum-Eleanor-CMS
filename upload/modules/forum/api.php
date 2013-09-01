<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
class ApiForum extends BaseClass
{
	private
		$config=array();

	public function __construct($config=array())
	{
		$this->config=$config ? $config : include __dir__.'/config.php';
	}

	public function LangUrl($q,$lang)
	{
		#ToDo!
	}

	public function SitemapConfigure(&$post,&$ti=13)
	{
		#ToDo!
	}

	/*
		$data - данные, полученные от этой функции на предыдущем этапе
		$conf - конфигурация от функции SitemapConfigure
		$opts - опции, где:
			per_time - количество ссылок за один раз.
			type (тип данных):
				number - получить полное число всех новых ссылок
				get - получить ссылки
			callback - функция, которую следует вызать для отправки результата
			sections - секции модуля
	*/
	public function SitemapGenerate($data,$conf,$opts)
	{
		#ToDo!
	}
}