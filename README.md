Windwork MySQL数据库访问组件
====================================
可通过选择不同的驱动连接MySQL查询。

## 数据库读写

### 数据库实例创建
```
// 在config/app.php设置数据库参数
// 获取数据库操作实例
$db = wfDb();
```

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
     * @param array $fieldArr  允许插入的字段名
     * @param string $isReplace = false 是否使用 REPLACE INTO插入数据，false为使用 INSERT INTO
     */
    public function insertRows(array $rows, $table, $fieldArr = [], $isReplace = false);
    
```

使用案例
```
$dbCfgs = [
    // 数据库设置
    'host'           => '127.0.0.1',   // 本机测试
    'port'           => '3306',        // 数据库服务器端口
    'name'           => 'windworkdb',  // 数据库名
    'user'           => 'root',        // 数据库连接用户名
    'pass'           => '123456',      // 数据库连接密码
    'tablePrefix'    => 'wk_',         // 表前缀
    'debug'          => 0,
    'class'          => 'PDOMySQL',    // MySQLi|PDOMySQL
];
$class = "\\wf\\db\\strategy\\{$dbCfg['class']}";
$db = new $class($dbCfgs);

// 获取所有记录
$rows = $db->getAll("SELECT * FROM my_table");


// 获取一条记录
$row = $db->getRow("SELECT * FROM my_table LIMIT 1");


// 获取一行中的第一列
$column = $db->getColumn("SELECT * FROM my_table LIMIT 1");


// 执行sql
$db->exec("INSERT INTO my_table (f1, f2) VALUE ('fff1', 'ffff2')");
```

## SQL防注入
我们通过sql格式化以后，可有效防注入。

以%作为标识，%后面的字符为格式化参数的数据类型。支持的类型有：
- %t：表名(Table)，将被进行数据表名称反注入处理； 
- %c：字段名(Column)，将被进行数据表字段名反注入处理；  
- %n：数字值(Number)，将被过滤掉非数字和.的字符；
- %i：整形(Int)，将被强制转换为整形；
- %f：浮点型(Float)，将被强制转换为浮点型； 
- %s：字符串值(String)，将被进行字符串值反注入处理; 
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

可以嵌套启用事务，最终只在最上一级事务提交后才会真正执行事务。

使用事务的案例：

```

try {
    // 开启事务
    $trans = wfDb()->beginTransaction();

    // 数据库写入业务代码
    // ……

    // 没异常则提交事务
    $trans->commit();
} catch(\Exception $e) {
    // 出现异常则回滚事务
    $trans->rollback();
}

```
<a name="config"></a>
## 数据库连接配置参数

```
// config/db.php
return [
    // 主数据库
    'default' => [
        'class'          => '\\wf\\db\\strategy\\PDOMySQL',    // MySQLi/PDOMySQL
        'host'           => '127.0.0.1',   // 本机测试
        'port'           => '3306',        // 数据库服务器端口
        'name'           => 'windworkdb',  // 数据库名
        'user'           => 'root',        // 数据库连接用户名
        'pass'           => '123456',      // 数据库连接密码
        'tablePrefix'    => 'wk_',         // 表前缀
        'debug'          => 0,
    ],
    
    // 主从分离，启用后，从slave读，从default写
    /*
    'slave' => array(
        // 数据库设置
        'class'          => '\\wf\\db\\strategy\\PDOMySQL',
        'host'           => '127.0.0.1',   // 本机测试
        'port'           => '3306',        // 数据库服务器端口
        'name'           => 'windworkdb',  // 数据库名
        'user'           => 'root',        // 数据库连接用户名
        'pass'           => '123456',      // 数据库连接密码
        'tablePrefix'    => 'wk_',         // 表前缀
        'debug'          => 0,
    ),
    */
];
```

## TODO
- 完善使用文档
- 改进文档注释


<br />  
<br />  

### 要了解更多？  
> - [官方完整文档首页](http://docs.windwork.org/manual/)  
> - [官方源码首页](https://github.com/windwork)  
