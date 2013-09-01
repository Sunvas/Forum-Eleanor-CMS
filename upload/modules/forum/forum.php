<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
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
			$O=$this->Create($n,$this);
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
		$this->name=get_class($this);
		if($this->name==__CLASS__)
			$this->config=$config ? $config : include self::$root.'/config.php';
		else#Убиваем начальное "Forum"
			$this->name=substr($this->name,5);
	}

	/**
	 * Реализация метода Create трэйта Chain
	 * @param string $name Имя класса для загрузки
	 * @param obj $Base Объект базового класса
	 */
	protected function Create($name,$Base)
	{
		if($name==__class__)
			return new self;
		$c='Forum'.$name;
		if(class_exists($c,false) or include self::$root.strtolower($name).'.php')
			return new$c(false,$Base);
		throw new EE('Class not found: '.$name,EE::DEV,array('file'=>__file__,'line'=>__line__));
	}
}
Forum::$root=__DIR__.DIRECTORY_SEPARATOR;