<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
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
class PDOMySQL extends \wf\db\ADB implements \wf\db\IDB
{
    /**
     * 数据库操作对象
     * 
     * @var \PDO
     */
    private $dbh = null;
    
    /**
     * @param array $cfg
     * @throws \wf\db\Exception
     */
    public function __construct(array $cfg)
    {
        if (!class_exists('\\PDO')) {
            throw new \wf\db\Exception('error on connect to database：你的PHP引擎未启用PDO_MYSQL扩展。');
        }
    
        parent::__construct($cfg);
        
        try {        
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8";
            $this->dbh = new \PDO($dsn, $cfg['user'], $cfg['pass']);
        } catch (\PDOException $e) {
            throw new \wf\db\Exception('error on connect to database：'.$e->getMessage());
        }
        
        $this->dbh->exec("sql_mode=''");
        $this->dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::beginTransaction()
     */
    public function beginTransaction()
    {
        if (!$this->transactions) {
            $this->dbh->beginTransaction();
        }

        ++$this->transactions;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::commit()
     */
    public function commit()
    {
        --$this->transactions;
    
        if($this->transactions == 0 && false === $this->dbh->commit()) {
            throw new \wf\db\Exception('transaction commit error: ' . $this->getLastErr());
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::lastInsertId()
     */
    public function lastInsertId()
    {
        //return $this->dbh->lastInsertId(); // 部分情况不能获取到
        return $this->getColumn("SELECT LAST_INSERT_ID()");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::query()
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
        
        $query = $this->dbh->query($sql);        
        if(false === $query) {
            $this->log[] = $this->getLastErr();
            throw new \wf\db\Exception($this->getLastErr());
        }
        
        return $query;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::exec()
     */
    public function exec($sql, array $args = [])
    {
        if ($args) {
            $sql = \wf\db\QueryBuilder::format($sql, $args);
        }
        
        $sql = \wf\db\QueryBuilder::tablePrefix($sql, $this->cfg['tablePrefix']);    
                            
        // 记录数据库查询次数
        $this->execTimes ++;
        $this->log[] = $sql;
        
        $result = $this->dbh->exec($sql);

        if(false === $result) {
            $this->log[] = $this->getLastErr();
            throw new \wf\db\Exception($this->getLastErr());
        }
                                
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::getAll()
     */
    public function getAll($sql, array $args = [])
    {
        $query = $this->query($sql, $args);
        if (!$query) {
            return [];
        }
        
        $rs = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $rs;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::getRow()
     */
    public function getRow($sql, array $args = [])
    {
        $query = $this->query($sql, $args);
        if (!$query) {
            return [];
        }
        
        $rs = $query->fetch(\PDO::FETCH_ASSOC);
        return $rs;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::getColumn()
     */
    public function getColumn($sql, array $args = [])
    {
        $query = $this->query($sql, $args);
        if (!$query) {
            return  null;
        }
        
        $value = $query->fetchColumn();
        
        return $value;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::getLastErr()
     */
    public function getLastErr()
    {
        return implode(' ', $this->dbh->errorInfo());
    }
        
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::setAutoCommit()
     */
    public function setAutoCommit($isAutoCommit = false)
    {
        $this->dbh->setAttribute(\PDO::ATTR_AUTOCOMMIT, $isAutoCommit);
        
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\db\IDB::rollBack()
     */
    public function rollBack()
    {
        --$this->transactions;
        
        if ($this->transactions <= 0 && false === $this->dbh->rollback()) {
            throw new \wf\db\Exception('transaction rollback error: ' . $this->getLastErr());
        }
    }
}

