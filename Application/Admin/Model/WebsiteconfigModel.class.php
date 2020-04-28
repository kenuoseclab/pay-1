<?php
namespace Admin\Model;

use Think\Model;

class WebsiteconfigModel extends Model
{

    protected $_validate = array(
        array(
            "websitename",
            "require",
            "网站名称不能为空",
            0,
            "regex",
            3
        ),
        array(
            "websitename",
            "3,10",
            "网站名称最少 3 个字符，最多 10 个字符",
            2,
            "length",
            3
        ),
        array(
            "domain",
            "require",
            "域名不能为空",
            0,
            "regex",
            3
        )
    );
}
?>
