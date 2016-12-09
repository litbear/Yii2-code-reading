<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Component is the base class that implements the *property*, *event* and *behavior* features.
 * Component 是实现属性（魔术方法），事件，行为等特性的基类
 *
 * Component provides the *event* and *behavior* features, in addition to the *property* feature which is implemented in
 * its parent class [[Object]].
 * Component 提供是事件和行为特性，属性特性从父类Object处获得
 *
 * Event is a way to "inject" custom code into existing code at certain places. For example, a comment object can trigger
 * an "add" event when the user adds a comment. We can write custom code and attach it to this event so that when the event
 * is triggered (i.e. comment will be added), our custom code will be executed.
 * 事件，是一种将自定义代码注入到指定位置的方式。比如说：当用户新增评论的时候，评论对象会触发一个“新增”的事件。我们可以写一段
 * 自定义代码，并且把这段代码绑定到事件上，这样一来，当事件被触发的时候，自定义方法也会被执行
 *
 * An event is identified by a name that should be unique within the class it is defined at. Event names are *case-sensitive*.
 * 在被定义的类内，事件名必须是唯一的，区分大小写的
 *
 * One or multiple PHP callbacks, called *event handlers*, can be attached to an event. You can call [[trigger()]] to
 * raise an event. When an event is raised, the event handlers will be invoked automatically in the order they were
 * attached.
 * 事件句柄，由一个或多个PHP调用组成，事件句柄可以被绑定到事件上，通过调用触发器可以触发事件。当事件被触发时，事件句柄内
 * 内的php调用会被按照绑定时的顺序依次执行
 *
 * To attach an event handler to an event, call [[on()]]:
 * 当为事件绑定事件句柄时，会调用on()函数
 *
 * ~~~
 * $post->on('update', function ($event) {
 *     // send email notification
 * });
 * ~~~
 *
 * In the above, an anonymous function is attached to the "update" event of the post. You may attach
 * the following types of event handlers:
 * 可以用作php句柄的有效回调有以下几种：
 *
 * - anonymous function: `function ($event) { ... }`
 * - 匿名函数
 * - object method: `[$object, 'handleAdd']`
 * - 对象的方法
 * - static class method: `['Page', 'handleAdd']`
 * - 类的静态方法
 * - global function: `'handleAdd'`
 * - 全局函数
 *
 * The signature of an event handler should be like the following:
 * 事件句柄的签名如下所示：
 *
 * ~~~
 * function foo($event)
 * ~~~
 *
 * where `$event` is an [[Event]] object which includes parameters associated with the event.
 * 这里的$event参数必须是一个包含着事件相关参数的Event对象
 *
 * You can also attach a handler to an event when configuring a component with a configuration array.
 * The syntax is like the following:
 * 当使用最后一个参数（配置数组）配置一个组件的时候，你也可以向事件绑定事件句柄，如下所示
 *
 * ~~~
 * [
 *     'on add' => function ($event) { ... }
 * ]
 * ~~~
 *
 * where `on add` stands for attaching an event to the `add` event.
 * 这里的`on add` 可以看作向add事件绑定事件句柄
 *
 * Sometimes, you may want to associate extra data with an event handler when you attach it to an event
 * and then access it when the handler is invoked. You may do so by
 * 有些时候，你可能会希望在为事件绑定事件句柄的似乎带入一些额外的数据，以便在事件句柄被调用的时候访问这些数据
 * 你可以采用如下的方法
 *
 * ~~~
 * $post->on('update', function ($event) {
 *     // the data can be accessed via $event->data
 * }, $data);
 * ~~~
 *
 * A behavior is an instance of [[Behavior]] or its child class. A component can be attached with one or multiple
 * behaviors. When a behavior is attached to a component, its public properties and methods can be accessed via the
 * component directly, as if the component owns those properties and methods.
 * 行为，是Behavior类或其子类的实例，一个组件可以绑定一个或多个行为，当行为被绑定到组件上的时候，，行为的公共方法和属性
 * 可以被组件直接访问
 *
 * To attach a behavior to a component, declare it in [[behaviors()]], or explicitly call [[attachBehavior]]. Behaviors
 * declared in [[behaviors()]] are automatically attached to the corresponding component.
 *
 * One can also attach a behavior to a component when configuring it with a configuration array. The syntax is like the
 * following:
 *
 * ~~~
 * [
 *     'as tree' => [
 *         'class' => 'Tree',
 *     ],
 * ]
 * ~~~
 *
 * where `as tree` stands for attaching a behavior named `tree`, and the array will be passed to [[\Yii::createObject()]]
 * to create the behavior object.
 *
 * @property Behavior[] $behaviors List of behaviors attached to this component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Component extends Object
{
    /**
     * @var array the attached event handlers (event name => handlers)
     */
    private $_events = [];
    /**
     * @var Behavior[]|null the attached behaviors (behavior name => behavior). This is `null` when not initialized.
     */
    private $_behaviors;


    /**
     * Returns the value of a component property.
     * 返回组件的属性 
     * This method will check in the following order and act accordingly:
     * 除了继承Object中的使用魔术方法 __get() 和 __set()获取属性之外
     * 还要获取所有行为类的属性 以确保行为类的方法和属性能够被用到
     * （前文 Behavior内已提到过） 下面两句就不翻了
     *
     *  - a property defined by a getter: return the getter result
     *  - a property of a behavior: return the behavior property value
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $component->property;`.
     * 本方法是PHP魔术方法  不要直接访问哦
     * @param string $name the property name
     * @return mixed the property value or the value of a behavior's property
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     * @see __set()
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $behavior->$name;
                }
            }
        }
        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Sets the value of a component property.
     * 为组件属性设置值
     * This method will check in the following order and act accordingly:
     * 本方法会考虑以下即几种情况
     *
     *  - a property defined by a setter: set the property value
     *  - 由相应的set函数定义的变量
     *  - an event in the format of "on xyz": attach the handler to the event "xyz"
     *  - 以on 开头的事件 （注意空格）
     *  - a behavior in the format of "as xyz": attach the behavior named as "xyz"
     *  - 以as 开头的行为
     *  - a property of a behavior: set the behavior property value
     *  - 行为内的属性（似乎是 最先找到的被赋值 后面的不管？）
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$component->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only.
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
        } elseif (strncmp($name, 'on ', 3) === 0) {
            // on event: attach event handler
            $this->on(trim(substr($name, 3)), $value);

            return;
        } elseif (strncmp($name, 'as ', 3) === 0) {
            // as behavior: attach behavior
            $name = trim(substr($name, 3));
            $this->attachBehavior($name, $value instanceof Behavior ? $value : Yii::createObject($value));

            return;
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = $value;

                    return;
                }
            }
        }
        if (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     * 检查属性是否被设置了 也就是 定义了 且值不为空
     * This method will check in the following order and act accordingly:
     * 该方法会考虑以下几种情况
     *
     *  - a property defined by a setter: return whether the property is set
     *  - 相应的get方法能取到不为null的值
     *  - a property of a behavior: return whether the property is set
     *  - 行为中设置了并且不为null值
     *  - return `false` for non existing properties
     *  - 再找不到即返回false
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($component->property)`.
     * @param string $name the property name or the event name
     * @return boolean whether the named property is set
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $behavior->$name !== null;
                }
            }
        }
        return false;
    }

    /**
     * Sets a component property to be null.
     * This method will check in the following order and act accordingly:
     * 还是考虑以下几种方法 本类中有set方法的 行为中有的
     *
     *  - a property defined by a setter: set the property value to be null
     *  - a property of a behavior: set the property value to be null
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($component->property)`.
     * 无需直接调用  使用unset($component->property) 即可
     * 
     * @param string $name the property name
     * @throws InvalidCallException if the property is read only.
     * @see http://php.net/manual/en/function.unset.php
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
            return;
        } else {
            // behavior property
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = null;
                    return;
                }
            }
        }
        throw new InvalidCallException('Unsetting an unknown or read-only property: ' . get_class($this) . '::' . $name);
    }

    /**
     * Calls the named method which is not a class method.
     * 调用本组建类没有的方法时 从行为里找
     *
     * This method will check if any attached behavior has
     * the named method and will execute it if available.
     * 遍历所有行为 哪个有就用第一个 其余的不管 
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     * @param string $name the method name
     * @param array $params method parameters
     * @return mixed the method return value
     * @throws UnknownMethodException when calling unknown method
     */
    public function __call($name, $params)
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**
     * This method is called after the object is created by cloning an existing one.
     * It removes all behaviors because they are attached to the old object.
     * 克隆前 先把本类的行为和事件清空
     */
    public function __clone()
    {
        $this->_events = [];
        $this->_behaviors = null;
    }

    /**
     * Returns a value indicating whether a property is defined for this component.
     * A property is defined if:
     * 根据：
     * 本类是否有该属性 行为内是否有该属性
     * 仅检测get方法 检测属性是否真的存在
     * 检测本组件 检测绑定行为内
     * 以上这六种情况综合判断
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return $this->canGetProperty($name, $checkVars, $checkBehaviors) || $this->canSetProperty($name, false, $checkBehaviors);
    }

    /**
     * Returns a value indicating whether a property can be read.
     * A property can be read if:
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a readable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a value indicating whether a property can be set.
     * A property can be written if:
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a writable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a value indicating whether a method is defined.
     * A method is defined if:
     * 判断是否有给定的方法
     *
     * - the class has a method with the specified name
     * - 判断本类
     * - an attached behavior has a method with the given name (when `$checkBehaviors` is true).
     * - 判断所有行为
     *
     * @param string $name the property name
     * @param boolean $checkBehaviors whether to treat behaviors' methods as methods of this component
     * @return boolean whether the property is defined
     */
    public function hasMethod($name, $checkBehaviors = true)
    {
        if (method_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->hasMethod($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a list of behaviors that this component should behave as.
     * 返回一个本组件应当执行的事件列表
     *
     * Child classes may override this method to specify the behaviors they want to behave as.
     * 子类可以重写本方法以指定所需要的行为
     *
     * The return value of this method should be an array of behavior objects or configurations
     * indexed by behavior names. A behavior configuration can be either a string specifying
     * the behavior class or an array of the following structure:
     * 本方法的返回值是一个以行为对象为元素，或者行为名为键的数组为的元素 组成的数组
     * 一个行为配置元素，可以是一个行为类的字符串名字 也可以是如下结构的数组
     * 
     *
     * ~~~
     * 'behaviorName' => [
     *     'class' => 'BehaviorClass',
     *     'property1' => 'value1',
     *     'property2' => 'value2',
     * ]
     * ~~~
     *
     * Note that a behavior class must extend from [[Behavior]]. Behavior names can be strings
     * or integers. If the former, they uniquely identify the behaviors. If the latter, the corresponding
     * behaviors are anonymous and their properties and methods will NOT be made available via the component
     * (however, the behaviors can still respond to the component's events). 
     * 注意，行为类必须继承自Behavior类。行为的名字可以是字符串或者是整数，如果是前者，则唯一的标识行为。
     * 如果是后者对应的行为是匿名的，并且，它们的属性和方法将不能通过组件使用。（然而，该行为仍然能通过
     * 组件的事件调用）
     *
     * Behaviors declared in this method will be attached to the component automatically (on demand).
     * 在这个方法里定义的行为会自动绑定到本组件内
     *
     * @return array the behavior configurations.
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * Returns a value indicating whether there is any handler attached to the named event.
     * 判断传进来的事件名 上是否有事件句柄
     * @param string $name the event name
     * @return boolean whether there is any handler attached to the event.
     */
    public function hasEventHandlers($name)
    {
        // 先把定义在本类的行为全部绑定上
        $this->ensureBehaviors();
        // 然后载判断有没有这个行为
        // 先看本类的属性里有没有 再看类级方法里有没有
        return !empty($this->_events[$name]) || Event::hasHandlers($this, $name);
    }

    /**
     * Attaches an event handler to an event.
     * 向事件绑定一个事件句柄
     *
     * The event handler must be a valid PHP callback. The following are
     * some examples:
     *
     * ~~~
     * function ($event) { ... }         // anonymous function
     * [$object, 'handleClick']          // $object->handleClick()
     * ['Page', 'handleClick']           // Page::handleClick()
     * 'handleClick'                     // global function handleClick()
     * ~~~
     *
     * The event handler must be defined with the following signature,
     *
     * ~~~
     * function ($event)
     * ~~~
     *
     * where `$event` is an [[Event]] object which includes parameters associated with the event.
     *
     * @param string $name the event name
     * @param callable $handler the event handler
     * @param mixed $data the data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[Event::data]].
     * @param boolean $append whether to append new event handler to the end of the existing
     * handler list. If false, the new handler will be inserted at the beginning of the existing
     * handler list.
     * @see off()
     */
    public function on($name, $handler, $data = null, $append = true)
    {
        $this->ensureBehaviors();
        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
            array_unshift($this->_events[$name], [$handler, $data]);
        }
    }

    /**
     * Detaches an existing event handler from this component.
     * 从当前组件解绑一个已存在的事件句柄
     * This method is the opposite of [[on()]].
     * @param string $name event name
     * @param callable $handler the event handler to be removed.
     * If it is null, all handlers attached to the named event will be removed.
     * @return boolean if a handler is found and detached
     * @see on()
     */
    public function off($name, $handler = null)
    {
        $this->ensureBehaviors();
        if (empty($this->_events[$name])) {
            return false;
        }
        if ($handler === null) {
            unset($this->_events[$name]);
            return true;
        } else {
            $removed = false;
            foreach ($this->_events[$name] as $i => $event) {
                if ($event[0] === $handler) {
                    unset($this->_events[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_events[$name] = array_values($this->_events[$name]);
            }
            return $removed;
        }
    }

    /**
     * Triggers an event.
     * 触发事件
     * This method represents the happening of an event. It invokes
     * all attached handlers for the event including class-level handlers.
     * @param string $name the event name
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     */
    public function trigger($name, Event $event = null)
    {
        $this->ensureBehaviors();
        if (!empty($this->_events[$name])) {
            /**
             * 假如触发的时候没传$event，那就new一个
             * 然后在运行时配置这个新的Event对象。
             */
            if ($event === null) {
                $event = new Event;
            }
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->handled = false;
            $event->name = $name;
            foreach ($this->_events[$name] as $handler) {
                $event->data = $handler[1];
//                调试信息 证明了$handler[0] 是个数组 
//                call_user_func([实例变量,方法名],方法参数);
//                if($name == 'EVENT_GREET'){var_dump($name);var_dump($handler[0]);}
                call_user_func($handler[0], $event);
                // stop further handling if the event is handled
                if ($event->handled) {
                    return;
                }
            }
        }
        // invoke class-level attached handlers
        Event::trigger($this, $name, $event);
    }

    /**
     * Returns the named behavior object.
     * @param string $name the behavior name
     * @return Behavior the behavior object, or null if the behavior does not exist
     */
    public function getBehavior($name)
    {
        $this->ensureBehaviors();
        return isset($this->_behaviors[$name]) ? $this->_behaviors[$name] : null;
    }

    /**
     * Returns all behaviors attached to this component.
     * 获取本组件绑定的所有行为
     * @return Behavior[] list of behaviors attached to this component
     */
    public function getBehaviors()
    {
        $this->ensureBehaviors();
        return $this->_behaviors;
    }

    /**
     * Attaches a behavior to this component.
     * 为本组件绑定一个行为
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be attached to
     * this component by calling the [[Behavior::attach()]] method.
     * 本方法会先根据给出的配置创建一个行为对象，然后，使用Behavior::attach()把行为对象绑定到本组件内
     * @param string $name the name of the behavior.
     * @param string|array|Behavior $behavior the behavior configuration. This can be one of the following:
     *
     *  - a [[Behavior]] object
     *  - a string specifying the behavior class
     *  - an object configuration array that will be passed to [[Yii::createObject()]] to create the behavior object.
     *
     * @return Behavior the behavior object
     * @see detachBehavior()
     */
    public function attachBehavior($name, $behavior)
    {
        $this->ensureBehaviors();
        return $this->attachBehaviorInternal($name, $behavior);
    }

    /**
     * Attaches a list of behaviors to the component.
     * Each behavior is indexed by its name and should be a [[Behavior]] object,
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @see attachBehavior()
     */
    public function attachBehaviors($behaviors)
    {
        $this->ensureBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehaviorInternal($name, $behavior);
        }
    }

    /**
     * Detaches a behavior from the component.
     * The behavior's [[Behavior::detach()]] method will be invoked.
     * 从组件中解绑一个行为
     * 行为对象相应的detach()方法会被执行（双向解绑？）
     * @param string $name the behavior's name.
     * @return Behavior the detached behavior. Null if the behavior does not exist.
     * 会返回解绑后的行为
     */
    public function detachBehavior($name)
    {
        $this->ensureBehaviors();
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();
            return $behavior;
        } else {
            return null;
        }
    }

    /**
     * Detaches all behaviors from the component.
     * 遍历每一个行为 执行解绑
     */
    public function detachBehaviors()
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }

    /**
     * Makes sure that the behaviors declared in [[behaviors()]] are attached to this component.
     * 确保定义在了behaviors()中的行为被绑定在了本组件上 （就是说先把子类定义的行为给绑定上）
     */
    public function ensureBehaviors()
    {
        if ($this->_behaviors === null) {
            $this->_behaviors = [];
            foreach ($this->behaviors() as $name => $behavior) {
                $this->attachBehaviorInternal($name, $behavior);
            }
        }
    }

    /**
     * Attaches a behavior to this component.
     * 将行为绑定在本组件上
     * @param string|integer $name the name of the behavior. If this is an integer, it means the behavior
     * is an anonymous one. Otherwise, the behavior is a named one and any existing behavior with the same name
     * will be detached first.
     * 行为名称。接受字符串和整型，如果是整型，则意味着该行为是匿名的。否则，行为如果是命名的，所有同名的行为会先解绑
     * （再绑定形参内的行为） 有覆盖的作用
     * @param string|array|Behavior $behavior the behavior to be attached
     * @return Behavior the attached behavior.
     */
    private function attachBehaviorInternal($name, $behavior)
    {
        /**
         * 如果$behavior 不是Behavior的示例，则通过
         * Yii::createObject() 创建一个实例
         */
        if (!($behavior instanceof Behavior)) {
            $behavior = Yii::createObject($behavior);
        }
        /**
         * 整型就加到数组尾部
         * 这里的整型指的就是Component::behaviors()返回的数组中的匿名元素。
         * 本方法针对的是Component::behaviors()返回的数组中的每一个元素进行操作的
         * [
         *     'behaviorName' => [
         *         'class' => 'BehaviorClass',
         *         'property1' => 'value1',
         *         'property2' => 'value2',
         *     ]
         * ]
         */
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            /**
             * 非整型 就先解绑所有同名行为，再绑定传来的行为
             */
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }
        return $behavior;
    }
}
