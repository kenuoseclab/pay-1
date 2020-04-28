<?php
namespace Admin\Model;

class PayForAnotherModel extends BaseModel
{
    
    protected $_validate = [
        ['title', 'trim', '代付名称首尾不能有空格', 1, 'function',],
        ['title', 'require', '代付名称不为空', 1],
        ['code', 'trim', '控制器名称首尾不能有空格', 1, 'function', ],
        ['code', 'require', '控制器名称不为空',1],
        ['status', array(0,1) , '开启状态修改错误', 0, 'in'],
        ['is_default', array(0,1), '默认状态修改错误', 0, 'in'],
    ];

    protected $_auto = [
        ['updatetime', 'time', 3, 'function',],
    ];

    public function editAllDefault($id){
        $where['id'] = [ 'neq', $id ];
        $result = $this->where($where)->save(['is_default'=>0]);
       
        if($result !== FALSE){
            $where['id'] = $id;
            $result = $this->where($where)->save(['is_default'=>1]);

        }

        return $result!==FALSE?true:false;
    }

}