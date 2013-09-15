<?php
/*
	Resale is forbidden!
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
return array(
	'my_on_mod%'=>'My pending moderation (%s)',
	'on_mod%'=>'Pending moderation (%s)',
	'active%'=>'Active (%s)',
	'blocked%'=>'Blocked (%s)',
	'all%'=>'All (%s)',
	'wait-my'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'On topic there '.($pl ? 'are' : 'is').' '.$cnt.($pl ? ' your posts' : ' your topic').' pending moderation. To see '.($pl ? 'it' : 'them')
		.' click <a href="'.$href.'">here</a>.';
	},
	'wait-moder'=>function($href,$cnt)
	{
		$pl=$cnt>1;
		return'On forum there '.($pl ? 'are' : 'is').' '.$cnt.($pl ? ' topics' : ' topic').' pending moderation. To see '.($pl ? 'it' : 'them')
		.' click <a href="'.$href.'">here</a>.';
	},
	'leave_link'=>' leave link to topic in this forum',
	'filter'=>'Filter',
	'with_status:'=>'With status: ',
	'only_my'=>'Show only my posts',
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
		#Replace last comma with "and"
		$r=preg_replace('#,([^,]+)$#',' and\1',$r);
		return$r;
	},
	'here-now'=>function()
	{
		return'This topic is reading by ';
	},
	'here-now-post'=>function()
	{
		return'This post is reading by ';
	},
	'rules'=>'Forum rules',
	'edited'=>function($date,$who,$whohref,$reason)
	{
		return Eleanor::$Language->Date($date,'fdt').' this post was edited by '
			.($whohref ? '<a href="'.$whohref.'">'.$who.'</a>' : $who)
			.'. Reason: '
			.($reason ? $reason : '<i>unknown</i>');
	},
	'downloaded'=>function($cnt)
	{
		return $cnt.' downloads';
	},
	'not-subscribe'=>'No subscription',
	'notify'=>'Subscription with notification',
	'immediately'=>'Instant',
	'daily'=>'Daily',
	'weekly'=>'Weekly',
	'monthly'=>'Monthly',
	'mess-1'=>'This topic is pending moderation. It can be accessed by author and moderator.',
	'mess0'=>'This topic is blocked.',
	'togglestatus'=>'Toggle status',
	'onmod'=>'Pending moderation',
	'ban'=>'Blocked',
	'activate'=>'Activate',
	'permdelete'=>'Permanently delete',
	'delete'=>'Delete',
	'move'=>'Move',
	'close'=>'Close',
	'open'=>'Open',
	'unpin'=>'Unpin',
	'pin'=>'Pin',
	'merget'=>'Merge topic',
	'merge'=>'Merge',
	'psmess-1'=>'You are viewing only pending moderation posts of topic. To view current active topic click <a href="%s">here</a>.',
	'psmess0'=>'You are viewing only pending moderation posts of topic. To view current active topic click <a href="%s">here</a>.',
	'noposts'=>'No posts found. Try to change the filter',
	'lnp'=>'Load new posts',
	'answer'=>'Answer',
	'deletet'=>'Delete topic',
	'new-topic'=>'New topic',
	'moder-posts'=>'Posts moderation',
	'moder-topic'=>'Topic moderation',
	'your-name'=>'Your name',
	'text'=>'Text',
	'enter-captcha'=>'Enter code',
	'tofull'=>'Go to full answer...',
	'prev'=>'Previous post',
	'next'=>'Next post',
	'post-from'=>'Post # %d of %d',
	'pmess-1'=>'This post is pending moderation. It can accessed by author and moderator.',
	'pmess0'=>'This post is blocked.',
	'edit'=>'Edit',
	'quick-edit'=>'Quick edit',
	'quote'=>'Quote',
	'quick-quote'=>'Quick quote',
	'online'=>'Online',
	'from'=>'from',
	'link'=>'link',
	'profile'=>'Profile',
	'group%'=>'Group: %s',
	'posts%'=>'Posts: %s',
	'register%'=>'Register: %s',
	'from%'=>'From: %s',
	'repa%'=>'Reputation: %s',
	'rno'=>'no',
	'total%'=>'Total: %s',
	'attached-images'=>'Attached images',
	'attached-files'=>'Attached files',
	'approved'=>'Approved',
	'rejected'=>'Rejected',
	'thanks'=>'Thanks',
	'moveposts'=>'URL or ID of topic where posts will be moved',
	'apply-filter'=>'Apply filter',
	'post'=>'Publish',
	'main-author'=>'Author of post:',
);