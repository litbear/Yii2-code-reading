<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * The AssetConverterInterface must be implemented by asset converter classes.
 * AssetConverterInterface接口是所有静态资源转换类必须实现的接口。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface AssetConverterInterface
{
    /**
     * Converts a given asset file into a CSS or JS file.
     * 将预处理的css和js文件转换为原声的js或css文件
     * @param string $asset the asset file path, relative to $basePath
     * @param string $basePath the directory the $asset is relative to.
     * @return string the converted asset file path, relative to $basePath.
     */
    public function convert($asset, $basePath);
}
