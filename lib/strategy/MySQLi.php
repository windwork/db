<?php
/**
 * Windwork
 * 
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
 * 
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */
namespace wf\db\strategy;

/**
 * 使用 PDO扩展对MySQL数据库进行操作
 * 如果是自己写sql语句的时候，请不要忘了防注入，只是在您不输入sql的情况下帮您过滤MySQL注入了
 *
 * @package     wf.db.strategy
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
class MySQLi extends \wf\db\DBAbstract
{    
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
    public function __construct(array $cfg)
    {
        if (!class_exists('\\mysqli')) {
            throw new \wf\db\Exception('error on connect to database：please install mysqli extension OR change to PDOMySQL extension.');
        }
    
        parent::__construct($cfg);
        
        if(!$this->mysqli = new \mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port'], @$cfg['db__socket'])) {
            throw new \wf\db\Exception('error on connect to database：'.$this->mysqli->connect_error);
        }

        $this->mysqli->set_charset("utf8");
        $this->mysqli->query("sql_mode=''");
    }
    
    /**
     * (non-PHPdoc)
     * @see DB::beginTransaction()
     */
    public function beginTransaction()
    {
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
    public function commit()
    {
        --$this->transactions;
    
        if($this->transactions == 0 && false === $this->mysqli->commit()) {
            throw new \wf\db\Exception('transaction commit error: ' . $this->getLastErr());
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see DB::lastInsertId()
     */
    public function lastInsertId()
    {
        return $this->mysqli->insert_id;
    }
    
    /**
     * 执行SQL查询，执行读取查询
     *
     * @param String $sql
     * @param array $args
     * @throws \wf\db\Exception
     * @return \mysqli_result
     */
    public function query($sql, array $args = [])
    {
        if ($args) {
            $sql = \wf\db\QueryBuilder::format($sql, $args);
        }
        
        $sql = \wf\db\QueryBuilder::tablePrefix($sql, $this->cfg['tablePrefix']);
                            
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
     * 执行SQL，针对没有结果集合返回的操作，比如INSERT、UPDATE、DELETE等写入操作，它返回的结果是当前操作影响的列数。
     * 
     * @param string $sql
     * @param array $args
     * @throws \wf\db\Exception
     * @return bool 
     */
    public function exec($sql, array $args = [])
    {
        return $this->query($sql, $args);
    }
    
    /**
     * (non-PHPdoc)
     * @see DB::getAll()
     */
    public function getAll($sql, array $args = [])
    {
        $result = $this->query($sql, $args);
        
        if (!$result) {
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        
        return $rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see DB::getRow()
     */
    public function getRow($sql, array $args = [])
    {
        $result = $this->query($sql, $args);
        
        if (!$result) {
            return [];
        }
        
        $row = $result->fetch_row();
        
        return $row;
    }
    
    /**
     * (non-PHPdoc)
     * @see DB::getColumn()
     */
    public function getColumn($sql, array $args = [])
    {
        $result = $this->query($sql, $args);
        
        if (!$result) {
            return  null;
        }
        
        $row = $result->fetch_row();
        
        return $row[0];
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\DBInterface::getLastErr()
     */
    public function getLastErr()
    {
        return $this->mysqli->error;
    }
        
    /**
     * 设置是否自动提交事务，启用事务的时候有效
     * 
     * @return \wf\db\DBInterface
     */
    public function setAutoCommit($isAutoCommit = false)
    {
        $this->mysqli->autocommit($isAutoCommit);
        
        return $this;
    }
    
    public function rollBack()
    {
        --$this->transactions;
            
        if ($this->transactions <= 0 && false === $this->mysqli->rollback()) {                
            throw new \wf\db\Exception('transaction rollback error: '.$this->getLastErr());
        }
    }
}

