<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use ReflectionClass;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 *
 *  $container = new \yii\di\Container;
 *
 *  // 直接以类名注册一个依赖，虽然这么做没什么意义。
 *  // $_definition['yii\db\Connection'] = 'yii\db\Connetcion'
 *  $container->set('yii\db\Connection');
 *
 *  // 注册一个接口，当一个类依赖于该接口时，定义中的类会自动被实例化，并供
 *  // 有依赖需要的类使用。
 *  // $_definition['yii\mail\MailInterface' => ['class' => 'yii\swiftmailer\Mailer'] ]
 *  $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
 *
 *  // 注册一个别名，当调用$container->get('foo')时，可以得到一个
 *  // yii\db\Connection 实例。
 *  // $_definition['foo' => 'class' => ['yii\db\Connection'] ]
 *  $container->set('foo', 'yii\db\Connection');
 *
 *  // 用一个配置数组来注册一个类，需要这个类的实例时，这个配置数组会发生作用。
 *  // $_definition['yii\db\Connection'] = ['class' => 'yii\db\Connection' , 'foo' => 'bar' ...]
 *  $container->set('yii\db\Connection', [
 *      'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
 *      'username' => 'root',
 *      'password' => '',
 *      'charset' => 'utf8',
 *  ]);
 *
 *  // 用一个配置数组来注册一个别名，由于别名的类型不详，因此配置数组中需要
 *  // 有 class 元素。
 *  // $_definition['db'] = ['class' => 'yii\db\Connection' , 'foo' => 'bar' ...]
 *  $container->set('db', [
 *      'class' => 'yii\db\Connection',
 *      'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
 *      'username' => 'root',
 *      'password' => '',
 *      'charset' => 'utf8',
 *  ]);
 *
 *  // 用一个PHP callable来注册一个别名，每次引用这个别名时，这个callable都会被调用。
 *  // $_definition['db'] = function(...){...} (object(Closure))
 *  $container->set('db', function ($container, $params, $config) {
 *      return new \yii\db\Connection($config);
 *  });
 *
 *  // 用一个对象来注册一个别名，每次引用这个别名时，这个对象都会被引用。
 *  // $_definition['pageCache'] = anInstanceOfFileCache
 *  $container->set('pageCache', new FileCache);
 */

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * A dependency injection (DI) container is an object that knows how to instantiate and configure objects and
 * all their dependent objects. For more information about DI, please refer to
 * [Martin Fowler's article](http://martinfowler.com/articles/injection.html).
 * 依赖注入容器是一个用来解决如何实例化和配置对象，以及该对象所依赖的对象的类。
 *
 * Container supports constructor injection as well as property injection.
 * 该容器支持构造函数注入和属性注入
 *
 * To use Container, you first need to set up the class dependencies by calling [[set()]].
 * You then call [[get()]] to create a new class object. Container will automatically instantiate
 * dependent objects, inject them into the object being created, configure and finally return the newly created object.
 * 要使用这个函数  你首先需要使用set() 方法解决类的依赖，然后调用get()方法去创建一个新的对象，容器会自动的实例化该对
 * 对象以及该对象依赖的对象，并将依赖的对象注入其中，配置这个对象并且返回
 *
 * By default, [[\Yii::$container]] refers to a Container instance which is used by [[\Yii::createObject()]]
 * to create new object instances. You may use this method to replace the `new` operator
 * when creating a new object, which gives you the benefit of automatic dependency resolution and default
 * property configuration.
 * 默认情况下使用\Yii::createObject()代替new关键字实例化新对象 帮助你自动解决类的依赖和属性的配置
 *
 * Below is an example of using Container:
 * 以下是使用容器的例子
 *
 * ```php
 * namespace app\models;
 *
 * use yii\base\Object;
 * use yii\db\Connection;
 * use yii\di\Container;
 *
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder extends Object implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister extends Object
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('yii\db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     'class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * // which is equivalent to:
 *
 * $db = new \yii\db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 * ```
 *
 * @property array $definitions The list of the object definitions or the loaded shared objects (type or ID =>
 * definition or instance). This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Container extends Component
{
    /**
     * @var array singleton objects indexed by their types
     * 用于保存单例Singleton对象，以对象类型为键
     * 【类名接口名或别名】=>【类的实例，为null时表示该类尚未初始化】
     */
    private $_singletons = [];
    
    
    
    /**
     * @var array object definitions indexed by their types
     * 用于保存依赖的定义，以对象类型为键
     * 【类名接口名或别名】=>【一个具有class元素的数组，或是一个回调】
     */
    private $_definitions = [];
    
    
    
    /**
     * @var array constructor parameters indexed by object types
     * 用于保存构造函数的参数，以对象类型为键
     * 【类名接口名或别名】=>【一个数组，一般满足yii\base\Object对于构造函数参数的要求】
     */
    private $_params = [];
    
    
    
    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     * 用于缓存ReflectionClass对象，以类名或接口名为键
     * 【类名接口名或别名】=>【某个类的ReflectionsClass实例】
     */
    private $_reflections = [];
    
    
    
    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     * 用于缓存依赖信息，以类名或接口名为键
     * 【类名接口名或别名】=>【一个无下标数组，表示类的构造参数的类型】
     * - 当类的构造函数参数为基本数据类型的时候，数组元素为null
     * - 当类的构造函数参数为类类型的时候，数组元素为Instance类的实例
     * - 当类的构造函数参数具有默认值时，数组元素为该默认值
     * - 当数组为空数组是，表示类不具有构造函数
     */
    private $_dependencies = [];


    /**
     * Returns an instance of the requested class.
     * 【将依赖信息中保存的Istance实例所引用的类或接口进行实例化。】
     * 为请求的类返回一个实例
     *
     * You may provide constructor parameters (`$params`) and object configurations (`$config`)
     * that will be used during the creation of the instance.
     * 在创建实例过程中，将会用到你提供的构造函数参数(`$params`)和对象配置数组(`$config`)
     *
     * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last
     * parameter to the class constructor; Otherwise, the configuration will be applied *after* the object is
     * instantiated.
     * 假如待实例化的类实现了[[\yii\base\Configurable]]接口，那么`$config` 参数将会被传递成为构造器的最后一个参数
     * 另外，在类实例化之后，将会用到配置数组
     *
     * Note that if the class is declared to be singleton by calling [[setSingleton()]],
     * the same instance of the class will be returned each time this method is called.
     * In this case, the constructor parameters and object configurations will be used
     * only if the class is instantiated the first time.
     * 注意，假如该类使用 [[setSingleton()]]被定义为单例类，那么每次运行get()方法，返回的
     * 都是同一个类。因此，构造器参数和对象配置数组只会在第一次被使用
     *
     * @param string $class the class name or an alias name (e.g. `foo`) that was previously registered via [[set()]]
     * or [[setSingleton()]].
     * 之前使用 [[set()]] 或 [[setSingleton()]]方法注册过的类名，别名或接口
     * @param array $params a list of constructor parameter values. The parameters should be provided in the order
     * they appear in the constructor declaration. If you want to skip some parameters, you should index the remaining
     * ones with the integers that represent their positions in the constructor parameter list.
     * 构造方法参数组成的数组，参数应该按它们在构造方法中出现的顺序提供，假如你想跳过某些参数，你需要用数字索引标出
     * 剩下的参数。这就意味着他们在构造器参数中的位置
     * @param array $config a list of name-value pairs that will be used to initialize the object properties.
     * 以键值对形式定义的，初始化对象属性的数组
     * @return object an instance of the requested class.
     * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition
     */
    public function get($class, $params = [], $config = [])
    {
//        var_dump($class);
//        var_dump(isset($this->_definitions[$class]));
        /**
         * 假如在单例属性中有，则直接返回
         * 否则在
         */
        if (isset($this->_singletons[$class])) {
            // singleton
            return $this->_singletons[$class];
        } elseif (!isset($this->_definitions[$class])) {
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definitions[$class];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $object = call_user_func($definition, $this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);
//            var_dump('enter is_array');
            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                $object = $this->get($concrete, $params, $config);
            }
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException("Unexpected object definition type: " . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }
//        var_dump($this->_dependencies);
        return $object;
    }

    /**
     * Registers a class definition with this container.
     * 向容器注册类的依赖
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * // 像这样注册类名，这一步可以跳过
     * $container->set('yii\db\Connection');
     *
     * // register an interface 注册接口
     * // When a class depends on the interface, the corresponding class
     * // 当某个类依赖某个接口时，相应的类会作为依赖类被实例化
     * // will be instantiated as the dependent object
     * $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * // 注册别名，可以使用 $container->get('foo')去创建一个Connection实例
     * $container->set('foo', 'yii\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // 使用配置注册类，当类使用get()实例化时，这些配置会生效
     * // will be applied when the class is instantiated by get()
     * $container->set('yii\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // 使用配置项注册别名
     * // In this case, a "class" element is required to specify the class
     * // 在这种情况下，"class"元素需要指定的类（就是类的全限定名?）
     * $container->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable 注册一个php回调
     * // The callable will be executed when $container->get('db') is called
     * // 该回调会在调用 $container->get('db') 的时候调用
     * $container->set('db', function ($container, $params, $config) {
     *     return new \yii\db\Connection($config);
     * });
     * ```
     *
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     * 假如某类已经存在（在容器中）了，则后来注入的会覆盖前者，如果不想覆盖，需要使用has()判断该类是否存在于容器了
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. It can be one of the following:
     * $definition 表示依赖的定义，可以是一个类名、配置数组或一个PHP callable
     *
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable
     *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
     *   parameters, `$config` the object configuration, and `$container` the container object. The return value
     *   of the callable will be returned by [[get()]] as the object instance requested.
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property
     *   values of the newly created object when [[get()]] is called. The `class` element stands for the
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
     * - a string: a class name, an interface name or an alias name.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     */
    public function set($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        //  set() 在注册依赖时，会把使用 setSingleton() 注册的依赖删除
        unset($this->_singletons[$class]);
//        var_dump($this->_definitions);
        return $this;
    }

    /**
     * Registers a class definition with this container and marks the class as a singleton class.
     * 向容器注册类的依赖，并且将该类标记为单例类
     *
     * This method is similar to [[set()]] except that classes registered via this method will only have one
     * instance. Each time [[get()]] is called, the same instance of the specified class will be returned.
     * 本方法类似于set()，除了本方法只会返回一个实例之外。每次调用setSingleton()方法的时候，都会返回同一实例。（原文写错了吧）
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. See [[set()]] for more details.
     * $definition 表示依赖的定义，可以是一个类名、配置数组或一个PHP callable
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     * @see set()
     */
    public function setSingleton($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        // 要将 $_singleton[] 中的同名依赖设为 null ， 表示定义了一个Singleton，但是并未实现化。
        $this->_singletons[$class] = null;
        return $this;
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $class class name, interface name or alias name
     * @return boolean whether the container has the definition of the specified name..
     * @see set()
     */
    public function has($class)
    {
        return isset($this->_definitions[$class]);
    }

    /**
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     * @param string $class class name, interface name or alias name
     * @param boolean $checkInstance whether to check if the singleton has been instantiated.
     * @return boolean whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
     * the method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton($class, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    /**
     * Removes the definition for the specified name.
     * @param string $class class name, interface name or alias name
     */
    public function clear($class)
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    /**
     * Normalizes the class definition.
     * 对依赖的定义进行规范化处理
     * 注意！！！此函数的返回值主要传给$this->_definitions  该属性是一个数组特点为
     * 【类名接口名或别名】=>【一个具有class元素的数组，或是一个回调】
     * 因此本方法的主要职责就是 如果参数$definition 不是回调或对象，则确保它有一个class元素
     * @param string $class class name
     * @param string|array|callable $definition the class definition
     * @return array the normalized class definition
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function normalizeDefinition($class, $definition)
    {
        // $definition 是空的转换成 ['class' => $class] 形式
        if (empty($definition)) {
            return ['class' => $class];
        // $definition 是字符串，转换成 ['class' => $definition] 形式
        } elseif (is_string($definition)) {
            return ['class' => $definition];
        // $definition 是PHP callable 或对象，则直接将其作为依赖的定义
        } elseif (is_callable($definition, true) || is_object($definition)) {
            return $definition;
        // $definition 是数组则确保该数组定义了 class 元素
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException("A class definition requires a \"class\" member.");
                }
            }
            return $definition;
        // 这也不是，那也不是，那就抛出异常算了
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }

    /**
     * Returns the list of the object definitions or the loaded shared objects.
     * @return array the list of the object definitions or the loaded shared objects (type or ID => definition or instance).
     */
    public function getDefinitions()
    {
        return $this->_definitions;
    }

    /**
     * Creates an instance of the specified class.
     * 为指定类创建实例
     * This method will resolve dependencies of the specified class, instantiate them, and inject
     * them into the new instance of the specified class.
     * 此方法会为指定的类解决依赖，实例化依赖，并注入到所需的类
     * @param string $class the class name
     * @param array $params constructor parameters
     * @param array $config configurations to be applied to the new instance
     * @return object the newly created instance of the specified class
     */
    protected function build($class, $params, $config)
    {
//        var_dump($class);
//        var_dump($this->getDependencies($class));
        /* @var $reflection ReflectionClass */
        // 调用getDependencies来获取并缓存依赖信息，以及相应的反射类，留意这里 list 的用法
        list ($reflection, $dependencies) = $this->getDependencies($class);
        
        // 用传入的 $params 的内容补充、覆盖到依赖信息中 
        // 这样$param就覆盖掉了构造方法参数的默认值
        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }
        
        // 这个语句是两个条件：
        // 一是要创建的类是一个 yii\base\Object 类，
        // Object类的构造函数参数是一个【属性=>值】的数组。
        // 二是依赖信息不为空，也就是要么已经注册过依赖，
        // 要么为build() 传入构造函数参数。
        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (empty($config)) {
//            var_dump($dependencies);
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            // 将最后一个参数认作配置数组 
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }

    /**
     * Merges the user-specified constructor parameters with the ones registered via [[set()]].
     * @param string $class class name, interface name or alias name
     * @param array $params the constructor parameters
     * @return array the merged parameters
     */
    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        } else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }

    /**
     * Returns the dependencies of the specified class.
     * 返回指定类的依赖 本方法会被本类的build()方法使用
     * @param string $class class name, interface name or alias name
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class)
    {
//        var_dump($class);
//        var_dump($this->_reflections['app\models\UserFinder']);
        /**
         * $this->_reflections 是用于缓存ReflectionClass对象，
         * 以类名或接口名为键 所以先去找缓存
         */
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }
        /**
         * 缓存里没有 那么执行反射
         */
        $dependencies = [];
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                /**
                 * 如果构造函数参数有默认值，则压入$dependencies 数组
                 * （注意，这里是没有键的）作为依赖
                 * 即然是默认值了，就肯定是【简单类型】了。
                 * 如果构造函数参数没有默认值，则为其创建一个引用。
                 * 就是前面提到的 Instance 类型。
                 */
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    /**
                     * 这里说的不是简单数据类型，应该是object或者是closure
                     */
                    $c = $param->getClass();
                    // Instance为NULL 那么接下来怎么处理啊？？？
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }
            
        /**
         * 放入类的属性 放入缓存
         */
        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;
//        var_dump($this->_dependencies['app\models\UserFinder']);
        return [$reflection, $dependencies];
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * 通过将它们替换为类实例解决依赖关系
     * 【和getDependencies()一样，本方法也是是关乎 $_reflections 和 $_dependencies 数组的
     * 本方法也会在build()方法中用到 另外还会在get()方法中用到】
     * @param array $dependencies the dependencies
     * @param ReflectionClass $reflection the class reflection associated with the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies($dependencies, $reflection = null)
    {
//        var_dump($dependencies);
        /**
         * $dependencies 是无下标的 默认从0开始
         */
        foreach ($dependencies as $index => $dependency) {
            // 只有是Instance类的实例才能进行下一步
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    // 进入递归
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    // 这里表明，如果$dependency->id 为NULL 则逻辑也进行不下去，
                    // 搞了一大堆就是抛异常而已
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }
        return $dependencies;
    }
}
