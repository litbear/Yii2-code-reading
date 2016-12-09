<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ActionFilter is the base class for action filters.
 * ActionFilter 是所有动作过滤器类的基类
 *
 * An action filter will participate in the action execution workflow by responding to
 * the `beforeAction` and `afterAction` events triggered by modules and controllers.
 * 动作过滤器会在动作中通过对模型和数据触发的`beforeAction` 和 `afterAction`两个事件作出反应
 * 来参与解析工作流。
 *
 * Check implementation of [[\yii\filters\AccessControl]], [[\yii\filters\PageCache]] and [[\yii\filters\HttpCache]] as examples on how to use it.
 * 查看 [[\yii\filters\AccessControl]], [[\yii\filters\PageCache]] 和 [[\yii\filters\HttpCache]] 三个类的实现，理解如何用它
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActionFilter extends Behavior
{
    /**
     * @var array list of action IDs that this filter should apply to. If this property is not set,
     * then the filter applies to all actions, unless they are listed in [[except]].
     * If an action ID appears in both [[only]] and [[except]], this filter will NOT apply to it.
     * 数组，要应用送做过滤器的控制器动作ID的集合，假如未设置本属性，则动作过滤器会应用所有[[except]]属性以外
     * 的动作，假如有动作ID既出现在[[only]]属性中也出现在[[except]]属性中，则控制器不会对其经行处理。
     *
     * Note that if the filter is attached to a module, the action IDs should also include child module IDs (if any)
     * and controller IDs.
     * 注意，假如动作过滤器绑定在模块之上，则控制器动作集合中的元素要包括模块ID和控制器ID
     *
     * @see except
     */
    public $only;
    /**
     * @var array list of action IDs that this filter should not apply to.
     * 要排除过滤器的控制器动作列表
     * @see only
     */
    public $except = [];


    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        $owner->on(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        if ($this->owner) {
            $this->owner->off(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
            $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
            $this->owner = null;
        }
    }

    /**
     * @param ActionEvent $event
     */
    public function beforeFilter($event)
    {
        if (!$this->isActive($event->action)) {
            return;
        }

        $event->isValid = $this->beforeAction($event->action);
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
            $this->owner->on(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter'], null, false);
        } else {
            $event->handled = true;
        }
    }

    /**
     * @param ActionEvent $event
     */
    public function afterFilter($event)
    {
        $event->result = $this->afterAction($event->action, $event->result);
        $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        return true;
    }

    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param Action $action the action just executed.
     * @param mixed $result the action execution result
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        return $result;
    }

    /**
     * Returns a value indicating whether the filer is active for the given action.
     * @param Action $action the action being filtered
     * @return boolean whether the filer is active for the given action.
     */
    protected function isActive($action)
    {
        if ($this->owner instanceof Module) {
            // convert action uniqueId into an ID relative to the module
            $mid = $this->owner->getUniqueId();
            $id = $action->getUniqueId();
            if ($mid !== '' && strpos($id, $mid) === 0) {
                $id = substr($id, strlen($mid) + 1);
            }
        } else {
            $id = $action->id;
        }
        return !in_array($id, $this->except, true) && (empty($this->only) || in_array($id, $this->only, true));
    }
}
