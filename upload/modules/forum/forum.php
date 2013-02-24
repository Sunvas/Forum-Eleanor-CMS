<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
class Forum extends BaseClass
{
	#ToDo! PHP 5.4 trait Chain
	public
		$Base,#Объект базового класса, с которого все запустилось
		$good=true,#Флаг того, что объект пригоден для работы
		$held,#Массив "удерживаемых" объектов зависимых объектов
		$name;#Название текущего класса

	final public function Free()
	{
		if($this->good)
		{
			$this->good=false;
			$isb=isset($this->Base);
			foreach($this->held as $k=>&$v)
			{
				if(!$isb or $this->Base!==$v and (!isset($this->Base->held[$k]) or $this->Base->held[$k]!==$v))
					$v->Free();
				unset($this->held[$k]);
			}

			if($isb)
			{
				foreach($this->Base->held as &$v)
					unset($v->held[ $this->name ]);
				unset($this->Base->held[ $this->name ],$this->Base);
			}
		}
	}

	public function __get($n)
	{
		if(isset($this->held[$n]))
			return$this->held[$n];
		if(isset($this->Base))
			$O=$this->Base->__get($n);
		elseif($n==$this->name)
			return$this;
		else
		{
			$O=$this->Create($n);
			$O->Base=$this;
			$O->name=$n;
		}
		$this->held[$n]=$O;
		return$O;
	}
	#PHP 5.4 [E] trait chain

	public
		$config;#Конфиг форума

	public static
		$root;

	protected function __construct($config=false)
	{
		$this->config=$config ? $config : include self::$root.'/config.php';
	}

	/**
	 * Реализация метода Create трэйта Chain
	 *
	 * @param $name Имя класса для загрузки
	**/
	protected function Create($name)
	{
		if($name==__class__)
			return new self;
		$c='Forum'.$name;
		if(class_exists($c,false) or include self::$root.strtolower($name).'.php')
			return new$c;
		throw new EE('Class not found: '.$name,EE::DEV,array('file'=>__file__,'line'=>__line__));
	}
}
Forum::$root=dirname(__file__).DIRECTORY_SEPARATOR;