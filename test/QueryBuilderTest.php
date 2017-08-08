<?php
require_once '../lib/QueryBuilder.php';
require_once '../lib/Finder.php';

use \wf\db\QueryBuilder;

/**
 * QueryBuilder test case.
 */
class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp ();
        
        
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        
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
     * Tests QueryBuilder::tablePrefix()
     */
    public function testTablePrefix()
    {
        $sql = "SELECT * FROM wk_xxx";
        $sql = QueryBuilder::tablePrefix($sql, 'my_test_');
        
        $this->assertEquals("SELECT * FROM my_test_xxx", $sql);

        $sql = "SELECT * FROM wk_xxx WHERE true";
        $sql = QueryBuilder::tablePrefix($sql, 'my_test_');
        $this->assertEquals("SELECT * FROM my_test_xxx WHERE true", $sql);

        $sql = "SELECT * FROM wk_xxx, b WHERE true";
        $sql = QueryBuilder::tablePrefix($sql, 'my_test_');
        $this->assertEquals("SELECT * FROM my_test_xxx, b WHERE true", $sql);
    }
    
    /**
     * Tests QueryBuilder::quoteInputVar()
     */
    public function testQuoteInputVar()
    {
        $input1 = "dsfsd\"str'xsdsfs\032xxsd\n\t\r";
        $exp1 = "'dsfsd\\\"str\'xsdsfs\\032xxsd\\n\t\\r'";
        $ret1 = QueryBuilder::quoteInputVar($input1);
        $this->assertEquals((string)$exp1, $ret1);

        // bool
        $ret2 = QueryBuilder::quoteInputVar(true);
        $this->assertTrue($ret2 === '1');        
        $ret3 = QueryBuilder::quoteInputVar(false);
        $this->assertTrue($ret3 === '0');
        
        // empty
        $ret4 = QueryBuilder::quoteInputVar('');
        $this->assertTrue($ret4 === "''");
        
        // array
        $input5 = ['hi', 66, ''];
        $ret5 = QueryBuilder::quoteInputVar($input5);
        $this->assertEquals(["'hi'", "'66'", "''"], $ret5);
    }
    
    /**
     * Tests QueryBuilder::quoteFieldName()
     */
    public function testQuoteField()
    {        
        // 函数
        $ret1 = QueryBuilder::quoteFieldNames('a, POW(2,3),count(*)');
        $ret2 = QueryBuilder::quoteFieldNames('ABS(-1)');
        $ret3 = QueryBuilder::quoteFieldNames('distinct aa.bb');
        $ret4 = QueryBuilder::quoteFieldNames('distinct(a.bb)');
        $ret5 = QueryBuilder::quoteFieldNames('aa, bb,`xx.cc, count(*), count(bb.cc)');
        
        $this->assertEquals('`a`,POW(2,3),count(*)', $ret1);
        $this->assertEquals('ABS(-1)', $ret2);
        $this->assertEquals('DISTINCT `aa`.`bb`', $ret3);
        $this->assertEquals('distinct(`a`.`bb`)', $ret4);
        $this->assertEquals('`aa`,`bb`,`xx`.`cc`,count(*),count(`bb`.`cc`)', $ret5);
    }
    
    /**
     * Tests QueryBuilder::quoteFieldNames()
     */
    public function testQuoteFields()
    {
        $str = 'tb.a,tb.b,SUM(xx.c)';
        $ret = QueryBuilder::quoteFieldNames($str);
        
        $this->assertEquals('`tb`.`a`,`tb`.`b`,SUM(`xx`.`c`)', $ret);
    }
    
    /**
     * Tests QueryBuilder::where()
     */
    public function testWhere()
    {
        // =
        $ret = QueryBuilder::where('tb.aa', 123, '=');
        $this->assertEquals('`tb`.`aa`=\'123\'', $ret);

        // !=
        $ret = QueryBuilder::where('tb.aa', 123, '!=');
        $this->assertEquals('`tb`.`aa`!=\'123\'', $ret);
        
        // <>
        $ret = QueryBuilder::where('tb.aa', 123, '<>');
        $this->assertEquals('`tb`.`aa`<>\'123\'', $ret);

        // >
        $ret = QueryBuilder::where('tb.aa', 123, '>');
        $this->assertEquals('`tb`.`aa`>\'123\'', $ret);

        // >=
        $ret = QueryBuilder::where('tb.aa', 123, '>=');
        $this->assertEquals('`tb`.`aa`>=\'123\'', $ret);

        // <
        $ret = QueryBuilder::where('tb.aa', 123, '<');
        $this->assertEquals('`tb`.`aa`<\'123\'', $ret);
        
        // <=
        $ret = QueryBuilder::where('tb.aa', 123, '<=');
        $this->assertEquals('`tb`.`aa`<=\'123\'', $ret);
        
        // in
        $ret = QueryBuilder::where('tb.aa', [1, 2, 3], 'in');
        $this->assertEquals("`tb`.`aa` IN('1','2','3')", $ret);
        
        // notin
        $ret = QueryBuilder::where('tb.aa', [1, 2, 3], 'notin');
        $this->assertEquals("`tb`.`aa` NOT IN('1','2','3')", $ret);

        // not in
        $ret = QueryBuilder::where('tb.aa', [1, 2, 3], 'not in');
        $this->assertEquals("`tb`.`aa` NOT IN('1','2','3')", $ret);

        // in 不是数组类型则使用 =
        $ret = QueryBuilder::where('tb.aa', 123, 'in');
        $this->assertEquals('`tb`.`aa`=\'123\'', $ret);
        
        // notin 不是数组类型则使用 != 
        $ret = QueryBuilder::where('tb.aa', 123, 'notin');
        $this->assertEquals('`tb`.`aa`!=\'123\'', $ret);

        // like
        $ret = QueryBuilder::where('tb.aa', 'cm"%', 'like');
        $exp = '`tb`.`aa` LIKE(\'cm\"%\')';
        $this->assertEquals($exp, $ret);

        // +-|&^ 格式为 "$field=$field $glue $val"; $glue为+-|&^
        // +
        $ret = QueryBuilder::where('tb.aa', 123, '+');
        $this->assertEquals('`tb`.`aa`=`tb`.`aa`+\'123\'', $ret);
        // -
        $ret = QueryBuilder::where('tb.aa', 123, '-');
        $this->assertEquals('`tb`.`aa`=`tb`.`aa`-\'123\'', $ret);
        // |
        $ret = QueryBuilder::where('tb.aa', 123, '|');
        $this->assertEquals('`tb`.`aa`=`tb`.`aa`|\'123\'', $ret);
        // &
        $ret = QueryBuilder::where('tb.aa', 123, '&');
        $this->assertEquals('`tb`.`aa`=`tb`.`aa`&\'123\'', $ret);
        // ^
        $ret = QueryBuilder::where('tb.aa', 123, '^');
        $this->assertEquals('`tb`.`aa`=`tb`.`aa`^\'123\'', $ret);
        
        // type = sql，注意：$val将不会被做防SQL注入处理
        $ret = QueryBuilder::where('tb.aa', "SELECT uid FROM user", 'IN', 'sql');
        $this->assertEquals('`tb`.`aa` IN(SELECT uid FROM user)', $ret);
        $ret = QueryBuilder::where('tb.aa', "SELECT uid FROM user", 'notin', 'sql');
        $this->assertEquals('`tb`.`aa` NOT IN(SELECT uid FROM user)', $ret);
        $ret = QueryBuilder::where('tb.aa', "SELECT uid FROM user", 'not in', 'sql');
        $this->assertEquals('`tb`.`aa` NOT IN(SELECT uid FROM user)', $ret);

        // type = sql LIKE 当不希望$val被防注入时使用
        $ret = QueryBuilder::where('tb.aa', "xx'yy", 'LIKE', 'sql');
        $this->assertEquals('`tb`.`aa` LIKE xx\'yy', $ret);
        
        // type = sql =、!=、<>、>、< 当不希望$val被防注入时使用
        $ret = QueryBuilder::where('tb.aa', "xx'yy", '=', 'sql');
        $this->assertEquals('`tb`.`aa` = xx\'yy', $ret);
        $ret = QueryBuilder::where('tb.aa', "xx'yy", '<>', 'sql');
        $this->assertEquals('`tb`.`aa` <> xx\'yy', $ret);
        $ret = QueryBuilder::where('tb.aa', "xx'yy", '>=', 'sql');
        $this->assertEquals('`tb`.`aa` >= xx\'yy', $ret);        
    }
    
    /**
     * Tests QueryBuilder::whereArr()
     */
    public function testWhereArr()
    {
        // and
        $arr = [
            ['t.a', 100, '>'],
            ['t.b', 'nn%', 'like'],
        ];
        $ret = QueryBuilder::whereArr($arr);
        $this->assertEquals(" (`t`.`a`>'100' AND `t`.`b` LIKE('nn%')) ", $ret);

        // and
        $arr = [
            'and',
            ['t.a', 100, '>'],
            ['t.b', 'nn%', 'like'],
        ];
        $ret = QueryBuilder::whereArr($arr);
        $this->assertEquals(" (`t`.`a`>'100' AND `t`.`b` LIKE('nn%')) ", $ret);
        
        // or
        $arr = [
            'or',
            ['t.a', 100, '>'],
            ['t.b', 'nn%', 'like'],
        ];
        $ret = QueryBuilder::whereArr($arr);
        $this->assertEquals(" (`t`.`a`>'100' OR `t`.`b` LIKE('nn%')) ", $ret);
        
        // 复杂
        $arr = [
            ['t.a', 100, '>'],
            ['t.b', 'nn%', 'like'],
            [
                'or',
                ['t.xa', 11, '>'],
                ['t.xb', 9999, '<='],
                [
                    ['t.id', [1, 2, 3], 'in'],
                    ['t.oa', "SELECT uid FROM user WHERE is_checked = 1", 'in', 'sql'],
                ]
            ],
        ];
        $ret = QueryBuilder::whereArr($arr);
        $exp = " (`t`.`a`>'100' AND `t`.`b` LIKE('nn%') AND  (`t`.`xa`>'11' OR `t`.`xb`<='9999' OR  (`t`.`id` IN('1','2','3') AND `t`.`oa` IN(SELECT uid FROM user WHERE is_checked = 1)) ) ) ";
        $this->assertEquals($exp, $ret);
    }
    
    /**
     * Tests QueryBuilder::format()
     */
    public function testFormat()
    {
        //  %t:表名； %c：字段名； %n:数字值；%i：整形；%f：浮点型； %s：字符串值; %x:保留不处理 
        $exp = "SELECT `uid`,`uname`,`password` FROM `user` WHERE `uid` = 100 AND `checked` = 1";
        $sql = "SELECT %c FROM %t WHERE %c = %i AND `checked` = %i";
        $arg = [
            'uid,uname,password',
            'user',
            'uid',
            100,
            1
        ];
        $ret = QueryBuilder::format($sql, $arg);
        $this->assertEquals($exp, $ret);
        
        // t/c/n/i/f/s/x
        $exp = "SELECT `uid`,`uname`,`password` FROM `user` WHERE `uid` = 100 AND `checked` = 1 AND `price` = 125.8 AND `income` = 88.8888 AND `rid` IN(11,22,33) AND `nickname` = 'hello'";
        $sql = "SELECT %c FROM %t WHERE %c = %i AND `checked` = %i AND `price` = %f AND `income` = %n AND `rid` IN(%x) AND `nickname` = %s";
        $arg = [
            'uid,uname,password',
            'user',
            'uid',
            100,
            1,
            '125.80xjwj',
            '88.8888abc3434x*s.521',
            '11,22,33',
            'hello',
        ];
        $ret = QueryBuilder::format($sql, $arg);
        $this->assertEquals($exp, $ret);
    }
    
    /**
     * Tests QueryBuilder::buildQueryOptions()
     */
    public function testBuildQueryOptions()
    {
        $opts = [
            'field' => 't.aa, t2.bb, t3.cc',
            'table' => 'my_table t',
            'join'  => [
                ['table2 t2', 't2.id', 't.id'],
                ['table3 AS t3', 't3.id', 't.id', 'cross'],
            ],
            'order' => 't.id DESC, t2.time DESC',
            'limit' => '0, 20',
        ];
        
        $sql = QueryBuilder::optionsToSql($opts);
        $exp = "SELECT `t`.`aa`,`t2`.`bb`,`t3`.`cc` FROM `my_table` `t` LEFT JOIN `table2` `t2` ON `t2`.`id` = `t`.`id` cross JOIN `table3` AS `t3` ON `t3`.`id` = `t`.`id` ORDER BY `t`.`id` DESC, `t2`.`time` DESC LIMIT 0,20";
        
        $sql = $this->stripSql($sql);
        $exp = $this->stripSql($exp);
        $this->assertEquals($exp, $sql);
        
        $finder = new \wf\db\Finder();
        $finder->field('aa,bb,cc,count(id)')
        ->from('demo')
        ->where('is_checked', 1)
        ->andWhere('is_paid', 1)
        ->orWhere('price', 12.5, '>');
        
        $buildSql = QueryBuilder::optionsToSql($finder->getOptions());
        $finderSQL = $finder->asSql();
        
        $this->assertEquals($buildSql, $finderSQL);
        
        // group,having
        $opts = [
            'field' => 't.aa, t2.bb, t3.cc, sum(t.total) as sum_price',
            'table' => 'my_table t',
            'join'  => 'LEFT JOIN my_table2 t2 ON t2.id = t.id',
            'group' => 'uid',
            'having' => ['sum_price', 99, '>'],
        ];
        $sql = QueryBuilder::optionsToSql($opts);
        $exp = "SELECT `t`.`aa`,`t2`.`bb`,`t3`.`cc`, sum(`t`.`total`) as `sum_price` FROM `my_table` `t` LEFT JOIN my_table2 t2 ON t2.id = t.id GROUP BY `uid` HAVING `sum_price` > '99'";

        $sql = $this->stripSql($sql);
        $exp = $this->stripSql($exp);
        
        $this->assertEquals($exp, $sql);

        $opts = [
            'field' => 'xxxx',
            'fieldRaw' => 'aaa, bbb, ccc AS x',
            'table' => 'my_table t',
        ];
        $sql = QueryBuilder::optionsToSql($opts);
        $exp = "SELECT aaa, bbb, ccc AS x FROM `my_table` `t`";
        
        $sql = $this->stripSql($sql);
        $exp = $this->stripSql($exp);
        
        $this->assertEquals($exp, $sql);
        
    }

    /**
     * Tests QueryBuilder::buildSqlSet()
     */
    public function testOptionsToCountSql()
    {
        $opt = [
            'table' => 'my_table t1',
            'where' => ['checked', 1],
        ];    
        $sql = QueryBuilder::optionsToCountSql($opt);
        $exp = "SELECT COUNT(*) FROM `my_table` `t1` WHERE `checked`='1'";

        $sql = $this->stripSql($sql);
        $exp = $this->stripSql($exp);
        
        $this->assertEquals($exp, $sql);
    }
    
    /**
     * Tests QueryBuilder::buildSqlSet()
     */
    public function testBuildSqlSet()
    {
        $data = [
            'nickname' => 'hello',
            'phone' => '139xxxxxxxx',
            'city' => 'nanning'
        ];
        
        $ret = QueryBuilder::buildSqlSet($data, ['nickname', 'phone', 'city']);
        $this->assertEquals(" `nickname` = 'hello' , `phone` = '139xxxxxxxx' , `city` = 'nanning' ", $ret);
        
        $ret = QueryBuilder::buildSqlSet($data, ['nickname', 'phone']);
        $this->assertEquals(" `nickname` = 'hello' , `phone` = '139xxxxxxxx' ", $ret);

        $ret = QueryBuilder::buildSqlSet($data, ['nickname', 'phone', 'city'], ['nickname', 'phone']);
        $this->assertEquals(" `city` = 'nanning' ", $ret);
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
        $sql = strtolower(trim($sql));
        return $sql;
    }
}

