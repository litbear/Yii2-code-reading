<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Configurable is the interface that should be implemented by classes who support configuring
 * its properties through the last parameter to its constructor.
 * Configurable 是一个支持通过构造器最后一个函数配制对象的接口
 *
 * The interface does not declare any method. Classes implementing this interface must declare their constructors
 * like the following:
 * 此接口中未定义任何方法，实现此接口的任何类都必须像如下这样定义它的构造器
 *
 * ```php
 * public function __constructor($param1, $param2, ..., $config = [])
 * ```
 *
 * That is, the last parameter of the constructor must accept a configuration array.
 * 像这样，最后一个参数接收一个配置数组
 *
 * This interface is mainly used by [[\yii\di\Container]] so that it can pass object configuration as the
 * last parameter to the implementing class' constructor.
 * 此接口主要用在[[\yii\di\Container]]容器中，以便……后面车轱辘话同上，不翻了
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0.3
 */
interface Configurable
{
}

