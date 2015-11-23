<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface ManagerInterface
{
    /**
     * Checks if the user has the specified permission.
     * 检查用户是否拥有指定的权限
     * @param string|integer $userId the user ID. This should be either an integer or a string representing
     * the unique identifier of a user. See [[\yii\web\User::id]].
     * 字符串或整型，用户ID，详情参考[[\yii\web\User::id]]
     * @param string $permissionName the name of the permission to be checked against
     * 指定用来验证的权限名
     * @param array $params name-value pairs that will be passed to the rules associated
     * with the roles and permissions assigned to the user.
     * 数组，键值对形式，将传给 分配给用户的角色和权限相关的 规则。
     * @return boolean whether the user has the specified permission.
     * 布尔值，给定用户是否含有指定的权限
     * @throws \yii\base\InvalidParamException if $permissionName does not refer to an existing permission
     */
    public function checkAccess($userId, $permissionName, $params = []);

    /**
     * Creates a new Role object.
     * Note that the newly created role is not added to the RBAC system yet.
     * You must fill in the needed data and call [[add()]] to add it to the system.
     * 创建新的角色对象，注意，新创建的角色尚未添加到RBAC系统。你必须为其填充必要的数据，
     * 并调用[[add()]]方法将其加入系统。
     * @param string $name the role name
     * 角色的名字
     * @return Role the new Role object
     * 新建的角色对象
     */
    public function createRole($name);

    /**
     * Creates a new Permission object.
     * Note that the newly created permission is not added to the RBAC system yet.
     * You must fill in the needed data and call [[add()]] to add it to the system.
     * 创建新的权限对象。注意，新创建的权限对象尚未添加到RBAC系统，你必须为其填充必要的数据，
     * 并调用[[add()]]方法将其加入系统。
     * @param string $name the permission name
     * 字符串，权限名
     * @return Permission the new Permission object
     * 新创建的权限对象
     */
    public function createPermission($name);

    /**
     * Adds a role, permission or rule to the RBAC system.
     * 为RBAC系统新增一个角色，权限或是规则
     * @param Role|Permission|Rule $object
     * @return boolean whether the role, permission or rule is successfully added to the system
     * 布尔值，是否成功被添加
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    public function add($object);

    /**
     * Removes a role, permission or rule from the RBAC system.
     * 从RBAC系统移除一个角色，权限或异常
     * @param Role|Permission|Rule $object
     * @return boolean whether the role, permission or rule is successfully removed
     */
    public function remove($object);

    /**
     * Updates the specified role, permission or rule in the system.
     * 从RBAC系统中修改该角色，权限或异常
     * @param string $name the old name of the role, permission or rule
     * @param Role|Permission|Rule $object
     * @return boolean whether the update is successful
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    public function update($name, $object);

    /**
     * Returns the named role.
     * 根据指定名称返回一个角色
     * @param string $name the role name.
     * @return null|Role the role corresponding to the specified name. Null is returned if no such role.
     */
    public function getRole($name);

    /**
     * Returns all roles in the system.
     * 根据指定名称返回多个角色
     * @return Role[] all roles in the system. The array is indexed by the role names.
     */
    public function getRoles();

    /**
     * Returns the roles that are assigned to the user via [[assign()]].
     * Note that child roles that are not assigned directly to the user will not be returned.
     * 根据指定用户ID返回（通过[[assign()]]方法）分配给这个用户的角色，注意
     * 没有直接分配给用户的子规则不会返回。【不太理解？？？】
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * 字符串或整型，用户ID
     * @return Role[] all roles directly or indirectly assigned to the user. The array is indexed by the role names.
     * 直接或间接分配给指定用户的角色集合。数组名为集合的名
     */
    public function getRolesByUser($userId);

    /**
     * Returns the named permission.
     * 根据权限名返回权限实例
     * @param string $name the permission name.
     * @return null|Permission the permission corresponding to the specified name. Null is returned if no such permission.
     */
    public function getPermission($name);

    /**
     * Returns all permissions in the system.
     * 返回系统中的所有权限实例
     * @return Permission[] all permissions in the system. The array is indexed by the permission names.
     */
    public function getPermissions();

    /**
     * Returns all permissions that the specified role represents.
     * 返回指定角色的所有权限
     * @param string $roleName the role name
     * @return Permission[] all permissions that the role represents. The array is indexed by the permission names.
     */
    public function getPermissionsByRole($roleName);

    /**
     * Returns all permissions that the user has.
     * 返回指定用户的所有权限
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * @return Permission[] all permissions that the user has. The array is indexed by the permission names.
     */
    public function getPermissionsByUser($userId);

    /**
     * Returns the rule of the specified name.
     * 根据用户名返回规则
     * @param string $name the rule name
     * @return null|Rule the rule object, or null if the specified name does not correspond to a rule.
     */
    public function getRule($name);

    /**
     * Returns all rules available in the system.
     * 返回系统中所有可用的规则
     * @return Rule[] the rules indexed by the rule names
     */
    public function getRules();

    /**
     * Adds an item as a child of another item.
     * 为项目添加一个子项目
     * @param Item $parent
     * @param Item $child
     * @throws \yii\base\Exception if the parent-child relationship already exists or if a loop has been detected.
     */
    public function addChild($parent, $child);

    /**
     * Removes a child from its parent.
     * Note, the child item is not deleted. Only the parent-child relationship is removed.
     * 为项目移除一个子项目。注意，子项目项目本身没被删除，只删除了子项目与父项目的关系
     * @param Item $parent
     * @param Item $child
     * @return boolean whether the removal is successful
     */
    public function removeChild($parent, $child);

    /**
     * Removed all children form their parent.
     * Note, the children items are not deleted. Only the parent-child relationships are removed.
     * 为项目移除所有子项目，注意，不是删除子项目，而是删除项目间的父子关系
     * @param Item $parent
     * @return boolean whether the removal is successful
     */
    public function removeChildren($parent);

    /**
     * Returns a value indicating whether the child already exists for the parent.
     * 判断父项目是否包含指定的子项目
     * @param Item $parent
     * @param Item $child
     * @return boolean whether `$child` is already a child of `$parent`
     */
    public function hasChild($parent, $child);

    /**
     * Returns the child permissions and/or roles.
     * 返回子权限 和/或 子规则
     * @param string $name the parent name
     * @return Item[] the child permissions and/or roles
     */
    public function getChildren($name);

    /**
     * Assigns a role to a user.
     * 为用户分配一个角色
     * 
     * @param Role $role
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * @return Assignment the role assignment information.
     * @throws \Exception if the role has already been assigned to the user
     */
    public function assign($role, $userId);

    /**
     * Revokes a role from a user.
     * 从用户撤出一个角色
     * @param Role $role
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * @return boolean whether the revoking is successful
     */
    public function revoke($role, $userId);

    /**
     * Revokes all roles from a user.
     * 从用户撤出所有角色
     * @param mixed $userId the user ID (see [[\yii\web\User::id]])
     * @return boolean whether the revoking is successful
     */
    public function revokeAll($userId);

    /**
     * Returns the assignment information regarding a role and a user.
     * 返回关于角色和用户的分配信息
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * @param string $roleName the role name
     * @return null|Assignment the assignment information. Null is returned if
     * the role is not assigned to the user.
     */
    public function getAssignment($roleName, $userId);

    /**
     * Returns all role assignment information for the specified user.
     * 返回指定用户的所有角色的分配信息
     * @param string|integer $userId the user ID (see [[\yii\web\User::id]])
     * @return Assignment[] the assignments indexed by role names. An empty array will be
     * returned if there is no role assigned to the user.
     */
    public function getAssignments($userId);

    /**
     * Removes all authorization data, including roles, permissions, rules, and assignments.
     * 移除所有的授权信息，包括角色，权限，规则和（与用户的）分配关系
     */
    public function removeAll();

    /**
     * Removes all permissions.
     * All parent child relations will be adjusted accordingly.
     * 移除所有的权限，所有（权限的？）父子关系都要进行相应的调整
     */
    public function removeAllPermissions();

    /**
     * Removes all roles.
     * All parent child relations will be adjusted accordingly.
     * 移除所有角色，所有角色的父子关系都要进行相应的调整
     */
    public function removeAllRoles();

    /**
     * Removes all rules.
     * All roles and permissions which have rules will be adjusted accordingly.
     * 移除所有规则，所有规则的父子关系都要进行相应的调整
     */
    public function removeAllRules();

    /**
     * Removes all role assignments.
     * 移除移除所有角色的分配
     */
    public function removeAllAssignments();
}
