<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * BootstrapInterface is the interface that should be implemented by classes who want to participate in the application bootstrap process.
 * BootstrapInterface接口是所有想要参与应用启动进程的类必须实现的接口。
 *
 * The main method [[bootstrap()]] will be invoked by an application at the beginning of its `init()` method.
 * 主要的方法[[bootstrap()]]会在应用`init()`方法的开始被执行。
 *
 * Bootstrapping classes can be registered in two approaches.
 * Bootstrapp类会通过两种途径注册到应用中
 *
 * The first approach is mainly used by extensions and is managed by the Composer installation process.
 * You mainly need to list the bootstrapping class of your extension in the `composer.json` file like following,
 * 第一种方法主要被用于扩展，同时被Composer的安装进程管理。你只需在`composer.json` 文件中像下面这样列出bootstrap类：
 *
 * ```json
 * {
 *     // ...
 *     "extra": {
 *         "bootstrap": "path\\to\\MyBootstrapClass"
 *     }
 * }
 * ```
 *
 * If the extension is installed, the bootstrap information will be saved in [[Application::extensions]].
 * 扩展被安装后，那么启动信息会被保存在[[Application::extensions]]中
 *
 * The second approach is used by application code which needs to register some code to be run during
 * the bootstrap process. This is done by configuring the [[Application::bootstrap]] property:
 * 第二种途径是通过配置[[Application::bootstrap]] 属性：
 *
 * ```php
 * return [
 *     // ...
 *     'bootstrap' => [
 *         "path\\to\\MyBootstrapClass1",
 *         [
 *             'class' => "path\\to\\MyBootstrapClass2",
 *             'prop1' => 'value1',
 *             'prop2' => 'value2',
 *         ],
 *     ],
 * ];
 * ```
 *
 * As you can see, you can register a bootstrapping class in terms of either a class name or a configuration class.
 * 如你所见，你可以通过类名或者配置数组注册启动类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app);
}

/**
 * 以Yii2-user扩展为例：
 * 该扩展的composer.json文件中有如下内容
 * ```javascript
 * {
 *  "..."
 *  "extra": {
        "bootstrap": "dektrium\\user\\Bootstrap",
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        },
        "asset-installer-paths": {
            "npm-asset-library": "vendor/npm",
            "bower-asset-library": "vendor/bower"
        }
    }
 * }
 * ```
 * 因此在执行composer update之后，更改了@vendor\yiisoft\extensions.php文件。
 * 该文件中多了以下几行：
 * ```php
 *   'dektrium/yii2-user' => 
 *      array (
 *        'name' => 'dektrium/yii2-user',
 *        'version' => '0.9.3.0',
 *        'alias' => 
 *        array (
 *          '@dektrium/user' => $vendorDir . '/dektrium/yii2-user',
 *        ),
 *        'bootstrap' => 'dektrium\\user\\Bootstrap',
 *      ),
 * ```
 * 因此在`yii\base\application::bootstrap()方法中会在遍历extensions的时候
 * 实例化bootstrap类，并执行该类的bootstrap()方法。
 */
