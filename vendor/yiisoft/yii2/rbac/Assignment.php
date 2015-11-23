<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

use Yii;
use yii\base\Object;

/**
 * Assignment represents an assignment of a role to a user.
 * Assignment类代表了角色与用户的对应关系
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Kochetov <creocoder@gmail.com>
 * @since 2.0
 */
class Assignment extends Object
{
    /**
     * @var string|integer user ID (see [[\yii\web\User::id]])
     * 用户ID
     */
    public $userId;
    /**
     * @return string the role name
     * 角色名
     */
    public $roleName;
    /**
     * @var integer UNIX timestamp representing the assignment creation time
     * 分配创建时间戳
     */
    public $createdAt;
}
