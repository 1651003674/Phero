<?php 

namespace Phero\Database\Traits\UnitTrait;

use Phero\System\Tool;
/**
 * @Author: lerko
 * @Date:   2017-06-02 16:11:42
 * @Last Modified by:   lerko
 * @Last Modified time: 2017-06-02 17:16:34
 */
trait WhereUnitTrait
{
	//查询条件列表
	protected $where = [];
	public function getWhere() {return $this->where;}
	protected $whereGroup = false;
	/**
	 * [where description]
	 * 设置条件语句
	 * @param  [type] $where [
	 *                       ×数据库字段---index:0
	 *                       ×value数据---index:1
	 *                       -可选
	 *                       		比较符号 可选---index:2(默认未等号)
	 *                       		下个字段连接符 可选---index:3(默认为空字符串)
	 * ]
	 * @param  [type] $from  [来自那个表  如果是多表链接的话]
	 * @param  boolean $group     [是否进行where分组 1:左括号 2:右括号]
	 * @param  string  $whereTemp [where字段模板]
	 * @return [type]             [description]
	 */
	public function where($where, $from = null, $group = false, $whereTemp = "") {
		// if (!isset($where) || count($where) < 2) {
		// 	return;
		// }
		if (isset($from)) {
			$where['from'] = $from;
		}
		if($this->whereGroup!==false)
			$group = $this->whereGroup;
		//这里的wheregroup是通过where进行添加的
		if ($group !== false) {
			$where['group'] = $group;
		}
		if (!empty($whereTemp)) {
			$where['temp'] = $whereTemp;
		}
		$this->where[] = $where;
		return $this;
	}

	/**
	 * Where的分组函数
	 * @Author   Lerko
	 * @DateTime 2017-03-20T15:12:01+0800
	 * @param    Closure                  $func [description]
	 */
	public function Set(\Closure $func){
		$this->call_set=true;
		$this->setGroup();
		$func=$func->bindTo($this);
		$this_self=$func();
		$this->setGroup(parent::GroupEnd);
		return $this_self;
	}

	/**
	 * where 分组标示符号
	 * @Author   Lerko
	 * @DateTime 2017-03-20T15:51:57+0800
	 * @return   [type]                   [description]
	 */
	public function setGroup($type=self::GroupStart){
		if($type==self::GroupStart)
			$this->whereGroup=1;
		else if($type==self::GroupEnd)
			$this->whereGroup=2;
		else
			$this->whereGroup=0;
		if($type!=self::GroupDisbale)
			$this->where(null,null,true);
	}

	public function setWhereRelation($tablename){
		Tool::getInstance()->setWhereRelation($this->where,$tablename,$this->getNameByCleverWay($this));
	}
}
