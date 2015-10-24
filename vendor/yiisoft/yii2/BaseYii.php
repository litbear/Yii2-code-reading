<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\UnknownClassException;
use yii\log\Logger;
use yii\di\Container;

/**
 * Gets the application start timestamp.开始运行时间戳
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME', microtime(true));
/**
 * This constant defines the framework installation directory.vendor\yiisoft\yii2
 */
defined('YII2_PATH') or define('YII2_PATH', __DIR__);
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 * var_dump(YII2_PATH);die; 返回的是 D:\wamp\www\yii2-app-basic\vendor\yiisoft\yii2
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
/**
 * This constant defines in which environment the application is running. Defaults to 'prod', meaning production environment.
 * You may define this constant in the bootstrap script. The value could be 'prod' (production), 'dev' (development), 'test', 'staging', etc.
 * 运行环境 ： 生产 开发 调试 等等……
 */
defined('YII_ENV') or define('YII_ENV', 'prod');
/**
 * Whether the the application is running in production environment
 */
defined('YII_ENV_PROD') or define('YII_ENV_PROD', YII_ENV === 'prod');
/**
 * Whether the the application is running in development environment
 */
defined('YII_ENV_DEV') or define('YII_ENV_DEV', YII_ENV === 'dev');
/**
 * Whether the the application is running in testing environment
 */
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 * 开启错误处理句柄
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);

/**
 * BaseYii is the core helper class for the Yii framework.
 *
 * Do not use BaseYii directly. Instead, use its child class [[\Yii]] which you can replace to
 * customize methods of BaseYii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BaseYii
{
    /**
     * @var array class map used by the Yii autoloading mechanism.
     * The array keys are the class names (without leading backslashes), and the array values
     * are the corresponding class file paths (or path aliases). This property mainly affects
     * how [[autoload()]] works.
     * 储存 类名=>类文件的路径 集合
     * @see autoload()
     */
    public static $classMap = [];
    /**
     * @var \yii\console\Application|\yii\web\Application the application instance
     */
    public static $app;
    /**
     * @var array registered path aliases
     * @see getAlias()
     * @see setAlias()
     * '@yii'别名对应为 /path/to/app/vendor/yiisoft/yii2 （注意！尾部没有'/'）
     */
    public static $aliases = ['@yii' => __DIR__];
    /**
     * @var Container the dependency injection (DI) container used by [[createObject()]].
     * You may use [[Container::set()]] to set up the needed dependencies of classes and
     * their initial property values.
     * @see createObject()
     * @see Container
     */
    public static $container;


    /**
     * Returns a string representing the current version of the Yii framework.
     * @return string the version of Yii framework
     */
    public static function getVersion()
    {
        return '2.0.6';
    }

    /**
     * Translates a path alias into an actual path.
     * 将别名转换为真实路径 
     *
     * The translation is done according to the following procedure:
     * 别名转换遵循以下原则：
     *
     * 1. If the given alias does not start with '@', it is returned back without change;
     * 1. 如果输入的别名并不以'@'开头 则原样输出
     * 2. Otherwise, look for the longest registered alias that matches the beginning part
     *    of the given alias. If it exists, replace the matching part of the given alias with
     *    the corresponding registered path.
     * 2. 否则，查找相匹配的最长的别名，然后用别名路径来替换传入的参数中的别名。
     * 3. Throw an exception or return false, depending on the `$throwException` parameter.
     * 3. 根据第二个参数`$throwException` 抛出异常或返回错误
     *
     * For example, by default '@yii' is registered as the alias to the Yii framework directory,
     * say '/path/to/yii'. The alias '@yii/web' would then be translated into '/path/to/yii/web'.
     * 比如，'@yii'被注册为 /path/to/app/vendor/yiisoft/yii2 则'@yii/web'会被转换为
     * /path/to/app/vendor/yiisoft/yii2/web
     *
     * If you have registered two aliases '@foo' and '@foo/bar'. Then translating '@foo/bar/config'
     * would replace the part '@foo/bar' (instead of '@foo') with the corresponding registered path.
     * This is because the longest alias takes precedence.
     * 如果你注册了两个别名 '@foo' 和 '@foo/bar' 那么。在转换'@foo/bar/config'的时候，会优先使用'@foo/bar' 
     * 去代替'@foo' 因为较长的别名会优先发挥作用 (单看getAlias()源码似乎解决不了为什么较长别名优先匹配的疑问)
     *
     * However, if the alias to be translated is '@foo/barbar/config', then '@foo' will be replaced
     * instead of '@foo/bar', because '/' serves as the boundary character.
     *
     * Note, this method does not check if the returned path exists or not.
     * 提示，此方法不会检查返回的路径是否存在
     *
     * @param string $alias the alias to be translated.
     * @param boolean $throwException whether to throw an exception if the given alias is invalid.
     * If this is false and an invalid alias is given, false will be returned by this method.
     * @return string|boolean the path corresponding to the alias, false if the root alias is not previously registered.
     * @throws InvalidParamException if the alias is invalid while $throwException is true.
     * @see setAlias()
     */
    public static function getAlias($alias, $throwException = true)
    {
        // 是否以'@'开头
        if (strncmp($alias, '@', 1)) {
            // not an alias
            return $alias;
        }

        // 查找别名中'/'首次出现的位置
        $pos = strpos($alias, '/');
        // 如果没找到'/'则原样赋给$root 如果找到了则截取'/'之前的部分赋值给$root 即父别名
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            // 检查该别名的父别名是否设置
            if (is_string(static::$aliases[$root])) {
                // 别名中出现了'/' 则拼接 如没出现 则取原始路径
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            } else {
                // 如果父别名对应了一个数组 那么循环这个数组 并把（键）和（去掉根别名的别名字符串）拼接
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        return $path . substr($alias, strlen($name));
                    }
                }
            }
        }

        if ($throwException) {
            throw new InvalidParamException("Invalid path alias: $alias");
        } else {
            return false;
        }
    }

    /**
     * Returns the root alias part of a given alias.
     * A root alias is an alias that has been registered via [[setAlias()]] previously.
     * If a given alias matches multiple root aliases, the longest one will be returned.
     * @param string $alias the alias
     * @return string|boolean the root alias, or false if no root alias is found
     */
    public static function getRootAlias($alias)
    {
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $root;
            } else {
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        return $name;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Registers a path alias.
     * 注册一个路径别名
     *
     * A path alias is a short name representing a long path (a file path, a URL, etc.)
     * For example, we use '@yii' as the alias of the path to the Yii framework directory.
     * 路径别名是……一段废话
     * 
     *
     * A path alias must start with the character '@' so that it can be easily differentiated
     * from non-alias paths.
     * 路径别名必须以'@'开头用以区别其他非路径别名
     *
     * Note that this method does not check if the given path exists or not. All it does is
     * to associate the alias with the path.
     * 此函数不会验证给出路径的存在与否 他做的只是字符串替换
     *
     * Any trailing '/' and '\' characters in the given path will be trimmed.
     * 原始路径最后一位的'/' 和 '\' 都会被去掉
     *
     * @param string $alias the alias name (e.g. "@yii"). It must start with a '@' character.
     * It may contain the forward slash '/' which serves as boundary character when performing
     * alias translation by [[getAlias()]].
     * @param string $path the path corresponding to the alias. If this is null, the alias will
     * be removed. Trailing '/' and '\' characters will be trimmed. This can be
     *
     * - a directory or a file path (e.g. `/tmp`, `/tmp/main.txt`)
     * - a URL (e.g. `http://www.yiiframework.com`)
     * - a path alias (e.g. `@yii/base`). In this case, the path alias will be converted into the
     *   actual path first by calling [[getAlias()]].
     *
     * @throws InvalidParamException if $path is an invalid alias.
     * @see getAlias()
     */
    public static function setAlias($alias, $path)
    {
        if (strncmp($alias, '@', 1)) {
            // 别名是否以'@'开头 如果不是 则补上
            $alias = '@' . $alias;
        }
        // 别名中是否出现了'/' 在第几位出现
        $pos = strpos($alias, '/');
        // 如果别名中没有“/”，则根别名$root就是所输入的别名，否则从截取“/”前面的作为根别名
        // 如：@www，根别名就为@www；
        // 如:@www/data，那么根别名截取为@www。
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($path !== null) {
            // 如果真实路径不为空
            // 真实路径是否以'@'开头？ 是则直接调用getAlias来解析得到路径。否则去掉结尾的“/”、“\”
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            if (!isset(static::$aliases[$root])) {
                /*
                 * 如果根别名没设置过 且别名中没有'/' 
                 *     则设置为['@www'=>'路径']
                 * 如果跟别名没有设置过 但别名中有'/'
                 *     则设置为['@www'=>['@www/data'=>'路径']]
                 */
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) {
                /**
                 * 如果根别名设置过 且是根别名对应的值是字符串
                 * 如果别名中没有'/'（就是说正在设置的根别名覆盖了原有的根别名）
                 *     则设置为['@www'=>'路径']
                 * 如果别名中有'/' 则再加上一个根别名的元素
                 *     则设置为['@www'=>['@www/data'=>'路径1','@www' => '路径2']]
                 * 
                 */
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                /**
                 * 剩下最后一种情况就是
                 * 如果根别名设置过 且对应的值是数组
                 * 则新增一个元素 即：
                 * ['@www'=>['@www/data'=>'路径1','@www' => '路径2','@www/data/new'=>'路径3']]
                 * 并排序
                 */
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
        } elseif (isset(static::$aliases[$root])) {
            /**
             * 如果路径为null的话则代表着要unset掉相应的
             * 元素 下面的代码貌似多此一举 因为 如果数组
             * ['@aaa' => ['@aaa' => '/tmp/aaa' , '@aaa/bbb' => '/usr/bbb']]
             * unset掉了static::$aliases['@aaa']['@aaa']貌似会导致其他的失效？
             * 验证后：不会失效
             */
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
//        var_dump(static::$aliases);
    }

    /**
     * Class autoload loader.
     * This method is invoked automatically when PHP sees an unknown class.
     * The method will attempt to include the class file according to the following procedure:
     * 类自动加载器
     * 本方法会在调用未知类的时候自动加载其所在的文件
     * 本方法会通过以下几步尝试include类所在的文件：
     *
     * 1. Search in [[classMap]];
     * 1. 在[[classMap]]中搜索
     * 2. If the class is namespaced (e.g. `yii\base\Component`), it will attempt
     *    to include the file associated with the corresponding path alias
     *    (e.g. `@yii/base/Component.php`);
     * 2. 假如等待调用的类使用了命名空间，该类则会尝试include相应的别名
     *
     * This autoloader allows loading classes that follow the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/)
     * and have its top-level namespace or sub-namespaces defined as path aliases.
     *
     * Example: When aliases `@yii` and `@yii/bootstrap` are defined, classes in the `yii\bootstrap` namespace
     * will be loaded using the `@yii/bootstrap` alias which points to the directory where bootstrap extension
     * files are installed and all classes from other `yii` namespaces will be loaded from the yii framework directory.
     *
     * Also the [guide section on autoloading](guide:concept-autoloading).
     *
     * @param string $className the fully qualified class name without a leading backslash "\"
     * @throws UnknownClassException if the class does not exist in the class file
     */
    public static function autoload($className)
    {
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
            if ($classFile[0] === '@') {
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }

        include($classFile);

        if (YII_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            throw new UnknownClassException("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }

    /**
     * Creates a new object using the given configuration.
     * 使用制定配置去实例化一个对象
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     * 这个方法支持使用类名，配置数组或者一个匿名函数
     *
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // create an object using a configuration array
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // create an object with two constructor parameters
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * Using [[\yii\di\Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param string|array|callable $type the object type. This can be specified in one of the following forms:
     *
     * - a string: representing the class name of the object to be created
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
     * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     * 在Yii框架代码中，只要创建实例，就是调用 Yii::createObject() 这个方法来实现。 可以说，
     * Yii中所有的实例（除了Application，DI容器自身等入口脚本中实例化的），都是通过DI容器来获取的。
     * 当Service Locator中服务或组件的定义是一个PHP callable时，对其形式有一定要求。 
     * 一是返回一个实例，二是不接收任何参数。 这在 Yii::createObject() 中也可以看出来。
     *
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws InvalidConfigException if the configuration is invalid.
     * @see \yii\di\Container
     */
    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return call_user_func($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        } else {
            throw new InvalidConfigException("Unsupported configuration type: " . gettype($type));
        }
    }

    private static $_logger;

    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        } else {
            return self::$_logger = static::createObject('yii\log\Logger');
        }
    }

    /**
     * Sets the logger object.
     * @param Logger $logger the logger object.
     */
    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    /**
     * Logs a trace message.
     * Trace messages are logged mainly for development purpose to see
     * the execution work flow of some code.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function trace($message, $category = 'application')
    {
        if (YII_DEBUG) {
            static::getLogger()->log($message, Logger::LEVEL_TRACE, $category);
        }
    }

    /**
     * Logs an error message.
     * An error message is typically logged when an unrecoverable error occurs
     * during the execution of an application.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function error($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Logs a warning message.
     * A warning message is typically logged when an error occurs while the execution
     * can still continue.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function warning($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_WARNING, $category);
    }

    /**
     * Logs an informative message.
     * An informative message is typically logged by an application to keep record of
     * something important (e.g. an administrator logs in).
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function info($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_INFO, $category);
    }

    /**
     * Marks the beginning of a code block for profiling.
     * This has to be matched with a call to [[endProfile]] with the same category name.
     * The begin- and end- calls must also be properly nested. For example,
     *
     * ~~~
     * \Yii::beginProfile('block1');
     * // some code to be profiled
     *     \Yii::beginProfile('block2');
     *     // some other code to be profiled
     *     \Yii::endProfile('block2');
     * \Yii::endProfile('block1');
     * ~~~
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see endProfile()
     */
    public static function beginProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_BEGIN, $category);
    }

    /**
     * Marks the end of a code block for profiling.
     * This has to be matched with a previous call to [[beginProfile]] with the same category name.
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see beginProfile()
     */
    public static function endProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_END, $category);
    }

    /**
     * Returns an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information.
     * @return string an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information
     */
    public static function powered()
    {
        return 'Powered by <a href="http://www.yiiframework.com/" rel="external">Yii Framework</a>';
    }

    /**
     * Translates a message to the specified language.
     *
     * This is a shortcut method of [[\yii\i18n\I18N::translate()]].
     *
     * The translation will be conducted according to the message category and the target language will be used.
     *
     * You can add parameters to a translation message that will be substituted with the corresponding value after
     * translation. The format for this is to use curly brackets around the parameter name as you can see in the following example:
     *
     * ```php
     * $username = 'Alexander';
     * echo \Yii::t('app', 'Hello, {username}!', ['username' => $username]);
     * ```
     *
     * Further formatting of message parameters is supported using the [PHP intl extensions](http://www.php.net/manual/en/intro.intl.php)
     * message formatter. See [[\yii\i18n\I18N::translate()]] for more details.
     *
     * @param string $category the message category.
     * @param string $message the message to be translated.
     * @param array $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        if (static::$app !== null) {
            return static::$app->getI18n()->translate($category, $message, $params, $language ?: static::$app->language);
        } else {
            $p = [];
            foreach ((array) $params as $name => $value) {
                $p['{' . $name . '}'] = $value;
            }

            return ($p === []) ? $message : strtr($message, $p);
        }
    }

    /**
     * Configures an object with the initial property values.
     * 使用初始化属性配置对象
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * Returns the public member variables of an object.
     * This method is provided such that we can get the public member variables of an object.
     * It is different from "get_object_vars()" because the latter will return private
     * and protected variables if it is called within the object itself.
     * @param object $object the object to be handled
     * @return array the public member variables of the object
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }
}
