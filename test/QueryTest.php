<?php
require_once '../QueryHelper.php';
require_once '../Exception.php';
require_once '../Query.php';

use \wf\db\QueryHelper;
use \wf\db\Query;
use \wf\db\Exception;

/**
 * Query test case.
 */
class QueryTest extends PHPUnit_Framework_TestCase {
	
	/**
	 *
	 * @var Query
	 */
	private $query;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		// TODO Auto-generated QueryTest::setUp()
		
		$this->query = new Query();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		// TODO Auto-generated QueryTest::tearDown()
		$this->query = null;
		
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	/**
	 * Tests Query->getCdt()
	 */
	public function testGetCdt() {
		$cdts = (new \wf\db\Query())
		->table('my_table tbl')
		->fields('a,b,c')
		->group('aa, bb')
		->having(['aa', 55, '<'])
		->join('LEFT JOIN tb.aaa a ON a.xx = tbl.id')
		->where([['id', 11], ['name', 'mimo%', 'like']])
		->order('a.xx')
		->getCdt();
		
		$cdts = \wf\db\QueryHelper::buildQueryOptions($cdts);

		$sql = "SELECT {$cdts['fields']}
				FROM {$cdts['table']} {$cdts['join']}
				{$cdts['where']}
				{$cdts['group']}
				{$cdts['having']}
				{$cdts['order']}";
				
		$sqlStrip = function($str) {
			$str = str_replace('`', '', strtolower($str));
			$str = preg_replace("/[\r\n\t]/s", ' ', $str);
			$str = preg_replace("/\s+/", ' ', trim($str));
			
			return $str;
		};
		
		$exp = "SELECT `a`,`b`,`c` FROM `my_table` `tbl` LEFT JOIN tb.aaa a ON a.xx = tbl.id WHERE (`id`='11' AND `name` LIKE('mimo%')) GROUP BY `aa`,`bb` HAVING `aa`<'55' ORDER BY a.xx ";
		$exp = $sqlStrip($exp);
		$sql = $sqlStrip($sql);

		$this->assertEquals($exp, $sql);
	}
	
	/**
	 * Tests Query->fields()
	 */
	public function testFields() {
		// TODO Auto-generated QueryTest->testFields()
		$this->markTestIncomplete ( "fields test not implemented" );
		
		$this->query->fields(/* parameters */);
	}
	
	/**
	 * Tests Query->table()
	 */
	public function testTable() {
		// TODO Auto-generated QueryTest->testTable()
		$this->markTestIncomplete ( "table test not implemented" );
		
		$this->query->table(/* parameters */);
	}
	
	/**
	 * Tests Query->join()
	 */
	public function testJoin() {
		// TODO Auto-generated QueryTest->testJoin()
		$this->markTestIncomplete ( "join test not implemented" );
		
		$this->query->join(/* parameters */);
	}
	
	/**
	 * Tests Query->where()
	 */
	public function testWhere() {
		// TODO Auto-generated QueryTest->testWhere()
		$this->markTestIncomplete ( "where test not implemented" );
		
		$this->query->where(/* parameters */);
	}
	
	/**
	 * Tests Query->group()
	 */
	public function testGroup() {
		// TODO Auto-generated QueryTest->testGroup()
		$this->markTestIncomplete ( "group test not implemented" );
		
		$this->query->group(/* parameters */);
	}
	
	/**
	 * Tests Query->having()
	 */
	public function testHaving() {
		// TODO Auto-generated QueryTest->testHaving()
		$this->markTestIncomplete ( "having test not implemented" );
		
		$this->query->having(/* parameters */);
	}
}

