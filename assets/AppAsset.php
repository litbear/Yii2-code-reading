<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    // 字符串，对web服务软件可读的，包含处理后的静态资源文件包的文件夹。
    public $basePath = '@webroot';
    // 字符串，静态资源文件的基本URL地址
    public $baseUrl = '@web';
    // 数组，本资源包包含的CSS文件列表。
    public $css = [
        'css/site.css',
    ];
    // 数组，本资源包包含的JS文件列表
    public $js = [
    ];
    // 数组，当前静态资源库依赖的其他资源库类名。
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
