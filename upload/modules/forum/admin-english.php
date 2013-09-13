<?php
return array(
	#For admin/index.php
	'EMPTY_TITLE'=>function($l){return'Title can not be empty'.($l ? ' (for '.$l.')' : '');},
	'EMPTY_NAME'=>function($l){return'Name can not be empty'.($l ? ' (for '.$l.')' : '');},
	'flist'=>'List of forums',
	'bgr'=>'Basic rights of groups',
	'massrights'=>'Mass appointment of rights',
	'fsns'=>'Forums aren&#39;t selected.',
	'gsns'=>'Groups aren&#39;t selected.',
	'subf_i'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{topic} - topic title<br />
{topiclink} - link to the topic<br />
{topicnewlink} - link to the first unread post of the topic.<br />
{topiclastlink} - link to the last post of the topic<br />
{forum} - forum name<br />
{forumlink} - link to the forum<br />
{author} - author&#39;s name<br />
{authorlink} - link to the author&#39;s profile<br />
{created} - the date of the creation of topic<br />
{lastview} - the date of the last viewing of forum<br />
{lastsend} - the date of the last notification sent<br />
{text} - text<br />
{name} - user name<br />
{cancel} - link to unsubscribe',
	'subf'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{forum} - forum name<br />
{forumlink} - link to the forum<br />
{createdlink} - link to created topics on forum<br />
{lastview} - date of last forum view<br />
{lastsend} - the date of the last notification sent<br />
{cnt} - the number of new topics<br />
{name} - user name<br />
{cancel} - link to unsubscribe',
	'subst_i'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{forum} - forum name<br />
{forumlink} - link to the forum<br />
{topiclink} - link to the topic<br />
{topic} - topic title<br />
{topicnewlink} - link to the first unread post of the topic.<br />
{topiclastlink} - link to the last post of the topic<br />
{postlink} - link to the post<br />
{author} - author&#39;s name<br />
{authorlink} - link to the author&#39;s profile<br />
{created} - the date of the post creation<br />
{lastview} - the date of the last viewing of topic<br />
{lastsend} - the date of the last notification sent<br />
{text} - text<br />
{name} - user name<br />
{cancel} - link to unsubscribe',
	'subt'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{forum} - forum name<br />
{forumlink} - link to the forum<br />
{topic} - topic title<br />
{topiclink} - link to the topic<br />
{topicnewlink} - link to the first unread post of the topic.<br />
{topiclastlink} - link to the last post of the topic<br />
{lastview} - the date of the last viewing of topic<br />
{lastsend} - the date of the last notification sent<br />
{cnt} - the number of new posts<br />
{name} - user name<br />
{cancel} - link to unsubscribe',
	'letrep'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{forum} - forum name<br />
{forumlink} - link to the forum<br />
{topic} - topic title<br />
{topiclink} - link to the topic<br />
{postlink} - link to the post<br />
{name} - user name<br />
{author} - author&#39;s name жалобы<br />
{authorlink} - link to the author&#39;s profile жалобы<br />
{text} - accompanying text<br />
{points} - received points<br />
{current} - current reputation',
	'labuse'=>'{site} - site name<br />
{sitelink} - link to the site<br />
{forumlink} - link to the forum<br />
{topiclink} - link to the topic<br />
{postlink} - link to the post<br />
{username} - author&#39;s name поста<br />
{userlink} - link to the author&#39;s profile поста<br />
{forum} - forum name<br />
{topic} - topic title<br />
{name} - moderator user name<br />
{abuse} - text of complaint<br />
{author} - complaint author&#39;s name<br />
{authorlink} - link to the author&#39;s profile of complaint',
	'ltitle'=>'Email title',
	'ltext'=>'Email text',
	'nsti'=>'Notification of topic subscription (immediate)',
	'nst'=>'Notification of topic subscription (delayed)',
	'nsfi'=>'Notification of forum subscription (immediate)',
	'nsf'=>'Notification of forum subscription (delayed)',
	'abuse-letter'=>'Notification for moderator after clicking abuse button',
	'nrep'=>'Notification of changing reputation',
	'letters'=>'Formats of letters',
	'fusers'=>'Forum users',
	'mlist'=>'List of moderators',
	'uploads'=>'Uploaded files',
	'prefixes'=>'Prefixes of topics',
	'fsubscr'=>'Forum subscriptions',
	'tsubscr'=>'Topic subscriptions',
	'~reputation'=>'Reputation changes',
	'service'=>'Forum service',
	'rgif'=>'Rights of group &quot;%&quot; on forum &quot;%&quot;',
	'fdel'=>'Forum deleting',
	'delc'=>'Confirm of forum deleting',
	'dlvf'=>'Deleting language versions of forum',
	'glist'=>'List of groups',
	'forumedit'=>'Editing of forum',
	'addforum'=>'Add forum',
	'editgroup'=>'Editing of group &quot;%s&quot;',
	'caccess'=>'Global forum accessibility',
	'caccess_'=>'Forum is visible for group',
	'ctopics'=>'View a list of topics',
	'ctopics_'=>'Users of group will be able to view a list of topics',
	'cantopics'=>'Display not only their own, but other people&#39;s topics',
	'cantopics_'=>'In the list of topics will be present topics of other users',
	'cread'=>'Allow read topics',
	'cread_'=>'Users will be able to read topics and separate posts within them',
	'cattach'=>'Open access to attachments',
	'cattach_'=>'Users will be able to access file attachments',
	'cpost'=>'Allow to post in own topics',
	'cpost_'=>'Users will be able to post in their own topic',
	'capost'=>'Allow to post in other people&#39;s topics',
	'capost_'=>'Users will be able to post in other people&#39;s topics',
	'cedit'=>'Allow edit own posts',
	'cedit_'=>'Users will be able to edit or delete own posts',
	'ceditlimit'=>'Time limitation of editing or deleting the post',
	'ceditlimit_'=>'After publication, the user can edit or delete own post for only a specified number of seconds. 0 - disabled',
	'cnew'=>'Allow create new topics',
	'cnew_'=>'Users will be able to create topics',
	'cmod'=>'Allow edit / delete other people&#39;s posts in their own topics.',
	'cmod_'=>'Users will be able edit or delete other people&#39;s posts in their own topics. Attention! When this option is enabled, users will be able edit / delete own posts in their own topics without limitations.',
	'cclose'=>'Allow open / close own topics',
	'cclose_'=>'Users will be able to open / close own topics',
	'cdeletet'=>'Allow delete own topics',
	'cdeletet_'=>'Users will be able delete own topics. If this option is disabled, then remove the first post of topic users will be unable.',
	'cdelete'=>'Allow delete own posts',
	'cdelete_'=>'Users will be able delete own posts',
	'ceditt'=>'Allow edit titles of own topics',
	'ccomplaint'=>'Allow to use abuse button',
	'ccanclose'=>'Allow work with a closed topic, like with the open',
	'ccanclose_'=>'Publish / edit / delete posts.',
	'edituser'=>'Editing user &quot;%s&quot;',
	'editprefix'=>'Edition prefix of topics',
	'addprefix'=>'Add prefix',
	'editmoder'=>'Editing moderator',
	'addmoder'=>'Adding moderator',
	'mcsingle'=>'Single moderation',
	'mcmovet'=>'Moving topics',
	'mcmove'=>'Moving posts',
	'mcdeletet'=>'Deleting topics',
	'mcdelete'=>'Deleting posts',
	'mceditt'=>'Editing titles of topics',
	'mcedit'=>'Editing posts',
	'mcchstatust'=>'Toggling status of topics',
	'mcchstatus'=>'Toggling status of posts',
	'mcmerget'=>'Merging topics',
	'mcmerge'=>'Merging posts',
	'mcpin'=>'Pinning / unpinning topics',
	'mcopcl'=>'Opening / closing topics',
	'mceditq'=>'Allow edit voting in topics',
	'mcviewip'=>'Show ip of posts',
	'mcuser_warn'=>'Allow warn users',
	'multimod'=>'Multi moderation',
	'mcmmovet'=>'Multi moderation of topics',
	'mcmmove'=>'Multi moderation of posts',
	'mcmdeletet'=>'Multi deletion of topics',
	'mcmdelete'=>'Multi deletion of posts',
	'mcmchstatust'=>'Multi toggling statuses of topics',
	'mcmchstatus'=>'Multi toggling statuses of posts',
	'mcmopcl'=>'Multi opening / closing of topics',
	'mcmpin'=>'Multi pinning / unpinning of topics',
	'mceditrep'=>'Allow to edit reputation',
);