<?php
namespace Admin\Model;

class AdminAuthGroupAccessModel extends BaseModel
{
    protected $tableName = 'auth_group_access';
    
    /**
     * 获取用户所有权限
     */
    public function getUserRules($user_id)
    {
        $where = array('a.uid' => $user_id);
        $join = 'LEFT JOIN pay_auth_group b ON b.id=a.group_id';
        $rules = $this->alias('a')
                      ->where($where)
                      ->join($join)
                      ->field('b.rules')
                      ->select();
       
        if(!$rules){
            return array();
        }
        
        $rules_str = '';
        foreach($rules as $v){
            $rules_str .= $v['rules'] . ',';
        }
        
        $rules_str = rtrim($rules_str, ',');

        $rules_arr = array_unique(explode(',', $rules_str));
        
        $admin_menu_model = new AdminMenuModel();
        $menus = $admin_menu_model->getMenus($rules_arr);

        $menus = get_column($menus, 2);
        
        //dump($menus);exit;
        return $menus;
        
    }
}