<?php
/*
	Resale is forbidden!
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
return array(
	'my_on_mod%'=>'Мої на модерації (%s)',
	'on_mod%'=>'На модерації (%s)',
	'active%'=>'Активні (%s)',
	'blocked%'=>'Заблоковані (%s)',
	'all%'=>'Будь-яким (%s)',
	'wait-my'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'В цій темі присутн'.($pl ? 'і' : 'я').' також '.$cnt.Ukrainian::Plural($cnt,array(' ваш пост, що чекає',' ваші пости, що чекають',' ваших постів, що чекають'))
		.' на модерацію. Побачити '.($pl ? 'їх' : 'її').' ви можете <a href="'.$href.'">тут</a>.';
	},
	'wait-moder'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'В цій темі присутн'.($pl ? 'і' : 'я').' також '.$cnt.Ukrainian::Plural($cnt,array(' пост, що чекає',' пости, що чекають',' постів, що чекають'))
		.' на модерацію. Побачити '.($pl ? 'їх' : 'її').' ви можете <a href="'.$href.'">тут</a>.';
	},
	'leave_link'=>' залишити посилання на тему(и) в цьому форумі',
	'filter'=>'Фільтр',
	'with_status:'=>'Зі статусом: ',
	'only_my'=>'Показувати тільки мої пости',
	'whoon'=>function($g,$u,$h,$b)
	{
		$r=$g>0 ? $g.Ukrainian::Plural($g,array(' гість',' гостя',' гостей')) : '';
		if($u>0)
		{
			if($r)
				$r.=', ';
			$r.=$u.Ukrainian::Plural($u,array(' користувач',' користувача',' користувачів'));
		}
		if($h>0)
		{
			if($r)
				$r.=', ';
			$r.=$h.Ukrainian::Plural($h,array(' прихований користувач',' прихованих користувача',' прихованих користувачів'));
		}
		if($b>0)
		{
			if($r)
				$r.=', ';
			$r.=$b.Ukrainian::Plural($b,array(' пошуковий бот',' пошукових бота',' пошукових ботів'));
		}
		#Замена последнів запятой на "і"
		$r=preg_replace('#,([^,]+)$#',' і\1',$r);
		return$r;
	},
	'here-now'=>function($g,$u,$h,$b)
	{
		return $g+$b+$u+$h>1 ? 'Цю тему читають ' : 'Цю тему читає ';
	},
	'here-now-post'=>function($g,$u,$h,$b)
	{
		return $g+$b+$u+$h>1 ? 'Цей пост читають ' : 'Цей пост читає ';
	},
	'rules'=>'Правила форуму',
	'edited'=>function($date,$who,$whohref,$reason)
	{
		return Ukrainian::Date($date,'fdt').' цей пост відредагував '
		.($whohref ? '<a href="'.$whohref.'">'.$who.'</a>' : $who)
		.'. Причина: '
		.($reason ? $reason : '<i>не вказано</i>');
	},
	'downloaded'=>function($cnt)
	{
		return $cnt.Russian::Plural($cnt,array(' скачування',' скачування',' скачувань'));
	},
	'not-subscribe'=>'Підписка відсутня',
	'notify'=>'Підписка із сповіщенням',
	'immediately'=>'Негайним',
	'daily'=>'Щоденним',
	'weekly'=>'Щотижневим',
	'monthly'=>'Щомісячним',
	'mess-1'=>'Ця тема очікує модерації. Доступна для перегляду автору і модератору.',
	'mess0'=>'Ця тема заблокована.',
	'togglestatus'=>'Змінити статус',
	'onmod'=>'На модерації',
	'ban'=>'Блокування',
	'activate'=>'Активувати',
	'permdelete'=>'Остаточно видалити',
	'delete'=>'Видалити',
	'move'=>'Перемістити',
	'close'=>'Закрити',
	'open'=>'Відкрити',
	'unpin'=>'Відкріпити',
	'pin'=>'Закріпити',
	'merget'=>'Склеїти тему',
	'merge'=>'Склеїти',
	'psmess-1'=>'Ви переглядаєте тільки потребують модерації пости теми. Для перегляду активної теми, перейдіть <a href="%s">сюди</a>.',
	'psmess0'=>'Ви переглядаєте тільки заблоковані пости теми. Для перегляду активної теми, перейдіть <a href="%s">сюди</a>.',
	'noposts'=>'Пости не знайдені. Спробуйте змінити фільтр.',
	'lnp'=>'Завантажити нові пости',
	'answer'=>'Відповісти',
	'deletet'=>'Видалити тему',
	'new-topic'=>'Нова тема',
	'moder-posts'=>'Модерування постів',
	'moder-topic'=>'Модерування теми',
	'your-name'=>'Ваше ім&#39;я',
	'text'=>'Текст',
	'enter-captcha'=>'Введіть код',
	'tofull'=>'Перейти у повну відповідь...',
	'prev'=>'Попередній пост',
	'next'=>'Наступний пост',
	'post-from'=>'Пост %d з %d',
	'pmess-1'=>'Даний пост очікує модерації. Доступний для перегляду автору і модератору.',
	'pmess0'=>'Даний пост заблокований.',
	'edit'=>'Правити',
	'quick-edit'=>'Швидка правка',
	'quote'=>'Цитата',
	'quick-quote'=>'Швидка цитата',
	'online'=>'Онлайн',
	'from'=>'від',
	'link'=>'посилання',
	'profile'=>'Профіль',
	'group%'=>'Група: %s',
	'posts%'=>'Постів: %s',
	'register%'=>'Реєстрація: %s',
	'from%'=>'Звідки: %s',
	'repa%'=>'Репутація: %s',
	'rno'=>'немає',
	'total%'=>'Всього: %s',
	'attached-images'=>'Прикріплені зображення',
	'attached-files'=>'Прикріплені файли',
	'approved'=>'Схвалили',
	'rejected'=>'Відкинули',
	'thanks'=>'Дякую',
	'moveposts'=>'Адреса або ID теми, куди перенести пости',
	'apply-filter'=>'Застосувати фільтр',
	'post'=>'Опублікувати пост',
	'main-author'=>'Автор посту:',

	#Підтримка мовних версій
	'related-text'=>'Ця тема також доступна українською мовою.',
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
	#[E] Підтримка мовних версій

	'updated'=>function($date)
	{
		return'Останнє оновлення '.mb_strtolower(Ukrainian::Date($date,'fdt'));
	},
);