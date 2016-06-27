<?php
namespace wf\db;

class Query {
	protected $cdt = [];
	
	/**
	 * @return array
	 */
	public function getCdt() {
		return $this->cdt;
	}
	
	/**
	 * 字段名列表，默认是 *，如：f.a, f.b
	 * @param string $fields
	 * @return \wf\db\Query
	 */
	public function fields($fields = '*') {
		$this->cdt['fields'] = $fields;
		
		return $this;
	}
	
	/**
	 * 查询的表名
	 * 可以是多个表，默认是当前模型的表，table_a, table_b AS b
	 * @param string $table
	 * @return \wf\db\Query
	 */
	public function table($table) {
		$this->cdt['table'] = $table;
		
		return $this;
	}
	
	/**
	 * 连接表，
	 * 如： LEFT JOIN table_name ON field_a = field_b
	 * @param string $join
	 * @return \wf\db\Query
	 */
	public function join($join) {
		$this->cdt['join'] = $join;
		
		return $this;
	}
	
	/**
	 * 
	 * 构造sql多个查询条件
	 * 
	 * <div>
	 *   <b>规则：</b>查询条件有两部分构成
	 *   <ul>
	 *     <li>一个是查询条件之间的逻辑关系 AND|OR 字符，这个不是必须的。如果指定and/or，必须放在数组的第一位，即下标为0。</li>
	 *     <li>一个是查询元素（比较表达式）， array('字段', '值', '比较逻辑 = > < ...')</li>
	 *   </ul>
	 * </div>
	 * <b>例如，允许格式如下：</b>
	 * <ul>
	 *     <li>一个条件 $array = array('field', 'val', 'glue', 'type')</li>
	 *     <li>多个条件，不指定and/or的条件 $array = array(array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
	 *     <li>多个条件，指定and/or的条件$array = array('and', array('field', 'val', 'glue'), array('field', 'val', 'glue'), ...)</li>
	 *     <li>$array = array('and|or', array('field', 'val', 'glue'), array('and|or', array('field1', 'val1', 'glue1'), array('field2', 'val2', 'glue2'), ...), array('field3', 'val', 'glue'), ...);</li>
	 * </ul>
	 * 
	 * @param array $where 查询条件 
	 * <pre>
	 *   array(
	 *   0 => '查询逻辑，and|or，不设置该项则查询条件默认使用AND关系', 
	 *   1 => array('字段1', '值', '比较方式，默认=，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=', '参数值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句'), 
	 *   2 => array('字段12', '值', '比较方式', '格式', '参数值的类型'), 
	 *   ...)
	 * </pre>
	 * @return \wf\db\Query
	 */
	public function where($where) {
		$this->cdt['where'] = $where;
		
		return $this;
	}
	
	/**
	 * 对$order参数进行SQL注入过滤后，在前面加上ORDER BY
	 * 
	 * @param string $order
	 * @return \wf\db\Query
	 */
	public function order($order) {
		$this->cdt['order'] = $order;
		
		return $this;
	}
	
	/**
	 * 对$group参数进行SQL注入过滤后，在前面加上GROUP BY
	 * 
	 * @param string $group
	 * @return \wf\db\Query
	 */
	public function group($group) {
		$this->cdt['group'] = $group;
		
		return $this;
	}
	
	/**
	 * 数组结构，格式同where
	 * 对$having参数进行SQL注入过滤后，在前面加上 HAVING
	 * @param string $having
	 * @return \wf\db\Query
	 */
	public function having($having) {
		$this->cdt['having'] = $having;
		
		return $this;
	}
	
}

