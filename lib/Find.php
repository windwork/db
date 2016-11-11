<?php
namespace wf\db;

/**
 * 查询选项构造器
 *
 * @package     wf.db
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.query.html
 * @since       0.1.0
 */
class Find implements \ArrayAccess {
	/**
	 * 
	 * @var \wf\db\IDB
	 */
	protected $db;
	
	/**
	 * 
	 * @var array
	 */
	protected $options = [
		'fields'  => '*',
		'table'   => '',
		'join'    => [],
		'where'   => [],
		'group'   => '',
		'having'  => '',
		'order'   => '',
		'limit'   => '',
	];
	
	public function __construct(array $options = []) {
		$this->options = $options;
	}
	
	/**
	 * 
	 * @param \wf\db\IDB $db
	 * @return \wf\db\Find
	 */
	public function setDb(\wf\db\IDB $db) {
		$this->db = $db;
		return $this;
	}
	
	/**
	 * 
	 * @return \wf\db\IDB
	 */
	public function getDb() {
		return $this->db;
	}
	
	/**
	 * 获取符合条件的所有记录
	 * 
	 * @param int $offset
	 * @param int $rows
	 */
	public function all($offset = null, $rows = null) {
		$opts = $this->options;
		
		if ($offset !== null && $rows !== null) {
			$opts['offset'] = "{$offset}, {$rows}";
		}
		
		$sql = QueryHelper::optionsToSql($opts);
		$all = $this->getDb()->getAll($sql);
		
		return $all;
	}
	
	/**
	 * 获取一行记录，返回关联数组格式
	 * 
	 * @return array
	 */
	public function row() {
		$sql = QueryHelper::optionsToSql($this->options);
		$row = $this->getDb()->getRow($sql);
		
		return $row;		
	}
	
	/**
	 * 获取字段值
	 * @param string $field
	 * @return scalar
	 */
	public function column($field = '') {
		$opts = $this->options;
		if ($field) {
			$opts['fields'] = $field;
		}
		
		$sql = QueryHelper::optionsToSql($opts);
		$col = $this->getDb()->getColumn($sql);
		
		return $col;
	}
	
	/**
	 * 获取记录数
	 * @param string $field
	 * @return int
	 */
	public function count($field = '') {
		$opts = $this->options;
		if ($field) {
		    $opts['fields'] = $field;
		}
		
		$sql = \wf\db\QueryHelper::optionsToCountSql($opts);
		$num = $this->getDb()->getColumn($sql);
		
		return $num;
		
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}
	
	/**
	 * 字段名列表，默认是 *，如：f.a, f.b
	 * @param string $fields
	 * @return \wf\db\Find
	 */
	public function fields($fields = '*') {
		$this->options['fields'] = $fields;
		
		return $this;
	}
	
	/**
	 * 查询的表名
	 * 可以是多个表，默认是当前模型的表，table_a, table_b AS b
	 * @param string $table
	 * @return \wf\db\Find
	 */
	public function from($table) {
		$this->options['table'] = $table;
		
		return $this;
	}
	
	/**
	 * 连接表，
	 * 如： LEFT JOIN table_name t ON t.field_a = tb.field_b
	 *     RIGHT JOIN table_name t ON t.field_a = tb.field_b
	 *     INNER JOIN table_name t ON t.field_a = tb.field_b
	 * @param string $join
	 * @param bool $reset = false
	 * @return \wf\db\Find
	 */
	public function join($join, $reset = false) {
		if ($reset) {
			$this->options['join'] = [];
		}
		$this->options['join'][] = $join;
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
	 *   [
	 *     0 => '查询逻辑，and|or，不设置该项则查询条件默认使用AND关系', 
	 *     1 => array('字段1', '值', '比较方式，默认=，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=', '参数值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句'), 
	 *     2 => array('字段12', '值', '比较方式', '格式', '参数值的类型'), 
	 *     ...
	 *   ]
	 * </pre>
	 * @return \wf\db\Find
	 */
	public function where($where) {
		$this->options['where'][] = $where;
		
		return $this;
	}
	
	/**
	 * 同where方法
	 * @see \wf\db\Find::where()
	 */
	public function andWhere($where) {
		$this->where($where);
		
		return $this;
	}
	
	/**
	 * 
	 * @param array $where 查询条件 
	 * <pre>
	 *   array(
	 *     0 => '查询逻辑，and|or，不设置该项则查询条件默认使用AND关系', 
	 *     1 => array('字段1', '值', '比较方式，默认=，可选=,+,-,|,&,^,like,in,notin,>,<,<>,>=,<=,!=', '参数值的类型，默认是string，string：字符串，field：字段，int：整形，float：浮点型，sql：sql语句'), 
	 *     2 => array('字段12', '值', '比较方式', '格式', '参数值的类型'), 
	 *   ...)
	 * </pre>
	 */
	public function orWhere($where) {
		if ($this->options['where']) {
			$where = [
				'OR',
				$this->options['where'],
				$where
			];
			$this->options['where'] = [];
		}
		
		$this->options['where'][] = $where;
		return $this;
	}
	
	/**
	 * 对$order参数进行SQL注入过滤后，在前面加上ORDER BY
	 * 
	 * @param string $order
	 * @return \wf\db\Find
	 */
	public function order($order) {
		$this->options['order'] = $order;
		
		return $this;
	}
	
	/**
	 * 对$group参数进行SQL注入过滤后，在前面加上GROUP BY
	 * 
	 * @param string $group
	 * @return \wf\db\Find
	 */
	public function group($group) {
		$this->options['group'] = $group;
		
		return $this;
	}
	
	/**
	 * 数组结构，格式同where
	 * 对$having参数进行SQL注入过滤后，在前面加上 HAVING
	 * @param string $having
	 * @return \wf\db\Find
	 */
	public function having($having) {
		$this->options['having'] = $having;
		
		return $this;
	}
	
	/**
	 * SQL分页查询
	 * @param number $offset
	 * @param number $rows
	 * @return \wf\db\Find
	 */
	public function limit($offset, $rows = 0) {
		$this->options['limit'] = QueryHelper::limit($offset, $rows);
		
		return $this;
	}


	//--- 以下为实现 ArrayAccess 接口的方法 -------------------------------------
	/**
	 * options下标是否存贮
	 * @param string $offset
	 */
	public function offsetExists($offset) {
		return isset($this->options[$offset]);
	}
	
	/**
	 * 根据下标获取options子元素值
	 * @param string $offset
	 */
	public function offsetGet($offset) {
		return $this->options[$offset];
	}
	
	/**
	 * 设置options子元素值
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->options[$offset] = $value;
	}
	
	/**
	 * unset options子元素
	 * @param string $offset
	 */
	public function offsetUnset($offset) {
		unset($this->options[$offset]);
	}
}

