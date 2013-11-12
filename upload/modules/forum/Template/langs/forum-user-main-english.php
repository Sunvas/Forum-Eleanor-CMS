<?php
return array(
	'inaccessible'=>'Forums unavailable to you',
	'nowonforum'=>'Now on forum',
	'stats'=>'Statistics',
	'challread'=>'Mark forums read',
	'nonewposts'=>'No new posts',
	'whoon'=>function($g,$u,$h,$b)
	{
		$r=$g>0 ? $g.' guest'.($g>1 ? 's' : '') : '';
		if($u>0)
		{
			if($r)
				$r.=', ';
			$r.=$u.' user'.($u>1 ? 's' : '');
		}
		if($h>0)
		{
			if($r)
				$r.=', ';
			$r.=$h.' hidden user'.($h>1 ? 's' : '');
		}
		if($b>0)
		{
			if($r)
				$r.=', ';
			$r.=$b.' search engine bot'.($b>1 ? 's' : '');
		}
		#Replace last comma with ""
		$r=preg_replace('#,([^,]+)$#',' и\1',$r);
		return$r;
	},
	'quantity'=>'Quantity indicators:',
	'tau'=>function($t,$a,$u)
	{
		return'<b>'.$t.'</b> topic'.($t>1 ? 's' : '')
			.'<br /><b>'.$a.'</b> answer'.($a>1 ? 's' : '')
			.'<br /><b>'.$u.'</b> user'.($u>1 ? 's' : '')
			.'<br />';
	},
	'forum'=>'Forum',
	'topics'=>'Topics',
	'answers'=>'Answers',
	'lastpost'=>'Last post',
	'group:'=>'Group: %s',
	'moders:'=>'Moderators: %s',
	'subforums:'=>'Sub-forums: %s',
	'mt'=>'Topics pending moderation',
	'ma'=>'Answers pending moderation',
	'tar'=>'Access to the topics is prohibited.',
	'topic:'=>'Topic:',
	'author:'=>'Author:',
	'hasnp'=>'There are new posts. Mark Read.',
	'rules'=>'Forum rules',
	'nort'=>'You are not allowed to view the topics',
	'chst'=>'Toggle status',
	'onmod'=>'Pending moderation',
	'hided'=>'Hide',
	'active'=>'Active',
	'delete'=>'Delete',
	'move'=>'Move',
	'open'=>'Open',
	'close'=>'Close',
	'pin'=>'Pin',
	'unpin'=>'Unpin',
	'merge'=>'Merge topics',
	'new-topic'=>'New topic',
	'not-subscribe'=>'No subscription',
	'notify'=>'Subscription with notification',
	'immediately'=>'Instant',
	'daily'=>'Daily',
	'weekly'=>'Weekly',
	'monthly'=>'Monthly',
	'topic'=>'Topic',
	'views'=>'Views',
	'tnf'=>'Topics are not found',
	'closed'=>'Closed',
	'moved'=>'Moved',
	'merged'=>'Merged',
	'tread'=>'Topic read',
	'gnp'=>'Go to the first unread post',
	'waits'=>'Pending',
	'imp'=>'Important',
	'voting'=>'Voting',
	'twp'=>'Answers pending moderation',
	'glp'=>'Go to the last post',
	'mfr'=>'Mark forum read',
	'here-now'=>function()
	{
		return'This forum is reading by ';
	},
	'moder-topics'=>'Topics moderation',
	'leave_link'=>' leave link to topic(s) in this forum',
	'main_topic'=>'General topic:',
	'other_topic'=>'-other topic-',
	'id_or_url_ot'=>'Input ID of topic or link of general topic',
	'ld_all'=>'all time',
	'ld_30'=>'last 30 days',
	'ld_60'=>'last 60 days',
	'ld_90'=>'last 90 days',
	'my_on_mod%'=>'My pending moderation (%s)',
	'on_mod%'=>'Pending moderation (%s)',
	'active%'=>'Active (%s)',
	'blocked%'=>'Blocked (%s)',
	'all%'=>'All (%s)',
	'filter'=>'Filter',
	'with_status:'=>'With status: ',
	'for:'=>'For: ',
	'only_my'=>'Show only my topics',
	'rss_topics'=>'Topics of forum &quot;%s&quot;',
	'rss_posts'=>'Posts of forum &quot;%s&quot;',
	'wait-my'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'On forum there '.($pl ? 'are' : 'is').' '.$cnt.($pl ? ' your topics' : ' your topic').' pending moderation. To see '.($pl ? 'it' : 'them')
			.' click <a href="'.$href.'">here</a>.';
	},
	'wait-moder'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'On forum there '.($pl ? 'are' : 'is').' '.$cnt.($pl ? ' topics' : ' topic').' pending moderation. To see '.($pl ? 'it' : 'them')
		.' click <a href="'.$href.'">here</a>.';
	},
	'all-topics'=>'All topics',
	'apply-filter'=>'Apply filter',
);