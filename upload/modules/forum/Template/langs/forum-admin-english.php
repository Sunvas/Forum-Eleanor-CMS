<?php
return array(
	'flist'=>'List of forums',
	'addforum'=>'Add forum',
	'mlist'=>'List of moderators',
	'addmoder'=>'Add moderator',
	'prefixes'=>'Prefixes of topics',
	'reputation'=>'Reputation',
	'~reputation'=>'Reputation changes',
	'bgr'=>'Basic rights of groups',
	'massrights'=>'Mass appointment of rights',
	'fusers'=>'Forum users',
	'letters'=>'Formats of letters',
	'addprefix'=>'Add prefix',
	'uploads'=>'Uploaded files',
	'fsubscr'=>'Forum subscriptions',
	'tsubscr'=>'Topic subscriptions',
	'service'=>'Forum service',
	'oprgr'=>'Set rights of groups',
	'pos'=>'Position',
	'begin'=>'To top',
	'after'=>'After',
	'up'=>'Up',
	'down'=>'Down',
	'subforums'=>'Sub-forums',
	'addsubforum'=>'Add sub-forum',
	'fbnf'=>'Sub-forums are not found',
	'fnf'=>'Forums are not found',
	'fpp'=>'Forums per page: %s',
	'location'=>'Location',
	'parent'=>'Parent',
	'fcat'=>'This is category, not forum',
	'fcat_'=>'In categories sub-forums are placed, create topics in them is impossible.',
	'uid'=>'User identification',
	'logo'=>'Logotype',
	'descr'=>'Description',
	'rules'=>'Forum rules',
	'opts'=>'Options',
	'incposts'=>'Enable posts counter',
	'enrep'=>'Enable reputation',
	'hideattach'=>'Hide attaches',
	'hideattach_'=>'Enabling this option will make it impossible to transfer links of attachment to third party',
	'puttopics'=>'What do with topics',
	'deltopics'=>'delete topics',
	'ltif'=>'Leave in current forum with changing language',
	'ptaf'=>'Move to other forum',
	'uni'=>'For all languages',
	'delete'=>'Delete',
	'cancel'=>'Cancel',
	'delcf'=>function($langs,$title)
	{
		$uni=$langs==array('');
		return'You are going to delete '.($uni ? '<i>universal</i> ' : '').(count($langs)>1 ? ' language versions' : ' language version').' '.($uni ? '' : '&quot;'.join('&quot;, &quot;',$langs).'&quot;').' of forum &quot;'.$title.'&quot;. Select a forum where topics will be moved.<br /><b style="color:red">Attention! This action is irreversible impact also on sub-forums.</b>';
	},
	'delfp'=>function($langs,$title)
	{
		$cnt=count($langs);
		$uni=$langs==array('');
		return($uni ? 'Universal language' : 'Language').($cnt>1 ? ' versions' : ' version').join(', ',$langs).' of forum &quot;'.$title.'&quot; '.($cnt>1 ? 'are deleting...' : 'is deleting...');
	},
	'deld'=>function($langs,$title)
	{
		$cnt=count($langs);
		$uni=$langs==array('');
		return($uni ? 'Universal language' : 'Language').($cnt>1 ? ' versions' : ' version').join(', ',$langs).' of forum &quot;'.$title.'&quot; was successfully deleted.';
	},
	'back'=>'Back',
	'group'=>'Group',
	'supermod'=>'Supermoderator',
	'propag'=>'Progress',
	'subgroups'=>'Sub-groups',
	'ga'=>function($cnt)
	{
		return'after '.($cnt>1 ? 'posts' : 'post');
	},
	'no'=>'no',
	'sgnf'=>'Sub-groups was not found',
	'togr'=>'To group',
	'-nopropag-'=>'-Do not progress-',
	'afposts'=>'After posting',
	'posts.'=>' posts.',
	'gr'=>'Global rights',
	'shu'=>'Show hidden users',
	'premod'=>'Premoderation posts',
	'mains'=>'General settings',
	'rbd'=>'Permissions by default',
	'assignment'=>'Assignment',
	'groups'=>'Groups',
	'forums'=>'Forums',
	'rights'=>'Permissions',
	'riss'=>'Permissions successfully saved',
	'b'=>'Begins with',
	'q'=>'Coincides with',
	'e'=>'Ends with',
	'm'=>'Contain',
	'un'=>'User name',
	'posts'=>'Posts',
	'recrep'=>'Recount reputation',
	'unf'=>'Users was not found',
	'rcounted'=>function($ua)
	{
		return'Reputation of user'.(count($ua)>1 ? 's ' : ' ').join(', ',$ua).' was recounted';
	},
	'reg'=>'Register',
	'ft'=>'from to',
	'upp'=>'Users per page: %s',
	'pon'=>'Posts on forum',
	'rpr'=>'Prohibit posting posts',
	'rpru'=>'Prohibit posting posts until',
	'note'=>'Note',
	'internal'=>'Internal, only for administrator',
	'creation'=>'Creation',
	'mnf'=>'Moderators was not found',
	'forum'=>'Forum',
	'dnm'=>'-not important-',
	'mpp'=>'Moderators was not found: %s',
	'users'=>'Users',
	'add'=>'Add',
	'moderrights'=>'Moderator rights',
	'gdc'=>function($forums,$groups,$users)
	{
		return'Do you really want to delete moderators '.rtrim($forums,' ,')
		.' in the person of '.($groups ? rtrim($groups,', ').' groups' : '')
		.($users ? ($groups ? ' and ' : '').rtrim($users,', ').' users' : '')
		.'?';
	},
	'setrights'=>'Configure rights',
	'atsf'=>'Apply to sub-forums?',
	'save'=>'Successfully saved',
	'dfc'=>'Do you really want to delete &quot;%s&quot;? Select a forum where topics will be moved.',
	'fdels'=>'Forum &quot;%s&quot; is deleting...',
	'fdeleted'=>'Forum &quot;%s&quot; was successfully deleted.',
	'prefix'=>'Prefix',
	'pnf'=>'Prefixes was not found',
	'design'=>'Name',
	'ppp'=>'Prefixes per page: %s',
	'pdc'=>function($prefix,$forums)
	{
		return'Do you really want to delete prefix of topics &quot;'.$prefix.'&quot; used in '.(count($forums)>1 ? 'forums ' : 'forum ').join(', ',$forums).'?';
	},
	'save-forum'=>'Save forum',
	'add-forum'=>'Add forum',
	'save-prefix'=>'Save prefix',
	'add-prefix'=>'Add prefix',

	'syncusers'=>'Users synchronization',
	'finishdate'=>'Finish date',
	'dateupdate'=>'Date of next update',
	'begindate'=>'Date of begin',
	'startedsync'=>'start of synchronization',
	'prevrun'=>'Previous launches',
	'error'=>'Errors',
	'sudbs'=>'The start date of synchronization',
	'run'=>'Start',

	#Ошибки
	'PARENT_HAS_NOT_SAME_LANG'=>'Error in inheritance of languages',
	'EMPTY_FORUMS'=>'Forums not selected',
	'EMPTY_MODERS'=>'Neither users nor groups are not selected',
);