Windwork MySQL数据库访问组件
====================================
可通过选择不同的驱动连接MySQL查询。

## 数据库读写

数据库操作对象可执行如下方法进行数据库读写：

```	
	/**
	 * 获取所有记录
	 * 
	 * @param string $sql
	 * @param array $args = [] sql格式化参数值列表
	 */
	public function getAll($sql, array $args = []);
	
	/**
	 * 获取第一列
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表
	 */
	public function getRow($sql, array $args = []);
			
	/**
	 * 获取第一列第一个字段
	 * 
	 * @param string $sql
	 * @param array $args =[]  sql格式化参数值列表
	 */
	public function getColumn($sql, array $args = []);

	/**
	 * 执行写入SQL
	 * 针对没有结果集合返回的写入操作，
	 * 比如INSERT、UPDATE、DELETE等操作，它返回的结果是当前操作影响的列数。
	 * 
	 * @param string $sql
	 * @param array $args = []  sql格式化参数值列表
	 * @throws \wf\db\Exception
	 * @return int
	 */
	public function exec($sql, array $args = []);
	
	/**
	 * 取得上一步 INSERT 操作产生的 ID
	 *
	 * @return string 
	 */
	public function lastInsertId();
	
	/**
	 * 插入多行数据
	 * 过滤掉没有的字段
	 *
	 * @param array $rows
	 * @param string $table  插入表
	 * @param array $fields  允许插入的字段名
	 * @param string $isReplace = false 是否使用 REPLACE INTO插入数据，false为使用 INSERT INTO
	 */
	public function insertRows(array $rows, $table, $fields = [], $isReplace = false);
	
```

使用案例
```
$dbCfgs =  [
    'default' => [
		// 数据库设置
		'db_host'           => '127.0.0.1',   // 本机测试
		'db_port'           => '3306',        // 数据库服务器端口
		'db_name'           => 'windworkdb',  // 数据库名
		'db_user'           => 'root',        // 数据库连接用户名
		'db_pass'           => '123456',      // 数据库连接密码
		'db_table_prefix'   => 'wk_',         // 表前缀
		'db_debug'          => 0,
		'db_adapter'        => 'PDOMySQL',    // MySQLi|PDOMySQL
    ]
];
$db = \wf\db\DBFactory::create($dbCfgs, 'default');

// 获取所有记录
$rows = $db->getAll("SELECT * FROM my_table");


// 获取一条记录
$row = $db->getRow("SELECT * FROM my_table LIMIT 1");


// 获取一行中的第一列
$column = $db->getColumn("SELECT * FROM my_table LIMIT 1");


// 执行sql
$db->exec("INSERT INTO my_table (f1, f2) VALUE ('fff1', 'ffff2')");
```

## 防注入
我们通过sql格式化以后，可有效防注入。

以%作为标识，%后面的字符为格式化参数的数据类型。支持的类型有：
- %t：表名； 
- %a：字段名；  
- %n：数字值；
- %i：整形；
- %f：浮点型； 
- %s：字符串值; 
- %x：保留不处理

例如：
```
$sql = 'SELECT %f FROM %t WHERE uid > %i AND uname LIKE %s';
$arg = ['nickname, uid, email', 'user', 5, '%马%'];
$db->getAll($sql, $arg); 
// 执行的SQL被格式化为如下SQL
// SELECT `nickname`, `uid`, `email` FROM `user` WHERE uid > 5 AND uname LIKE '%马%'
```

## 使用事务
使用事务的前提是：你使用的引擎必须支持事务。MyISAM、MEMORY引擎不支持事务，InnoDB引擎支持事务。MySQL经过多年的发展，InnoDB引擎已经是MySQL引擎中最有优势的引擎，所以推荐你优先使用InnoDB引擎。

- Windwork 模型基类中默认已启用事务，不需要另外启用。
- 可以嵌套启用事务，最终只在最上一级事务提交后才会真正执行事务。


## TODO
- 完善使用文档
- 改进文档注释