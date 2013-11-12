<?php
/*
	Resale is forbidden!
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
return array(
	'my_on_mod%'=>'Мои на модерации (%s)',
	'on_mod%'=>'На модерации (%s)',
	'active%'=>'Активны (%s)',
	'blocked%'=>'Заблокированые (%s)',
	'all%'=>'Любым (%s)',
	'wait-my'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'В этой теме присутству'.($pl ? 'ют' : 'ет').' также '.$cnt.Russian::Plural($cnt,array(' ваш пост, ожидающий',' ваши поста, ожидающие',' ваших постов, ожидающих'))
		.' модерации. Увидеть '.($pl ? 'их' : 'eё').' вы можете <a href="'.$href.'">здесь</a>.';
	},
	'wait-moder'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'В этой теме присутству'.($pl ? 'ют' : 'ет').' также '.$cnt.Russian::Plural($cnt,array(' пост, ожидающий',' поста, ожидающие',' постов, ожидающих'))
		.' модерации. Увидеть '.($pl ? 'их' : 'eё').' вы можете <a href="'.$href.'">здесь</a>.';
	},
	'leave_link'=>' оставить ссылку на тему(ы) в этом форуме',
	'filter'=>'Фильтр',
	'with_status:'=>'Cо статусом: ',
	'only_my'=>'Показывать только мои посты',
	'whoon'=>function($g,$u,$h,$b)
	{
		$r=$g>0 ? $g.Russian::Plural($g,array(' гость',' гостя',' гостей')) : '';
		if($u>0)
		{
			if($r)
				$r.=', ';
			$r.=$u.Russian::Plural($u,array(' пользователь',' пользователя',' пользователей'));
		}
		if($h>0)
		{
			if($r)
				$r.=', ';
			$r.=$h.Russian::Plural($h,array(' скрытый пользователь',' скрытых пользователя',' скрытых пользователей'));
		}
		if($b>0)
		{
			if($r)
				$r.=', ';
			$r.=$b.Russian::Plural($b,array(' поисковый бот',' поисковых бота',' поисковых ботов'));
		}
		#Замена последней запятой на "и"
		$r=preg_replace('#,([^,]+)$#',' и\1',$r);
		return$r;
	},
	'here-now'=>function($g,$u,$h,$b)
	{
		return $g+$b+$u+$h>1 ? 'Эту тему читают ' : 'Эту тему читает ';
	},
	'here-now-post'=>function($g,$u,$h,$b)
	{
		return $g+$b+$u+$h>1 ? 'Этот пост читают ' : 'Этот пост читает ';
	},
	'rules'=>'Правила форума',
	'edited'=>function($date,$who,$whohref,$reason)
	{
		return Russian::Date($date,'fdt').' этот пост отредактировал '
			.($whohref ? '<a href="'.$whohref.'">'.$who.'</a>' : $who)
			.'. Причина: '
			.($reason ? $reason : '<i>не указана</i>');
	},
	'downloaded'=>function($cnt)
	{
		return $cnt.Russian::Plural($cnt,array(' скачивание',' скачивания',' скачиваний'));
	},
	'not-subscribe'=>'Подписка отсутствует',
	'notify'=>'Подписка с уведомлением',
	'immediately'=>'Немедленным',
	'daily'=>'Ежедневным',
	'weekly'=>'Еженедельным',
	'monthly'=>'Ежемесячным',
	'mess-1'=>'Данная тема ожидает модерации. Доступна для просмотра создателю и модератору.',
	'mess0'=>'Данная тема заблокирована.',
	'togglestatus'=>'Изменить статус',
	'onmod'=>'На модерации',
	'ban'=>'Блокировка',
	'activate'=>'Активировать',
	'permdelete'=>'Окончательно удалить',
	'delete'=>'Удалить',
	'move'=>'Переместить',
	'close'=>'Закрыть',
	'open'=>'Открыть',
	'unpin'=>'Открепить',
	'pin'=>'Закрепить',
	'merget'=>'Склеить тему',
	'merge'=>'Склеить',
	'psmess-1'=>'Вы просматриваете только нуждающиеся в модерации посты темы. Для просмотра активной темы, перейдите <a href="%s">сюда</a>.',
	'psmess0'=>'Вы просматриваете только заблокированные посты темы. Для просмотра активной темы, перейдите <a href="%s">сюда</a>.',
	'noposts'=>'Посты не найдены. Попробуйте изменить фильтр.',
	'lnp'=>'Загрузить новые посты',
	'answer'=>'Ответить',
	'deletet'=>'Удалить тему',
	'new-topic'=>'Новая тема',
	'moder-posts'=>'Модерирование постов',
	'moder-topic'=>'Модерирование темы',
	'your-name'=>'Ваше имя',
	'text'=>'Текст',
	'enter-captcha'=>'Введите код',
	'tofull'=>'Перейти в полный ответ...',
	'prev'=>'Предыдущий пост',
	'next'=>'Следующий пост',
	'post-from'=>'Пост %d из %d',
	'pmess-1'=>'Данный пост ожидает модерации. Доступен для просмотра автору и модератору.',
	'pmess0'=>'Данный пост заблокирован.',
	'edit'=>'Править',
	'quick-edit'=>'Быстрая правка',
	'quote'=>'Цитата',
	'quick-quote'=>'Быстрая цитата',
	'online'=>'Онлайн',
	'from'=>'от',
	'link'=>'ссылка',
	'profile'=>'Профиль',
	'group%'=>'Группа: %s',
	'posts%'=>'Постов: %s',
	'register%'=>'Регистрация: %s',
	'from%'=>'Откуда: %s',
	'repa%'=>'Репутация: %s',
	'rno'=>'нет',
	'total%'=>'Всего: %s',
	'attached-images'=>'Прикрепленные изображения',
	'attached-files'=>'Прикрепленные файлы',
	'approved'=>'Одобрили',
	'rejected'=>'Отвергли',
	'thanks'=>'Спасибо',
	'moveposts'=>'Адрес либо ID темы, куда перенести посты',
	'apply-filter'=>'Применить фильтр',
	'post'=>'Опубликовать пост',
	'main-author'=>'Автор поста:',

	#Поддержка языковых версий
	'related-text'=>'Эта тема доступна также и на русском языке.',
	'related'=>function(array$related)
	{
		$res='';
		foreach($related as $id=>$topic)
		{
			$lang=__DIR__.'/forum-user-topic-'.$topic['language'].'.php';
			if(is_file($lang))
			{
				$lang=include$lang;
				$res.=$lang['related-text'].'<a href="index.php?language='.$topic['language'].'">&gt;&gt;</a><br />';
			}
		}
		return substr($res,0,-6);
	},
	#[E] Поддержка языковых версий

	'updated'=>function($date)
	{
		return'Последнее обновление '.mb_strtolower(Russian::Date($date,'fdt'));
	},
);