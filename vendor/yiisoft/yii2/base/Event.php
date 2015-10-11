<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Event is the base class for all event classes.
 * Event是所有事件的基类
 * 【似乎针对event类直接绑定的事件都是类级事件？】
 *
 * It encapsulates the parameters associated with an event.
 * The [[sender]] property describes who raises the event.
 * And the [[handled]] property indicates if the event is handled.
 * If an event handler sets [[handled]] to be true, the rest of the
 * uninvoked handlers will no longer be called to handle the event.
 * 本类将所有事件相关的参数封装为一个类。sender属性用来表示谁绑定了
 * 事件。handled属性用来表示本事件是否已处理，假如一个事件句柄的handled
 * 属性值为true，那么剩下的为被执行的句柄将不再被本事件调用
 * 
 * 
 * Additionally, when attaching an event handler, extra data may be passed
 * and be available via the [[data]] property when the event handler is invoked.
 * 此外，当事件句柄被绑定时，额外的数据会被传入，并且当事件句柄被执行时，可以动过
 * 该类的data属性访问到
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Event extends Object
{
    /**
     * @var string the event name. This property is set by [[Component::trigger()]] and [[trigger()]].
     * Event handlers may use this property to check what event it is handling.
     * 事件句柄用name属性表示他们被绑定在了什么事件上（就是事件名）
     */
    public $name;
    /**
     * @var object the sender of this event. If not set, this property will be
     * set as the object whose "trigger()" method is called.
     * This property may also be a `null` when this event is a
     * class-level event which is triggered in a static context.
     * sender属性表示发送（此处不明白）了这个事件的类。假如没设置这个属性，
     * 则会用调用了trigger()的那个对象昂代替。当该事件为在惊呆上下文中触发
     * 的类级事件的时候，这个属性也可以设为null，
     */
    public $sender;
    /**
     * @var boolean whether the event is handled. Defaults to false.
     * When a handler sets this to be true, the event processing will stop and
     * ignore the rest of the uninvoked event handlers.
     * 布尔值，表示事件是否已被处理，当handled设置为true的时候，事件进程将会终止，
     * 并且剩下的事件句柄将跳过、不被执行
     */
    public $handled = false;
    /**
     * @var mixed the data that is passed to [[Component::on()]] when attaching an event handler.
     * Note that this varies according to which event handler is currently executing.
     * 在事件句柄被绑定的时候，[[Component::on()]]传入的数据，该属性取决于正在执行的句柄
     */
    public $data;

    private static $_events = [];


    /**
     * Attaches an event handler to a class-level event.
     * 为类级事件绑定事件句柄
     *
     * When a class-level event is triggered, event handlers attached
     * to that class and all parent classes will be invoked.
     * 当类级事件被触发后，绑定在该类和所有父类的事件句柄都会被执行
     *
     * For example, the following code attaches an event handler to `ActiveRecord`'s
     * `afterInsert` event:
     * 例如，以下代码为`ActiveRecord`的插入后事件绑定了一个事件句柄
     *
     * ~~~
     * Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
     *     Yii::trace(get_class($event->sender) . ' is inserted.');
     * });
     * ~~~
     *
     * The handler will be invoked for EVERY successful ActiveRecord insertion.
     * 该句柄会在所有ActiveRecord成功插入后执行
     *
     * For more details about how to declare an event handler, please refer to [[Component::on()]].
     * 更多关于如何定义事件句柄的细节，请参考[[Component::on()]]
     *
     * @param string $class the fully qualified class name to which the event handler needs to attach.
     * 被事件句柄所绑定的类的全限定名
     * @param string $name the event name.
     * 事件名称
     * @param callable $handler the event handler.
     * 事件句柄——一个有效的php回调
     * @param mixed $data the data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[Event::data]].
     * 绑定句柄时传入的，在事件被触发时可被（事件句柄？）访问的额外数据
     * @param boolean $append whether to append new event handler to the end of the existing
     * handler list. If false, the new handler will be inserted at the beginning of the existing
     * handler list.
     * 默认为真，则添加到事件句柄列表队尾，否则添加到开头
     * @see off()
     */
    public static function on($class, $name, $handler, $data = null, $append = true)
    {
        /**
         * 去掉类全限定名前面的'\'，队列为空或不追加，则新增一个元素
         * 追加则使用array_unshift提到第一位
         */
        $class = ltrim($class, '\\');
        if ($append || empty(self::$_events[$name][$class])) {
            self::$_events[$name][$class][] = [$handler, $data];
        } else {
            array_unshift(self::$_events[$name][$class], [$handler, $data]);
        }
    }

    /**
     * Detaches an event handler from a class-level event.
     * 从类级事件中解绑一个事件句柄
     *
     * This method is the opposite of [[on()]].
     * 此方法是on()的反方法
     *
     * @param string $class the fully qualified class name from which the event handler needs to be detached.
     * 待解绑类的全限定名
     * @param string $name the event name.
     * 事件名
     * @param callable $handler the event handler to be removed.
     * If it is null, all handlers attached to the named event will be removed.
     * 待移除的事件句柄（函数调用），如果是空的，则事件下的所有句柄都会被移除
     * @return boolean whether a handler is found and detached.
     * @see on()
     */
    public static function off($class, $name, $handler = null)
    {
        $class = ltrim($class, '\\');
        // 假如没找到该类 返回false
        if (empty(self::$_events[$name][$class])) {
            return false;
        }
        /**
         * 假如句柄严格等于null 则该事件名下所有事件全部删除
         */
        if ($handler === null) {
            unset(self::$_events[$name][$class]);
            return true;
        } else {
            /**
             * 假如句柄不为空。则遍历相应的数组
             * 招到并删除
             * $remove 的作用是为了去掉空位，
             * 比如0,1,2,3去掉了2，变成0,1,3，
             * array_values之后重新变成0,1,2
             */
            $removed = false;
            foreach (self::$_events[$name][$class] as $i => $event) {
                if ($event[0] === $handler) {
                    unset(self::$_events[$name][$class][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                self::$_events[$name][$class] = array_values(self::$_events[$name][$class]);
            }

            return $removed;
        }
    }

    /**
     * Returns a value indicating whether there is any handler attached to the specified class-level event.
     * Note that this method will also check all parent classes to see if there is any handler attached
     * to the named event.
     * 返回一个布尔值，用来表明是否有任何的句柄绑定在指定的类级事件上
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @return boolean whether there is any handler attached to the event.
     */
    public static function hasHandlers($class, $name)
    {
        if (empty(self::$_events[$name])) {
            return false;
        }
        /**
         * 根据$class的不同类型 取出全限定名
         * 查看该类或者该类的父类是否绑定了事件
         * 父类绑定事件也算
         */
        if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
        do {
            if (!empty(self::$_events[$name][$class])) {
                return true;
            }
        } while (($class = get_parent_class($class)) !== false);

        return false;
    }

    /**
     * Triggers a class-level event.
     * 触发一个类级事件的静态方法
     * This method will cause invocation of event handlers that are attached to the named event
     * for the specified class and all its parent classes.
     * 
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     */
    public static function trigger($class, $name, $event = null)
    {
        /*
         * 指定事件名对应的值为空则直接返回
         */
        if (empty(self::$_events[$name])) {
            return;
        }
        /**
         * 如果事件（这里的是Event的类或其子类的实例）是空的
         * 那么久new一个，注意，如果是子类的话就new相应的子类，
         * 而不是父类Event
         */
        if ($event === null) {
            $event = new static;
        }
        $event->handled = false;
        $event->name = $name;

        if (is_object($class)) {
            if ($event->sender === null) {
                $event->sender = $class;
            }
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
        do {
            if (!empty(self::$_events[$name][$class])) {
                foreach (self::$_events[$name][$class] as $handler) {
                    $event->data = $handler[1];
                    call_user_func($handler[0], $event);
                    if ($event->handled) {
                        return;
                    }
                }
            }
        } while (($class = get_parent_class($class)) !== false);
    }
}
