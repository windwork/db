<?php
require_once '../lib/QueryHelper.php';
require_once '../lib/Exception.php';
require_once '../lib/Query.php';

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
	 * Tests Query->getOptions()
	 */
	public function testGetOptions() {
		$cdts = (new \wf\db\Query())
		->table('my_table tbl')
		->fields('a,b,c')
		->group('aa, bb')
		->having(['aa', 55, '<'])
		->join('LEFT JOIN tb.aaa a ON a.xx = tbl.id')
		->where([['id', 11], ['name', 'mimo%', 'like']])
		->order('a.xx');
		
		$sql = \wf\db\QueryHelper::optionsToSql($cdts->getOptions());
				
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
		
		// 
		$cdts['limit'] = '10, 20';
		$options = \wf\db\QueryHelper::buildQueryOptions($cdts);
		$sql .= " {$options['limit']}";
		$exp .= " LIMIT 10,20";
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
	 * Tests Query->where()
	 */
	public function testAndWhere() {
		$this->query->andWhere(['a', '1112', '='])
		->table('my_tb t')
		->join('LEFT JOIN tb_join j ON j.id = t.id')
		->join('LEFT JOIN tb_join_2 j2 ON j2.id = t.id')
		->andWhere([['b', '1112', '='], ['c', 23232, '>'], ['x', 33, '>']])
		->orWhere(['OR', ['d', 1112, '='], ['e', 23232, '<']])
		->andWhere(['f', '1112'])
		->group('j2.uid, j1.xid')
		->having('SUM(j.area)>1111 AND MAX(j.xx) < 11')
		->order('a ASC, B DESC')
		->limit(100, 20);
						
		$sql = \wf\db\QueryHelper::optionsToSql($this->query->getOptions());		
		$exp = "SELECT * FROM my_tb t LEFT JOIN tb_join j ON j.id = t.id LEFT JOIN tb_join_2 j2 ON j2.id = t.id 
				WHERE (a='1112' AND (b='1112' AND c>'23232' AND x > 33) OR (d='1112' OR e<'23232') AND f='1112') 
				GROUP BY j2.uid, j1.xid
				HAVING SUM(j.area)>1111 AND MAX(j.xx) < 11
				ORDER BY a ASC, B DESC
				LIMIT 100,20";
		
		$sync = function($str) {
			$str = str_replace(['\'', "\r\n", "\t", "\n", '`', '(', ')', ' '], '', $str);
			$str = strtolower($str);
			return $str;
		};
		
		$sql = $sync($sql);
		$exp = $sync($exp);
		
		$this->assertEquals($exp, $sql);
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

