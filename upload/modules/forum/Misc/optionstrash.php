<?php
defined('CMS')||die;
$c=include dirname(__DIR__).'/config.php';
$CM=new Categories;
$CM->Init($c['f']);
return Eleanor::Option('&mdash;',0,in_array(0,$co['value']),array(),2).$CM->GetOptions($co['value']);