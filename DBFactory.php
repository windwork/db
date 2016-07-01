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
 * 静态创建数据库访问类实例工厂类
 * 
 * @package     wf.db
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.db.html
 * @since       0.1.0
 */
final class DBFactory {
	/**
	 * 
	 * @var array
	 */
	private static $instance = array();
	
	/**
	 * 创建数据库访问组件实例
	 * @param array $cfg
	 * <pre>
	 * 数据库连接设置参数案例：
	 * $cfg = array(
	 *   // 
	 *   'default' => array(
	 *     'db_host'                 => '127.0.0.1',        // 本机测试
	 *     'db_port'                 => '3306',             // 数据库服务器端口
	 *     'db_name'                 => 'windworkdb',   // 数据库名
	 *     'db_user'                 => 'root',             // 数据库连接用户名
	 *     'db_pass'                 => '123456',           // 数据库连接密码
	 *     'db_table_prefix'         => 'wk_',              // 表前缀
	 *     'db_debug'                => 0,
	 *   ),
	 *   // 可主从分离
	 *   'slave' => array(
	 *     'db_host'                 => '127.0.0.1',        // 本机测试
	 *     'db_port'                 => '3306',             // 数据库服务器端口
	 *     'db_name'                 => 'windworkdb',   // 数据库名
	 *     'db_user'                 => 'root',             // 数据库连接用户名
	 *     'db_pass'                 => '123456',           // 数据库连接密码
	 *     'db_table_prefix'         => 'wk_',              // 表前缀
	 *     'db_debug'                => 0,
	 *   ),
	 * );
	 * </pre>
	 * @param string $connectId = 'default'
	 * @return \wf\db\IDB
	 */
	public static function create(array $cfgs, $connectId = 'default') {		
		// 如果该类实例未初始化则创建
		if(empty(static::$instance[$connectId])) {
			if (!isset($cfgs[$connectId])) {
				throw new Exception("不存在的数据库连接配置组（{$connectId}）");
			}
			
			$cfg = $cfgs[$connectId];
			
			// 默认使用 PDOMySQL来操作MySQL
		    $adapter = empty($cfg["db_adapter"]) ? 'PDOMySQL' : $cfg["db_adapter"];
		    
			$class = "\\wf\\db\\adapter\\{$adapter}";
			static::$instance[$connectId] = new $class($cfg);
		}
		
		return static::$instance[$connectId];
	}
}


