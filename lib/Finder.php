<?php
namespace wf\db;

/**
 * 查询对象构造器
 *
 * @package     wf.db
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.db.query.html
 * @since       0.1.0
 */
class Finder
{
    /**
     * 
     * @var \wf\db\DBInterface
     */
    protected $db;
    
    /**
     * 
     * @var array
     */
    protected $options = [
        'field'   => '*',
        'fieldRaw'  => '', // 字段不过滤注入漏洞（需自己处理），当查询复杂时使用
        'table'   => '',
        'join'    => [],
        'where'   => [],
        'group'   => '',
        'having'  => [],
        'order'   => '',
        'limit'   => '',
        // union 很少使用不支持，如需要直接写SQL
    ];
    
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }
    
    /**
     * 
     * @param \wf\db\DBInterface $db
     * @return \wf\db\Finder
     */
    public function setDb(\wf\db\DBInterface $db)
    {
        $this->db = $db;
        return $this;
    }
    
    /**
     * 
     * @return \wf\db\DBInterface
     */
    public function db()
    {
        return $this->db;
    }
    
    /**
     * 获取符合条件的所有记录
     * 
     * @param int $offset = 0 开始查询记录下标，最小为0
     * @param int $rows = 0 返回记录数，为0则忽略offset和rows都使用默认值
     * @return array
     */
    public function fetchAll($offset = 0, $rows = 0)
    {        
        $opts = $this->options;
        if ($offset && $rows) {
            $offset = (int)$offset;
            $rows = (int)$rows;            
            $opts['limit'] = "{$offset},{$rows}";
        }
        
        $sql = QueryBuilder::optionsToSql($opts);
        $all = $this->db()->getAll($sql);
        
        return $all;
    }
    
    /**
     * 获取一行记录，返回关联数组格式
     * 
     * @return array
     */
    public function fetchRow()
    {
        $opts = $this->options;
        $opts['limit'] = 1;
        
        $sql = QueryBuilder::optionsToSql($opts);
        $row = $this->db()->getRow($sql);
        
        return $row;        
    }
    
    /**
     * 获取字段值
     * @param string $field
     * @return scalar
     */
    public function fetchColumn($field = '')
    {
        $opts = $this->options;
        
        if ($field) {
            $opts['field'] = $field;
        }
        
        $sql = QueryBuilder::optionsToSql($opts);        
        $colValue = $this->db()->getColumn($sql);
        
        return $colValue;
    }
    
    /**
     * 获取记录数
     * @param string $field
     * @return int
     */
    public function fetchCount($field = '')
    {
        $opts = $this->options;
        
        if ($field) {
            $opts['field'] = $field;
        }
        
        $sql = \wf\db\QueryBuilder::optionsToCountSql($opts);
        $num = $this->db()->getColumn($sql);
        
        return $num;
        
    }
    
    /**
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * 字段名列表，将被进行注入漏洞过滤，默认是 *，如：f.a, f.b
     * @param string $field
     * @return \wf\db\Finder
     */
    public function field($field = '*')
    {
        $this->options['field'] = $field;
        
        return $this;
    }
    
    /**
     * 字段名列表，不进行漏洞过滤，设置后比field优先使用
     * @param string $fields
     * @return \wf\db\Finder
     */
    public function fieldRaw($fields = '*')
    {
        $this->options['fieldRaw'] = $fields;
        
        return $this;
    }
    
    /**
     * 查询的表名
     * 可以是多个表，默认是当前模型的表，table_a, table_b AS b
     * @param string $table
     * @return \wf\db\Finder
     */
    public function from($table)
    {
        $this->options['table'] = $table;
        
        return $this;
    }
    
    /**
     * 连接表，
     * 如：RIGHT JOIN myTable tb ON tb.a = xx.filed1
     *    则参数为->join('myTable tb', 'tb.a', 'xx.filed1', 'RIGHT')
     *     
     * @param string $table     连接的表
     * @param string $onFieldA  ON 等号前面的字段名
     * @param string $onFieldB  ON 等号后面的字段名
     * @param string $joinType = 'LEFT' join类型，LEFT|RIGHT|INNER|CROSS
     * @return \wf\db\Finder
     */
    public function join($table, $onFieldA, $onFieldB, $joinType = 'LEFT')
    {
        $joinType = strtoupper(trim($joinType));
        if (!in_array($joinType, ['LEFT', 'RIGHT', 'INNER', 'CROSS'])) {
            throw new \Exception('$joinType 不正确，只允许是LEFT|RIGHT|INNER|CROSS');
        }
        
        $this->options['join'][] = [$table, $onFieldA, $onFieldB, $joinType];
        return $this;
    }
    
    /**
     * 构造sql多个WHERE查询条件
     * 
     * <div>
     *   <b>规则：</b>查询条件有两部分构成
     *   <ul>
     *     <li>1、多个查询条件之间的逻辑关系 AND|OR 字符，这个不是必须的。如果指定and/or，必须放在数组的第一个元素，即下标为0。</li>
     *     <li>2、查询元素（比较表达式），['字段', '值', '操作符 = > < 等', '字段值的类型']</li>
     *   </ul>
     * </div>
     * <b>例如，允许格式如下：</b>
     * <ul>
     *     <li>一个条件 $array = ['字段', '值', '操作符 = > < 等', '字段值的类型']</li>
     *     <li>多个条件，不指定and/or的条件 $array = [['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...]</li>
     *     <li>多个条件，指定and/or的条件$array = [['and', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...]</li>
     *     <li>多个条件分组，$array = ['and|or', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['and|or', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...];</li>
     * </ul>
     * 
     * @param array $where 查询条件 
     * <pre>
     *   [
     *     0 => '查询逻辑，and|or，不设置该项则查询条件默认使用AND关系', 
     *     1 => ['字段', '值', '操作符 = > < 等，默认=，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=', '值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句'], 
     *     2 => ['字段', '值', '操作符 = > < 等', '字段值的类型'], 
     *     ...
     *   ]
     * </pre>
     * @return \wf\db\Finder
     */
    public function whereMulti($where)
    {
        $this->options['where'][] = $where;
        
        return $this;
    }
    
    /**
     * WHERE查询条件
     * 
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 运算符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function where($field, $value, $operator = '=', $type = 'string')
    {
        $this->options['where'][] = [$field, $value, $operator, $type];
        
        return $this;
    }


    /**
     * WHERE查询条件（AND）
     *
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 运算符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function andWhere($field, $value, $operator = '=', $type = 'string')
    {
        $this->where($field, $value, $operator, $type);
        
        return $this;
    }


    /**
     * WHERE查询条件（OR）
     *
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 运算符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function orWhere($field, $value, $operator = '=', $type = 'string')
    {
        if (!empty($this->options['where'])) {
            $this->options['where'] = [
                'OR',
                $this->options['where'],
                [$field, $value, $operator, $type],
            ];
        } else {        
            $this->where($field, $value, $operator, $type);
        }
        
        return $this;
    }
    
    /**
     * 对$order参数进行SQL注入过滤后，在前面加上ORDER BY
     * 
     * @param string $order
     * @return \wf\db\Finder
     */
    public function order($order)
    {
        $this->options['order'] = $order;
        
        return $this;
    }
    
    /**
     * 对$group参数进行SQL注入过滤后，在前面加上GROUP BY
     * 
     * @param string $group
     * @return \wf\db\Finder
     */
    public function group($group)
    {
        $this->options['group'] = $group;
        
        return $this;
    }
    
    /**
     * 构造sql多个HAVING查询条件
     * 
     * <div>
     *   <b>规则：</b>查询条件有两部分构成
     *   <ul>
     *     <li>1、多个查询条件之间的逻辑关系 AND|OR 字符，这个不是必须的。如果指定and/or，必须放在数组的第一个元素，即下标为0。</li>
     *     <li>2、查询元素（比较表达式），['字段', '值', '操作符 = > < 等', '字段值的类型']</li>
     *   </ul>
     * </div>
     * <b>例如，允许格式如下：</b>
     * <ul>
     *     <li>一个条件 $array = ['字段', '值', '操作符 = > < 等', '字段值的类型']</li>
     *     <li>多个条件，不指定and/or的条件 $array = [['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...]</li>
     *     <li>多个条件，指定and/or的条件$array = [['and', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...]</li>
     *     <li>多个条件分组，$array = ['and|or', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['and|or', ['字段', '值', '操作符 = > < 等', '字段值的类型'], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...], ['字段', '值', '操作符 = > < 等', '字段值的类型'], ...];</li>
     * </ul>
     * 
     * @param array $where 查询条件 
     * <pre>
     *   [
     *     0 => '查询逻辑，and|or，不设置该项则查询条件默认使用AND关系', 
     *     1 => ['字段', '值', '操作符 = > < 等，默认=，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=', '值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句'], 
     *     2 => ['字段', '值', '操作符 = > < 等', '字段值的类型'], 
     *     ...
     *   ]
     * </pre>
     * @return \wf\db\Finder
     */
    public function havingMulti($having)
    {
        $this->options['having'][] = $having;
        
        return $this;
    }
    
    /**
     * HAVING条件，格式同where
     * 
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 操作符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function having($field, $value, $operator = '=', $type = 'string')
    {
        $this->options['having'][] = [$field, $value, $operator, $type];
        
        return $this;
    }


    /**
     * HAVING查询条件（AND）
     *
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 运算符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function andHaving($field, $value, $operator = '=', $type = 'string')
    {
        $this->having($field, $value, $operator, $type);
        
        return $this;
    }

    /**
     * HAVING查询条件（OR）
     *
     * 如需要复杂SQL查询，请使用 db()->getAll($sql, $args)/db()->getRow($sql, $args)/db()->getColumn($sql, $args)
     * @param string $field 字段名
     * @param mixed $value  字段值
     * @param string $operator = '=' 运算符，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=等
     * @param string $type = 'string' 字段值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @return \wf\db\Finder
     */
    public function orHaving($field, $value, $operator = '=', $type = 'string')
    {
        if (!empty($this->options['having'])) {
            $this->options['having'] = [
                'OR',
                $this->options['having'],
                [$field, $value, $operator, $type],
            ];
        } else {        
            $this->having($field, $value, $operator, $type);
        }
        
        return $this;
    }
    
    /**
     * SQL分页查询
     * @param number $offset
     * @param number $rows
     * @return \wf\db\Finder
     */
    public function limit($offset, $rows = 0)
    {
        $offset = (int)($offset > 0 ? $offset : 0);
        $rows   = (int)($rows > 0 ? $rows : 0);
        
        $this->options['limit'] = "{$offset}, {$rows}";
        
        return $this;
    }
    
    /**
     * 将查询选项转成SQL语句
     * @return string
     */
    public function asSql()
    {
        return QueryBuilder::optionsToSql($this->options);
    }
}

