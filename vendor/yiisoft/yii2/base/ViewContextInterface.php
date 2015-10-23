<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ViewContextInterface is the interface that should implemented by classes who want to support relative view names.
 * ViewContextInterface 是所有支持相对视图名称的类需要实现的接口
 *
 * The method [[getViewPath()]] should be implemented to return the view path that may be prefixed to a relative view name.
 *  [[getViewPath()]] 的返回值应该是带有前缀的相对视图名称
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface ViewContextInterface
{
    /**
     * @return string the view path that may be prefixed to a relative view name.
     */
    public function getViewPath();
}
