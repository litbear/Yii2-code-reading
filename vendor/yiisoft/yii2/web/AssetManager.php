<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * AssetManager manages asset bundle configuration and loading.
 * AssetManager静态资源管理器类管理者资源包的配置和加载
 *
 * AssetManager is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->assetManager`.
 * AssetManager类默认作为一个应用组件配置在[[\yii\web\Application]]中，可以使用Yii::$app->assetManager`
 * 访问之
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as shown in the following example:
 * 可以在应用配置文件的components`元素中添加如下代码配置之：
 *
 * ```php
 * 'assetManager' => [
 *     'bundles' => [
 *         // you can override AssetBundle configs here
 *     ],
 * ]
 * ```
 *
 * @property AssetConverterInterface $converter The asset converter. Note that the type of this property
 * differs in getter and setter. See [[getConverter()]] and [[setConverter()]] for details.
 * AssetConverterInterface类实例，静态资源转换器。注意，本属性getter和setter方法的不同。更多详情参见 [[getConverter()]]和[[setConverter()]]方法
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AssetManager extends Component
{
    /**
     * @var array|boolean list of asset bundle configurations. This property is provided to customize asset bundles.
     * When a bundle is being loaded by [[getBundle()]], if it has a corresponding configuration specified here,
     * the configuration will be applied to the bundle.
     * 数组或布尔值，静态资源包的配置数组集合。本属性提供了静态资源包的个性化。当资源包被[[getBundle()]]方法加载后
     * 如果在本属性中制订了相应的配置数组。配置将会应用到包对象中。
     *
     * The array keys are the asset bundle names, which typically are asset bundle class names without leading backslash.
     * The array values are the corresponding configurations. If a value is false, it means the corresponding asset
     * bundle is disabled and [[getBundle()]] should return null.
     * 本属性为键值对形式，键位资源包的名字，即相应的包的全限定名不加开头的反斜线。数组的值是相应的配置数组。
     * 假如值为false，则表示禁用相应的包。[[getBundle()]]方法将返回null。
     *
     * If this property is false, it means the whole asset bundle feature is disabled and [[getBundle()]]
     * will always return null.
     * 假如本属性为false，则意味着所有的资源包特性都会被禁用， [[getBundle()]]所有返回值都为null
     *
     * The following example shows how to disable the bootstrap css file used by Bootstrap widgets
     * (because you want to use your own styles):
     * 下面的代码演示了如何禁用用于bootstrap小部件的css文件。
     *
     * ~~~
     * [
     *     'yii\bootstrap\BootstrapAsset' => [
     *         'css' => [],
     *     ],
     * ]
     * ~~~
     */
    public $bundles = [];
    /**
     * @var string the root directory storing the published asset files.
     * 字符串，储存发布资源文件的根文件夹
     */
    public $basePath = '@webroot/assets';
    /**
     * @var string the base URL through which the published asset files can be accessed.
     * 字符串，访问静态资源文件的基本URL地址
     */
    public $baseUrl = '@web/assets';
    /**
     * @var array mapping from source asset files (keys) to target asset files (values).
     * 键值对形式数组，源静态文件为键，目标资源文件为值。
     *
     * This property is provided to support fixing incorrect asset file paths in some asset bundles.
     * When an asset bundle is registered with a view, each relative asset file in its [[AssetBundle::css|css]]
     * and [[AssetBundle::js|js]] arrays will be examined against this map. If any of the keys is found
     * to be the last part of an asset file (which is prefixed with [[AssetBundle::sourcePath]] if available),
     * the corresponding value will replace the asset and be registered with the view.
     * For example, an asset file `my/path/to/jquery.js` matches a key `jquery.js`.
     * 本属性用来修正资源包中错误的资源文件路径。当静态资源包被注册到视图中时，每个在 [[AssetBundle::css|css]]属性和
     * [[AssetBundle::js|js]]属性数组中的静态资源文件会对照本map属性进行检查。假如某个键被发现是另一个静态资源文件的
     * 的结尾（假如可能的话，会被加上[[AssetBundle::sourcePath]]属性为前缀），则该键对应的值会被静态资源文件替代，并
     * 被注册到视图中，例如，静态资源文件`my/path/to/jquery.js`匹配键`jquery.js`
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to [[baseUrl]] and [[basePath]].
     * 注意，目标静态资源文件应为绝对URL地址，相对域名地址（以正斜线开头）或者相对于 [[baseUrl]] 和 [[basePath]]两个
     * 属性的路径。
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to [[baseUrl]] and [[basePath]].
     * 在下面的例子中，每个以`jquery.min.js`结尾的静态资源文件都会被替换成`jquery/dist/jquery.js`，也就是相对于 [[baseUrl]] 
     * 和 [[basePath]]属性的相对路径。
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     *
     * You may also use aliases while specifying map value, for example:
     * 同样，可以在值中使用别名，例如：
     *
     * ```php
     * [
     *     'jquery.min.js' => '@web/js/jquery/jquery.js',
     * ]
     * ```
     */
    public $assetMap = [];
    /**
     * @var boolean whether to use symbolic link to publish asset files. Defaults to false, meaning
     * asset files are copied to [[basePath]]. Using symbolic links has the benefit that the published
     * assets will always be consistent with the source assets and there is no copy operation required.
     * This is especially useful during development.
     * 布尔值，是否对发布的静态资源文件使用符号链接。默认为false。意味着静态资源文件会被复制到[[basePath]]
     * 下，使用符号链接可以将预编译后的静态文件不经复制直接硬链接到静态资源文件夹，省去了复制。
     *
     * However, there are special requirements for hosting environments in order to use symbolic links.
     * In particular, symbolic links are supported only on Linux/Unix, and Windows Vista/2008 or greater.
     * 然而硬链接只支持Linux/Unix与Vista 及Server 2008 以上的系统。
     *
     * Moreover, some Web servers need to be properly configured so that the linked assets are accessible
     * to Web users. For example, for Apache Web server, the following configuration directive should be added
     * for the Web folder:
     * 此外，web服务软件需要进一步的配置以支持符号链接。例如Apache服务器应该加入以下内容：
     *
     * ~~~
     * Options FollowSymLinks
     * ~~~
     */
    public $linkAssets = false;
    /**
     * @var integer the permission to be set for newly published asset files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
     * @var integer the permission to be set for newly generated asset directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;
    /**
     * @var callback a PHP callback that is called before copying each sub-directory or file.
     * This option is used only when publishing a directory. If the callback returns false, the copy
     * operation for the sub-directory or file will be cancelled.
     *
     * The signature of the callback should be: `function ($from, $to)`, where `$from` is the sub-directory or
     * file to be copied from, while `$to` is the copy target.
     *
     * This is passed as a parameter `beforeCopy` to [[\yii\helpers\FileHelper::copyDirectory()]].
     */
    public $beforeCopy;
    /**
     * @var callback a PHP callback that is called after a sub-directory or file is successfully copied.
     * This option is used only when publishing a directory. The signature of the callback is the same as
     * for [[beforeCopy]].
     * This is passed as a parameter `afterCopy` to [[\yii\helpers\FileHelper::copyDirectory()]].
     */
    public $afterCopy;
    /**
     * @var boolean whether the directory being published should be copied even if
     * it is found in the target directory. This option is used only when publishing a directory.
     * You may want to set this to be `true` during the development stage to make sure the published
     * directory is always up-to-date. Do not set this to true on production servers as it will
     * significantly degrade the performance.
     */
    public $forceCopy = false;
    /**
     * @var boolean whether to append a timestamp to the URL of every published asset. When this is true,
     * the URL of a published asset may look like `/path/to/asset?v=timestamp`, where `timestamp` is the
     * last modification time of the published asset file.
     * You normally would want to set this property to true when you have enabled HTTP caching for assets,
     * because it allows you to bust caching when the assets are updated.
     * @since 2.0.3
     */
    public $appendTimestamp = false;
    /**
     * @var callable a callback that will be called to produce hash for asset directory generation.
     * The signature of the callback should be as follows:
     *
     * ```
     * function ($path)
     * ```
     *
     * where `$path` is the asset path. Note that the `$path` can be either directory where the asset
     * files reside or a single file. For a CSS file that uses relative path in `url()`, the hash
     * implementation should use the directory path of the file instead of the file path to include
     * the relative asset files in the copying.
     *
     * If this is not set, the asset manager will use the default CRC32 and filemtime in the `hash`
     * method.
     *
     * Example of an implementation using MD4 hash:
     *
     * ```php
     * function ($path) {
     *     return hash('md4', $path);
     * }
     * ```
     *
     * @since 2.0.6
     */
    public $hashCallback;

    private $_dummyBundles = [];


    /**
     * Initializes the component.
     * @throws InvalidConfigException if [[basePath]] is invalid
     */
    public function init()
    {
        parent::init();
        $this->basePath = Yii::getAlias($this->basePath);
        if (!is_dir($this->basePath)) {
            throw new InvalidConfigException("The directory does not exist: {$this->basePath}");
        } elseif (!is_writable($this->basePath)) {
            throw new InvalidConfigException("The directory is not writable by the Web process: {$this->basePath}");
        } else {
            $this->basePath = realpath($this->basePath);
        }
        $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');
    }

    /**
     * Returns the named asset bundle.
     * 根据给定的名称返回相应的静态资源包。
     *
     * This method will first look for the bundle in [[bundles]]. If not found,
     * it will treat `$name` as the class of the asset bundle and create a new instance of it.
     * 本方法首先会在[[bundles]]属性中查找静态资源包，如果没找到，则会把`$name`参数当作资源包的
     * 类名，并实例化一个新的对象。
     *
     * @param string $name the class name of the asset bundle (without the leading backslash)
     * 字符串，静态资源包的全限定类名，不包括开头的反斜线。
     * @param boolean $publish whether to publish the asset files in the asset bundle before it is returned.
     * If you set this false, you must manually call `AssetBundle::publish()` to publish the asset files.
     * @return AssetBundle the asset bundle instance
     * 布尔值，在返回静态资源包前是否发布它。假如设置为false，则必须手动调用`AssetBundle::publish()`方法
     * 发布静态资源文件。
     * @throws InvalidConfigException if $name does not refer to a valid asset bundle
     */
    public function getBundle($name, $publish = true)
    {
        /**
         *  $this->bundles为false 则意味着禁用资源包
         * 所以返回一个空的资源包
         */
        if ($this->bundles === false) {
            return $this->loadDummyBundle($name);
        } elseif (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, [], $publish);
        } elseif ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        } elseif (is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, $this->bundles[$name], $publish);
        } elseif ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        } else {
            throw new InvalidConfigException("Invalid asset bundle configuration: $name");
        }
    }

    /**
     * Loads asset bundle class by name
     * 根据名称，加载静态资源包。
     *
     * @param string $name bundle name
     * 字符串，资源包包名
     * @param array $config bundle object configuration
     * 数组，资源包对象的配置数组。
     * @param boolean $publish if bundle should be published
     * 布尔值，是否发布资源包。
     * @return AssetBundle
     * @throws InvalidConfigException if configuration isn't valid
     */
    protected function loadBundle($name, $config = [], $publish = true)
    {
        if (!isset($config['class'])) {
            $config['class'] = $name;
        }
        /* @var $bundle AssetBundle */
        $bundle = Yii::createObject($config);
        if ($publish) {
            $bundle->publish($this);
        }
        return $bundle;
    }

    /**
     * Loads dummy bundle by name
     * 根据名称加载虚拟包
     *
     * @param string $name
     * @return AssetBundle
     */
    protected function loadDummyBundle($name)
    {
        if (!isset($this->_dummyBundles[$name])) {
            $this->_dummyBundles[$name] = $this->loadBundle($name, [
                'sourcePath' => null,
                'js' => [],
                'css' => [],
                'depends' => [],
            ]);
        }
        return $this->_dummyBundles[$name];
    }

    /**
     * Returns the actual URL for the specified asset.
     * The actual URL is obtained by prepending either [[baseUrl]] or [[AssetManager::baseUrl]] to the given asset path.
     * 为指定的静态资源返回真是的URL地址。真实的URL由静态资源管理器的[[AssetManager::baseUrl]]属性或静态资源包的[[baseUrl]]
     * 属性决定。
     * @param AssetBundle $bundle the asset bundle which the asset file belongs to
     * AssetBundle 静态资源包实例，静态资源文件属于的静态资源包
     * @param string $asset the asset path. This should be one of the assets listed in [[js]] or [[css]].
     * 字符串，静态资源的文件路径，应该是静态资源包的[[js]]属性或是[[css]]属性
     * @return string the actual URL for the specified asset.
     * 指定静态资源的URL地址
     */
    public function getAssetUrl($bundle, $asset)
    {
        // 本静态资源管理器中的assetMap属性取到了，则进一步处理
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            // 以别名开头 则处理别名
            if (strncmp($actualAsset, '@web/', 5) === 0) {
                $asset = substr($actualAsset, 5);
                $basePath = Yii::getAlias("@webroot");
                $baseUrl = Yii::getAlias("@web");
            } else {
                // 否则以本对象的basePath，和baseUrl属性处理
                $asset = Yii::getAlias($actualAsset);
                $basePath = $this->basePath;
                $baseUrl = $this->baseUrl;
            }
            // 本静态资源管理器没取到地址 则使用包中的basePath，和baseUrl
        } else {
            $basePath = $bundle->basePath;
            $baseUrl = $bundle->baseUrl;
        }

        // 如果 此时不是相对URL路径，且以正斜线开头 则返回就可以了
        if (!Url::isRelative($asset) || strncmp($asset, '/', 1) === 0) {
            return $asset;
        }

        /**
         * 如果是相对URL路径，且不以正斜线开头 则进行讨论
         * 需要加修改时间参数 就加修改时间
         */
        if ($this->appendTimestamp && ($timestamp = @filemtime("$basePath/$asset")) > 0) {
            return "$baseUrl/$asset?v=$timestamp";
        } else {
            return "$baseUrl/$asset";
        }
    }

    /**
     * Returns the actual file path for the specified asset.
     * @param AssetBundle $bundle the asset bundle which the asset file belongs to
     * @param string $asset the asset path. This should be one of the assets listed in [[js]] or [[css]].
     * @return string|boolean the actual file path, or false if the asset is specified as an absolute URL
     */
    public function getAssetPath($bundle, $asset)
    {
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            return Url::isRelative($actualAsset) ? $this->basePath . '/' . $actualAsset : false;
        } else {
            return Url::isRelative($asset) ? $bundle->basePath . '/' . $asset : false;
        }
    }

    /**
     * @param AssetBundle $bundle
     * @param string $asset
     * @return string|boolean
     */
    protected function resolveAsset($bundle, $asset)
    {
        // assetMap属性中找到则返回
        if (isset($this->assetMap[$asset])) {
            return $this->assetMap[$asset];
        }
        if ($bundle->sourcePath !== null && Url::isRelative($asset)) {
            $asset = $bundle->sourcePath . '/' . $asset;
        }

        $n = mb_strlen($asset);
        // 遍历assetMap 对比结尾，找到以$asset结尾的，则返回之
        foreach ($this->assetMap as $from => $to) {
            $n2 = mb_strlen($from);
            if ($n2 <= $n && substr_compare($asset, $from, $n - $n2, $n2) === 0) {
                return $to;
            }
        }

        // 都没找到则返回false
        return false;
    }

    private $_converter;

    /**
     * Returns the asset converter.
     * 返回静态资源转换器，（用于转换css与js的预处理语言）
     * @return AssetConverterInterface the asset converter.
     */
    public function getConverter()
    {
        if ($this->_converter === null) {
            $this->_converter = Yii::createObject(AssetConverter::className());
        } elseif (is_array($this->_converter) || is_string($this->_converter)) {
            if (is_array($this->_converter) && !isset($this->_converter['class'])) {
                $this->_converter['class'] = AssetConverter::className();
            }
            $this->_converter = Yii::createObject($this->_converter);
        }

        return $this->_converter;
    }

    /**
     * Sets the asset converter.
     * @param array|AssetConverterInterface $value the asset converter. This can be either
     * an object implementing the [[AssetConverterInterface]], or a configuration
     * array that can be used to create the asset converter object.
     */
    public function setConverter($value)
    {
        $this->_converter = $value;
    }

    /**
     * @var array published assets
     * 已经被发布的静态资源
     */
    private $_published = [];

    /**
     * Publishes a file or a directory.
     * 发布文件或者文件夹
     *
     * This method will copy the specified file or directory to [[basePath]] so that
     * it can be accessed via the Web server.
     * 本方法会将指定文件或文件夹复制到[[basePath]]文件夹之下，以使他们能被web服务软件
     * 可以读取他们
     *
     * If the asset is a file, its file modification time will be checked to avoid
     * unnecessary file copying.
     * 假如静态资源是个文件，则会检查文件的修改时间以避免不必要的复制。
     *
     * If the asset is a directory, all files and subdirectories under it will be published recursively.
     * Note, in case $forceCopy is false the method only checks the existence of the target
     * directory to avoid repetitive copying (which is very expensive).
     * 假如静态资源是文件夹，则所有的文件和子文件夹都会被递归发布。注意：如果$forceCopy参数为false
     * 则本方法会检查目标文件的存在性，并避免不必要的复制。
     *
     * By default, when publishing a directory, subdirectories and files whose name starts with a dot "."
     * will NOT be published. If you want to change this behavior, you may specify the "beforeCopy" option
     * as explained in the `$options` parameter.
     * 默认情况下，发布文件夹时，子文件夹和文件中以`.`开头的不会被发布，假如你想改变这种行为，可以配置
     *  "beforeCopy" 事件的选项。
     *
     * Note: On rare scenario, a race condition can develop that will lead to a
     * one-time-manifestation of a non-critical problem in the creation of the directory
     * that holds the published assets. This problem can be avoided altogether by 'requesting'
     * in advance all the resources that are supposed to trigger a 'publish()' call, and doing
     * that in the application deployment phase, before system goes live. See more in the following
     * discussion: http://code.google.com/p/yii/issues/detail?id=2579
     * 注意，在十分罕见的情况下，（大概意思）争相创建文件夹会导致错误，所以尽量在开发阶段一次性把静态
     * 资源发布完。
     *
     * @param string $path the asset (file or directory) to be published
     * 字符串，待发布的静态资源文件或文件夹
     * @param array $options the options to be applied when publishing a directory.
     * The following options are supported:
     * 数组，发布文件夹时将被应用的配置，以下是支持的配置参数：
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     * - only: 数组，指定将要被复制的文件路径集合。
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
     * - except: 数组，指定将会被排除的文件路径集合。
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to true.
     * - caseSensitive: 布尔值， "only" 或 "except" 选项中的路径是否区分大小写，默认为true 区分。
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   This overrides [[beforeCopy]] if set.
     * - beforeCopy: 回调函数，在复制文件或子文件夹之前会被调用的回调函数。
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied.
     *   This overrides [[afterCopy]] if set.
     * - afterCopy: 回调函数，在复制文件或子文件夹之后会被调用的回调函数。
     * - forceCopy: boolean, whether the directory being published should be copied even if
     *   it is found in the target directory. This option is used only when publishing a directory.
     *   This overrides [[forceCopy]] if set.
     * - forceCopy: 布尔值，在目标文件存在的情况下是否强制覆盖。
     *
     * @return array the path (directory or file path) and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    public function publish($path, $options = [])
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path];
        }

        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new InvalidParamException("The file or directory to be published does not exist: $path");
        }

        if (is_file($src)) {
            return $this->_published[$path] = $this->publishFile($src);
        } else {
            return $this->_published[$path] = $this->publishDirectory($src, $options);
        }
    }

    /**
     * Publishes a file.
     * 发布文件
     * @param string $src the asset file to be published
     * 字符串，待发布的静态资源文件
     * @return array the path and the URL that the asset is published as.
     * 数组，静态资源发布后的路径和URL地址
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishFile($src)
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

        // 创建文件所在的文件夹，递归创建不存在的父文件夹
        if (!is_dir($dstDir)) {
            FileHelper::createDirectory($dstDir, $this->dirMode, true);
        }

        if ($this->linkAssets) {
            if (!is_file($dstFile)) {
                symlink($src, $dstFile);
            }
        } elseif (@filemtime($dstFile) < @filemtime($src)) {
            copy($src, $dstFile);
            if ($this->fileMode !== null) {
                @chmod($dstFile, $this->fileMode);
            }
        }

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    /**
     * Publishes a directory.
     * 发布文件夹
     * @param string $src the asset directory to be published
     * 字符串，代发不的文件夹路径。
     * @param array $options the options to be applied when publishing a directory.
     * The following options are supported:
     * 数组，发布文件夹时应用的配置选项：
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to true.
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   This overrides [[beforeCopy]] if set.
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied.
     *   This overrides [[afterCopy]] if set.
     * - forceCopy: boolean, whether the directory being published should be copied even if
     *   it is found in the target directory. This option is used only when publishing a directory.
     *   This overrides [[forceCopy]] if set.
     *   和上面的都一样就不翻译了，有一点疑问，所谓的覆盖文件夹，是仅仅覆盖文件夹还是连同根文件夹下的文件与子文件夹
     *   一起覆盖？
     *
     * @return array the path directory and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishDirectory($src, $options)
    {
        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        if ($this->linkAssets) {
            if (!is_dir($dstDir)) {
                symlink($src, $dstDir);
            }
        } elseif (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !is_dir($dstDir)) {
            $opts = array_merge(
                $options,
                [
                    'dirMode' => $this->dirMode,
                    'fileMode' => $this->fileMode,
                ]
            );
            if (!isset($opts['beforeCopy'])) {
                if ($this->beforeCopy !== null) {
                    $opts['beforeCopy'] = $this->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
            }
            if (!isset($opts['afterCopy']) && $this->afterCopy !== null) {
                $opts['afterCopy'] = $this->afterCopy;
            }
            FileHelper::copyDirectory($src, $dstDir, $opts);
        }

        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

    /**
     * Returns the published path of a file path.
     * This method does not perform any publishing. It merely tells you
     * if the file or directory is published, where it will go.
     * @param string $path directory or file path being published
     * @return string the published file path. False if the file or directory does not exist
     */
    public function getPublishedPath($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->basePath . DIRECTORY_SEPARATOR . $this->hash($path) . (is_file($path) ? DIRECTORY_SEPARATOR . basename($path) : '');
        } else {
            return false;
        }
    }

    /**
     * Returns the URL of a published file path.
     * This method does not perform any publishing. It merely tells you
     * if the file path is published, what the URL will be to access it.
     * @param string $path directory or file path being published
     * @return string the published URL for the file or directory. False if the file or directory does not exist.
     */
    public function getPublishedUrl($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->baseUrl . '/' . $this->hash($path) . (is_file($path) ? '/' . basename($path) : '');
        } else {
            return false;
        }
    }

    /**
     * Generate a CRC32 hash for the directory path. Collisions are higher
     * than MD5 but generates a much smaller hash string.
     * 为文件夹路径生成CRC32哈希值，比MD5算法碰撞几率更高，但是生成的哈希值
     * 更短
     * @param string $path string to be hashed.
     * @return string hashed string.
     */
    protected function hash($path)
    {
        // 如果绑定了哈希回调函数 则用绑定的哈希回调函数处理之
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }
        // 默认情况下使用CRC32处理
        $path = (is_file($path) ? dirname($path) : $path) . filemtime($path);
        return sprintf('%x', crc32($path . Yii::getVersion()));
    }
}
