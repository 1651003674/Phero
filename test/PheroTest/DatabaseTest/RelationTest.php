<?php

namespace PheroTest\DatabaseTest;

use PheroTest\DatabaseTest\BaseTest;
use PheroTest\DatabaseTest\Unit\Marry;
use PheroTest\DatabaseTest\Unit\Mother;
use PheroTest\DatabaseTest\Unit\MotherInfo;
use PheroTest\DatabaseTest\Unit\Parents;
use Phero\Database\Enum\RelType;
use Phero\Database\Traits\TRelation;
/**
 * 关联插入测试
 * @Author: ‘chenyingqiao’
 * @Date:   2017-06-04 17:00:10
 * @Last Modified by:   ‘chenyingqiao’
 * @Last Modified time: 2017-07-30 15:23:57
 */
class RelationTest extends BaseTest
{
	use TRelation;
	/**
	 * @test
	 * 目前只支持单个的插入关联
	 * @Author   Lerko
	 * @DateTime 2017-06-14T14:05:58+0800
	 * @return   [type]                   [description]
	 */
	public function InsertRelation(){
		Mother::Inc()->whereEq("id",12)->delete();
		MotherInfo::Inc()->whereEq("id",12)->delete();
		$Mother=new Mother;
		$Mother->id=12;
		$Mother->name="relation_test关联插入测试";
		$Mother->info=new MotherInfo([
				"email"=>"00000000@qq.com"
			]);
		$Mother->relInsert();
		$motherInfo=MotherInfo::Inc()->whereEq("email","00000000@qq.com")->find();
		$this->TablePrint($motherInfo);
		$this->assertNotEmpty($motherInfo);
		// var_dump($Mother->sql());
		// var_dump($Mother->info->sql());
	}

	/**
	 * 更新
	 * @test
	 * @Author   Lerko
	 * @DateTime 2017-06-14T14:58:27+0800
	 * @param    Mother                   $Mother [description]
	 * @return   [type]                           [description]
	 */
	public function UpdateRelation(){
		$Mother=new Mother;
		$Mother->id=12;
		$Mother->name="relation_test关联插入测试".rand();
		$Mother->info=new MotherInfo([
				"email"=>"relationupdate@qq.com"
			]);
		$Mother->relUpdate();
		$data=MotherInfo::Inc()->whereEq("email","relationupdate@qq.com")->find();
		$this->TablePrint($data);
		$this->assertNotEmpty($data);
		// var_dump($Mother->sql());
		// var_dump($Mother->info->sql());
	}

	/**
	 * @test
	 * @Author   Lerko
	 * @DateTime 2017-06-14T16:16:25+0800
	 * @return   [type]                   [description]
	 */
	public function deleteRelation(){
		$Mother=new Mother;
		$Mother->id=12;
		$Mother->info=MotherInfo::Inc(["mid"=>12]);
		$Mother->relDelete();
		$data=MotherInfo::Inc()->whereEq("mid",12)->find();
		$this->TablePrint($data);
		$this->assertEmpty($data);
		// var_dump($Mother->sql());
		// var_dump($Mother->info->sql());
	}

	/**
	 * @test
	 * @Author   Lerko
	 * @DateTime 2017-07-04T14:23:19+0800
	 * @return   [type]                   [description]
	 */
	public function getRelationInfo(){
		// var_dump($this->getRelation(Mother::Inc()));
	}

	/**
	 * @test
	 * @Author   Lerko
	 * @DateTime 2017-07-04T15:47:14+0800
	 * @param    string                   $value [description]
	 * @return   [type]                          [description]
	 */
	public function selectRelation($value='')
	{
		$motherinfo=Mother::Inc()->limit(1,3)->relSelect();
		// var_export($motherinfo);
		// var_dump(Mother::lastInc()->sql());
		// var_dump(Mother::lastInc()->info->sql());
		$this->assertNotEmpty(array_shift($motherinfo)['info']);
	}

	/**
	 * @test
	 * @Author   Lerko
	 * @DateTime 2017-07-30T11:10:45+0800
	 * @return   [type]                   [description]
	 */
	public function selectRelationMatchRel()
	{
		$data=Marry::Inc()->relSelect();
		var_dump($data);
		Marry::Inc([
			"id"=>1,
			"mid"=>1,
			"pid"=>1
		])->relDelete();
		$this->TablePrint(Mother::Inc()->select());
		$this->TablePrint(Parents::Inc()->select());
	}
}
