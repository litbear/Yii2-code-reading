<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Yii;
use yii\base\InvalidConfigException;

/**
 * 【表示的是容器中的内容，代表的是对于实际对象的引用。
 * DI容器可以通过他获取所引用的实际对象。
 * 类仅有的一个属性 id 一般表示的是实例的类型。】
 * Instance represents a reference to a named object in a dependency injection (DI) container or a service locator.
 * Instance 表示着一个对 在依赖注入容器或者服务定位器中的命名对象 的引用
 * 【本质上是DI容器中对于某一个类实例的引用】
 * 【个人理解：】Instance类 是用来封装类的，封装什么类呢？封装所有依赖注入容器 和服务定位器对象的类
 * 就是说 依赖注入容器和服务定位器里所有的成员都应该是Instance类的实例
 *
 * You may use [[get()]] to obtain the actual object referenced by [[id]].
 * 你可以使用get()方法根据类的唯一识别id 去获得一个该类的真实对象的引用
 *
 * Instance is mainly used in two places:
 * Instance类主要被用在以下两个地方：
 *
 * - When configuring a dependency injection container, you use Instance to reference a class name, interface name
 *   or alias name. The reference can later be resolved into the actual object by the container.
 * - 配置依赖注入容器时，使用Instance类（还是其实例？）去引用一个类名、接口名或是别名。（后面一句看不懂，看例子先）
 * - In classes which use service locator to obtain dependent objects.
 * - 在使用服务定位器的类中获取依赖对象时
 *
 * The following example shows how to configure a DI container with Instance:
 * 以下实例揭示了如何用Instance类去配置一个依赖注入容器：
 *
 * ```php
 * $container = new \yii\di\Container;
 * $container->set('cache', 'yii\caching\DbCache', Instance::of('db'));
 * $container->set('db', [
 *     'class' => 'yii\db\Connection',
 *     'dsn' => 'sqlite:path/to/file.db',
 * ]);
 * ```
 *
 * And the following example shows how a class retrieves a component from a service locator:
 * 以下例子解释了，一个类是如何从服务定位器中检索到组件的
 *
 * ```php
 * class DbCache extends Cache
 * {
 *     public $db = 'db';
 *
 *     public function init()
 *     {
 *         parent::init();
 *         $this->db = Instance::ensure($this->db, 'yii\db\Connection');
 *     }
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Instance
{
    /**
     * @var string the component ID, class name, interface name or alias name
     */
    public $id;


    /**
     * Constructor.
     * 静态方法创建一个Instance实例
     * 注意 构造器被protect修饰 也就是说
     * 除了本类和子类之外不能被实例化
     * @param string $id the component ID
     */
    protected function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Creates a new Instance object.
     * 根据id（其实就是全限定名）创建一个新的实例
     * （也就是说储存的是类，创建出来的是实例？？？
     *  Instance::of() 这个写法挺有意思）
     * @param string $id the component ID
     * @return Instance the new Instance object.
     */
    public static function of($id)
    {
        return new static($id);
    }

    /**
     * 静态方法，用于将引用解析成实际的对象，并确保这个对象的类型
     * Resolves the specified reference into the actual object and makes sure it is of the specified type.
     *
     * The reference may be specified as a string or an Instance object. If the former,
     * it will be treated as a component ID, a class/interface name or an alias, depending on the container type.
     * 引用可以是字符串或是一个实例对象，假如是前者，则会被当作是组件ID，类名或者接口名挥着别名，这取决于容器类型。
     *
     * If you do not specify a container, the method will first try `Yii::$app` followed by `Yii::$container`.
     * 假如你没指定容器，则此方法会依次使用 `Yii::$app` 和 `Yii::$container`
     * 【总结：主要解决了两个问题，1，确保对象的类型——如果参数1是参数2的实例 则原样返回就行了。
     * 2，选择不同的容器去处理（实例化）】
     *
     * For example,
     *
     * ```php
     * use yii\db\Connection;
     *
     * // returns Yii::$app->db
     * $db = Instance::ensure('db', Connection::className());
     * // returns an instance of Connection using the given configuration
     * $db = Instance::ensure(['dsn' => 'sqlite:path/to/my.db'], Connection::className());
     * ```
     *
     * @param object|string|array|static $reference an object or a reference to the desired object.
     * 此参数接受对象，或者描述对象的配置数组
     * You may specify a reference in terms of a component ID or an Instance object.
     * 可以依据组件ID或者Instance对象配饰一个类？？？
     * Starting from version 2.0.2, you may also pass in a configuration array for creating the object.
     * 从2.0.2版本起，接受配置数组
     * If the "class" value is not specified in the configuration array, it will use the value of `$type`.
     * 如果配置数组中'class'这个key对应的值没有被指定的话，就使用第二个参数$type
     * @param string $type the class/interface name to be checked. If null, type check will not be performed.
     * @param ServiceLocator|Container $container the container. This will be passed to [[get()]].
     * @return object the object referenced by the Instance, or `$reference` itself if it is an object.
     * @throws InvalidConfigException if the reference is invalid
     */
    public static function ensure($reference, $type = null, $container = null)
    {
        if ($reference instanceof $type) {
            /**
             * $reference 如果是第二个参数的一个实例，
             * 则原样返回
             */
            return $reference;
        } elseif (is_array($reference)) {
            /**
             * $reference 参数如果是个数组，则先判断$reference 参数中
             * 是否有$reference['class']如果没有则使用$type参数
             * 接着判断第三个参数是不是Container类的实例，如果不是
             * （就是默认情况下，$type=null）则使用Yii:$container 
             * 也就是默认容器。清理掉$reference['class'] 将第一个参数$reference
             * 交给容器去配置
             */
            $class = isset($reference['class']) ? $reference['class'] : $type;
            if (!$container instanceof Container) {
                $container = Yii::$container;
            }
            unset($reference['class']);
            return $container->get($class, [], $reference);
        } elseif (empty($reference)) {
            /**
             * $reference 参数必须，如果是空的，则抛出异常
             */
            throw new InvalidConfigException('The required component is not specified.');
        }

        if (is_string($reference)) {
            $reference = new static($reference);
        }

        if ($reference instanceof self) {
            $component = $reference->get($container);
            if ($component instanceof $type || $type === null) {
                return $component;
            } else {
                throw new InvalidConfigException('"' . $reference->id . '" refers to a ' . get_class($component) . " component. $type is expected.");
            }
        }

        $valueType = is_object($reference) ? get_class($reference) : gettype($reference);
        throw new InvalidConfigException("Invalid data type: $valueType. $type is expected.");
    }

    /**
     * 获取这个实例所引用的实际对象，事实上它调用的是
     * yii\di\Container::get()来获取实际对象
     * Returns the actual object referenced by this Instance object.
     * （通过本类的实例，这不废话么）获取实际对象的引用
     * @param ServiceLocator|Container $container the container used to locate the referenced object.
     * If null, the method will first try `Yii::$app` then `Yii::$container`.
     * @return object the actual object referenced by this Instance object.
     */
    public function get($container = null)
    {
        /**
         * 如果指定了容器则从指定的容器获取
         */
        if ($container) {
            return $container->get($this->id);
        }
        /**
         * 否则从Yii类的两个静态属性中去找
         */
        if (Yii::$app && Yii::$app->has($this->id)) {
            return Yii::$app->get($this->id);
        } else {
            return Yii::$container->get($this->id);
        }
    }
}
