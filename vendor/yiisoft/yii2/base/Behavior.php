<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Behavior is the base class for all behavior classes.
 * Behavior是所有行为类的基类
 *
 * A behavior can be used to enhance the functionality of an existing component without modifying its code.
 * In particular, it can "inject" its own methods and properties into the component
 * and make them directly accessible via the component. It can also respond to the events triggered in the component
 * and thus intercept the normal code execution.
 * 行为可以在不修改已存在组件的情况下增强它的功能。特别是，它可以把自己的方法和属性注入到组件中
 * 使他们可以直接被组件访问，同时，也可以在组件触发事件时做出响应 因此拦截正常代码的执行
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Behavior extends Object
{
    /**
     * @var Component the owner of this behavior
     * 一个Component 类的实例
     * 表示事件的所有者
     */
    public $owner;


    /**
     * Declares event handlers for the [[owner]]'s events.
     * 为事件所有者定义事件句柄
     *
     * Child classes may override this method to declare what PHP callbacks should
     * be attached to the events of the [[owner]] component.
     * 子类也许会重写这个方法，用来规定 哪种PHP回调才能被绑定到（事件所有者，即Component）的事件上
     *
     * The callbacks will be attached to the [[owner]]'s events when the behavior is
     * attached to the owner; and they will be detached from the events when
     * the behavior is detached from the component.
     * 当行为绑定到事件所有者上的时候，毁掉也会绑定在事件所有者上
     * 行为解绑后，回调也随之解绑
     *
     * The callbacks can be any of the following:
     * 回调必须是如下几种形式（没有全局函数？）：
     *
     * - method in this behavior: `'handleClick'`, equivalent to `[$this, 'handleClick']`
     * - 本行为中的方法: 用`'handleClick'`, 等同于 `[$this, 'handleClick']`
     * - object method: `[$object, 'handleClick']`
     * - 对象的方法 
     * - static method: `['Page', 'handleClick']`
     * - 类的静态方法
     * - anonymous function: `function ($event) { ... }`
     * - 匿名函数
     *
     * The following is an example:
     *
     * ~~~
     * [
     *     Model::EVENT_BEFORE_VALIDATE => 'myBeforeValidate',
     *     Model::EVENT_AFTER_VALIDATE => 'myAfterValidate',
     * ]
     * ~~~
     *
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return [];
    }

    /**
     * Attaches the behavior object to the component.
     * 绑定行为对象到组件上
     * The default implementation will set the [[owner]] property
     * and attach event handlers as declared in [[events]].
     * 默认的实现会为本行为对象设置属性owner以及像enents()方法返回的那样绑定事件句柄
     * Make sure you call the parent implementation if you override this method.
     * 假如重写本方法，请确保调用了父类的实现。
     * @param Component $owner the component that this behavior is to be attached to.
     * 参数$owner 是一个Component实例
     * 该实例就是本行为所绑定的组件
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        foreach ($this->events() as $event => $handler) {
            // 如果events()返回的数组元素中值为字符串，则默认使用[$this, $handler]
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    /**
     * Detaches the behavior object from the component.
     * 从组件中解绑行为对象
     * The default implementation will unset the [[owner]] property
     * and detach event handlers declared in [[events]].
     * 默认的逻辑会使本对象的owner属性为null，并且从绑定的组件中逐一解绑本行为中的事件句柄
     * Make sure you call the parent implementation if you override this method.
     * 假如你在子类中重写了此方法，请确保调用了父类方法
     */
    public function detach()
    {
        if ($this->owner) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
}
