<?php
/**
 * Windwork
 *
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
 *
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */
namespace wf\db;

/**
 * 数据库操作抽象类
 *
 * @package     wf.db
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
abstract class DBAbstract implements DBInterface
{
    /**
     * 数据库连接配置
     * @var array
     */
    protected $cfg = [];

    /**
     * 开启事务的次数，记录次数解决嵌套事务的问题
     * @var int
     */
    protected $transactions = 0;

    /**
     * 事务链中还应继续执行回滚
     * 在嵌套事务中，开启多少次事务就应该执行多少次回滚
     * @var int
     */
    protected $rollbackAgain = false;

    /**
     * 记录当前请求执行的SQL查询语句
     * @var array
     */
    protected $log = [];

    /**
     * 数据库当前页面连接次数,每次实行SQL语句的时候 ++
     *
     * @var int
     */
    public $execTimes = 0;

    /**
     * 获取模型数据表信息
     *
     * <pre>
     * arry(
     *     'pk' => '主键',
     *     'ai' => true/false, //主键是否是自动增长
     *     'field' => array(
     *         '字段1名' => array(
     *                'field'   => '字段1名',
     *                'type'    => '字段类型',
     *                'key'     => '索引类型', //PKI/MUL/UNI
     *                'default' => '默认值',
     *                'ai'      => '是否是自动增长的',
     *         ),
     *         '字段2' => array(
     *                'field'   => $row['Field'],
     *                'type'    => $row['Type'],
     *                'key'     => $row['Key'],
     *                'default' => $row['Default'],
     *                'ai'      => $row['Extra'] == 'auto_increment',
     *         ),
     *         ...
     *     )
     * )
     * </pre>
     * @param string $table  表名
     * @return array
     */
    public function getTableSchema($table)
    {
        static $tableSchemaList = [];

        if((!$tableSchemaList || empty($tableSchemaList[$table]))) {
            $rows = $this->getAll("SHOW COLUMNS FROM %t", [$table]);
            $tableSchema = array(
                'pk'      => '',
                'ai'      => false,
                'field'   => []
            );
            foreach ($rows as $row) {
                $tableSchema['field'][$row['Field']] = $row;

                if ($row['Key'] == 'PRI') {
                    if($tableSchema['pk']) {
                        $tableSchema['pk'] = (array)$tableSchema['pk'];
                        $tableSchema['pk'][] = $row['Field'];
                    } else {
                        $tableSchema['ai'] = $row['Extra'] == 'auto_increment';
                        $tableSchema['pk'] = $row['Field'];
                    }
                }
            }

            $tableSchemaList[$table] = $tableSchema;
        }

        return $tableSchemaList[$table];
    }

    /**
     * 插入多行数据
     * 过滤掉没有的字段
     *
     * @param array $rows
     * @param string $table  插入表
     * @param array $field  允许插入的字段名
     * @param string $isReplace = false 是否使用 REPLACE INTO插入数据，false为使用 INSERT INTO
     * @return PDOStatement
     */
    public function insertRows(array $rows, $table, $fieldArr = [], $isReplace = false)
    {
        $type = $isReplace ? 'REPLACE' : 'INSERT';

        // 数据中允许插入的字段
        $allowFields = $fieldArr ? $fieldArr : array_keys(current($rows));
        $allowFields = QueryBuilder::quoteFieldNames(implode(',', $allowFields));

        //
        $valueArr = [];
        foreach ($rows as $row) {
            $rowStr = '';
            foreach ($row as $key => $val) {
                // 去掉不允许写入的属性
                if ($fieldArr && !in_array($key, $fieldArr)) {
                    unset($row[$key]);
                }
            }

            $rowStr = implode(',', array_map('\wf\db\QueryBuilder::quoteInputVar', $row));
            $valueArr[] = "({$rowStr})";
        }
        $values = $rowStr = implode(',', $valueArr);

        return $this->exec("%x INTO %t (%x) VALUES %x", array($type, $table, $allowFields, $values));
    }

    /**
     * 构筑函数设置配置信息
     * @param array $cfg
     */
    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }
}
