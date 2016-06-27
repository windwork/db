<?php
if (PHP_SAPI != 'cli') {
	die('access denied');
}

require_once __DIR__ . '/../IDB.php';
require_once __DIR__ . '/../ADB.php';
require_once __DIR__ . '/../Exception.php';
require_once __DIR__ . '/../SQLBuilder.php';
require_once __DIR__ . '/../DBFactory.php';
require_once __DIR__ . '/../adapter/MySQL.php';

use \wf\db\DBFactory;
use \wf\db\adapter\MySQL;
use wf\db\QueryHelper;

/**
 * PDOMySQL test case.
 */
class MySQLTest extends PHPUnit_Framework_TestCase {
	/**
	 * 
	 * @var \wf\db\IDB
	 */
	private $mySQL;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		$cfg = array(
			// 
			'default' => array(
				'db_adapter'       => 'MySQL',
				'db_host'          => '127.0.0.1',  // 本机测试
				'db_port'          => '3306',       // 数据库服务器端口
				'db_name'          => 'test',       // 数据库名
				'db_user'          => 'root',       // 数据库连接用户名
				'db_pass'          => '123456',     // 数据库连接密码
				'db_table_prefix'  => 'wk_',        // 表前缀
				'db_debug'         => 1,
			),
			// 可主从分离
			'slave' => array(
				'db_adapter'       => 'MySQL',
				'db_host'          => '127.0.0.1',  // 本机测试
				'db_port'          => '3306',       // 数据库服务器端口
				'db_name'          => 'test',       // 数据库名
				'db_user'          => 'root',       // 数据库连接用户名
				'db_pass'          => '123456',     // 数据库连接密码
				'db_table_prefix'  => 'wk_',        // 表前缀
				'db_debug'         => 1,
			),
		);

		DBFactory::setCfg($cfg);
		$this->mySQL = \wf\db\DBFactory::create();
		
		// 创建测试表
		$sql = "CREATE TABLE IF NOT EXISTS `wk_test_table` (
                  `id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
				  `str`  varchar(255) NOT NULL DEFAULT '' ,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
		$this->mySQL->query($sql);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		parent::tearDown ();
		$sql = "DROP TABLE IF EXISTS wk_test_table";
		$this->mySQL->query($sql);
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		
	}
	
	private function insertRow($val = '') {
		$val = $val ? $val : date('Y-m-d H:i:s');
		$sql = "INSERT INTO wk_test_table (str) VALUE ('{$val}')";
		$this->mySQL->query($sql);
	}
	
	/**
	 * Tests mySQL->lastInsertId()
	 */
	public function testLastInsertId() {
		$this->insertRow();
		$lastInsertId = $this->mySQL->lastInsertId();
		
		$this->assertNotEmpty($lastInsertId);
	}
	
	/**
	 * Tests mySQL->query()
	 */
	public function testQuery() {
		// TODO Auto-generated mySQLTest->testQuery()
		$this->markTestIncomplete ( "query test not implemented" );
		
		$this->mySQL->query(/* parameters */);
	}
	
	/**
	 * Tests mySQL->exec()
	 */
	public function testExec() {
		// TODO Auto-generated mySQLTest->testExec()
		$this->markTestIncomplete ( "exec test not implemented" );
		
		$this->mySQL->exec(/* parameters */);
	}
	
	/**
	 * Tests mySQL->getAll()
	 */
	public function testGetAll() {
		$this->insertRow();
		$this->insertRow();
		$rows = $this->mySQL->getAll("SELECT * FROM wk_test_table LIMIT 2");
		
		$this->assertEquals(2, count($rows));
	}
	
	/**
	 * Tests mySQL->getRow()
	 */
	public function testGetRow() {
		$uniqe = uniqid();
		$this->insertRow($uniqe);
		
		$row = $this->mySQL->getRow("SELECT * FROM wk_test_table WHERE str = '{$uniqe}'");
		$this->assertNotEmpty($row);
	}
	
	/**
	 * Tests mySQL->getOne()
	 */
	public function testGetOne() {
		$uniqe = uniqid();
		$this->insertRow($uniqe);
		
		$str = $this->mySQL->getRow("SELECT str FROM wk_test_table WHERE str = '{$uniqe}'");
		$this->assertNotEmpty($str);
	}
	
	/**
	 * Tests mySQL->getLastErr()
	 */
	public function testGetLastErr() {
		$sql = "SELECT x from tb_" . uniqid();
		try {
		    $this->mySQL->query($sql);
		} catch (\wf\db\Exception $e) {
			$lastErr = $this->mySQL->getLastErr();
		}
		
		$this->assertEquals($lastErr, $e->getMessage());
	}
	
	/**
	 * Tests mySQL->setAutoCommit()
	 */
	public function testSetAutoCommit() {
		// TODO Auto-generated mySQLTest->testSetAutoCommit()
		$this->markTestIncomplete ( "setAutoCommit test not implemented" );
		
		$this->mySQL->setAutoCommit(/* parameters */);
	}
	
	/**
	 * Tests mySQL->rollBack()
	 */
	public function testRollBack() {
		$uniqe = uniqid();
		$this->insertRow($uniqe);
		
		try {
			$this->mySQL->beginTransaction();
			$this->insertRow();
			$this->insertRow();
			throw new \wf\db\Exception('~');
		} catch (\wf\db\Exception $e) {
			$this->mySQL->rollBack();
		}
		
		$lastStr = $this->mySQL->getOne("SELECT str FROM wk_test_table ORDER BY id DESC");
		$this->assertEquals($uniqe, $lastStr);
	}
}

