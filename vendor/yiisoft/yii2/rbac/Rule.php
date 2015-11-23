<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

use yii\base\Object;

/**
 * Rule represents a business constraint that may be associated with a role, permission or assignment.
 * Rule类实例代表了一个与角色，权限或分配关系相关的业务约束。
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 2.0
 */
abstract class Rule extends Object
{
    /**
     * @var string name of the rule
     * 规则的名称
     */
    public $name;
    /**
     * @var integer UNIX timestamp representing the rule creation time
     * 规则创建的时间戳
     */
    public $createdAt;
    /**
     * @var integer UNIX timestamp representing the rule updating time
     * 规则修改的时间戳
     */
    public $updatedAt;


    /**
     * Executes the rule.
     * 执行规则
     *
     * @param string|integer $user the user ID. This should be either an integer or a string representing
     * the unique identifier of a user. See [[\yii\web\User::id]].
     * @param Item $item the role or permission that this rule is associated with
     * 与本条规则有关的角色或权限
     * @param array $params parameters passed to [[ManagerInterface::checkAccess()]].
     * @return boolean a value indicating whether the rule permits the auth item it is associated with.
     */
    abstract public function execute($user, $item, $params);
}
