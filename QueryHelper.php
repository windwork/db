<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
 * 
 * @copyright   Copyright (c) 2008-2015 Windwork Team. (http://www.windwork.org)
 * @license     http://opensource.org/licenses/MIT	MIT License
 */
namespace wf\db;

/**
 * SQL语句构造类
 *
 * @package     wf.db
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.sqlbuilder.html
 * @since       0.1.0
 */
class QueryHelper {
	
	/**
	 * 表前缀替换
	 *
	 * @param string $sql
	 * @param string $prefix
	 */
	public static function tablePrefix($sql, $prefix) {
		// sql中写表的前缀一律用 wk_, 运行的时候转换成配置文件中使用的前缀
		return preg_replace('/(\s+)wk_([0-9a-zA-Z_]+)(([,|\s]+)|$)/', ' ' . $prefix.'$2$3', $sql);
	}

	/**
	 * 获取总SQL查询的总记录数
	 *
	 * @param string $sql
	 * @return int
	 */
	public static function getTotalsSQLFromQuery($sql) {
		return preg_replace('/SELECT([^from].*) FROM /i', "SELECT COUNT(*) as count FROM ", $sql);
	}
	
	/**
	 * 变量进行注入转义并加上引号
	 * 
	 * @param mixed $str
	 * @param bool $allowArray
	 * @return string
	 */
	public static function quote($str, $allowArray = true) {
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
					$v = static::quote($v, true);
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
	public static function quoteField($field) {
		$field = trim($field);
		if(!$field) {
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
	public static function quoteFields($fields) {
		$fieldArr = is_string($fields) ? explode(',', trim($fields)) : (array)$fields;
		
		foreach ($fieldArr as $k => $field) {
			$field = preg_replace("/(\s+)/", ' ', trim($field));
			if ($field == '*') {
				// do nothing
			} elseif (strpos($field, '(')) {
				$field = preg_replace_callback(
					"/\\((.*?)\\)/i", 
					function($match) {
					    return '('.QueryHelper::quoteField($match[1]).')';
				    },				    
				    $field
	            );
				$field = preg_replace_callback(
					"/\\)(.+)/i", 
					function($match) {
					    return ') '.QueryHelper::quoteField($match[1]);
				    }, 
				    $field
	            );

				$fieldArr[$k] = $field;
			} else {
				$fieldArr[$k] = QueryHelper::quoteField($field);
			}
		}
		
		$fields = implode(',', $fieldArr);
		
		
		return $fields;
	}
	
	/**
	 * 分页查询
	 * 
	 * @param int $offset
	 * @param int $rows
	 * @return string
	 */
	public static function limit($offset, $rows = 0) {
		$rows   = (int)($rows > 0 ? $rows : 0);
		$offset = (int)($offset > 0 ? $offset : 0);
		
		if ($offset && $rows) {
			return " LIMIT $offset, $rows";
		} elseif ($rows) {
		    return " LIMIT $rows";
		} elseif ($offset) {
		    return " LIMIT $offset";
		} else {
		    return '';
		}
	}
	
	/**
	 * 构造排序条件，可以是多个排序条件
	 * 
	 * @param string $order
	 * @return string
	 */
	public static function order($order = '') {
		$order = trim($order);
		
		if(empty($order)) {
		    return '';
		}

		$order = preg_replace("/([^a-z0-9_\\.\\,\\s\\(\\)]+)/i", '', $order);
		$order = preg_replace_callback("/([a-z0-9_^\\(\\)]+)/i", 'static::quoteOrder', $order);
		
		return $order;
	}
	
	/**
	 * 供 QueryHelper::order()调用
	 * @param string $str
	 * @return string
	 * @throws \wf\db\Exception
	 */
	protected static function quoteOrder($str) {
		if ($str && is_array($str) && isset($str[1])) {
			$str = $str[1];
		}
		
		if ($str && !is_string($str)) {
			throw new \wf\db\Exception('Order fields must be string!');
		}
		
		if ($str && strtolower($str) != 'asc' && strtolower($str) != 'desc') {
			$str = static::quoteFields($str);
		}
		
		return $str;
	}
	
	/**
	 * 查询条件
	 * 
	 * @param string $field  字段名 
	 * @param string|array $val 值，使用in/notin的时候为array类型
	 * @param string $glue =,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=
	 * @param string $type $val参数值的类型，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句
	 * @throws \wf\db\Exception
	 * @return string
	 */
	public static function where($field, $val, $glue = '=', $type = 'string') {
		$glue = strtolower($glue);
		$glue = str_replace(' ', '', $glue);
		
		$field = static::quoteFields($field);
		
		if (is_array($val)) {
		    $glue = $glue == 'notin' ? 'notin' : 'in';
		} elseif ($type != 'sql' && ($glue == 'in' || $glue == 'notin')) {
		    $glue = '=';
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
		$val = $type == 'field' ? static::quoteFields($val) : static::quote($val);
		
		switch ($glue) {
			case '=':
			    return $field . $glue . $val;
			    break;
			case '-':
			case '+':
			    return $field . '=' . $field . $glue . $val;
			    break;
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
	 *     <li>一个条件 $array = array('field', 'val', 'glue', 'type')</li>
	 *     <li>多个不指定and/or的条件 $array = array(array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
	 *     <li>多个指定and/or的条件$array = array('and', array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
	 *     <li>$array = array('and|or', array('field', 'val', 'glue'), array('and|or', array('field1', 'val1', 'glue1'), array('field2', 'val2', 'glue2'), ...), array('field3', 'val', 'glue'), ...);</li>
	 * </ul>
	 * 
	 * @param array $array 查询条件 array('and|or', array('字段1', '值', '=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!='), array('字段1', '值', '逻辑'), ...)
	 * @throws \wf\db\Exception
	 * @return string
	 */
	public static function whereArr($array) {
		if (!is_array($array)) {
			throw new \wf\db\Exception('Illegal param, the param should be array, but string has given: $array = ' . var_export($array, 1));
		}

		// 如果参数格式是如下，第一个元素是数组：
		// $array = array(array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)
		// $array = array(array('field', 'val', 'glue'), array('and', array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...), ...)
		if (isset($array[0]) && is_array($array[0])) {
			$r = '';
			$v0 = 'AND'; // 没有指定关系逻辑则使用and
			$andOr = false;
			foreach ($array as $item) {
				if (!is_array($item)){
					continue;
				}
				
				$andOr && $r .= " $v0 ";
				$r .= static::whereArr($item);
				$andOr = true;
			}
			$r && $r = " ({$r}) ";
			return $r;
		
		} else if(isset($array[0]) && is_string($array[0])) {
			$v0 = strtoupper(trim($array[0]));
			// 如果参数格式如下：
			// $array = array('and|or', array('field', 'val', 'glue', 'type'), array('field2', 'val', 'glue'), ...);
			// $array = array('and|or', array('and|or', array('field1', 'val1', 'glue1'), array('field2', 'val2', 'glue2'), ...), array('field3', 'val', 'glue'), ...);
			if($v0 == 'AND' || $v0 == 'OR') {
				unset($array[0]);
				$r = '';
				$andOr = false;
				foreach ($array as $item) {
					if(is_string($item[0]) && (strtoupper(trim($item[0])) == 'AND' || strtoupper(trim($item[0])) == 'OR')) {
						$andOr && $r .= " $v0 ";
						$r .= static::whereArr($item);
					} elseif (!is_array($item)){
						continue;
					} else {
						empty($item[2]) && $item[2] = '=';
						isset($item[3]) || $item[3] = '';
						$andOr && $r .= " $v0 ";
						$r .= static::where($item[0], $item[1], $item[2], $item[3]);
					}
			
					$andOr = true;
				}
			
				$r && $r = " ({$r}) ";
			
				return $r;
			}
			
			// 如果参数格式是： $array = array('field', 'val', 'glue')
			else if($v0 != 'AND' && $v0 != 'OR' && isset($array[1]) && (empty($array[2]) || is_scalar($array[2]))) {
				empty($array[2]) && $array[2] = '=';
				isset($array[3]) || $array[3] = '';
				return static::where($array[0], $array[1], $array[2], $array[3]);
			} else {
				throw new \wf\db\Exception('非法的where条件，请参阅\wf\db\ADB::whereArr()');
			}			
		} else {
	    	throw new \wf\db\Exception('非法的where条件，请参阅\wf\db\ADB::whereArr()');
	    }
	    
	}
		
	/**
	 * sql格式化
	 * 
	 * @param string $sql %t:表名； %a：字段名；  %n:数字值；%i：整形；%f：浮点型； %s：字符串值; %x:保留不处理
	 * @param array $arg
	 * @throws \wf\db\Exception
	 * @return string
	 */
	public static function format($sql, $arg) {
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
		if(preg_match('/%[^tanifsx]/', $sql, $m)) {
			throw new \wf\db\Exception('SQL string format error! Not allowed type (' . $m[0] . ') found.');
		}
		
		$ret = preg_replace_callback('/%([tanifsx])/i', function($matchs) use($arg) {
			static $find = 0;
			
			$m = $matchs[1];
			
			if ($m == 'a' || $m == 't') {
				$val = static::quoteFields($arg[$find]);
			} elseif ($m == 'n') {
				$val = preg_replace("/[^0-9\\.]/", '', $arg[$find]);
			} elseif ($m == 'i') {
				$val = (int)$arg[$find];
			} elseif ($m == 'f') {
				$val = (float)$arg[$find];
			} elseif ($m == 's') {
				$val = static::quote($arg[$find]);
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
	 *     'fields' =>'f.a, f.b', // 字段名列表，默认是 *
	 *     'table'  => 'table_a, table_b AS b', // 查询的表名，可以是多个表，默认是当前模型的表
	 *     'join'   => array(array('table_name', 'field_a', 'field_b'), arrray(), ..., "格式2直接写join语法"), // => LEFT JOIN `table_name` ON `field_a` = `field_b`
	 *     'where'  => array() // 查询条件 array('and|or', array('字段1', '值', '=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!='), array('字段1', '值', '逻辑'), ...)
	 *     'group'  => '', // 将对其进行SQL注入过滤并且在前面加上GROUP BY 
	 *     'having' => '', // 数组结构，格式同where，将对其进行SQL注入过滤并且在前面加上 HAVING
	 *     'order'  => '', // 将对其进行SQL注入过滤并且在前面加上 ORDER BY
	 * )</pre>
	 * @see \wf\db\ADB::whereArr()
	 * @throws \wf\db\Exception
	 * @return array
	 */
	public static function buildQueryOptions($options = array()) {
		if(!is_array($options)) {
			throw new \wf\db\Exception('The param must be array!');
		}
		
		$result = array();
		
		// fields
		$result['fields'] = empty($options['fields']) ? '*' : QueryHelper::quoteFields($options['fields']);
		
		// table
		$result['table'] = empty($options['table']) ? '' : QueryHelper::quoteFields($options['table']);
		
		// 'join' => array(array($table, $fieldA, $fieldB), ....)
		$result['join'] = '';
		if (!empty($options['join'])) {
			$options['join'] = (array)$options['join'];
			foreach ($options['join'] as $joinItem) {
				if (is_string($joinItem)) {
					$result['join'] .= " {$joinItem} ";
				} else {
					if(count($joinItem) < 3 || !is_string($joinItem[0]) || !is_string($joinItem[1]) || !is_string($joinItem[2])) {
						throw new \wf\db\Exception('Error join option!');
					}
					$fieldA = QueryHelper::quoteFields($joinItem[1]);
					$fieldB = QueryHelper::quoteFields($joinItem[2]);
					$result['join'] .= " LEFT JOIN " . QueryHelper::quoteFields($joinItem[0]) . " ON {$fieldA} = {$fieldB} ";
				}
			}
		}
		
		// where
		$result['where'] = empty($options['where']) ? '' : ' WHERE ' . QueryHelper::whereArr($options['where']);
		
		// group
		$result['group'] = empty($options['group']) ? '' : ' GROUP BY ' . QueryHelper::quoteFields($options['group']);
		
		// having
		$result['having'] = empty($options['having']) ? '' : ' HAVING ' . QueryHelper::whereArr($options['having']);

		// order
		$result['order'] = empty($options['order']) ? '' : ' ORDER BY ' . QueryHelper::order($options['order']);
				
		return $result;
	}
	
	/**
	 * 从数组的下标对应的值中获取SQL的"字段1=值1,字段2=值2"的结构
	 * 
	 * @param array $data 
	 * @param array $keyInArray
	 * @param array $keyNotInArray
	 * @throws Exception
	 * @return string 返回 "`f1` = 'xx', `f2` = 'xxx'"
	 */
	public static function buildSqlSet(array $data, array $keyInArray, array $keyNotInArray = array()) {
		$set = array();
		$arg = array();
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
				$set[] = " %a = null ";
				$arg[] = $k;
			} else {
				$set[] = " %a = %s ";
				$arg[] = $k;
				$arg[] = $v;
			}
		}
	
		if (!$set || !$arg) {
			throw new \wf\db\Exception('请传入正确的数据');
		}
	
		$sets  = join(',', $set);
	
		return QueryHelper::format($sets, $arg);
	}
	
}

