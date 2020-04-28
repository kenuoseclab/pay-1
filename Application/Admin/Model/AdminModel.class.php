<?php
namespace Admin\Model;

class AdminModel extends BaseModel
{
    protected $tableName = 'admin';
    
    /**
     * 获取角色列表
     * @params $type=1有分页，2无分布全部数据
     */
    public function getAdminList($groupid = 0)
    {

        $where=$groupid>0?array('a.groupid'=>$groupid):"";
        $count = $this->where($where)->count();
        
        $page = new \Think\Page($count, parent::PAGE_LIMIT);
        $join = 'LEFT JOIN pay_auth_group b ON b.id=a.groupid';

        $list = $this->alias('a')->field("a.*,b.title")->join($join)->where($where)->limit($page->firstRow, $page->listRows)->order('a.id asc')->select();

        return array(
            'list' => $list,
            'page' => $page->show(),
        );
    }
    
    /**
     * 根据id查找管理员
     * @param unknown $id
     */
    public function findAdmin($id)
    {
        $where = array(
            'id'     => $id

        );
        return $this->where($where)->find();
    }
    



}