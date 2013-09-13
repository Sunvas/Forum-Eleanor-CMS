<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

/**
 * Геренатор ссылок внутри форума: на темы, форумы, действия и т.п.
 */
class ForumLinks extends Forum
{
	protected
		$Url;

	public function __construct($config=false)
	{
		$this->Url=Eleanor::getInstance()->Url;
		parent::__construct($config);
	}

	/**
	 * Генератор ссылок типа "действие"
	 * @param string $do Название действия
	 * @param mixed $act Идентификатор действия либо Массив дополнительных параметров
	 * @param mixed $a Массив дополнительных параметров
	 */
	public function Action($do,$act=false,array$a=array())
	{
		if($this->Url->furl)
		{
			$r=array($do);
			if(is_array($act))
				$r+=$act;
			elseif($act or (string)$act=='0')
				$r[0].='-'.$act;
			return$this->Url->Construct($r+array(''=>$a),true,'');
		}
		return$this->Url->Construct(is_array($act) ? array('do'=>$do,''=>$a)+$act : array($do=>$act,''=>$a));
	}

	/**
	 * Генератор ссылок на тему
	 * @param int $f ID форума
	 * @param int $t ID темы
	 * @param string $uri URI темы
	 * @param mixed $a Массив дополнительных параметров
	 */
	public function Topic($f,$t,$uri='',$a=array())
	{
		if($this->Url->furl)
		{
			$r=$uri=='' ? array() : $this->ForumArr($f);
			$r[]=$r ? $uri : 'topic-'.$t;
			return$this->Url->Construct($r+array(''=>$a),true,'');
		}
		return $this->Url->Construct(array('topic'=>$t,''=>$a));
	}

	/**
	 * Генератор ссылок на форумы
	 * @param int $id Идентификатор форума
	 * @param array $a Массив дополнительных параметров
	 */
	public function Forum($id,array$a=array())
	{
		if($this->Url->furl)
		{
			$ending=false;
			$r=$this->ForumArr($id);
			if(!$r)
			{
				$r[]='forum-'.$id;
				$ending='';
			}
			return$this->Url->Construct($r+array(''=>$a),true,$ending);
		}
		if(isset($this->Forums->dump[$id]))
			return$this->Url->Construct(array('forum'=>$id,''=>$a));
	}

	/**
	 * Генератор массив для последующей генерации ссылок на форумы
	 * @param int $id Идентификатор форума
	 * @return array
	 */
	public function ForumArr($id)
	{
		$Forums=$this->Forums;
		if(isset($Forums->dump[$id]['_uri']))
			return$Forums->dump[$id]['_uri'];
		if(!isset($Forums->dump[$id]))
			return array();

		$r=array();
		if($Forums->dump[$id]['parents'] and $Forums->dump[$id]['uri'])
			foreach(explode(',',$Forums->dump[$id]['parents']) as $v)
				if(isset($Forums->dump[$v]))
					if($Forums->dump[$v]['uri'])
						$r[]=array($Forums->dump[$v]['uri']);
					else
						return array();

		if($Forums->dump[$id]['uri'])
		{
			$r[]=$Forums->dump[$id]['uri'];
			return$Forums->dump[$id]['_uri']=$r;
		}
	}
}