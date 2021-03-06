<?php

namespace Phero\Command;

use League\CLImate\CLImate;
use Phero\Database\Realize\MysqlDbHelp;
use Phero\Map\Note\Field;
use Phero\Map\Note\RelationEnable;
use Phero\Map\Note\Table;
use Phero\System\DI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Console\Config\DefaultApplicationConfig;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
/**
 * @Author: lerko
 * @Date:   2017-06-19 20:02:38
 * @Last Modified by:   lerko
 * @Last Modified time: 2017-08-20 23:12:03
 */
class UnitBuilder extends Command
{
	protected function configure(){
		$this->setName("builder")
			->setDescription("从创建unit实体")
			->addOption("u", null, InputOption::VALUE_OPTIONAL, "数据库链接的用户名 默认使用root", "root")
			->addOption("p", null, InputOption::VALUE_OPTIONAL, "数据库链接的密码 默认为空", "")
			->addOption("port", null, InputOption::VALUE_OPTIONAL, "数据库的端口", "3306")
			->addOption("dir", null, InputOption::VALUE_REQUIRED, "生成的位置")
			->addOption("namespace", null, InputOption::VALUE_REQUIRED, "用户生成Unit的命名空间")
			->addOption("db", null, InputOption::VALUE_REQUIRED, "生成unit对应的数据库")
			->addOption("h", null, InputOption::VALUE_REQUIRED, "数据库的远程地址",'127.0.0.1')
			->addArgument("tables",InputArgument::IS_ARRAY|InputArgument::OPTIONAL,"需要单独生成的表的名称",[]);
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$climate=new CLImate;
		$climate->bold()->backgroundBlue()->border();

		$fileFloder = $input->getOption("dir");
		if(!is_dir($fileFloder)){
			mkdir(iconv("UTF-8", "GBK", $fileFloder),0777,true);
		}

		$namespace=$input->getOption("namespace");
		$dbname=$input->getOption("db");
		$host=$input->getOption("h");
		$username=$input->getOption("u");
		$password=$input->getOption("p");
		$port=$input->getOption("port");
		$tables_input=$input->getArgument("tables");

		DI::inj("pdo_instance",new \PDO("mysql:dbname={$dbname};host={$host};port={$port}",$username,$password));
		$DbHelp=new MysqlDbHelp();
		$tables=$DbHelp->queryResultArray("show tables");
		$tables_gen=[];
		if(!empty($tables_input)){
			foreach ($tables_input as $key => $value) {
				if(in_array($value,$tables_input)){
					$tables_gen[]=$value;
				}else{
					$climate->red("$value 不存在");
				}
			}
		}else{
			$tables_gen=$tables;
		}
		$progress=$climate->progress()->total(count($tables_gen));
		foreach ($tables_gen as $key => $value) {
			$value=$value["Tables_in_{$dbname}"];
			$progress->current($key + 1);
			$this->_createPhp($value,$dbname,$namespace,$fileFloder);
		}
	}

	/**
	 * 创建php文件
	 * @Author   Lerko
	 * @DateTime 2017-06-27T10:36:25+0800
	 * @param    [type]                   $tablename [description]
	 * @param    [type]                   $dbname    [description]
	 * @return   [type]                              [description]
	 */
	private function _createPhp($tablename,$dbname,$namespace,$fileFloder){
		$classname=$this->splitTableName($tablename);
		$classes=$this->_createDbUnit($classname,$namespace);
		$tableNode=new Table();
		$tableNode->name=$tablename;
		$tableNode->alias=$this->base64_sp($tablename);
		$classes->setDocblock($this->_createTableDocBlock($tableNode));
		$DbHelp=new MysqlDbHelp();
		$field=$DbHelp->queryResultArray("select * from information_schema.columns where table_schema = '{$dbname}' and table_name = '{$tablename}';");
		foreach ($field as $key => $value) {
			if(strstr($value["DATA_TYPE"],"int")!==false)
				$type="int";
			else
				$type="string";
			if(strstr($value['COLUMN_KEY'],'PRI')!==false)
				$is_primary=true;
			else
				$is_primary=false;
			$classes->addPropertyFromGenerator($this->_createProperty($value['COLUMN_NAME'],$type,$tablename,$value['COLUMN_COMMENT'],$is_primary));
		}
		$content=$classes->generate();
		file_put_contents($fileFloder."/".$classname.".php", "<?php\n".$content);
	}

	//创建类
	private function _createDbUnit($name,$namespace){
		$classgenerator=new ClassGenerator;
		$classgenerator->setExtendedClass("Phero\Database\DbUnit")
				->setName($name)
				 ->setNamespaceName($namespace);
		return $classgenerator;
	}

	/**
	 * 创建Table的注解
	 * @Author   Lerko
	 * @DateTime 2017-06-26T11:19:35+0800
	 * @param    Table                    $tableNode [description]
	 * @param    RelationEnable|null      $relation  [description]
	 * @return   [type]                              [description]
	 */
	private function _createTableDocBlock(Table $tableNode,RelationEnable $relation=null){
		$GeneratorData=[];
		$description="[name={$tableNode->name},";
		$alias=$tableNode->alias;
		if(empty($alias)){$description.="]";}else{$description.="alias=$alias]";}
		$GeneratorData['tags'][]=["name"=>"Table","description"=>$description];
		if($relation){
			$GeneratorData['tags'][]=['name'=>"RelationEnable"];
		}
		return DocBlockGenerator::fromArray($GeneratorData);
	}

	/**
	 * 创建property的注解
	 * @Author   Lerko
	 * @DateTime 2017-06-26T11:19:58+0800
	 * @param    Field                    $field [description]
	 * @return   [type]                          [description]
	 */
	private function _createPropertyDocBlock(Field $field,$discription="",$is_primary=false){
		$GeneratorData=[];
		$name=$field->name;
		$type=$field->type;
		$alias=$field->alias;
		if(isset($name))
			$description="[name={$name}";

		if(isset($type))$description.=",type={$type}";
		if(isset($alias))$description.=",alias={$alias}]";
		else $description.="]";
		$GeneratorData['tags'][]=["name"=>"Field","description"=>$description];
		$GeneratorData['longDescription']=$discription;
		if($is_primary)
			$GeneratorData['tags'][]=["name"=>"Primary","description"=>""];
		return DocBlockGenerator::fromArray($GeneratorData);
	}

	/**
	 * 创建新的property
	 * @Author   Lerko
	 * @DateTime 2017-06-26T11:21:44+0800
	 * @param    [type]                   $name [description]
	 * @return   [type]                         [description]
	 */
	private function _createProperty($name,$type,$tablename,$discription="",$is_primary=false){
		$field=new Field();
		$field->name=$name;
		$field->type=$type;
		$field->alias=$this->base64_sp($tablename."_".$name);
		return (new PropertyGenerator($name))->setDocBlock($this->_createPropertyDocBlock($field,$discription,$is_primary));
	}

	/**
	 * 将驼峰法的表明变成大小写形式
	 * @Author   Lerko
	 * @DateTime 2017-06-26T18:04:40+0800
	 * @param    [type]                   $tablename [description]
	 * @return   [type]                              [description]
	 */
	private function splitTableName($tablename,$prefix=false){
		$name_arr=explode('_', $tablename);
		if($prefix) array_shift($name_arr);
		$name="";
		foreach ($name_arr as $key => $value) {
			$name.=ucfirst($value);
		}
		return $name;
	}

	/**
	 * 替换命名空间的/
	 * @Author   Lerko
	 * @DateTime 2017-06-27T09:34:53+0800
	 * @param    [type]                   $string [description]
	 * @return   [type]                           [description]
	 */
	private function replaceSp($string){
		return str_replace("/", '\\', $string);
	}

	private function base64_sp($string,$encode=true){
		return strtolower($string);
		// if($encode) return str_replace("=","_",base64_encode($string));
		// else return str_replace("_","=",base64_decode($string));
	}
}
