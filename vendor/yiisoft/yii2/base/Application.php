<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Application is the base class for all application classes.
 * Application是所有应用类的基类
 *
 * @property \yii\web\AssetManager $assetManager The asset manager application component. This property is
 * read-only.
 * $assetManager 一个\yii\web\AssetManager类的实例，负责管理应用组件的一个只读属性
 * @property \yii\rbac\ManagerInterface $authManager The auth manager application component. Null is returned
 * if auth manager is not configured. This property is read-only.
 * $authManager \yii\rbac\ManagerInterface接口的一个实例，负责管理应用组件权限的一个只读属性。
 * 假如该对象还没有被配置，则返回null
 * @property string $basePath The root directory of the application.
 * $basePath 返回应用的根文件夹
 * @property \yii\caching\Cache $cache The cache application component. Null if the component is not enabled.
 * This property is read-only.
 * $cache \yii\caching\Cache类的实例，应用的缓存组件，未开启的状态下返回null，只读属性
 * @property \yii\db\Connection $db The database connection. This property is read-only.
 * $db \yii\db\Connection类的实例，存放数据库的连接，只读属性。
 * @property \yii\web\ErrorHandler|\yii\console\ErrorHandler $errorHandler The error handler application
 * component. This property is read-only.
 * $errorHandler \yii\web\ErrorHandler或\yii\console\ErrorHandler类的实例，应用的错误句柄组件，只读属性。
 * @property \yii\i18n\Formatter $formatter The formatter application component. This property is read-only.
 * $formatter \yii\i18n\Formatter类的实例，应用的格式化组件，只读属性。
 * @property \yii\i18n\I18N $i18n The internationalization application component. This property is read-only.
 * $i18n \yii\i18n\I18N类的实例，类的国际化组件，只读属性。
 * @property \yii\log\Dispatcher $log The log dispatcher application component. This property is read-only.
 * $log  \yii\log\Dispatcher类的实例，类的日志调度组件，只读属性。
 * @property \yii\mail\MailerInterface $mailer The mailer application component. This property is read-only.
 * $mailer \yii\mail\MailerInterface类的实例，应用的邮件组件，只读属性。
 * @property \yii\web\Request|\yii\console\Request $request The request component. This property is read-only.
 * $request \yii\web\Request或\yii\console\Request类的属性，请求组件，只读属性。
 * @property \yii\web\Response|\yii\console\Response $response The response component. This property is
 * read-only.
 * $response \yii\web\Response或\yii\console\Response类的属性 响应组件，只读属性。
 * @property string $runtimePath The directory that stores runtime files. Defaults to the "runtime"
 * subdirectory under [[basePath]].
 * $runtimePath 字符串，用来存放运行时文件的文件夹，默认为basePath下的runtime子文件夹。
 * @property \yii\base\Security $security The security application component. This property is read-only.
 * $security \yii\base\Security类的实例，应用的安全组件，只读属性。
 * @property string $timeZone The time zone used by this application.
 * $timeZone 字符串，应用所在的时区。
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * $uniqueId 字符串，存放模块的唯一id，只读属性。
 * @property \yii\web\UrlManager $urlManager The URL manager for this application. This property is read-only.
 * $urlManager \yii\web\UrlManager类的实例，应哟的url管理对象，只读属性。
 * @property string $vendorPath The directory that stores vendor files. Defaults to "vendor" directory under
 * [[basePath]].
 * $vendorPath 字符串，存放第三方文件的文件夹，默认为basePath下的vendor文件夹。
 * @property View|\yii\web\View $view The view application component that is used to render various view
 * files. This property is read-only.
 * $view View或\yii\web\View类的实例，应用的视图组件，用来为视图文件渲染变量，只读属性。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Application extends Module
{
    /**
     * @event Event an event raised before the application starts to handle a request.
     * 处理请求前执行的事件（名）。
     */
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    /**
     * @event Event an event raised after the application successfully handles a request (before the response is sent out).
     * 成功处理请求后，执行响应之前执行的事件（名）。
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';
    /**
     * Application state used by [[state]]: application just started.
     * 用于state属性，表示应用的开始
     */
    const STATE_BEGIN = 0;
    /**
     * Application state used by [[state]]: application is initializing.
     * 用于state属性，表示应用正在初始化。
     */
    const STATE_INIT = 1;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_BEFORE_REQUEST]].
     * 用于state属性，表示应用正在触发请求前的事件。
     */
    const STATE_BEFORE_REQUEST = 2;
    /**
     * Application state used by [[state]]: application is handling the request.
     * 用于state属性，表示应用正在处理请求。
     */
    const STATE_HANDLING_REQUEST = 3;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_AFTER_REQUEST]]..
     * 用于state属性，表示应用正在实行请求成功处理后的事件。
     */
    const STATE_AFTER_REQUEST = 4;
    /**
     * Application state used by [[state]]: application is about to send response.
     * 用于state属性，
     */
    const STATE_SENDING_RESPONSE = 5;
    /**
     * Application state used by [[state]]: application has ended.
     * 用于state属性，表示应用已经结束。
     */
    const STATE_END = 6;

    /**
     * @var string the namespace that controller classes are located in.
     * This namespace will be used to load controller classes by prepending it to the controller class name.
     * The default namespace is `app\controllers`.
     * 记录控制器位于的命名空间。
     * 该命名空间将用于获取控制器类所在文件的路径。默认空间为'app\controllers'
     *
     * Please refer to the [guide about class autoloading](guide:concept-autoloading.md) for more details.
     * 请参考自动加载类的相关文档获取更多信息
     */
    public $controllerNamespace = 'app\\controllers';
    /**
     * @var string the application name.
     * 应用的名称
     */
    public $name = 'My Application';
    /**
     * @var string the version of this application.
     * 应用的版本
     */
    public $version = '1.0';
    /**
     * @var string the charset currently used for the application.
     * 应用当前使用的字符串编码
     */
    public $charset = 'UTF-8';
    /**
     * @var string the language that is meant to be used for end users. It is recommended that you
     * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
     * for English, while `en-US` stands for English (United States).
     * @see sourceLanguage
     * 表示终端用户的语言，参考XXX文档……
     */
    public $language = 'en-US';
    /**
     * @var string the language that the application is written in. This mainly refers to
     * the language that the messages and view files are written in.
     * @see language
     * 书写应用的语言，，主要用于消息和视图
     */
    public $sourceLanguage = 'en-US';
    /**
     * @var Controller the currently active controller instance
     * 当前激活的控制器实例
     */
    public $controller;
    /**
     * @var string|boolean the layout that should be applied for views in this application. Defaults to 'main'.
     * If this is false, layout will be disabled.
     * 将要用于本应用视图的布局文件，默认为'main'，假如为false则表示禁用布局。
     */
    public $layout = 'main';
    /**
     * @var string the requested route
     * 请求路由
     */
    public $requestedRoute;
    /**
     * @var Action the requested Action. If null, it means the request cannot be resolved into an action.
     * 被请求的动作，如果为null，则表示请求无法解析出动作。
     */
    public $requestedAction;
    /**
     * @var array the parameters supplied to the requested action.
     * 提供给请求的参数数组
     */
    public $requestedParams;
    /**
     * @var array list of installed Yii extensions. Each array element represents a single extension
     * with the following structure:
     * Yii框架已安装的扩展，每个数组元素代表一个单独的扩展，结构如下：
     *
     * ~~~
     * [
     *     'name' => 'extension name',
     *     'version' => 'version number',
     *     'bootstrap' => 'BootstrapClassName',  // optional, may also be a configuration array
     *     'alias' => [
     *         '@alias1' => 'to/path1',
     *         '@alias2' => 'to/path2',
     *     ],
     * ]
     * ~~~
     *
     * The "bootstrap" class listed above will be instantiated during the application
     * [[bootstrap()|bootstrapping process]]. If the class implements [[BootstrapInterface]],
     * its [[BootstrapInterface::bootstrap()|bootstrap()]] method will be also be called.
     * 上述的bootstrap键所指向的值是在应用执行初始化方法bootstrap()时被初始化的。假如该类实现了
     * BootstrapInterface接口，那么BootstrapInterface::bootstrap()或bootstrap()将会被调用。
     *
     * If not set explicitly in the application config, this property will be populated with the contents of
     * `@vendor/yiisoft/extensions.php`.
     * 假如在配置文件中没有明确的声明，该属性将会填充@vendor/yiisoft/extensions.php中的内容
     */
    public $extensions;
    /**
     * @var array list of components that should be run during the application [[bootstrap()|bootstrapping process]].
     * 一个应用在启动过程中（执行bootstrap()过程中）会被运行的组件组成的数组列表
     *
     * Each component may be specified in one of the following formats:
     * 每个组件都由一下几种格式之一描述：
     *
     * - an application component ID as specified via [[components]].
     * - 由components指定的应用组件id
     * - a module ID as specified via [[modules]].
     * - 由modules指定的模块id
     * - a class name.
     * - 类的全限定名
     * - a configuration array.
     * - 配置数组
     *
     * During the bootstrapping process, each component will be instantiated. If the component class
     * implements [[BootstrapInterface]], its [[BootstrapInterface::bootstrap()|bootstrap()]] method
     * will be also be called.
     * 在应用启动过程中，每个组件都会被初始化，假如该类实现了BootstrapInterface接口，
     * 那么BootstrapInterface::bootstrap()或bootstrap()将会被调用。
     */
    public $bootstrap = [];
    /**
     * @var integer the current application state during a request handling life cycle.
     * This property is managed by the application. Do not modify this property.
     * 用来描述在处理请求的生命周期中，当前应用处于何种状态，该属性由应用管理，不要修改这个属性。
     */
    public $state;
    /**
     * @var array list of loaded modules indexed by their class names.
     * 一个由已被加载的模块类名组成的数组列表
     */
    public $loadedModules = [];


    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        // 将自身实例用作Yii的属性
        Yii::$app = $this;
        // 缓存当前对象
        $this->setInstance($this);

        // 设置当前状态
        $this->state = self::STATE_BEGIN;

        // 预处理配置文件
        $this->preInit($config);

        // 注册错误处理句柄
        $this->registerErrorHandler($config);

        Component::__construct($config);
    }

    /**
     * Pre-initializes the application.
     * 预初始化应用（从配置文件读取配置的第一步）
     * This method is called at the beginning of the application constructor.
     * It initializes several important application properties.
     * If you override this method, please make sure you call the parent implementation.
     * @param array $config the application configuration
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function preInit(&$config)
    {
        if (!isset($config['id'])) {
            throw new InvalidConfigException('The "id" configuration for the Application is required.');
        }
        // $config['basePath']即为本文件所处文件夹的上一层
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
        }

        // 设置第三方库文件夹
        if (isset($config['vendorPath'])) {
            $this->setVendorPath($config['vendorPath']);
            unset($config['vendorPath']);
        } else {
            // set "@vendor"
            $this->getVendorPath();
        }
        // 设置运行时文件夹
        if (isset($config['runtimePath'])) {
            $this->setRuntimePath($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            // set "@runtime"
            $this->getRuntimePath();
        }

        // 设置时区 默认取UTC
        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
            unset($config['timeZone']);
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('UTC');
        }

        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            // 配置数组的组件集合里 如果没有 则用预写的核心组件赋值
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
                // 如果有，且值传来的配置数组元素值为数组，且数组中没有class，则使用预写的class赋值
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
            // 传来的配置数组中如果有class，则不用管他，留下一步处理
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // 设置初始化状态
        $this->state = self::STATE_INIT;
        $this->bootstrap();
    }

    /**
     * Initializes extensions and executes bootstrap components.
     * This method is called by [[init()]] after the application has been fully configured.
     * If you override this method, make sure you also call the parent implementation.
     * 初始化扩展并解析启动组件。本方法在应用被完全配置后由[[init()]]方法调用，假如你重写了本方法，
     * 请确保在子类方法中调用了父类的。
     */
    protected function bootstrap()
    {
        if ($this->extensions === null) {
            $file = Yii::getAlias('@vendor/yiisoft/extensions.php');
            $this->extensions = is_file($file) ? include($file) : [];
        }
        /*
         * 在应用启动过程中，每个组件都会被初始化，假如该类实现了BootstrapInterface接口，
         * 那么BootstrapInterface::bootstrap()或bootstrap()将会被调用。
         */
        foreach ($this->extensions as $extension) {
            if (!empty($extension['alias'])) {
                foreach ($extension['alias'] as $name => $path) {
                    Yii::setAlias($name, $path);
                }
            }
            if (isset($extension['bootstrap'])) {
                $component = Yii::createObject($extension['bootstrap']);
                if ($component instanceof BootstrapInterface) {
                    Yii::trace("Bootstrap with " . get_class($component) . '::bootstrap()', __METHOD__);
                    $component->bootstrap($this);
                } else {
                    Yii::trace("Bootstrap with " . get_class($component), __METHOD__);
                }
            }
        }

        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                if ($this->has($class)) {
                    $component = $this->get($class);
                } elseif ($this->hasModule($class)) {
                    $component = $this->getModule($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            if (!isset($component)) {
                $component = Yii::createObject($class);
            }

            if ($component instanceof BootstrapInterface) {
                Yii::trace("Bootstrap with " . get_class($component) . '::bootstrap()', __METHOD__);
                $component->bootstrap($this);
            } else {
                Yii::trace("Bootstrap with " . get_class($component), __METHOD__);
            }
        }
    }

    /**
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        if (YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                exit(1);
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }

    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Since this is an application instance, it will always return an empty string.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return '';
    }

    /**
     * Sets the root directory of the application and the @app alias.
     * This method can only be invoked at the beginning of the constructor.
     * 设置应用的根文件夹，设置@app 别名。本方法只能在构造器的开头执行。
     * @param string $path the root directory of the application.
     * @property string the root directory of the application.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        parent::setBasePath($path);
        Yii::setAlias('@app', $this->getBasePath());
    }

    /**
     * Runs the application.
     * This is the main entrance of an application.
     * @return integer the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
        try {

            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            // 获得请求句柄 为响应组织内容
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            // 发送响应
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {

            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;

        }
    }

    /**
     * Handles the specified request.
     *
     * This method should return an instance of [[Response]] or its child class
     * which represents the handling result of the request.
     *
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    abstract public function handleRequest($request);

    private $_runtimePath;

    /**
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }

        return $this->_runtimePath;
    }

    /**
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path)
    {
        $this->_runtimePath = Yii::getAlias($path);
        Yii::setAlias('@runtime', $this->_runtimePath);
    }

    private $_vendorPath;

    /**
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].
     * 如果没设置vendor，则默认曲basePath的子文件夹vendor
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }

    /**
     * Sets the directory that stores vendor files.
     * @param string $path the directory that stores vendor files.
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Yii::getAlias($path);
        Yii::setAlias('@vendor', $this->_vendorPath);
        Yii::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Yii::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }

    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * If time zone is not configured in php.ini or application config,
     * it will be set to UTC by default.
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * Returns the database connection component.
     * @return \yii\db\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }

    /**
     * Returns the log dispatcher component.
     * @return \yii\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }

    /**
     * Returns the error handler component.
     * @return \yii\web\ErrorHandler|\yii\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * Returns the cache component.
     * @return \yii\caching\Cache the cache application component. Null if the component is not enabled.
     */
    public function getCache()
    {
        return $this->get('cache', false);
    }

    /**
     * Returns the formatter component.
     * @return \yii\i18n\Formatter the formatter application component.
     */
    public function getFormatter()
    {
        return $this->get('formatter');
    }

    /**
     * Returns the request component.
     * 返回request组件
     * @return \yii\web\Request|\yii\console\Request the request component.
     * 返回的组件是\yii\web\Request或\yii\console\Request 类的实例 在哪儿绑定的暂时没发现？？？
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return \yii\web\Response|\yii\console\Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Returns the view object.
     * @return View|\yii\web\View the view application component that is used to render various view files.
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * Returns the URL manager for this application.
     * @return \yii\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**
     * Returns the internationalization (i18n) component
     * @return \yii\i18n\I18N the internationalization application component.
     */
    public function getI18n()
    {
        return $this->get('i18n');
    }

    /**
     * Returns the mailer component.
     * @return \yii\mail\MailerInterface the mailer application component.
     */
    public function getMailer()
    {
        return $this->get('mailer');
    }

    /**
     * Returns the auth manager for this application.
     * @return \yii\rbac\ManagerInterface the auth manager application component.
     * Null is returned if auth manager is not configured.
     */
    public function getAuthManager()
    {
        return $this->get('authManager', false);
    }

    /**
     * Returns the asset manager.
     * @return \yii\web\AssetManager the asset manager application component.
     */
    public function getAssetManager()
    {
        return $this->get('assetManager');
    }

    /**
     * Returns the security component.
     * @return \yii\base\Security the security application component.
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * Returns the configuration of core application components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'log' => ['class' => 'yii\log\Dispatcher'],
            'view' => ['class' => 'yii\web\View'],
            'formatter' => ['class' => 'yii\i18n\Formatter'],
            'i18n' => ['class' => 'yii\i18n\I18N'],
            'mailer' => ['class' => 'yii\swiftmailer\Mailer'],
            'urlManager' => ['class' => 'yii\web\UrlManager'],
            'assetManager' => ['class' => 'yii\web\AssetManager'],
            'security' => ['class' => 'yii\base\Security'],
        ];
    }

    /**
     * Terminates the application.
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * before terminating the application.
     * @param integer $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode
     */
    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ? : $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        } else {
            exit($status);
        }
    }
}
