<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use Yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;

/**
 * VerbFilter is an action filter that filters by HTTP request methods.
 * 动词过滤器可以过滤掉不希望使用的HTTP请求方法
 *
 * It allows to define allowed HTTP request methods for each action and will throw
 * an HTTP 405 error when the method is not allowed.
 * 本类可以为每个动作(action)定于允许的HTTP请求方法。当使用不被允许的方法请求时，会
 * 抛出405错误
 *
 * To use VerbFilter, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will define a typical set of allowed
 * request methods for REST CRUD actions.
 * 使用本过滤器，可以在你的控制器类的`behaviors()` 方法中定义配置数组。例如，以下代码
 * 定义了对REST风格CURD动作允许的请求方法的集合。
 *
 * ~~~
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => \yii\filters\VerbFilter::className(),
 *             'actions' => [
 *                 'index'  => ['get'],
 *                 'view'   => ['get'],
 *                 'create' => ['get', 'post'],
 *                 'update' => ['get', 'put', 'post'],
 *                 'delete' => ['post', 'delete'],
 *             ],
 *         ],
 *     ];
 * }
 * ~~~
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class VerbFilter extends Behavior
{
    /**
     * @var array this property defines the allowed request methods for each action.
     * For each action that should only support limited set of request methods
     * you add an entry with the action id as array key and an array of
     * allowed methods (e.g. GET, HEAD, PUT) as the value.
     * If an action is not listed all request methods are considered allowed.
     *
     * You can use '*' to stand for all actions. When an action is explicitly
     * specified, it takes precedence over the specification given by '*'.
     *
     * For example,
     *
     * ~~~
     * [
     *   'create' => ['get', 'post'],
     *   'update' => ['get', 'put', 'post'],
     *   'delete' => ['post', 'delete'],
     *   '*' => ['get'],
     * ]
     * ~~~
     */
    public $actions = [];


    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        /**
         * 这里的数组元素[Controller::EVENT_BEFORE_ACTION => 'beforeAction']
         * 'beforeAction'虽然是字符串，但是在调用yii\base\Behavior::on()的时候
         * 会执行$owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
         * 这句，也就是着本行为行为将自己的一个方法绑定到了Controller的EVENT_BEFORE_ACTION
         * 事件上。
         */
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * @param ActionEvent $event
     * @return boolean
     * @throws MethodNotAllowedHttpException when the request method is not allowed.
     */
    public function beforeAction($event)
    {
        $action = $event->action->id;
        if (isset($this->actions[$action])) {
            $verbs = $this->actions[$action];
        } elseif (isset($this->actions['*'])) {
            $verbs = $this->actions['*'];
        } else {
            return $event->isValid;
        }

        $verb = Yii::$app->getRequest()->getMethod();
        $allowed = array_map('strtoupper', $verbs);
        if (!in_array($verb, $allowed)) {
            $event->isValid = false;
            // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
            Yii::$app->getResponse()->getHeaders()->set('Allow', implode(', ', $allowed));
            throw new MethodNotAllowedHttpException('Method Not Allowed. This url can only handle the following request methods: ' . implode(', ', $allowed) . '.');
        }

        return $event->isValid;
    }
}
