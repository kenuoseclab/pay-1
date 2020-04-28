<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-09-06
 * Time: 1:43
 */
namespace Admin\Model;

use Think\Model;

class UpdatelogModel extends Model
{
    private $_status;
    private $_result;
    private $_exception;
    private $_errno = 0;
    private $_allowErrorCode = array(1050, 1054, 1060, 1061, 1062, 1091, 1146);
    protected $tableName = 'updatelog';


    public function run($result)
    {
        $this->_result = $result;
        $this->_commit();
        return $this->_status;
    }

    private function _commit()
    {
        //$link = $this->db->connect();
        $sql = str_replace('pay_', C('DB_PREFIX'), $this->_result);
        try {
            //$this->_status = @mysql_query($sql, $link);
            //if (!$this->_status) {
                $this->_status = $this->query($sql);
            //}
        } catch (Exception $e) {
            $this->_status = 0;
            $this->_errno = mysql_errno();

            if (in_array($this->_errno, $this->_allowErrorCode)) {
                $this->_status = 1;
            }
            $this->_exception = '[CODE] : ' . $this->_errno . ' # ' . $e->getMessage();
        }
    }
}