<?php
require_once 'QueryHelper.php';

use \wf\db\QueryHelper;

/**
 * QueryHelper test case.
 */
class QueryHelperTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	/**
	 * Tests QueryHelper::tablePrefix()
	 */
	public function testTablePrefix() {
		$sql = "SELECT * FROM wk_xxx";
		$sql = QueryHelper::tablePrefix($sql, 'my_test_');
		
		$this->assertEquals("SELECT * FROM my_test_xxx", $sql);

		$sql = "SELECT * FROM wk_xxx WHERE true";
		$sql = QueryHelper::tablePrefix($sql, 'my_test_');
		$this->assertEquals("SELECT * FROM my_test_xxx WHERE true", $sql);

		$sql = "SELECT * FROM wk_xxx, b WHERE true";
		$sql = QueryHelper::tablePrefix($sql, 'my_test_');
		$this->assertEquals("SELECT * FROM my_test_xxx, b WHERE true", $sql);
	}
	
	/**
	 * Tests QueryHelper->getTotalsFromQuery()
	 */
	public function testGetTotalsSQLFromQuery() {
		$sql = "SELECT xx FROM tb";
		$countSql = \wf\db\QueryHelper::getTotalsSQLFromQuery($sql);
		
		$this->assertEquals('SELECT COUNT(*) as count FROM tb', $countSql);
	}
	
	/**
	 * Tests QueryHelper::quote()
	 */
	public function testQuote() {
		// TODO Auto-generated QueryHelperTest::testQuote()
		$this->markTestIncomplete ( "quote test not implemented" );
		
		QueryHelper::quote(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::quoteField()
	 */
	public function testQuoteField() {
		// TODO Auto-generated QueryHelperTest::testQuoteField()
		$this->markTestIncomplete ( "quoteField test not implemented" );
		
		QueryHelper::quoteField(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::quoteFields()
	 */
	public function testQuoteFields() {
		// TODO Auto-generated QueryHelperTest::testQuoteFields()
		$this->markTestIncomplete ( "quoteFields test not implemented" );
		
		QueryHelper::quoteFields(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::limit()
	 */
	public function testLimit() {
		// TODO Auto-generated QueryHelperTest::testLimit()
		$this->markTestIncomplete ( "limit test not implemented" );
		
		QueryHelper::limit(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::order()
	 */
	public function testOrder() {
		// TODO Auto-generated QueryHelperTest::testOrder()
		$this->markTestIncomplete ( "order test not implemented" );
		
		QueryHelper::order(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::where()
	 */
	public function testWhere() {
		// TODO Auto-generated QueryHelperTest::testWhere()
		$this->markTestIncomplete ( "where test not implemented" );
		
		QueryHelper::where(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::whereArr()
	 */
	public function testWhereArr() {
		// TODO Auto-generated QueryHelperTest::testWhereArr()
		$this->markTestIncomplete ( "whereArr test not implemented" );
		
		QueryHelper::whereArr(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::format()
	 */
	public function testFormat() {

		$arg = array('xxx', 'tb', 'f1', 'xxx xxx');
		$fmtSql = QueryHelper::format("SELECT %a FROM %t WHERE %a = %s", $arg);
		$expSql = "SELECT `{$arg[0]}` FROM `{$arg[1]}` WHERE `{$arg[2]}` = '{$arg[3]}'";
		
		$this->assertEquals($expSql, $fmtSql);
	}
	
	/**
	 * Tests QueryHelper::buildQueryOptions()
	 */
	public function testBuildQueryOptions() {
		// TODO Auto-generated QueryHelperTest::testBuildQueryOptions()
		$this->markTestIncomplete ( "buildQueryOptions test not implemented" );
		
		QueryHelper::buildQueryOptions(/* parameters */);
	}
	
	/**
	 * Tests QueryHelper::buildSqlSet()
	 */
	public function testBuildSqlSet() {
		// TODO Auto-generated QueryHelperTest::testBuildSqlSet()
		$this->markTestIncomplete ( "buildSqlSet test not implemented" );
		
		QueryHelper::buildSqlSet(/* parameters */);
	}
}

