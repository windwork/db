<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
 * 
 * @copyright   Copyright (c) 2008-2015 Windwork Team. (http://www.windwork.org)
 * @license     http://opensource.org/licenses/MIT	MIT License
 */
namespace wf\db;

/**
 * 数据库操作抽象类
 *  
 * @package     wf.db
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
abstract class ADB {
	/**
	 * 是否启用调试模式
	 * @var bool
	 */
	public $debug = false;
	
	/**
	 * 数据库连接配置
	 * @var array
	 */
	protected $cfg = array();

	/**
	 * 开启事务的次数，记录次数解决嵌套事务的问题
	 * @var int
	 */
	protected $transactions = 0;
	
	/**
	 * 记录当前请求执行的SQL查询语句
	 * @var array
	 */
	protected $log = array();
		
	/**
	 * 数据库当前页面连接次数,每次实行SQL语句的时候 ++
	 * 
	 * @var int
	 */
	public $execTimes = 0;
	
	/**
	 * 获取模型数据表信息
	 * 
	 * <pre>
	 * arry(
	 *     'pk' => '主键',
	 *     'ai' => true/false, //主键是否是自动增长
	 *     'fields' => array(
	 *         '字段1名' => array(
	 *				'field'   => '字段1名',
	 *				'type'    => '字段类型',
	 *				'key'     => '索引类型', //PKI/MUL/UNI
	 *				'default' => '默认值',
	 *				'ai'      => '是否是自动增长的',
	 *         ),
	 *         '字段2' => array(
	 *				'field'   => $row['Field'],
	 *				'type'    => $row['Type'],
	 *				'key'     => $row['Key'],
	 *				'default' => $row['Default'],
	 *				'ai'      => $row['Extra'] == 'auto_increment',
	 *         ),
	 *         ...
	 *     )
	 * )
	 * </pre>
	 * @param string $table  表名
	 * @return array
	 */
	public function getTableInfo($table) {
		static $tableInfoList = array();
		
		$cacheObj = null;
		if (function_exists('cache')) {
			$cacheObj = \cache();
		}
		
		$cacheObj && empty($tableInfoList) && $tableInfoList = $cacheObj->read('db/tableInfoList');

		if((!$tableInfoList || empty($tableInfoList[$table]))) {
			//"SHOW FULL COLUMNS FROM {$table}"
			$rows = $this->getAll("SHOW COLUMNS FROM {$table}");
			$tableInfo = array(
				'pk'      => '', 
				'ai'      => false, 
				'fields'  => array()
			);
			foreach ($rows as $row) {
				$tableInfo['fields'][strtolower($row['Field'])] = $row;
				
				if ($row['Key'] == 'PRI') {
					if($tableInfo['pk']) {
						$tableInfo['pk'] = (array)$tableInfo['pk'];
						$tableInfo['pk'][] = strtolower($row['Field']);
					} else {
						$tableInfo['ai'] = $row['Extra'] == 'auto_increment';
						$tableInfo['pk'] = strtolower($row['Field']);
					}
				}
			}
			
			$tableInfoList[$table] = $tableInfo;
			$cacheObj && $cacheObj->write('db/tableInfoList', $tableInfoList);
		}
		
		return $tableInfoList[$table];
	}

	/**
	 * 插入多行数据
	 * 过滤掉没有的字段
	 *
	 * @param array $rows
	 * @param string $table  插入表
	 * @param array $fields  允许插入的字段名
	 * @param string $isReplace = false 是否使用 REPLACE INTO插入数据，false为使用 INSERT INTO
	 * @return PDOStatement
	 */
	public function insertRows(array $rows, $table, $fields = array(), $isReplace = false) {
		$type = $isReplace ? 'REPLACE' : 'INSERT';
		
		// 数据中允许插入的字段
		$allowFields = $fields ? $fields : array_keys(current($rows));
		$allowFields = QueryHelper::quoteFields(implode(',', $allowFields));
		
		// 
		$valueArr = array();
		foreach ($rows as $row) {
			$rowStr = '';
			foreach ($row as $key => $val) {
			    // 去掉不允许写入的属性
				if ($fields && !in_array(strtolower($key), $fields)) {
					unset($row[$key]);
				}
			}
			
			$rowStr = implode(',', array_map('\wf\db\QueryHelper::quote', $row));
			$valueArr[] = "({$rowStr})";
		}
		$values = $rowStr = implode(',', $valueArr);
		
		return $this->exec("%x INTO %t (%x) VALUES %x", array($type, $table, $allowFields, $values));
	}
	
	/**
	 * 构筑函数设置配置信息
	 * @param array $cfg
	 */
	public function __construct(array $cfg) {
		$this->cfg = $cfg;
		$this->debug = $cfg['db_debug'];
	}
}


