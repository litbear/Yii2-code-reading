<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

use yii\base\Component;
use yii\base\Action;
use yii\web\User;
use yii\web\Request;
use yii\base\Controller;

/**
 * This class represents an access rule defined by the [[AccessControl]] action filter
 * [[AccessControl]]动作过滤器中定义的访问规则类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessRule extends Component
{
    /**
     * @var boolean whether this is an 'allow' rule or 'deny' rule.
     * 布尔值，判断本规则类是允许规则还是拒绝规则
     */
    public $allow;
    /**
     * @var array list of action IDs that this rule applies to. The comparison is case-sensitive.
     * If not set or empty, it means this rule applies to all actions.
     * 数组，应用本条规则的控制器动作ID集合，大小写敏感。如果是空的或者未设置，则对所有控制器动作应用本条规则
     */
    public $actions;
    /**
     * @var array list of controller IDs that this rule applies to. The comparison is case-sensitive.
     * If not set or empty, it means this rule applies to all controllers.
     * 数组，应用本条规则的控制器ID集合。大小写敏感，如果是空的或者未设置，则对所有控制器应用本条规则
     */
    public $controllers;
    /**
     * @var array list of roles that this rule applies to. Two special roles are recognized, and
     * they are checked via [[User::isGuest]]:
     * 数组，本条规则允许的所有角色组成的集合，可以识别两种角色，由[[User::isGuest]]属性进行检查：
     *
     * - `?`: matches a guest user (not authenticated yet)
     * - `?`: 未登录的访客
     * - `@`: matches an authenticated user
     * - `@`:登录的用户
     *
     * If you are using RBAC (Role-Based Access Control), you may also specify role or permission names.
     * In this case, [[User::can()]] will be called to check access.
     * 假如使用了RBAC，同样可以为本属性指定角色名或者权限名。
     *
     * If this property is not set or empty, it means this rule applies to all roles.
     * 假如本属性未指定或者为空值，则意味着对所有角色应用本属性。
     */
    public $roles;
    /**
     * @var array list of user IP addresses that this rule applies to. An IP address
     * can contain the wildcard `*` at the end so that it matches IP addresses with the same prefix.
     * For example, '192.168.*' matches all IP addresses in the segment '192.168.'.
     * If not set or empty, it means this rule applies to all IP addresses.
     * 数组，本访问规则接受的IP地址，可以使用`*`通配符。例如就不翻译了，假如未设置本属性，或设置空值，则意味着
     * 接受所有IP地址。
     * @see Request::userIP
     */
    public $ips;
    /**
     * @var array list of request methods (e.g. `GET`, `POST`) that this rule applies to.
     * The request methods must be specified in uppercase.
     * If not set or empty, it means this rule applies to all request methods.
     * 本条访问规则接受的所有HTTP方法名组成的集合。HTTP方法名必须是大写的。假如未设置或者设为空值，则意味着
     * 接受所有HTTP方法
     * @see \yii\web\Request::method
     */
    public $verbs;
    /**
     * @var callable a callback that will be called to determine if the rule should be applied.
     * The signature of the callback should be as follows:
     * 判断是否通过本规则的回调函数，方法签名如下：
     *
     * ~~~
     * function ($rule, $action)
     * ~~~
     *
     * where `$rule` is this rule, and `$action` is the current [[Action|action]] object.
     * The callback should return a boolean value indicating whether this rule should be applied.
     * 参数中的`$rule`指的是本规则， `$action`指的是当前控制器动作。回调函数的返回值为布尔值。
     */
    public $matchCallback;
    /**
     * @var callable a callback that will be called if this rule determines the access to
     * the current action should be denied. If not set, the behavior will be determined by
     * [[AccessControl]].
     *
     * The signature of the callback should be as follows:
     * 判断是否被本规则拒绝的回调函数，方法签名如下：
     *
     * ~~~
     * function ($rule, $action)
     * ~~~
     *
     * where `$rule` is this rule, and `$action` is the current [[Action|action]] object.
     */
    public $denyCallback;


    /**
     * Checks whether the Web user is allowed to perform the specified action.
     * 确认是否允许当前用户执行指定的控制器动作
     * @param Action $action the action to be performed
     * @param User $user the user object
     * @param Request $request
     * @return boolean|null true if the user is allowed, false if the user is denied, null if the rule does not apply to the user
     */
    public function allows($action, $user, $request)
    {
        // 先看各项规则要素，如果当前请求都匹配了说明本条规则应该用于本次请求
        if ($this->matchAction($action)
            && $this->matchRole($user)
            && $this->matchIP($request->getUserIP())
            && $this->matchVerb($request->getMethod())
            && $this->matchController($action->controller)
            && $this->matchCustom($action)
        ) {
            // 再看本条规则是允许访问还是拒绝访问
            return $this->allow ? true : false;
        } else {
            return null;
        }
    }

    /**
     * @param Action $action the action
     * @return boolean whether the rule applies to the action
     */
    protected function matchAction($action)
    {
        return empty($this->actions) || in_array($action->id, $this->actions, true);
    }

    /**
     * @param Controller $controller the controller
     * @return boolean whether the rule applies to the controller
     */
    protected function matchController($controller)
    {
        return empty($this->controllers) || in_array($controller->uniqueId, $this->controllers, true);
    }

    /**
     * @param User $user the user object
     * @return boolean whether the rule applies to the role
     */
    protected function matchRole($user)
    {
        if (empty($this->roles)) {
            return true;
        }
        foreach ($this->roles as $role) {
            if ($role === '?') {
                if ($user->getIsGuest()) {
                    return true;
                }
            } elseif ($role === '@') {
                if (!$user->getIsGuest()) {
                    return true;
                }
            } elseif ($user->can($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $ip the IP address
     * @return boolean whether the rule applies to the IP address
     */
    protected function matchIP($ip)
    {
        if (empty($this->ips)) {
            return true;
        }
        foreach ($this->ips as $rule) {
            if ($rule === '*' || $rule === $ip || (($pos = strpos($rule, '*')) !== false && !strncmp($ip, $rule, $pos))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $verb the request method
     * @return boolean whether the rule applies to the request
     */
    protected function matchVerb($verb)
    {
        return empty($this->verbs) || in_array($verb, $this->verbs, true);
    }

    /**
     * @param Action $action the action to be performed
     * @return boolean whether the rule should be applied
     */
    protected function matchCustom($action)
    {
        return empty($this->matchCallback) || call_user_func($this->matchCallback, $this, $action);
    }
}
