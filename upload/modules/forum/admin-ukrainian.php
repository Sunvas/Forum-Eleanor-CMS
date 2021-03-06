<?php
return array(
	#Для admin/index.php
	'EMPTY_TITLE'=>function($l){return'Заголовок не може бути порожнімм'.($l ? ' (для '.$l.')' : '');},
	'EMPTY_NAME'=>function($l){return'Назва не може бути порожнім'.($l ? ' (для '.$l.')' : '');},
	'flist'=>'Список форумів',
	'bgr'=>'Базові права груп',
	'massrights'=>'Масове призначення прав',
	'fsns'=>'Не вибрані форуми',
	'gsns'=>'Не вибрані групи',
	'subf_i'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{topic} - назва теми<br />
{topiclink} - посилання на тему<br />
{topicnewlink} - посилання на перший непрочитаний пост теми<br />
{topiclastlink} - посилання на останній пост теми<br />
{forum} - назва форуму<br />
{forumlink} - посилання на форум<br />
{author} - ім&#39;я автора<br />
{authorlink} - посилання на автора<br />
{created} - дата створення теми<br />
{lastview} - дата останнього перегляду форуму<br />
{lastsend} - дата останньої відправки<br />
{text} - текст<br />
{name} - ім&#39;я користувача<br />
{cancel} - посилання на відміну підписки',
	'subf'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{forum} - назва форуму<br />
{forumlink} - посилання на форум<br />
{createdlink} - посилання на створені теми на форумі<br />
{lastview} - дата останнього перегляду форуму<br />
{lastsend} - дата останньої відправки<br />
{cnt} - число нових тем<br />
{name} - ім&#39;я користувача<br />
{cancel} - посилання на відміну підписки',
	'subst_i'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{forum} - назва форуму<br />
{forumlink} - посилання на форум<br />
{topiclink} - посилання на тему<br />
{topic} - назва теми<br />
{topicnewlink} - посилання на перший непрочитаний пост теми<br />
{topiclastlink} - посилання на останній пост теми<br />
{postlink} - посилання на пост<br />
{author} - ім&#39;я автора<br />
{authorlink} - посилання на автора<br />
{created} - дата створення посту<br />
{lastview} - дата останнього перегляду теми<br />
{lastsend} - дата останньої відправки<br />
{text} - текст<br />
{name} - ім&#39;я користувача<br />
{cancel} - посилання на відміну підписки',
	'subt'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{forum} - назва форуму<br />
{forumlink} - посилання на форум<br />
{topic} - назва теми<br />
{topiclink} - посилання на тему<br />
{topicnewlink} - посилання на перший непрочитаний пост теми<br />
{topiclastlink} - посилання на останній пост теми<br />
{lastview} - дата останнього перегляду теми<br />
{lastsend} - дата останньої відправки<br />
{cnt} - число нових повідомлень<br />
{name} - ім&#39;я користувача<br />
{cancel} - посилання на відміну підписки',
	'letrep'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{forum} - назва форуму<br />
{forumlink} - посилання на форум<br />
{topic} - назва теми<br />
{topiclink} - посилання на тему<br />
{postlink} - посилання на пост<br />
{name} - ім&#39;я користувача<br />
{author} - ім&#39;я автора скарги<br />
{authorlink} - посилання на автора скарги<br />
{text} - супутній текст<br />
{points} - отримана репутація<br />
{current} - поточна репутація',
	'labuse'=>'{site} - назва сайту<br />
{sitelink} - посилання на сайт<br />
{forumlink} - посилання на форум<br />
{topiclink} - посилання на тему<br />
{postlink} - посилання на пост<br />
{username} - ім&#39;я автора посту<br />
{userlink} - посилання на автора посту<br />
{forum} - назва форуму<br />
{topic} - назва теми<br />
{name} - ім&#39;я користувача модератора<br />
{abuse} - текст скарги<br />
{author} - ім&#39;я автора скарги<br />
{authorlink} - посилання на автора скарги',
	'ltitle'=>'Заголовок листа',
	'ltext'=>'Текст листа',
	'nsti'=>'Повідомлення про підписану тему (негайне)',
	'nst'=>'Повідомлення про підписану тему (із затримкою)',
	'nsfi'=>'Повідомлення про підписаний форум (негайне)',
	'nsf'=>'Повідомлення про підписаний форум (із затримкою)',
	'abuse-letter'=>'Повідомлення модератору по кнопці "скарга"',
	'nrep'=>'Повідомлення про зміну репутації',
	'letters'=>'Формати листів',
	'fusers'=>'користувачі форуму',
	'mlist'=>'Список модераторів',
	'uploads'=>'Завантажені файли',
	'prefixes'=>'Префікси тем',
	'fsubscr'=>'Підписки на форуми',
	'tsubscr'=>'Підписки на теми',
	'~reputation'=>'Зміни репутації',
	'service'=>'Обслуговування форуму',
	'rgif'=>'Права групи &quot;%&quot; в форумі &quot;%&quot;',
	'fdel'=>'Видалення форуму',
	'delc'=>'Підтвердження видалення',
	'dlvf'=>'Видалення мовних версій форуму',
	'glist'=>'Список груп',
	'forumedit'=>'Редагування форуму',
	'addforum'=>'Додати форум',
	'editgroup'=>'Редагування групи &quot;%s&quot;',
	'caccess'=>'загальна доступність форуму',
	'caccess_'=>'Форум видно групі',
	'ctopics'=>'Перегляд списку тем',
	'ctopics_'=>'Користувачі групи зможуть переглядати список тем',
	'cantopics'=>'Відображати не тільки свої, але і чужі теми',
	'cantopics_'=>'В списку тем будуть присутні теми інших користувачів',
	'cread'=>'Дозволити читати теми',
	'cread_'=>'Користувачі зможуть читати теми та окремі повідомлення в них',
	'cattach'=>'Відкрити доступ до вкладень',
	'cattach_'=>'Користувачі зможуть отримувати доступ до файлових вкладень',
	'cpost'=>'Дозволити відповідати в свої теми',
	'cpost_'=>'Користувачі зможуть відповідати в свої теми',
	'capost'=>'Дозволити відповідати в чужі теми',
	'capost_'=>'Користувачі зможуть відповідати в чужі теми',
	'cedit'=>'Дозволити редагувати свої повідомлення',
	'cedit_'=>'Користувачі зможуть відредагувати або видалити свої повідомлення',
	'ceditlimit'=>'Тимчасове обмеження редагування/видалення повідомлення',
	'ceditlimit_'=>'Після публікації, користувач зможе відредагувати або видалити своє повідомлення тільки на протязі вказаногї кількості секунд. 0 - відключено',
	'cnew'=>'Дозволити створювати теми',
	'cnew_'=>'Користувачі зможуть створювати теми',
	'cmod'=>'Дозволити редагувати/видаляти чужі повідомлення в своїх темах',
	'cmod_'=>'Користувачі зможуть відредагувати або видалити чужі повідомлення в своїх темах. Увага! При включенні цієї опції, Користувачі зможуть редагувати/видаляти свої повідомлення в своїх темах без обмежень.',
	'cclose'=>'Дозволити відкривати/закривати свої теми',
	'cclose_'=>'Користувачі зможуть відкривати/закривати свої теми',
	'cdeletet'=>'Дозволити видаляти свої теми',
	'cdeletet_'=>'Користувачі зможуть видаляти свої теми. Якщо ця опція відключена, то видалити перше повідомлення Користувачі не зможуть.',
	'cdelete'=>'Дозволити видаляти свої повідомлення',
	'cdelete_'=>'Користувачі зможуть видаляти свої повідомлення',
	'ceditt'=>'Дозволити редагувати заголовки своїх тем',
	'ccomplaint'=>'Дозволити користуватись кнопкою &quot;скарга&quot;',
	'ccanclose'=>'Дозволити працювати з закрытою темою, як з відкритою',
	'ccanclose_'=>'Публікувати/редагувати/видаляти пости',
	'ccreatevoting'=>'Дозволити створювати опитування',
	'ccreatevoting_'=>'Користувачі зможуть прикріплювати опитування до своїх тем',
	'ceditvoting'=>'Дозволити правити опитування',
	'ceditvoting_'=>'Користувачі зможуть змінювати опитування не впливаючи на їх результати',
	'cvote'=>'Дозволити голосувати в опитуваннях',
	'cvote_'=>'Користувачі зможуть брати участь в опитуваннях',
	'edituser'=>'Редагування користувача &quot;%s&quot;',
	'editprefix'=>'Редагування префікса тем',
	'addprefix'=>'Додати префікс',
	'editmoder'=>'Редагування модератора',
	'addmoder'=>'Додати модератора',
	'mcsingle'=>'Поодинока модерація',
	'mcmovet'=>'Переміщення тем',
	'mcmove'=>'Переміщення повідомлень',
	'mcdeletet'=>'Видалення тем',
	'mcdelete'=>'Видалення повідомлень',
	'mceditt'=>'Редагування заголовків тем',
	'mcedit'=>'Редагування Повідомлень',
	'mcchstatust'=>'Зміна статусу тем',
	'mcchstatus'=>'Зміна статусу повідомлень',
	'mcmerget'=>'Об&#39;єднання тем',
	'mcmerge'=>'Об&#39;єднання повідомлень',
	'mcpin'=>'Закріплення / відкріплення тем',
	'mcopcl'=>'Відкриття / закриття тем',
	'mceditq'=>'Дозволити редагування опитувань в темах',
	'mcviewip'=>'Відображати IP адреси повідомлень',
	'mcuser_warn'=>'Дозволити попереджати користувачів',
	'multimod'=>'Мультимодерація',
	'mcmmovet'=>'Мультипереміщення тем',
	'mcmmove'=>'Мультипереміщення повідомлень',
	'mcmdeletet'=>'Мультивидалення тем',
	'mcmdelete'=>'Мультивидалення повідомлень',
	'mcmchstatust'=>'Мультиизміна статусів тем',
	'mcmchstatus'=>'Мультиизміна статусів повідомлень',
	'mcmopcl'=>'Мультивідкриття / закриття тем',
	'mcmpin'=>'Мультизакріплення / відкріплення тем',
	'mceditrep'=>'Дозволити редагувати репутацію',
	'mcvoting'=>'Модерування опитувань',
);