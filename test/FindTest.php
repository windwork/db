<?php
require_once '../lib/QueryBuilder.php';
require_once '../lib/Exception.php';
require_once '../lib/Find.php';

use \wf\db\QueryBuilder;
use \wf\db\Find;
use \wf\db\Exception;

/**
 * Find test case.
 */
class FindTest extends PHPUnit_Framework_TestCase 
{
    
    /**
     *
     * @var Find
     */
    private $find;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() 
    {
        parent::setUp ();
        
        // TODO Auto-generated FindTest::setUp()
        
        $this->find = new Find();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() 
    {
        // TODO Auto-generated FindTest::tearDown()
        $this->find = null;
        
        parent::tearDown ();
    }
    
    /**
     * Constructs the test case.
     */
    public function __construct() 
    {
        // TODO Auto-generated constructor
    }
    
    /**
     * Tests Find->getOptions()
     */
    public function testGetOptions() 
    {
        $finder = new \wf\db\Find();
        $sql = $finder
        ->from('my_table tbl')
        ->field('a,b,c')
        ->group('aa, bb')
        ->having('aa', 55, '<')
        ->join('tb.aaa a', 'a.xx', 'tbl.id')
        ->join('tb.ccc AS c', 'c.xx', 'tbl.id', 'iNnEr')
        ->whereMulti([['a.id', 11], ['b.name', 'mimo%', 'like']])
        ->order('c.xx')
        ->asSql();
                        
        $exp = "SELECT `a`,`b`,`c` FROM `my_table` `tbl` LEFT JOIN `tb`.`aaa` `a` ON `a`.`xx` = `tbl`.`id` INNER JOIN `tb`.`ccc` AS `c` ON `c`.`xx` = `tbl`.`id` WHERE ( (`a`.`id`='11' AND `b`.`name` LIKE('mimo%')) ) GROUP BY `aa`,`bb` HAVING (`aa`<'55') ORDER BY `c`.`xx`";
        $exp = $this->stripSql($exp);
        $sql = $this->stripSql($sql);

        $this->assertEquals($exp, $sql);
        
        $finder->limit(10, 20);
        
        $sql = $finder->asSql();
        $exp .= " LIMIT 10,20";
        
        $exp = $this->stripSql($exp);
        $sql = $this->stripSql($sql);
        
        $this->assertEquals($exp, $sql);
    }
    
    /**
     * Tests Find->field()
     */
    public function testField() 
    {
        $fileds = 't1.a1,t1.pw,count(*)';
        $rFileds = $this->find->field($fileds)->getOptions()['field'];
        $this->assertEquals($fileds, $rFileds);
    }
    
    /**
     * Tests Find->fieldRaw()
     */
    public function testFieldsRaw() 
    {
        $filedsRaw = 't1.a1,t1.pw,count(*)';
        $rFiledsRaw = $this->find->fieldRaw($filedsRaw)->getOptions()['fieldRaw'];
        $this->assertEquals($filedsRaw, $rFiledsRaw);
    }
    
    /**
     * Tests Find->from()
     */
    public function testFrom() 
    {
        $table = 'tba A, tbB B';
        $rTable = $this->find->from($table)->getOptions()['table'];
        $this->assertEquals($table, $rTable);
    }
    
    /**
     * Tests Find->join()
     */
    public function testJoin() 
    {        
        $ret = $this->find->join('tbl a', 'a.f1', 'b.f2')->getOptions()['join'];
        $this->assertTrue(end($ret) == ['tbl a', 'a.f1', 'b.f2', 'LEFT']);

        $ret = $this->find->join('tbl a', 'a.f2', 'b.f5', 'right')->getOptions()['join'];
        $this->assertTrue(end($ret) == ['tbl a', 'a.f2', 'b.f5', 'RIGHT']);

        $ret = $this->find->join('tbl a', 'a.f2', 'b.f5', 'cross')->getOptions()['join'];
        $this->assertTrue(end($ret) == ['tbl a', 'a.f2', 'b.f5', 'CROSS']);
        
        $ret = $this->find->join('tbl a', 'a.f2', 'b.f5', 'inNer')->getOptions()['join'];
        $this->assertTrue(end($ret) == ['tbl a', 'a.f2', 'b.f5', 'INNER']);
    }
    
    /**
     * Tests Find->where()
     */
    public function testWhere() 
    {
        $this->find->where('a', '1112', '=')
        ->from('my_tb t')
        ->join('tb_join j', 'j.id', 't.id')
        ->join('tb_join_2 j2', 'j2.id', 't.id')
        ->whereMulti([['b', '1112', '='], ['c', 23232, '>'], ['x', 33, '>']])
        ->whereMulti(['OR', ['d', 1112, '='], ['e', 23232, '<']])
        ->andWhere('f', '1112')
        ->orWhere('aa', '99', '>')
        ->group('j2.uid, j1.xid')
        ->having('SUM(j.area)', 1111, '>')
        ->having('MAX(j.xx)', 11, '<')
        ->order('a ASC, B DESC')
        ->limit(100, 20);
                        
        $sql = $this->find->asSql();        
        $exp = "SELECT * FROM `my_tb` `t` LEFT JOIN `tb_join` `j` ON `j`.`id` = `t`.`id` LEFT JOIN `tb_join_2` `j2` ON `j2`.`id` = `t`.`id`
                WHERE ((`a` = '1112' AND (`b` ='1112' AND `c` > '23232' AND `x` > '33') AND (`d` = '1112' OR `e` < '23232') AND `f` = '1112') OR `aa`>'99') 
                GROUP BY `j2`.`uid`, `j1`.`xid`
                HAVING (SUM(`j`.`area`) > '1111' AND MAX(`j`.`xx`) < '11') 
                ORDER BY `a` ASC, `B` DESC
                LIMIT 100,20";
        
        $sql = $this->stripSql($sql);
        $exp = $this->stripSql($exp);
        
        $this->assertEquals($exp, $sql);
    }
    
    /**
     * Tests Find->group()
     */
    public function testGroup() 
    {
        $groupBy = 'name,passwd';
        $r = $this->find->group($groupBy)->getOptions()['group'];
        $this->assertEquals($groupBy, $r);
    }
    
    /**
     * Tests Find->having()
     */
    public function testHaving() 
    {
        $exp1 = 'SELECT * FROM `tb` GROUP BY `uid` HAVING (SUM(`totals`) > \'1000\' AND MAX(`price`) > \'200\')';
        $finder1 = clone $this->find;
        $sql1 = $finder1
        ->from('tb')
        ->group('uid')
        ->having('SUM(totals)', 1000, '>')
        ->having('MAX(price)', 200, '>')
        ->asSql();

        $exp1 = $this->stripSql($exp1);
        $sql1 = $this->stripSql($sql1);
        
        $this->assertEquals($exp1, $sql1);
        
        $finder1 = clone $this->find;
        $sql1 = $finder1
        ->from('tb')
        ->group('uid')
        ->having('SUM(totals)', 1000, '>')
        ->andHaving('MAX(price)', 200, '>') // andHaving
        ->asSql();

        $exp1 = $this->stripSql($exp1);
        $sql1 = $this->stripSql($sql1);
        
        $this->assertEquals($exp1, $sql1);

        $exp2 = 'SELECT * FROM `tb` GROUP BY `uid` HAVING ((SUM(`totals`) > \'1000\') OR MIN(`price`) < \'10\')';
        $finder2 = clone $this->find;
        $sql2 = $finder2
        ->from('tb')
        ->group('uid')
        ->having('SUM(totals)', 1000, '>')
        ->orHaving('MIN(price)', 10, '<') // orHaving
        ->asSql();

        $exp2 = $this->stripSql($exp2);
        $sql2 = $this->stripSql($sql2);
        
        $this->assertEquals($exp2, $sql2);
        
        // 逻辑关系比较复杂，用->havingMulti()方法
        $exp3 = "SELECT * FROM `tb` GROUP BY `uid` HAVING ((
                (
                    SUM(`totals`) > '1000' AND MAX(`price`) > '200') 
                    OR 
                    (MIN(`price`) > '800' AND `aa` > '99')
                ))";
        $having = [
            'or',
            [
                ['SUM(`totals`)', 1000, '>'],
                ['MAX(`price`)', 200, '>'],
            ],
            [
                ['MIN(`price`)', 800, '>'],
                ['aa', 99, '>'],
            ],
        ];
        
        $finder3 = clone $this->find;
        $sql3 = $finder3
        ->from('tb')
        ->group('uid')
        ->havingMulti($having)
        ->asSql();

        $exp3 = $this->stripSql($exp3);
        $sql3 = $this->stripSql($sql3);
        
        $this->assertEquals($exp3, $sql3);
    }
    
    /**
     * 清理SQL便于对比
     * @param unknown $sql
     */
    private static function stripSql($sql) 
    {
        $sql = str_replace(["\r\n", "\t", "\n"], ' ', $sql);
        $sql = preg_replace("/\s+([^0-9a-z_'])/i", '\\1', $sql);
        $sql = preg_replace("/([^0-9a-z_'])\s+/i", '\\1', $sql);
        $sql = preg_replace("/\s+/i", ' ', $sql);
        $sql = strtolower($sql);
        return $sql;
    }
}

