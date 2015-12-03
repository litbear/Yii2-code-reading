<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\Object;
use yii\helpers\Url;
use Yii;

/**
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 * AssetBundle资源包类表示一系列CSS，JS，图片等静态资源的集合。
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 * 每个资源包都有一个在应用内所有资源包中唯一识别的名称。名字就是类的全限定名
 *
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 * 资源包可以依赖其他资源包。当向视图注册资源包的时候，依赖的资源包会被自动注册。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AssetBundle extends Object
{
    /**
     * @var string the directory that contains the source asset files for this asset bundle.
     * A source asset file is a file that is part of your source code repository of your Web application.
     * 字符串，包含本资源包所有静态资源文件的文件夹。静态资源文件是你web应用源代码库的一部分。
     *
     * You must set this property if the directory containing the source asset files is not Web accessible.
     * By setting this property, [[AssetManager]] will publish the source asset files
     * to a Web-accessible directory automatically when the asset bundle is registered on a page.
     * 如果包含静态资源文件的文件夹对web服务软件是不可读的，那么你必须设置这个属性，通过设置本属性，
     * 静态资源管理器会在向页面注册静态资源包的时候自动地将静态资源文件的源文件（预编译代码，SCSS等等）
     * 发布到web服务软件可访问的文件夹下。
     *
     * If you do not set this property, it means the source asset files are located under [[basePath]].
     * 假如你没有设置本属性，就意味着静态资源文件位于[[basePath]]属性指向的文件夹下。
     *
     * You can use either a directory or an alias of the directory.
     * 本属性可使用绝对文件路径或者路径别名。
     * @see $publishOptions
     */
    public $sourcePath;
    /**
     * @var string the Web-accessible directory that contains the asset files in this bundle.
     * 字符串，对web服务软件可读的，包含处理后的静态资源文件包的文件夹。
     *
     * If [[sourcePath]] is set, this property will be *overwritten* by [[AssetManager]]
     * when it publishes the asset files from [[sourcePath]].
     * 假如设置了[[sourcePath]] 属性，本属性会被静态资源管理器对象（在发布来自[[sourcePath]] 
     * 的文件时）覆盖。
     *
     * You can use either a directory or an alias of the directory.
     * 可以使用别名。
     */
    public $basePath;
    /**
     * @var string the base URL for the relative asset files listed in [[js]] and [[css]].
     * 字符串，静态资源文件的基本URL地址
     *
     * If [[sourcePath]] is set, this property will be *overwritten* by [[AssetManager]]
     * when it publishes the asset files from [[sourcePath]].
     * 假如设置了[[sourcePath]] 属性，本属性会被静态资源管理器对象（在发布来自[[sourcePath]] 
     * 的文件时）覆盖。
     *
     * You can use either a URL or an alias of the URL.
     * 可以使用别名。
     */
    public $baseUrl;
    /**
     * @var array list of bundle class names that this bundle depends on.
     * 数组，当前静态资源库依赖的其他资源库类名。
     *
     * For example:
     * 例如：
     *
     * ```php
     * public $depends = [
     *    'yii\web\YiiAsset',
     *    'yii\bootstrap\BootstrapAsset',
     * ];
     * ```
     */
    public $depends = [];
    /**
     * @var array list of JavaScript files that this bundle contains. Each JavaScript file can be
     * specified in one of the following formats:
     * 数组，本资源包包含的JS文件列表，每个JS文件都可以指定为如下格式之一：
     *
     * - an absolute URL representing an external asset. For example,
     *   `http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js` or
     *   `//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js`.
     * - 外部资源的绝对URL地址，例如：`http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js` 或者
     *   `//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js`.
     * - a relative path representing a local asset (e.g. `js/main.js`). The actual file path of a local
     *   asset can be determined by prefixing [[basePath]] to the relative path, and the actual URL
     *   of the asset can be determined by prefixing [[baseUrl]] to the relative path.
     * - 本地资源的相对UEL地址，【这段没看懂】
     *
     * Note that only forward slash "/" should be used as directory separators.
     * 注意，正斜线仅被用于文件路径分隔符。
     */
    public $js = [];
    /**
     * @var array list of CSS files that this bundle contains. Each CSS file can be specified
     * in one of the three formats as explained in [[js]].
     * 数组，本资源包包含的CSS文件列表。css文件也可以被指定为JS文件的那三种类型。
     * 注意，正斜线仅被用于文件路径分隔符。
     *
     * Note that only forward slash "/" can be used as directory separator.
     * 注意，正斜线仅被用于文件路径分隔符。
     */
    public $css = [];
    /**
     * @var array the options that will be passed to [[View::registerJsFile()]]
     * when registering the JS files in this bundle.
     * 数组，把js文件注册到本资源包时，将被传递到 [[View::registerJsFile()]]方法中的选项，
     */
    public $jsOptions = [];
    /**
     * @var array the options that will be passed to [[View::registerCssFile()]]
     * when registering the CSS files in this bundle.
     * 数组，把CSS文件注册到本资源包时，将被传递到 [[View::registerCssFile()]]方法中的选项，
     */
    public $cssOptions = [];
    /**
     * @var array the options to be passed to [[AssetManager::publish()]] when the asset bundle
     * is being published. This property is used only when [[sourcePath]] is set.
     * 数组，发布资源包时，传递到[[AssetManager::publish()]]方法中的选项。本属性仅在[[sourcePath]]属性不为空
     * 时生效。
     */
    public $publishOptions = [];


    /**
     * Registers this asset bundle with a view.
     * 将本静态资源包注册到一个视图对象中。
     * @param View $view the view to be registered with
     * View类实例，本资源包被注册到的View类实例。
     * @return static the registered asset bundle instance
     * 被注册到视图中的静态资源包实例。
     */
    public static function register($view)
    {
        return $view->registerAssetBundle(get_called_class());
    }

    /**
     * Initializes the bundle.
     * If you override this method, make sure you call the parent implementation in the last.
     * 初始化静态资源包，确保最终调用了所有父类实现
     */
    public function init()
    {
        if ($this->sourcePath !== null) {
            $this->sourcePath = rtrim(Yii::getAlias($this->sourcePath), '/\\');
        }
        if ($this->basePath !== null) {
            $this->basePath = rtrim(Yii::getAlias($this->basePath), '/\\');
        }
        if ($this->baseUrl !== null) {
            $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');
        }
    }

    /**
     * Registers the CSS and JS files with the given view.
     * 为给定的视图注册CSS和JS文件
     * @param \yii\web\View $view the view that the asset files are to be registered with.
     */
    public function registerAssetFiles($view)
    {
        $manager = $view->getAssetManager();
        foreach ($this->js as $js) {
            $view->registerJsFile($manager->getAssetUrl($this, $js), $this->jsOptions);
        }
        foreach ($this->css as $css) {
            $view->registerCssFile($manager->getAssetUrl($this, $css), $this->cssOptions);
        }
    }

    /**
     * Publishes the asset bundle if its source code is not under Web-accessible directory.
     * It will also try to convert non-CSS or JS files (e.g. LESS, Sass) into the corresponding
     * CSS or JS files using [[AssetManager::converter|asset converter]].
     * 假如静态资源包的代码文件不再web服务软件可访问的文件夹下，发布相应的静态资源代码。
     * 本方法同样会将css和js的预处理语言通过[[AssetManager::converter|asset converter]]
     * 方法转换为原生的语言。
     * @param AssetManager $am the asset manager to perform the asset publishing
     */
    public function publish($am)
    {
        if ($this->sourcePath !== null && !isset($this->basePath, $this->baseUrl)) {
            list ($this->basePath, $this->baseUrl) = $am->publish($this->sourcePath, $this->publishOptions);
        }

        if (isset($this->basePath, $this->baseUrl) && ($converter = $am->getConverter()) !== null) {
            foreach ($this->js as $i => $js) {
                if (Url::isRelative($js)) {
                    $this->js[$i] = $converter->convert($js, $this->basePath);
                }
            }
            foreach ($this->css as $i => $css) {
                if (Url::isRelative($css)) {
                    $this->css[$i] = $converter->convert($css, $this->basePath);
                }
            }
        }
    }
}
