<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

use yii\base\Object;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Item extends Object
{
    const TYPE_ROLE = 1;
    const TYPE_PERMISSION = 2;

    /**
     * @var integer the type of the item. This should be either [[TYPE_ROLE]] or [[TYPE_PERMISSION]].
     * 整型，认证项目的类型，不是角色就是权限
     */
    public $type;
    /**
     * @var string the name of the item. This must be globally unique.
     * 认证项目的名称，必须是全局唯一的
     */
    public $name;
    /**
     * @var string the item description
     * 字符串，项目的描述
     */
    public $description;
    /**
     * @var string name of the rule associated with this item
     * 字符串，与本认证项目有关的规则名称
     */
    public $ruleName;
    /**
     * @var mixed the additional data associated with this item
     * 混合类型，认证项目的附加信息
     */
    public $data;
    /**
     * @var integer UNIX timestamp representing the item creation time
     * 创建本项目的时间戳
     */
    public $createdAt;
    /**
     * @var integer UNIX timestamp representing the item updating time
     * 修改本项目的时间戳
     */
    public $updatedAt;
}
