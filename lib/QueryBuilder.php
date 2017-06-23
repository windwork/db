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
 * SQL语句构造类
 *
 * @package     wf.db
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.db.queryhelper.html
 * @since       0.1.0
 */
class QueryBuilder
{
    
    /**
     * 表前缀替换
     *
     * @param string $sql
     * @param string $prefix
     */
    public static function tablePrefix($sql, $prefix)
    {
        // sql中写表的前缀一律用 wk_, 运行的时候转换成配置文件中使用的前缀
        return preg_replace('/(\s+)wk_([0-9a-zA-Z_]+)(([,|\s]+)|$)/', ' ' . $prefix.'$2$3', $sql);
    }
    
    /**
     * 变量进行注入转义并加上引号
     * 
     * @param mixed $str
     * @param bool $allowArray = true
     * @return string
     */
    public static function quoteInputVar($str, $allowArray = true)
    {
        // 字符串进行转义
        if (is_string($str)) {
            return '\'' . addcslashes($str, "\n\r\\'\"\032") . '\'';
        }
        
        // 数字
        if (is_numeric($str)) {
            return '\'' . $str . '\'';
        }
                
        // 数组
        if (is_array($str)) {
            if($allowArray) {
                foreach ($str as &$v) {
                    $v = static::quoteInputVar($v, true);
                }
                
                return $str;
            } else {
                return '\'\'';
            }
        }

        // 布尔型转成0/1（统一使用tinyint来保存bool类型）
        if (is_bool($str)) {
            return $str ? '1' : '0';
        }
        
        // 其他类型返回空字符
    
        return '\'\'';
    }

    /**
     * 字段转义
     * @param string $field
     * @return string
     */
    public static function quoteFieldName($field)
    {
        $field = trim($field);
        if(!$field || is_numeric($field)) {
            return $field;
        }
         
        if (strpos($field, '`') !== false) {
            $field = str_replace('`', '', $field);
        }
    
        $field = preg_replace("/(\\s+)/", '` `', $field);
        $field = preg_replace("/(\\.)/", '`.`', $field);
        $field = '`' . $field . '`';
        $field = str_ireplace(array('`as` ', '`distinct` ', '`*`', '`+`', '`-`', '`/`'), array('AS ', 'DISTINCT ', '*', '+', '-', '/'), $field);
    
        return $field;
    }
    
    /**
     * 字段名转义，可以是多个字段一起，如：table.field1 或 a.f1, b.f2, c.*
     * 
     * @param string|array $fields 
     * @return string
     */
    public static function quoteFieldNames($fields)
    {
        $fieldArr = is_string($fields) ? explode(',', trim($fields)) : (array)$fields;
        
        foreach ($fieldArr as $k => $field) {
            $field = preg_replace("/(\s+)/", ' ', trim($field));
            if ($field == '*') {
                // do nothing
            } elseif (false !== strpos($field, '(')) {
                if (preg_match("/(.*?)\\((.*?)\\)(.*)/i", $field, $match)) {
                    // xx() | xx(xx) | xx(xx)xx
                    $field = $match[1] . '(' . QueryBuilder::quoteFieldName($match[2]).')' . QueryBuilder::quoteFieldName($match[3]);
                } else {
                    // xx( | xx(xx
                    $field = preg_replace_callback(
                        "/\\((.+)/i",
                        function($match) {
                            return '('.QueryBuilder::quoteFieldName($match[1]);
                        },
                        $field
                    );
                }

                $fieldArr[$k] = $field;
            } elseif (false !== strpos($field, ')')) {
                // xx)
                $field = preg_replace_callback(
                    "/(.*)\\)/i",
                    function($match) {
                        return QueryBuilder::quoteFieldName($match[1]) . ')';
                    },
                    $field
                );
                
                $fieldArr[$k] = QueryBuilder::quoteFieldName(substr($field, 0, strlen($field) - 1)) . ')';
            } else {
                $fieldArr[$k] = QueryBuilder::quoteFieldName($field);
            }
        }
        
        $rFields = implode(',', $fieldArr);        
        
        return $rFields;
    }
    
    /**
     * @param string $str
     * @return string
     * @throws \wf\db\Exception
     */
    protected static function quoteOrder($str)
    {
        if ($str && is_array($str) && isset($str[1])) {
            $str = $str[1];
        }
        
        if ($str && !is_string($str)) {
            throw new \wf\db\Exception('Order fields must be string!');
        }
        
        if ($str && strtolower($str) != 'asc' && strtolower($str) != 'desc') {
            $str = static::quoteFieldNames($str);
        }
        
        return $str;
    }
    
    /**
     * 查询条件
     * 
     * @param string $field  字段名 
     * @param string|array $val 值，使用in/notin的时候为array类型
     * @param string $glue =,+,-,|,&,^,like,in,notin,not in,>,<,<>,>=,<=,!=
     * @param string $type $val参数值的类型，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
     * @throws \wf\db\Exception
     * @return string
     */
    public static function where($field, $val, $glue = '=', $type = 'string')
    {
        $glue = strtolower($glue);
        $glue = str_replace(' ', '', $glue);
        $type = str_replace(' ', '', $type);
        
        $field = static::quoteFieldNames($field);
        
        if (is_array($val)) {
            $glue = $glue == 'in' ? 'in' : 'notin';
        } elseif ($type != 'sql') {
            // 值不是数组类型，'in' => '='， 'notin' => '!='
            if($glue == 'in') {
                $glue = '=';
            } elseif($glue == 'notin') {
                $glue = '!=';
            }            
        }

        if ($type == 'sql') {
            $where = '';
            switch ($glue) {
                case 'like':
                    $where = "{$field} LIKE {$val}";
                    break;
                case 'in':
                    $where = "{$field} IN({$val})";
                    break;
                case 'notin':
                    $where = "{$field} NOT IN({$val})";
                    break;
                default:
                    $where = "{$field} {$glue} {$val}";
                    break;
            }
            return $where;
        }
        
        $glue || $glue = '=';
        $val = $type == 'field' ? static::quoteFieldNames($val) : static::quoteInputVar($val);
        
        switch ($glue) {
            case '=':
                return $field . $glue . $val;
                break;
            case '-':
            case '+':
            case '|':
            case '&':
            case '^':
                return $field . '=' . $field . $glue . $val;
                break;
            case '>':
            case '<':
            case '!=':
            case '<>':
            case '<=':
            case '>=':
                return $field . $glue . $val;
                break;
    
            case 'like':
                return $field . ' LIKE(' . $val . ')';
                break;
    
            case 'in':
            case 'notin':
                $val = $val ? implode(',', $val) : '\'\'';
                return $field . ($glue == 'notin' ? ' NOT' : '') . ' IN(' . $val . ')';
                break;
    
            default:
                throw new \wf\db\Exception('Not allow this glue between field and value: "' . $glue . '"');
        }
    }
    
    /**
     * 构造sql多个查询条件
     * 
     * <div>
     *   <b>规则：</b>查询条件有两部分构成
     *   <ul>
     *     <li>一个是查询元素（比较表达式）， array('字段', '值', '比较逻辑 = > < ...')</li>
     *     <li>一个是查询条件之间的逻辑关系 AND|OR 字符，这个不是必须的。如果指定and/or，必须放在数组的第一位，即下标为0。</li>
     *   </ul>
     * </div>
     * <b>构造格式为：</b>
     * <ul>
     *   <li>不指定and/or(默认and)：array(比较表达式1,比较表达式2, ...)</li>
     *   <li>指定and/or：array('AND|OR', 比较表达式1,比较表达式2, ...)</li>
     *   <li>嵌套混合：array('AND|OR', array('AND|OR', 比较表达式11,比较表达式12, ...), 比较表达式2, ...)</li>
     * </ul>
     * <b>例如允许格式如下：</b>
     * <ul>
     *     <li>一个条件 $options = array('field', 'val', 'glue', 'type')</li>
     *     <li>多个不指定and/or的条件 $options = array(array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
     *     <li>多个指定and/or的条件$options = array('and', array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
     *     <li>$options = array('and|or', array('field', 'val', 'glue'), array('and|or', array('field1', 'val1', 'glue1'), array('field2', 'val2', 'glue2'), ...), array('field3', 'val', 'glue'), ...);</li>
     * </ul>
     * 
     * @param array $options 查询条件 array('and|or', array('字段1', '值', '=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!='), array('字段1', '值', '逻辑'), ...)
     * @throws \wf\db\Exception
     * @return string
     */
    public static function whereArr($options)
    {
        if (!is_array($options) || empty($options[0]) || !(is_string($options[0]) || is_array($options[0]))) {
            throw new \wf\db\Exception('Illegal param, the param should be array, but string has given: $options = ' . var_export($options, 1));
        }

        if (is_array($options[0])) {
            // [[], [], [] ...] => ['and', [], [], [] ...]
            array_unshift($options, 'AND');
            return static::whereArr($options);        
        } 
        
        // ['and|or', [], [] ...] 或 ['field', 'value' ...]
        
        $logic = strtoupper(trim($options[0]));        
        if($logic == 'AND' || $logic == 'OR') {
            // ['and|or', [], [] ...]
            unset($options[0]);            
            $pieces = [];
            
            foreach ($options as $item) {
                $pieces[] = static::whereArr($item);
            }
            
            $ret = implode(" {$logic} ", $pieces);        
            $ret = " ({$ret}) ";
        
            return $ret;
        } else {
            // ['field', 'value', 'glue', 'type']
            empty($options[2]) && $options[2] = '=';
            isset($options[3]) || $options[3] = '';
            
            return static::where($options[0], $options[1], $options[2], $options[3]);
        }
    }
        
    /**
     * sql格式化
     * 
     * 在$sql参数中加入"%参数类型字符"的格式，并在$arg参数设置该格式的值，$sql中的第n个参数对应$arg中的第n个元素。
     * <pre>
     * $exp = "SELECT `uid`,`uname`,`password` FROM `user` WHERE `uid` = 100 AND `checked` = 1";
     * $sql = "SELECT %c FROM %t WHERE %c = %i AND `checked` = %i";
     * $arg = [
     *　　'uid,uname,password', // %c
     *　　'user',               // %t
     *　　'uid',                // %c
     *　　100,                  // %i
     *　　1                     // %i
     * ];
     * $ret = \wf\db\QueryBuilder::format($sql, $arg); // $ret == $exp
     * </pre>
     * 
     * @param string $sql
     * <pre>SQL格式化参数类型：
     *   %t：表名(Table)，将被进行数据表名称反注入处理
     *   %c：字段名(Column)，将被进行数据表字段名反注入处理
     *   %n：数字值(Number)，将被过滤掉非数字和.的字符
     *   %i：整形(Int)，将被强制转换为整形
     *   %f：浮点型(Float)，将被强制转换为浮点型
     *   %s：字符串值(String)，将被进行字符串值反注入处理
     *   %x：保留不处理
     *   </pre>
     * @param array $arg $sql参数对应的值
     * @throws \wf\db\Exception
     * @return string
     */
    public static function format($sql, $arg)
    {
        $arg = (array)$arg;
        
        if(preg_match('/(\"|\')/', $sql)) {
            throw new \wf\db\Exception('SQL string format error! It\'s Unsafe to take "|\' in SQL.');
        }
        
        $count = substr_count($sql, '%');
        if (!$count) {
            return $sql;
        } elseif ($count > count($arg)) {
            throw new \wf\db\Exception('SQL string format error! This SQL need "' . $count . '" vars to replace into.', 0, $sql);
        }
        
        // 格式化类型检查
        if(preg_match('/%[^tcnifsx]/', $sql, $m)) {
            throw new \wf\db\Exception('SQL string format error! Not allowed type (' . $m[0] . ') found.');
        }
        
        $ret = preg_replace_callback('/%([tcnifsx])/i', function($matchs) use($arg) {
            static $find = 0;
            
            $m = $matchs[1];
            
            if ($m == 'c' || $m == 't') {
                $val = static::quoteFieldNames($arg[$find]);
            } elseif ($m == 'n') {
                // 只能有1个点
                $val = preg_replace("/(.*?\\..*?)\\..*/", '\\1', $arg[$find]);
                $val = preg_replace("/[^0-9\\.].*/", '', $val); // 非数字或.后面全部清掉
            } elseif ($m == 'i') {
                $val = (int)$arg[$find];
            } elseif ($m == 'f') {
                $val = (float)$arg[$find];
            } elseif ($m == 's') {
                $val = static::quoteInputVar($arg[$find]);
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
            } elseif ($m == 'x') {
                $val = $arg[$find];
            }
    
            $find ++;
            return $val;
    
        }, $sql);
    
        return $ret;
    }
    
    /**
     * 查询选项解析
     * 
     * @param array $options = <pre>array(
     *     'field' =>'f.a, f.b', // 字段名列表，默认是 *
     *     'table'  => 'table_a, table_b AS b', // 查询的表名，可以是多个表，默认是当前模型的表
     *     'join'   => []  // [['table_name', 'ON_field_a', 'ON_field_b', 'LEFT|RIGHT|CROSS|INNSER'], ..., "格式2直接写join语法"], // => LEFT JOIN `table_name` ON `field_a` = `field_b`
     *     'where'  => []  // 查询条件 array('and|or', array('字段1', '值', '=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!='), array('字段1', '值', '逻辑'), ...)
     *     'group'  => '', // 将对其进行SQL注入过滤并且在前面加上GROUP BY 
     *     'having' => '', // 数组结构，格式同where，将对其进行SQL注入过滤并且在前面加上 HAVING
     *     'order'  => '', // 将对其进行SQL注入过滤并且在前面加上 ORDER BY
     * )</pre>
     * @see \wf\db\DBInterface::whereArr()
     * @throws \wf\db\Exception
     * @return array
     */
    public static function buildQueryOptions($options = [])
    {        
        if(!is_array($options)) {
            throw new \wf\db\Exception('The param must be array!');
        }

        isset($options['field']) or $options['field'] = '*';
        isset($options['join'])  or $options['join'] = [];
        isset($options['where']) or $options['where'] = [];
        
        $result = [];
        
        if (!empty($options['fieldRaw'])) {
            $result['field'] = $options['fieldRaw'];
        } else {
            $result['field'] = static::buildOptionField(@$options['field']);
        }
        
        $result['table']  = static::buildOptionTable(@$options['table']);
        
        // 可选
        $result['join']   = empty($options['join'])   ? '' : static::buildOptionJoins((array)@$options['join']);
        $result['where']  = empty($options['where'])  ? '' : static::buildOptionWhere((array)@$options['where']);
        $result['group']  = empty($options['group'])  ? '' : static::buildOptionGroup(@$options['group']);
        $result['having'] = empty($options['having']) ? '' : static::buildOptionHaving(@$options['having']);
        $result['order']  = empty($options['order'])  ? '' : static::buildOptionOrder(@$options['order']);
        $result['limit']  = empty($options['limit'])  ? '' : static::buildOptionLimit(@$options['limit']);
                
        return $result;
    }

    /**
     * 
     * @param string $table
     * @return string
     */
    protected static function buildOptionTable($table)
    {
        return empty($table) ? '' : QueryBuilder::quoteFieldNames($table);
    }
    
    /**
     * 
     * @param array $where
     * @return string
     */
    protected static function buildOptionWhere($where)
    {
        return empty($where) ? '' : (' WHERE ' . QueryBuilder::whereArr($where));
    }

    /**
     * 
     * @param string $fields
     * @return string
     */
    protected static function buildOptionField($fields)
    {
        return empty($fields) ? '*' : QueryBuilder::quoteFieldNames($fields);
    }
    
    /**
     * 
     * @param array $joins
     * @throws \wf\db\Exception
     * @return string
     */
    protected static function buildOptionJoins(array $joins)
    {
        if (empty($joins)) {
            return '';
        }
        
        $joinResult = '';
        $joins = (array)$joins;
        
        foreach ($joins as $joinItem) {
            if (is_string($joinItem)) {
                $joinResult .= " {$joinItem} ";
            } else {
                if(count($joinItem) < 3 || !is_string($joinItem[0]) || !is_string($joinItem[1]) || !is_string($joinItem[2])) {
                    throw new \wf\db\Exception('Error join option!');
                }
                $joinType = empty($joinItem[3]) ? 'LEFT' : $joinItem[3];
                $joinResult .= static::buildOptionJoin($joinItem[0], $joinItem[1], $joinItem[2], $joinType);
            }
        }
        
        return $joinResult;
    }
    
    /**
     * 
     * @param string $table
     * @param string $onFieldA
     * @param string $onFieldB
     * @param string $joinType
     */
    protected static function buildOptionJoin($table, $onFieldA, $onFieldB, $joinType = 'LEFT')
    {
        $table    = QueryBuilder::quoteFieldNames($table);
        $onFieldA = QueryBuilder::quoteFieldNames($onFieldA);
        $onFieldB = QueryBuilder::quoteFieldNames($onFieldB);
        
        $joinResult = " {$joinType} JOIN {$table} ON {$onFieldA} = {$onFieldB} ";
        
        return $joinResult;
    }

    /**
     * 
     * @param string $group
     * @return string
     */
    protected static function buildOptionGroup($group)
    {
        return empty($group) ? '' : ' GROUP BY ' . QueryBuilder::quoteFieldNames($group);
    }
    
    /**
     * 
     * @param string|array $having
     * @return string
     */
    protected static function buildOptionHaving($having)
    {
        if (empty($having)) {
            $result = '';
        } elseif (is_string($having)) {
            $result = ' HAVING ' . $having;
        } else {
            $result = ' HAVING ' .QueryBuilder::whereArr((array)$having);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param string $order
     * @return string
     */
    protected static function buildOptionOrder($order)
    {
        $order = trim($order);
        
        if(empty($order)) {
            return '';
        }
        
        $order = preg_replace("/([^a-z0-9_\\.\\,\\s\\(\\)]+)/i", '', $order);
        $order = preg_replace_callback("/([a-z0-9_^\\(\\)]+)/i", 'static::quoteOrder', $order);
        
        return ' ORDER BY ' . $order;
    }
    
    /**
     * 
     * @param string $limit
     * @return string
     */
    protected static function buildOptionLimit($limit)
    {
        return empty($limit) ? '' : ' LIMIT ' . preg_replace("/[^0-9,]/", '', $limit);
    }

    /**
     * 获取查询条件的SQL语句
     * @param array $options
     * @return string
     */
    public static function optionsToSql($options)
    {
        $options = QueryBuilder::buildQueryOptions($options);
        
        $sql = "SELECT {$options['field']}
                FROM {$options['table']}
                {$options['join']}
                {$options['where']}
                {$options['group']}
                {$options['having']}
                {$options['order']}
                {$options['limit']}";
        
        return $sql;
    }
    
    /**
     * 获取符合查询选项的记录数的SQL语句
     * @param array $options
     * @return string
     */
    public static function optionsToCountSql($options)
    {
        $options = QueryBuilder::buildQueryOptions($options);

        $sql = "SELECT COUNT({$options['field']})
                FROM {$options['table']}
                {$options['join']}
                {$options['where']}"; // no group|having
        
        return $sql;
    }
    
    /**
     * 从数组的下标对应的值中获取SQL的"字段1=值1,字段2=值2"的结构
     * 
     * @param array $data 下标 => 值结构
     * @param array $keyInArray 包含此数组中 的下标则保留，否则去掉
     * @param array $keyNotInArray = [] 要去掉的下标
     * @throws \wf\db\Exception
     * @return string 返回 "`f1` = 'xx', `f2` = 'xxx'"
     */
    public static function buildSqlSet(array $data, array $keyInArray, array $keyNotInArray = [])
    {
        $set = [];
        $arg = [];
        $fields = $keyNotInArray ? array_diff($keyInArray, $keyNotInArray) : $keyInArray;
    
        // 取表中存在的字段（MySQL字段名本身不区分大小写，我们全部转成小写）
        foreach($data as $k => $v) {
            $k = strtolower($k);
            if (!in_array($k, $fields)) {
                continue;
            }
                
            if (is_array($v)) {
                $v = serialize($v);
            } 
            
            // 字段值为null将不做写入属性，如需写入，把值设为空字符 ''
            if ($v === null) {
                $set[] = " %c = null ";
                $arg[] = $k;
            } else {
                $set[] = " %c = %s ";
                $arg[] = $k;
                $arg[] = $v;
            }
        }
    
        if (!$set || !$arg) {
            throw new \wf\db\Exception('请传入正确的数据');
        }
    
        $sets  = join(',', $set);
    
        return QueryBuilder::format($sets, $arg);
    }
    
}

