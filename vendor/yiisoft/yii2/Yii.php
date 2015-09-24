<?php
/**
 * Yii bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

require(__DIR__ . '/BaseYii.php');
//var_dump(YII2_PATH);die; 返回的是
//D:\wamp\www\yii2-app-basic\vendor\yiisoft\yii2

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Yii extends \yii\BaseYii
{
}
/**
 * spl_autoload_register() 函数第三个参数为true则
 * 会将Yii::autoload 置于自动加载函数队列之首 有思安使用
 */
spl_autoload_register(['Yii', 'autoload'], true, true);
//引入同文件夹下的class.php文件 此文件返回类的集合
Yii::$classMap = require(__DIR__ . '/classes.php');
//初始化依赖注入的容器
Yii::$container = new yii\di\Container();
