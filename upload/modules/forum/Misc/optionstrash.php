<?php
$c=include Eleanor::$root.'modules/forum/config.php';
$CM=new Categories;
$CM->Init($c['f']);
return Eleanor::Option('-Выключить корзину-',0,in_array(0,$co['value'])).$CM->GetOptions($co['value']);