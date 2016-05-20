<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * 三个直接子类：[[\yii\base\Component]] [[\yii\base\Event]] [[\yii\base\Behavior]]
 * Object is the base class that implements the *property* feature.
 * Object类是实现__getter(),__setter()与属性结合使用这一特点的基类
 *
 * A property is defined by a getter method (e.g. `getLabel`), and/or a setter method (e.g. `setLabel`). For example,
 * the following getter and setter methods define a property named `label`:
 * 一个属性由相应的getter和setter方法定义 比如下面这组函数定义了一个名叫label的属性
 *
 * ~~~
 * private $_label;
 *
 * public function getLabel()
 * {
 *     return $this->_label;
 * }
 *
 * public function setLabel($value)
 * {
 *     $this->_label = $value;
 * }
 * ~~~
 *
 * Property names are *case-insensitive*.
 * 属性名称是不区分大小写的
 *
 * A property can be accessed like a member variable of an object. Reading or writing a property will cause the invocation
 * of the corresponding getter or setter method. For example,
 * 属性可以像对象成员变量一样被访问。读取或设置属性会引起对应的getter或setter方法的调用，例如：
 *
 * ~~~
 * // equivalent to $label = $object->getLabel();
 * // 等价于使用 $label = $object->getLabel();
 * $label = $object->label;
 * // equivalent to $object->setLabel('abc');
 * // 等价于使用 $object->setLabel('abc');
 * $object->label = 'abc';
 * ~~~
 *
 * If a property has only a getter method and has no setter method, it is considered as *read-only*. In this case, trying
 * to modify the property value will cause an exception.
 * 假如一个属性只有getter方法而没有setter，则认为这个属性是只读的，对这个属性赋值将会引起一个异常
 *
 * One can call [[hasProperty()]], [[canGetProperty()]] and/or [[canSetProperty()]] to check the existence of a property.
 * 可以使用[[hasProperty()]], [[canGetProperty()]] and/or [[canSetProperty()]]这三个方法确认一个属性是否存在
 *
 * Besides the property feature, Object also introduces an important object initialization life cycle. In particular,
 * creating an new instance of Object or its derived class will involve the following life cycles sequentially:
 * 除了属性上的特点，Object类同样提供了一个重要的对象初始化生命周期，尤其是当实例化Object类与其子类的时候会进入
 * 以下这个生命周期循环
 *
 * 1. the class constructor is invoked;
 * 1. 调用类的构造方法
 * 2. object properties are initialized according to the given configuration;
 * 2. 使用给定的配置初始化对象的属性
 * 3. the `init()` method is invoked
 * 3. 调用init() 方法.
 *
 * In the above, both Step 2 and 3 occur at the end of the class constructor. It is recommended that
 * you perform object initialization in the `init()` method because at that stage, the object configuration
 * is already applied.
 * 以上步骤中，2,3步发生在对象构造方法执行完毕之后。在这里推荐你在类的init()方法中初始化对象，应为在进行这一步时
 * 独享已经配置完成
 *
 * In order to ensure the above life cycles, if a child class of Object needs to override the constructor,
 * it should be done like the following:
 * 为了确保以上生命周期能都被执行，必须在Object类的子类的构造方法中执行父类的构造方法
 *
 * ~~~
 * public function __construct($param1, $param2, ..., $config = [])
 * {
 *     ...
 *     parent::__construct($config);
 * }
 * ~~~
 *
 * That is, a `$config` parameter (defaults to `[]`) should be declared as the last parameter
 * of the constructor, and the parent implementation should be called at the end of the constructor.
 * 换言之，配置参数要作为构造方法的最后一个参数，并且父类构造器要在最后被调用一下（放在末尾调用是不是
 * 意味着父类配置优先级高于子类？）
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Object implements Configurable
{
    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * Constructor.
     * 构造方法
     * The default implementation does two things:
     * 默认情况下做了两件事
     *
     * - Initializes the object with the given configuration `$config`.
     * - 使用给定的配置实例化对象
     * - Call [[init()]].
     * - 调用本对象的init()方法
     *
     * If this method is overridden in a child class, it is recommended that
     * 如果此方法在子类被复写了，那就意味着：
     *
     * - the last parameter of the constructor is a configuration array, like `$config` here.
     * - 构造方法的最后一个函数是配置项数组，
     * - call the parent implementation at the end of the constructor.
     * - 在处理完子类构造方法逻辑之后要调用父类的构造方法
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }
        $this->init();
    }

    /**
     * Initializes the object.
     * 初始化对象
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     * 此方法在执行完构造方法逻辑之后调用
     */
    public function init()
    {
    }

    /**
     * Returns the value of an object property.
     * 返回对象属性的值
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $object->property;`.
     * @param string $name the property name
     * @return mixed the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only
     * @see __set()
     */
    public function __get($name)
    {
        /**
         * 当$foo->a = 'aaa;时
         * 如果 $foo->geta() 存在 则调用之
         * 如不存在 则判断是否可写（$foo->seta() 是否存在）
         * 并抛出异常
         */
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Sets value of an object property.
     * 判断相应的set方法是否存在 存在则调用之
     * 不存在则判断是否可读并抛出相应的异常
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$object->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     * 利用相应的get方法判断对象的属性是否被设置 
     * 注意是严格不等于 !==
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($object->property)`.
     *
     * Note that if the property is not defined, false will be returned.
     * @param string $name the property name or the event name
     * @return boolean whether the named property is set (not null).
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * Sets an object property to null.
     * 这里的unset并不是删除相应的属性，只是将属性设为null
     * 如果相应的的属性不存在 不会抛出异常，只是返回false
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($object->property)`.
     *
     * Note that if the property is not defined, this method will do nothing.
     * If the property is read-only, it will throw an exception.
     * @param string $name the property name
     * @throws InvalidCallException if the property is read only.
     * @see http://php.net/manual/en/function.unset.php
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Calls the named method which is not a class method.
     * 当调用不存在的方法时抛出异常
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     * 魔术方法不要直接调用
     * @param string $name the method name
     * @param array $params method parameters
     * @throws UnknownMethodException when calling unknown method
     * @return mixed the method return value
     */
    public function __call($name, $params)
    {
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**
     * Returns a value indicating whether a property is defined.
     * A property is defined if:
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true)
    {
        // $this->canSetProperty($name, false);中的第二个参数为false 因为前面已经判断过了
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    /**
     * Returns a value indicating whether a property can be read.
     * A property is readable if:
     * 第二个参数$checkVars 为真时 既要检查相应的get方法是否存在 也要检查属性是否存在
     * $checkVars 为假时，只检查相应的get方法是否存在
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * Returns a value indicating whether a property can be set.
     * A property is writable if:
     * 与get方法同理 根据$checkVars分情况讨论
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name the method name
     * @return boolean whether the method is defined
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}
