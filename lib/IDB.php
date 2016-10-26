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
 * 数据库操作接口
 * 
 * @package     wf.db
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
interface IDB {
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
	public function getTableSchema($table);
	
	/**
	 * 开始事务，数据库支持事务并启用的时候事务才有效，
	 * 默认没有启用自动提交，需要调用ADB::commit()提交
	 * 
	 * <code>
	 * useage:
	 *   try{
	 *       db()->beginTransaction();
	 *       $q1 = db()->query($sql);
	 *       $q2 = db()->query($sql);
	 *       $q3 = db()->query($sql);
	 *       db()->commit();
	 *   } catch(\wf\db\Exception $e) {
	 *       db()->rollBack();
	 *   }
	 * </code>
	 * @return \wf\db\IDB
	 */
	public function beginTransaction();
	
	/**
	 * 提交事务
	 * 
	 * @return bool
	 */
	public function commit();
		
	/**
	 * 执行SQL
	 * 针对没有结果集合返回的写入操作，
	 * 比如INSERT、UPDATE、DELETE等操作，它返回的结果是当前操作影响的列数。
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表 
	 * @throws \wf\db\Exception
	 * @return int
	 */
	public function exec($sql, array $args = []);
	
	/**
	 * 取得上一步 INSERT 操作产生的 ID
	 *
	 * @return string 
	 */
	public function lastInsertId();
		
	/**
	 * 执行SQL查询语句，一般用于只读查询
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表 
	 * @throws \wf\db\Exception
	 * @return \PDOStatement
	 */
	public function query($sql, array $args = []);
	
	/**
	 * 事务回滚
	 * 
	 * @return bool
	 */
	public function rollBack();
	
	/**
	 * 设置是否自动提交事务，启用事务的时候有效
	 * 
	 * @param bool $isAutoCommit
	 * @return \wf\db\IDB
	 */
	public function setAutoCommit($isAutoCommit = false);
	
	/**
	 * 获取最后错误的信息
	 * 
	 * @return string
	 */
	public function getLastErr();
			
	/**
	 * 获取第一列第一个字段
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表
	 */
	public function getColumn($sql, array $args = []);
	
	/**
	 * 获取所有记录
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表
	 */
	public function getAll($sql, array $args = []);
	
	/**
	 * 获取第一列
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表
	 */
	public function getRow($sql, array $args = []);

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
	public function insertRows(array $rows, $table, $fields = [], $isReplace = false);
	
}

