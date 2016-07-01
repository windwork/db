<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
 * 
 * @copyright   Copyright (c) 2008-2015 Windwork Team. (http://www.windwork.org)
 * @license     http://opensource.org/licenses/MIT	MIT License
 */
namespace wf\db\adapter;

use wf\db\QueryHelper;

/**
 * 使用 PDO扩展对MySQL数据库进行操作
 * 如果是自己写sql语句的时候，请不要忘了防注入，只是在您不输入sql的情况下帮您过滤MySQL注入了
 *
 * @package     wf.db.adapter
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
class MySQLi extends \wf\db\ADB implements \wf\db\IDB {
	/**
	 * 数据库操作对象
	 * 
	 * @var \mysqli
	 */
	private $mysqli = null;
	
	/**
	 * 数据库连接
	 *
	 * @param array $cfg
	 * @throws \wf\db\Exception
	 */
	public function __construct(array $cfg) {
		if (!class_exists('\\mysqli')) {
			throw new \wf\db\Exception('连接数据库时出错：你的PHP引擎未启用mysqli扩展。');
		}
	
		parent::__construct($cfg);
		
		if(!$this->mysqli = new \mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'], $cfg['db_port'], @$cfg['db__socket'])) {
			throw new \wf\db\Exception('连接数据库时出错：'.$this->mysqli->error);
		}

		$this->mysqli->set_charset("utf8");
		$this->mysqli->query("sql_mode=''");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::beginTransaction()
	 */
	public function beginTransaction() {
		if (!$this->transactions) {
			$this->mysqli->begin_transaction();
		}

		++$this->transactions;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::commit()
	 */
	public function commit() {
		--$this->transactions;
	
		if($this->transactions == 0 && false === $this->mysqli->commit()) {
		    throw new \wf\db\Exception($this->getLastErr());
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::lastInsertId()
	 */
	public function lastInsertId() {
		return $this->mysqli->insert_id;
	}
	
	/**
	 * 简单查询
	 *
	 * @param String $sql
	 * @param array $args
	 * @throws \wf\db\Exception
	 * @return \mysqli_result
	 */
	public function query($sql, array $args = array()) {
		if ($args) {
			$sql = QueryHelper::format($sql, $args);
		}
		
		$sql = QueryHelper::tablePrefix($sql, $this->cfg['db_table_prefix']);
							
		// 记录数据库查询次数
		$this->execTimes ++;
		$this->log[] = $sql;
		
		$query = $this->mysqli->query($sql);
		
        if(false === $query) {
        	$this->log[] = $this->getLastErr();
        	throw new \wf\db\Exception($this->getLastErr());
        }
        
    	return $query;
	}
	
	/**
	 * 执行SQL、针对没有结果集合返回的操作，比如INSERT、UPDATE、DELETE等操作，它返回的结果是当前操作影响的列数。
	 * 
	 * @param string $sql
	 * @param array $args
	 * @throws \wf\db\Exception
	 * @return bool 
	 */
	public function exec($sql, array $args = array()) {
		return (bool)$this->query($sql, $args);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::getAll()
	 */
	public function getAll($sql, array $args = array(), $allowCache = false) {
		$result = $this->query($sql, $args);
		
		if (!$result) {
			return  array();
		}

		$rows = $result->fetch_all(MYSQLI_ASSOC);
		
		return $rows;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::getRow()
	 */
	public function getRow($sql, array $args = array(), $allowCache = false) {
		$result = $this->query($sql, $args);
		
		if (!$result) {
			return  array();
		}
		
		$row = $result->fetch_row();
		
		return $row;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DB::getOne()
	 */
	public function getOne($sql, array $args = array(), $allowCache = false) {
		$result = $this->query($sql, $args);
		
		if (!$result) {
			return  null;
		}
		
		$row = $result->fetch_row();
		
		return $row[0];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wf\Object::getLastErr()
	 */
	public function getLastErr() {
		return implode(' ', $this->mysqli->error_list);
	}
		
	/**
	 * 设置是否自动提交事务，启用事务的时候有效
	 * 
	 * @return \wf\db\IDB
	 */
	public function setAutoCommit($isAutoCommit = false) {
		$this->mysqli->autocommit($isAutoCommit);
		
		return $this;
	}
	
	public function rollBack() {
		--$this->transactions;
			
		if ($this->transactions <= 0) {
			$this->mysqli->rollback();
		} else {			
			throw new \wf\db\Exception($this->getLastErr());
		}
	}
}

