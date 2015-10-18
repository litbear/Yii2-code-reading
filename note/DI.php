app\models\UserFinderInterface
app\models\UserFinder
app\models\UserLister
三个类(接口)是研究以来注入的示例，源代码为
<?php 
namespace app\models;

use yii\base\Object;
use yii\db\Connection;

// 定义接口
interface UserFinderInterface
{
    function findUser();
}

// 定义类，实现接口
class UserFinder extends Object implements UserFinderInterface
{
    public $db;

    // 从构造函数看，这个类依赖于 Connection
    public function __construct(Connection $db, $config = [])
    {
        $this->db = $db;
        parent::__construct($config);
    }

    public function findUser()
    {
    }
}

class UserLister extends Object
{
    public $finder;

    // 从构造函数看，这个类依赖于 UserFinderInterface接口
    public function __construct(UserFinderInterface $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($config);
    }
}
?>

则有依赖关系：
UserLister --> UserFinderInterface(指定UserFinder 实现) -->  yii\db\Connection
使用如下方式注入依赖

<?php
use yii\di\Container;

// 创建一个DI容器
$container = new Container;

// 为Connection指定一个数组作为依赖，当需要Connection的实例时，
// 使用这个数组进行创建
$container->set('yii\db\Connection', [
    'dsn' => 'sqlite:C:\foobar\testdrive.db',
    'charset' => 'utf8',
    'tablePrefix' => 'tbl_',
]);

// 在需要使用接口 UserFinderInterface 时，采用UserFinder类实现
$container->set('app\models\UserFinderInterface', [
    'class' => 'app\models\UserFinder',
]);

// 为UserLister定义一个别名
$container->set('userLister', 'app\models\UserLister');

// 获取这个UserList的实例
$lister = $container->get('userLister');

?>

set()过程中只维护了三个变量
$_singletons $_definitions 和 $_params
打印调试信息之后发现：
<?php
Container::$_definitions = 
[
    'yii\db\Connection' => 
        [
            'dsn' => 'sqlite:C:\foobar\testdrive.db',
            'charset' => 'utf8',
            'tablePrefix' => 'tbl_',
            'class' => 'yii\db\Connection'
        ],

    'app\models\UserFinderInterface' => 
        [
            'class' => 'app\models\UserFinder'
        ],

    'userLister' => 
        [
            'class' => 'app\models\UserLister'
        ]
];

/**
 * 因为set()的第三个参数都是空的
 */
Container::$_params = 
[
    'yii\db\Connection' => [],
    'app\models\UserFinderInterface' => [],
    'userLister' => []
];

//get()方法执行后开始维护的属性

Container::$__dependencies = 
[
    'app\models\UserLister' => [
        0 => object(yii\di\Instance){ public 'id' = 'app\models\UserFinderInterface'; },
        1 => []
    ],
    'app\models\UserFinder' => [
        0 => object(yii\di\Instance){ public 'id' => 'yii\db\Connection'; },
        1 => []
    ],
    'yii\db\Connection' => [
        0 => []
    ]
];
?>

<?php
    // 重头到尾的执行逻辑
    $container->get('userLister');
    // get($class, $params = [], $config = [])内部 
    elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            // 'userLister' 与 'app\models\UserLister' 相等 开始递归
            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                // $this->get('app\models\UserLister', [], []);
                $object = $this->get($concrete, $params, $config);
            }
        }

    // 进入build()方法构建依赖 
    elseif (!isset($this->_definitions['app\models\UserLister'])) {
            return $this->build('app\models\UserLister', $params, $config);
    }

    list ($reflection, $dependencies) = $this->getDependencies('app\models\UserLister');
    // 进入getDependencies()方法 由于$this->_reflections['app\models\UserLister']为空（还没被缓存）
    // 所以开始执行反射 反射类探测出了'app\models\UserLister'类的构造方法
    // public function __construct(UserFinderInterface $finder, $config = []) 需要两个参数，于是 
    // 向$this->_reflections['app\models\UserLister'] ; $this->_dependencies['app\models\UserLister'] ;
    // 压入缓存 并返回 —— 跳出getDependencies() 回到build()
    // 获取到依赖如下
    $this->_dependencies['app\models\UserLister'] = [
        0 => object(yii\di\Instance){ public 'id' = 'app\models\UserFinderInterface'; },
        1 => []
    ]
    // 回到build()方法中
    $dependencies = $this->resolveDependencies($dependencies, $reflection);
    // 进入resolveDependencies()中
    if ($dependency->id !== null) {
        // 进入递归
        $dependencies[$index] = $this->get('app\models\UserFinderInterface');
    }
    // 再次进入get()函数中$this->get('app\models\UserFinderInterface')
    // 在这里判断 ($concrete)'app\models\UserFinder' !== ($class)'app\models\UserFinderInterface'
    // 就是前面set()指定的依赖：'app\models\UserFinderInterface'接口 要指定 'app\models\UserFinder'
    // 这个实现方法 所以要再次递归 用get('app\models\UserFinder')去实例化这个方法
    elseif (is_array($definition)) {
        $concrete = $definition['class'];
        unset($definition['class']);
        $config = array_merge($definition, $config);
        $params = $this->mergeParams($class, $params);

        if ($concrete === $class) {
            $object = $this->build($class, $params, $config);
        } else {
            $object = $this->get('app\models\UserFinder', $params, $config);
        }
    }
    //再次进入get('app\models\UserFinder')方法
    elseif (!isset($this->_definitions['app\models\UserFinder'])) {
        return $this->build('app\models\UserFinder', $params, $config);
    }
    // 进入build()方法
    list ($reflection, $dependencies) = $this->getDependencies('app\models\UserFinder');
    // 再进入getDependencies('app\models\UserFinder')函数里 得到 妈的。快疯了
    $this->_dependencies['app\models\UserFinder'] = [
        0 => object(yii\di\Instance){ public 'id' = 'yii\db\Connection'; },
        1 => []
    ]
    // 回到build()方法
    $dependencies = $this->resolveDependencies($this->_dependencies['app\models\UserFinder'], $reflection);
    // 再次进入resolveDependencies($this->_dependencies['app\models\UserFinder'], $reflection)方法
    // 写到这里大概已经看懂了 不过抽时间结合 http://www.digpage.com/di.html 再把它完成吧
    // 自带编辑器搞不了markdown内php语法块的高量就不搞成md格式了 凑合看吧
?>