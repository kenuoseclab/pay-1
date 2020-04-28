<?php
return array(
    'view_filter' => array('Behavior\TokenBuildBehavior'),
    'app_begin' => array('Behavior\CheckLangBehavior'),
    'action_begin' => array('Behavior\CheckAuthBehavior'),
    'LANG_SWITCH_ON' => true, // 开启语言包功能
    'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
    'LANG_LIST' => 'zh-cn', // 允许切换的语言列表 用逗号分隔
    'DEFAULT_LANG' => 'zh-cn', // 默认语言
    'VAR_LANGUAGE' => 'l'
) // 默认语言切换变量
;
?>