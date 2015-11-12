<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\di\Instance;
use yii\web\User;
use yii\web\ForbiddenHttpException;

/**
 * AccessControl provides simple access control based on a set of rules.
 * AccessControl提供了基于一系列规则的简单访问控制
 *
 * AccessControl is an action filter. It will check its [[rules]] to find
 * the first rule that matches the current context variables (such as user IP address, user role).
 * The matching rule will dictate whether to allow or deny the access to the requested controller
 * action. If no rule matches, the access will be denied.
 * AccessControl本质上是一个动作过滤器，该过滤器会检查自身的[[rules]]，并找到第一个匹配当前上下
 * 文变量（比如IP地址，用户角色）的规则。匹配的规则会指明是否允许使用请求的控制器动作。假如没有规则匹配成功
 * 则表示拒绝使用。
 *
 * To use AccessControl, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will allow authenticated users to access the "create"
 * and "update" actions and deny all other users from accessing these two actions.
 * 要使用AccessControl,需要将它定义在控制器的`behaviors()`方法返回值中。例如，以下代码会允许所有
 * 已登录用户访问"create"和"update"动作，并拒绝其他用户使用这两个动作。【only 中没有列出的操作，
 * 将无条件获得授权】
 *
 * ~~~
 * public function behaviors()
 * {
 *     return [
 *         'access' => [
 *             'class' => \yii\filters\AccessControl::className(),
 *             'only' => ['create', 'update'],
 *             'rules' => [
 *                 // deny all POST requests
 *                 [
 *                     'allow' => false,
 *                     'verbs' => ['POST']
 *                 ],
 *                 // allow authenticated users
 *                 [
 *                     'allow' => true,
 *                     'roles' => ['@'],
 *                 ],
 *                 // everything else is denied
 *             ],
 *         ],
 *     ];
 * }
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessControl extends ActionFilter
{
    /**
     * @var User|array|string the user object representing the authentication status or the ID of the user application component.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     * User实例|数组|字符串 表示进行身份认证的用户实例或用户组件ID，从2.0.2版本开始也可以是一个实例化对象的配置数组。
     */
    public $user = 'user';
    /**
     * @var callable a callback that will be called if the access should be denied
     * to the current user. If not set, [[denyAccess()]] will be called.
     * 当前用户授权失败后的回调函数，假如未设置，则会调用[[denyAccess()]] 方法。
     *
     * The signature of the callback should be as follows:
     * 回调函数的方法签名应该像这样：
     *
     * ~~~
     * function ($rule, $action)
     * ~~~
     *
     * where `$rule` is the rule that denies the user, and `$action` is the current [[Action|action]] object.
     * `$rule` can be `null` if access is denied because none of the rules matched.
     * `$rule`参数表示拒绝用户的那条规则，`$action` 表示当前请求的控制器动作对象。如果任何条件都不匹配，用户被拒绝
     * 访问时，`$rule`参数可以为null。
     */
    public $denyCallback;
    /**
     * @var array the default configuration of access rules. Individual rule configurations
     * specified via [[rules]] will take precedence when the same property of the rule is configured.
     * 数组，访问规则的默认配置。当个人配置的[[rules]]与默认配置的规则相同是，个人配置的规则优先。
     */
    public $ruleConfig = ['class' => 'yii\filters\AccessRule'];
    /**
     * @var array a list of access rule objects or configuration arrays for creating the rule objects.
     * If a rule is specified via a configuration array, it will be merged with [[ruleConfig]] first
     * before it is used for creating the rule object.
     * @see ruleConfig
     * 所有规则对象组成的数组集合，或者是一个创建规则对象的配置数组。假如规则是由配置数组指定的，那么
     * 规则对象配置数组会在被用来创建规则对象前首先覆盖合并[[ruleConfig]]
     */
    public $rules = [];


    /**
     * Initializes the [[rules]] array by instantiating rule objects from configurations.
     * 重写初始化
     */
    public function init()
    {
        parent::init();
        $this->user = Instance::ensure($this->user, User::className());
        // 为每个规则创建相应的规则对象
        foreach ($this->rules as $i => $rule) {
            if (is_array($rule)) {
                $this->rules[$i] = Yii::createObject(array_merge($this->ruleConfig, $rule));
            }
        }
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * 本方法在执行控制器动作之前（所有过滤器之后）被触发。你可以重写本方法为动作做最后的准备。
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $user = $this->user;
        $request = Yii::$app->getRequest();
        /* @var $rule AccessRule */
        foreach ($this->rules as $rule) {
            if ($allow = $rule->allows($action, $user, $request)) {
                return true;
            } elseif ($allow === false) {
                if (isset($rule->denyCallback)) {
                    call_user_func($rule->denyCallback, $rule, $action);
                } elseif (isset($this->denyCallback)) {
                    call_user_func($this->denyCallback, $rule, $action);
                } else {
                    $this->denyAccess($user);
                }
                return false;
            }
        }
        // 如果所有规则都不匹配 
        if (isset($this->denyCallback)) {
            call_user_func($this->denyCallback, null, $action);
        } else {
            $this->denyAccess($user);
        }
        return false;
    }

    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * 拒绝用户访问，如果用户是访客身份，则重定向到登录页，如果已登录，说明权限不够，抛出403错误
     * @param User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess($user)
    {
        if ($user->getIsGuest()) {
            $user->loginRequired();
        } else {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
