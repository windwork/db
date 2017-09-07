<?php
if (PHP_SAPI != 'cli') {
    die('access denied');
}

require_once __DIR__ . '/../lib/DBInterface.php';
require_once __DIR__ . '/../lib/DBAbstract.php';
require_once __DIR__ . '/../lib/Exception.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../lib/adapter/MySQLi.php';

use wf\db\QueryBuilder;

/**
 * PDOMySQL test case.
 */
class MySQLTest extends PHPUnit_Framework_TestCase 
{
    /**
     * 
     * @var \wf\db\DBInterface
     */
    private $mySQLi;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() 
    {
        parent::setUp ();
        
        $cfg = array(
            // 
            'default' => array(
                'class'         => '\\wf\\db\\adapter\\MySQLi',    // MySQLi|PDOMySQL
                'host'          => '127.0.0.1',  // 本机测试
                'port'          => '3306',       // 数据库服务器端口
                'name'          => 'test',       // 数据库名
                'user'          => 'root',       // 数据库连接用户名
                'pass'          => '123456',     // 数据库连接密码
                'tablePrefix'   => 'wk_',        // 表前缀
                'debug'         => 1,
            ),
            // 可主从分离
            'slave' => array(
                'class'         => '\\wf\\db\\adapter\\MySQLi',    // MySQLi|PDOMySQL
                'host'          => '127.0.0.1',  // 本机测试
                'port'          => '3306',       // 数据库服务器端口
                'name'          => 'test',       // 数据库名
                'user'          => 'root',       // 数据库连接用户名
                'pass'          => '123456',     // 数据库连接密码
                'tablePrefix'   => 'wk_',        // 表前缀
                'debug'         => 1,
            ),
        );

        $this->mySQLi = new $cfg['default']['class']($cfg['default']);
        
        // 创建测试表
        $sql = "CREATE TABLE IF NOT EXISTS `wk_test_table` (
                  `id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
                  `str`  varchar(255) NOT NULL DEFAULT '' ,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->mySQLi->query($sql);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() 
    {
        parent::tearDown ();
        $sql = "DROP TABLE IF EXISTS wk_test_table";
        $this->mySQLi->query($sql);
    }
    
    /**
     * Constructs the test case.
     */
    public function __construct() {
        
    }
    
    private function insertRow($val = '') {
        $val = $val ? $val : date('Y-m-d H:i:s');
        $sql = "INSERT INTO wk_test_table (str) VALUE ('{$val}')";
        $this->mySQLi->query($sql);
    }
    
    /**
     * Tests mySQLi->lastInsertId()
     */
    public function testLastInsertId() 
    {
        $this->insertRow();
        $lastInsertId = $this->mySQLi->lastInsertId();
        
        $this->assertNotEmpty($lastInsertId);
    }
    
    /**
     * Tests mySQLi->query()
     */
    public function testQuery() 
    {
        // TODO Auto-generated mySQLiTest->testQuery()
        $this->markTestIncomplete ( "query test not implemented" );
        
        $this->mySQLi->query(/* parameters */);
    }
    
    /**
     * Tests mySQLi->exec()
     */
    public function testExec() 
    {
        // TODO Auto-generated mySQLiTest->testExec()
        $this->markTestIncomplete ( "exec test not implemented" );
        
        $this->mySQLi->exec(/* parameters */);
    }
    
    /**
     * Tests mySQLi->getAll()
     */
    public function testGetAll() 
    {
        $this->insertRow();
        $this->insertRow();
        $rows = $this->mySQLi->getAll("SELECT * FROM wk_test_table LIMIT 2");
        
        $this->assertEquals(2, count($rows));
    }
    
    /**
     * Tests mySQLi->getRow()
     */
    public function testGetRow() 
    {
        $uniqe = uniqid();
        $this->insertRow($uniqe);
        
        $row = $this->mySQLi->getRow("SELECT * FROM wk_test_table WHERE str = '{$uniqe}'");
        $this->assertNotEmpty($row);
    }
    
    /**
     * Tests mySQLi->getColumn()
     */
    public function testGetColumn() 
    {
        $uniqe = uniqid();
        $this->insertRow($uniqe);
        
        $str = $this->mySQLi->getRow("SELECT str FROM wk_test_table WHERE str = '{$uniqe}'");
        $this->assertNotEmpty($str);
    }
    
    /**
     * Tests mySQLi->getLastErr()
     */
    public function testGetLastErr() 
    {
        $sql = "SELECT x from tb_" . uniqid();
        try {
            $this->mySQLi->query($sql);
        } catch (\wf\db\Exception $e) {
            $lastErr = $this->mySQLi->getLastErr();
        }
        
        $this->assertEquals($lastErr, $e->getMessage());
    }
    
    /**
     * Tests mySQLi->setAutoCommit()
     */
    public function testSetAutoCommit() 
    {
        // TODO Auto-generated mySQLiTest->testSetAutoCommit()
        $this->markTestIncomplete ( "setAutoCommit test not implemented" );
        
        $this->mySQLi->setAutoCommit(/* parameters */);
    }
    
    /**
     * Tests mySQLi->rollback()
     */
    public function testRollBack() 
    {
        $uniqe = uniqid();
        $this->insertRow($uniqe);
        
        try {
            $this->mySQLi->beginTransaction();
            $this->insertRow();
            $this->insertRow();
            throw new \wf\db\Exception('~');
        } catch (\wf\db\Exception $e) {
            $this->mySQLi->rollback();
        }
        
        $lastStr = $this->mySQLi->getColumn("SELECT str FROM wk_test_table ORDER BY id DESC");
        $this->assertEquals($uniqe, $lastStr);
    }
}

