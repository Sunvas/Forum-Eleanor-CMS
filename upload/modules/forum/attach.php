<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumAttach extends Forum
{

	/**
	 * Получение ID аттачей из текста поста
	 * @param string $text Текст поста
	 * @return array of int ID аттачей
	 */
	public function GetFromText($text)
	{
		$attaches=array();
		if(preg_match_all('#\['.$this->Forum->config['abb'].'=(\d+)[\]]*?\]#',$text,$m)>0)
			foreach($m[1] as $v)
				$attaches[]=(int)$v;
		return$attaches;
	}

	/**
	 * Преобразование всех аттачей в открытый вид (прямые ссылки на файлы). Для того, чтобы OwnBB::Parse мог создать
	 * все необходимые превьюшки в случае необходимости. Сокрытие просиходит при помощи возвращаемого массива.
	 * @param array $posts id=>array('text'=>'...')
	 * @param array $attaches Дамп аттачей
	 * @return array 'from'=>[],'to'=>[] Для последующего сокрытия аттачей str_replace($a['from'],$a['to'],'text')
	 */
	public function DecodePosts(array&$posts,array$attaches,$single=false)
	{
		$replace=array();
		if($posts)
		{
			$config=$this->Forum->config;
			$pos='['.$config['abb'].'=';
			$pattern='#\['.$config['abb'].'=(\d+)[\]]*?\]#';
			$CB=function($m)use($config,$attaches,&$replace){
				$m[1]=(int)$m[1];
				if(isset($attaches[ $m[1] ]))
				{
					$file=$config['attachpath'].'p'.$attaches[ $m[1] ]['p'].'/'.$attaches[ $m[1] ]['file'];

					$replace['from'][]=$file;
					$replace['to'][]=$config['download'].$m[1];

					return$file;
				}
				return'';
			};

			if($single)
			{
				if(strpos($posts['text'],$pos)!==false)
					$posts['text']=preg_replace_callback($pattern,$CB,$posts['text']);
			}
			else
				foreach($posts as &$post)
					if(strpos($post['text'],$pos)!==false)
						$post['text']=preg_replace_callback($pattern,$CB,$post['text']);
		}
		return$replace;
	}
}