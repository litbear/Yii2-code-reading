<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\di\ServiceLocator;

/**
 * Module is the base class for module and application classes.
 * Module类是所有模块和应用类的基类
 *
 * A module represents a sub-application which contains MVC elements by itself, such as
 * models, views, controllers, etc.
 * 模块代表着一个资深含有MVC元素的子应用
 *
 * A module may consist of [[modules|sub-modules]].
 * 模块可以由子模块组成
 *
 * [[components|Components]] may be registered with the module so that they are globally
 * accessible within the module.
 * 组件可以注册到模块中，因此组件可以在模块中被全局访问
 *
 * @property array $aliases List of path aliases to be defined. The array keys are alias names (must start
 * with '@') and the array values are the corresponding paths or aliases. See [[setAliases()]] for an example.
 * This property is write-only.
 * $aliases 数组，定义别名的数组集合，数组的键是表明的名字（必须以@ 开头），数组的值是对应的真是路径或别名，请
 * 参考setAliases()方法获得示例，只读属性。
 * @property string $basePath The root directory of the module.
 * $basePath 字符串，模块的根文件夹。
 * @property string $controllerPath The directory that contains the controller classes. This property is
 * read-only.
 * $controllerPath 字符串，包含控制器类文件的文件夹，只读属性。
 * @property string $layoutPath The root directory of layout files. Defaults to "[[viewPath]]/layouts".
 * $layoutPath 字符串，存放布局文件的根文件夹，默认为"[[viewPath]]/layouts"
 * @property array $modules The modules (indexed by their IDs).
 * $modules 数组，存放模块的数组，数组的键为模块id。
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * $uniqueId 字符串，模块的唯一id,.只读属性。
 * @property string $viewPath The root directory of view files. Defaults to "[[basePath]]/views".
 * $viewPath 字符串，存放视图文件的根文件夹，默认为"[[basePath]]/views"
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Module extends ServiceLocator
{
    /**
     * @event ActionEvent an event raised before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     * 在解析控制器动作之前被触发的事件，可以通过将ActionEvent::isValid属性设置为false
     * 来取消控制器动作的执行。
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised after executing a controller action.
     * 控制器动作执行之后被触发的事件。
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var array custom module parameters (name => value).
     * 自定义模块的键值对参数数组、
     * 
     */
    public $params = [];
    /**
     * @var string an ID that uniquely identifies this module among other modules which have the same [[module|parent]].
     * 同级模块中唯一的模块id
     */
    public $id;
    /**
     * @var Module the parent module of this module. Null if this module does not have a parent.
     * 本模块的父模块，null表示没有父模块。
     * 
     */
    public $module;
    /**
     * @var string|boolean the layout that should be applied for views within this module. This refers to a view name
     * relative to [[layoutPath]]. If this is not set, it means the layout value of the [[module|parent module]]
     * will be taken. If this is false, layout will be disabled within this module.
     * 字符串或布尔类型，本模块所应用的布局文件，本属性引用一个与layoutPath 属性相关的视图文件名，假如为null，在表示要去
     * 父类继承，假如设置为false，则表示禁用布局文件。
     */
    public $layout;
    /**
     * @var array mapping from controller ID to controller configurations.
     * Each name-value pair specifies the configuration of a single controller.
     * A controller configuration can be either a string or an array.
     * If the former, the string should be the fully qualified class name of the controller.
     * If the latter, the array must contain a 'class' element which specifies
     * the controller's fully qualified class name, and the rest of the name-value pairs
     * in the array are used to initialize the corresponding controller properties. For example,
     * 定义控制器ID和控制器配置的数组。每个键值对定义了一个控制器，每个控制器配置既可以
     * 是字符串也可以是数组。假如是字符串，字符串必须是控制器的全限定名。假如是数组，数组必须
     * 包含一个名为class，值为全限定名的元素。其余的键值对将会用于初始化数组。例如：
     *
     * ~~~
     * [
     *   'account' => 'app\controllers\UserController',
     *   'article' => [
     *      'class' => 'app\controllers\PostController',
     *      'pageTitle' => 'something new',
     *   ],
     * ]
     * ~~~
     */
    public $controllerMap = [];
    /**
     * @var string the namespace that controller classes are in.
     * This namespace will be used to load controller classes by prepending it to the controller
     * class name.
     * 字符串，控制器类所在的命名空间，命名空间会被用于加载控制器类文件。
     *
     * If not set, it will use the `controllers` sub-namespace under the namespace of this module.
     * For example, if the namespace of this module is "foo\bar", then the default
     * controller namespace would be "foo\bar\controllers".
     * 假如没有显式定义，那么将会使用当前模块下的controllers子命名空间，比如，假如本模块的命名空间
     * 为'foo\bar'，那么，默认的控制器命名空间就是"foo\bar\controllers"
     *
     * See also the [guide section on autoloading](guide:concept-autoloading) to learn more about
     * defining namespaces and how classes are loaded.
     * 可以参阅相关文档了解自动加载的详细情况。
     */
    public $controllerNamespace;
    /**
     * @var string the default route of this module. Defaults to 'default'.
     * The route may consist of child module ID, controller ID, and/or action ID.
     * For example, `help`, `post/create`, `admin/post/create`.
     * If action ID is not given, it will take the default value as specified in
     * [[Controller::defaultAction]].
     * 字符串，本模块的默认路由，默认为default，路由由子模块id，控制器id以及控制器
     * 动作id组成，比如说， `help`, `post/create`, `admin/post/create`。假如控制器
     * 动作id没有给出，则使用在[[Controller::defaultAction]]给出的默认的控制器动作
     */
    public $defaultRoute = 'default';

    /**
     * @var string the root directory of the module.
     * 本模块的根文件夹
     */
    private $_basePath;
    /**
     * @var string the root directory that contains view files for this module
     * 包含本模块视图文件的根文件夹
     */
    private $_viewPath;
    /**
     * @var string the root directory that contains layout view files for this module.
     * 包含本模块布局文件的根文件夹
     */
    private $_layoutPath;
    /**
     * @var array child modules of this module
     * 本模块的子模块数组
     */
    private $_modules = [];


    /**
     * Constructor.
     * @param string $id the ID of this module
     * @param Module $parent the parent module (if any)
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $this->id = $id;
        $this->module = $parent;
        parent::__construct($config);
    }

    /**
     * Returns the currently requested instance of this module class.
     * 获取当前被请求的模块类的实例。
     * If the module class is not currently requested, null will be returned.
     * 假如本模块类当前没被请求，则会返回null。
     * This method is provided so that you access the module instance from anywhere within the module.
     * 此方法保证了你可以从模块中访问模块实例。
     * @return static|null the currently requested instance of this module class, or null if the module class is not requested.
     */
    public static function getInstance()
    {
        $class = get_called_class();
        return isset(Yii::$app->loadedModules[$class]) ? Yii::$app->loadedModules[$class] : null;
    }

    /**
     * Sets the currently requested instance of this module class.
     * 缓存当前请求用到的模块。
     * @param Module|null $instance the currently requested instance of this module class.
     * If it is null, the instance of the calling class will be removed, if any.
     * 如果不为空，则以类名为键，对象为值缓存，如果为空，则移除该类的元素。
     */
    public static function setInstance($instance)
    {
        if ($instance === null) {
            unset(Yii::$app->loadedModules[get_called_class()]);
        } else {
            Yii::$app->loadedModules[get_class($instance)] = $instance;
        }
    }

    /**
     * Initializes the module.
     * 初始化模块，init()方法从Object继承，会在parent::__constauct()调用时被执行。
     *
     * This method is called after the module is created and initialized with property values
     * given in configuration. The default implementation will initialize [[controllerNamespace]]
     * if it is not set.
     * 本方法主要实现的是初始化controllerNamespace属性的功能。
     *
     * If you override this method, please make sure you call the parent implementation.
     */
    public function init()
    {
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            // 获取当前模块的全限定名，且在全限定名中存在反斜杠时，
            // 把本类的名去掉，换成controllers，拼出命名空间
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Note that if the module is an application, an empty string will be returned.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
    }

    /**
     * Returns the root directory of the module.
     * It defaults to the directory containing the module class file.
     * @return string the root directory of the module.
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName());
        }

        return $this->_basePath;
    }

    /**
     * Sets the root directory of the module.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module. This can be either a directory name or a path alias.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        $path = Yii::getAlias($path);
        $p = realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidParamException("The directory does not exist: $path");
        }
    }

    /**
     * Returns the directory that contains the controller classes according to [[controllerNamespace]].
     * Note that in order for this method to return a value, you must define
     * an alias for the root namespace of [[controllerNamespace]].
     * @return string the directory that contains the controller classes.
     * @throws InvalidParamException if there is no alias defined for the root namespace of [[controllerNamespace]].
     */
    public function getControllerPath()
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }

    /**
     * Returns the directory that contains the view files for this module.
     * @return string the root directory of view files. Defaults to "[[basePath]]/views".
     */
    public function getViewPath()
    {
        if ($this->_viewPath !== null) {
            return $this->_viewPath;
        } else {
            return $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidParamException if the directory is invalid
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**
     * Returns the directory that contains layout view files for this module.
     * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath !== null) {
            return $this->_layoutPath;
        } else {
            return $this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
        }
    }

    /**
     * Sets the directory that contains the layout files.
     * @param string $path the root directory or path alias of layout files.
     * @throws InvalidParamException if the directory is invalid
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = Yii::getAlias($path);
    }

    /**
     * Defines path aliases.
     * This method calls [[Yii::setAlias()]] to register the path aliases.
     * This method is provided so that you can define path aliases when configuring a module.
     * @property array list of path aliases to be defined. The array keys are alias names
     * (must start with '@') and the array values are the corresponding paths or aliases.
     * See [[setAliases()]] for an example.
     * @param array $aliases list of path aliases to be defined. The array keys are alias names
     * (must start with '@') and the array values are the corresponding paths or aliases.
     * For example,
     *
     * ~~~
     * [
     *     '@models' => '@app/models', // an existing alias
     *     '@backend' => __DIR__ . '/../backend',  // a directory
     * ]
     * ~~~
     */
    public function setAliases($aliases)
    {
        foreach ($aliases as $name => $alias) {
            Yii::setAlias($name, $alias);
        }
    }

    /**
     * Checks whether the child module of the specified ID exists.
     * This method supports checking the existence of both child and grand child modules.
     * 根据指定ID检查子模块是否存在，本方法支持检查子模块和子孙模块的存在。
     * @param string $id module ID. For grand child modules, use ID path relative to this module (e.g. `admin/content`).
     * @return boolean whether the named module exists. Both loaded and unloaded modules
     * are considered.
     */
    public function hasModule($id)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? false : $module->hasModule(substr($id, $pos + 1));
        } else {
            return isset($this->_modules[$id]);
        }
    }

    /**
     * Retrieves the child module of the specified ID.
     * This method supports retrieving both child modules and grand child modules.
     * 根据模块id取出子模块
     * @param string $id module ID (case-sensitive). To retrieve grand child modules,
     * use ID path relative to this module (e.g. `admin/content`).
     * @param boolean $load whether to load the module if it is not yet loaded.
     * 假如模块还未被加载，是否加载
     * @return Module|null the module instance, null if the module does not exist.
     * @see hasModule()
     */
    public function getModule($id, $load = true)
    {
        /*
         *  假如传来的模块id中有单斜杠（至少在来自createController()
         *  函数内部的调用不会有斜杠吧？）则递归本方法依次取出子模块实例。
         */
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
        }

        // 假如在$this->_modules属性中找到了相应的模块，则实例化或直接返回实例
        if (isset($this->_modules[$id])) {
            if ($this->_modules[$id] instanceof Module) {
                return $this->_modules[$id];
            } elseif ($load) {
                Yii::trace("Loading module: $id", __METHOD__);
                /* @var $module Module */
                $module = Yii::createObject($this->_modules[$id], [$id, $this]);
                $module->setInstance($module);
                return $this->_modules[$id] = $module;
            }
        }

        return null;
    }

    /**
     * Adds a sub-module to this module.
     * @param string $id module ID
     * @param Module|array|null $module the sub-module to be added to this module. This can
     * be one of the following:
     *
     * - a [[Module]] object
     * - a configuration array: when [[getModule()]] is called initially, the array
     *   will be used to instantiate the sub-module
     * - null: the named sub-module will be removed from this module
     */
    public function setModule($id, $module)
    {
        if ($module === null) {
            unset($this->_modules[$id]);
        } else {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * Returns the sub-modules in this module.
     * @param boolean $loadedOnly whether to return the loaded sub-modules only. If this is set false,
     * then all sub-modules registered in this module will be returned, whether they are loaded or not.
     * Loaded modules will be returned as objects, while unloaded modules as configuration arrays.
     * @return array the modules (indexed by their IDs)
     */
    public function getModules($loadedOnly = false)
    {
        if ($loadedOnly) {
            $modules = [];
            foreach ($this->_modules as $module) {
                if ($module instanceof Module) {
                    $modules[] = $module;
                }
            }

            return $modules;
        } else {
            return $this->_modules;
        }
    }

    /**
     * Registers sub-modules in the current module.
     * 在当前模块注册子模块
     *
     * Each sub-module should be specified as a name-value pair, where
     * name refers to the ID of the module and value the module or a configuration
     * array that can be used to create the module. In the latter case, [[Yii::createObject()]]
     * will be used to create the module.
     * 每个子模块都应被定义为键值对，键名为模块id，键值可以为类名字符串或配置数组。、
     * 对于后着，将使用Yii::createObject()创建对象。
     *
     * If a new sub-module has the same ID as an existing one, the existing one will be overwritten silently.
     * 假如子模块与存在的模块id相同，那么存在的模块将会被自动覆盖，
     *
     * The following is an example for registering two sub-modules:
     * 注册两个子模块的例子如下：
     *
     * ~~~
     * [
     *     'comment' => [
     *         'class' => 'app\modules\comment\CommentModule',
     *         'db' => 'db',
     *     ],
     *     'booking' => ['class' => 'app\modules\booking\BookingModule'],
     * ]
     * ~~~
     *
     * @param array $modules modules (id => module configuration or instances)
     */
    public function setModules($modules)
    {
        foreach ($modules as $id => $module) {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * Runs a controller action specified by a route.
     * 根据路由的定义运行控制器动作。
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * If the route is empty, the method will use [[defaultRoute]].
     * 本方法解析定义的路由，并创建相应的子模块，控制器和对象实例，之后调用Controller::runAction()方法使用给定的参数
     * 运行动作。假如路由是空的，本方法将使用默认路由defaultRoute
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully
     */
    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            // 控制器实例化成功了，那就交给控制器的$controller->runAction()方法实例化动作
            list($controller, $actionID) = $parts;
            $oldController = Yii::$app->controller;
            Yii::$app->controller = $controller;
            $result = $controller->runAction($actionID, $params);
            Yii::$app->controller = $oldController;

            return $result;
        } else {
            $id = $this->getUniqueId();
            throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
        }
    }

    /**
     * Creates a controller instance based on the given route.
     * 根据给定的路由创建控制器实例。
     *
     * The route should be relative to this module. The method implements the following algorithm
     * to resolve the given route:
     * 路由必须与本模块有关，本方法实现了以下的算法去解析路由：
     *
     * 1. If the route is empty, use [[defaultRoute]];
     * 假如路由为空，使用defaultRoute
     * 2. If the first segment of the route is a valid module ID as declared in [[modules]],
     *    call the module's `createController()` with the rest part of the route;
     * 假如路由的第一段是在modules属性中定义的合法模块id，则调用（其模块的）createController()方法
     * 处理路由的剩余部分。
     * 3. If the first segment of the route is found in [[controllerMap]], create a controller
     *    based on the corresponding configuration found in [[controllerMap]];
     * 假如路由的第一段在controllerMap中，则跟据controllerMap中相应的配置实例化控制器。
     * 4. The given route is in the format of `abc/def/xyz`. Try either `abc\DefController`
     *    or `abc\def\XyzController` class within the [[controllerNamespace|controller namespace]].
     * 假如路由的规则形如abc/def/xyz，则依次使用controllerNamespace尝试abc\DefController或
     * abc\def\XyzController
     *
     * If any of the above steps resolves into a controller, it is returned together with the rest
     * part of the route which will be treated as the action ID. Otherwise, false will be returned.
     *
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|boolean If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise false will be returned.
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        // 路由为空则使用默认
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        // 双斜杠或开头结尾处的斜杠会引起substr的错误，因此排除
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        // 假如找到了单斜杠 则解析一个，剩下的单独存放，如：aaa/bbb/ccc =>['aaa','bbb/ccc']
        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            // 假如没找到单斜杠 则说明字符串就是模块id。
            $id = $route;
            $route = '';
        }

        // module and controller map take precedence
        // 模块和控制器map具有优先权，如果在控制器map中找到了，则实例化。
        if (isset($this->controllerMap[$id])) {
            // 为什么配置数组要传一个$this？
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
        /*
         *  控制器map找不到，就在模块map中招，找到了实例就返回实例的
         * $module->createController($route);方法。没找到实例接着往下走。
         */
        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        /*
         * 如果aaa/bbb/ccc 拆为 ['aaa','bbb/ccc']，但aaa既不是模块，也不是控制器，
         * 且第二段还存在单斜杠，那就换种拆法：['aaa/bbb','ccc']
         */
        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        // 创建出控制器对象
        $controller = $this->createControllerByID($id);
        // 假如还是没创建出控制器对象，那就把['aaa/bbb','ccc']还原为aaa/bbb/ccc 
        // 当作控制器全限定名实例化之
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }

        // 最后看看是不是还是为空，空就没办法了。
        return $controller === null ? false : [$controller, $route];
    }

    /**
     * Creates a controller based on the given controller ID.
     * 根据给定的控制器id创建控制器对象
     *
     * The controller ID is relative to this module. The controller class
     * should be namespaced under [[controllerNamespace]].
     * 控制器id与本模块有关，控制器类的命名空间必须在controllerNamespace属性之下
     *
     * Note that this method does not check [[modules]] or [[controllerMap]].
     * 注意，本方法不会检查modules和controllerMap两个属性。
     *
     * @param string $id the controller ID
     * @return Controller the newly created controller instance, or null if the controller ID is invalid.
     * @throws InvalidConfigException if the controller class and its file name do not match.
     * This exception is only thrown when in debug mode.
     */
    public function createControllerByID($id)
    {
        //判断给定的控制器id有没有单斜杠，单斜杠前面的作为前缀
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        // 假如类名不是由字母开头，字母数字反斜线-_组成的，则返回空
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return null;
        }
        // 假如前缀不为空，且不是由字母数字下划线组成的？ 则返回空
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return null;
        }

        // 将$className去掉空格首字母大写拼接Controller
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        // 拼接命名空间，去掉左侧斜杠，反斜杠替换掉正斜杠，拼接。
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix)  . $className, '\\');
        // 拼接出的全限定名中有-或者不存在此类则返回null
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        // 如果类存在且是控制器基类的子类，则实例化，并返回
        if (is_subclass_of($className, 'yii\base\Controller')) {
            $controller = Yii::createObject($className, [$id, $this]);
            // 还要判断实例化的对象的全限定名和拼接出的全限定名是否一样
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException("Controller class must extend from \\yii\\base\\Controller.");
        } else {
            return null;
        }
    }

    /**
     * This method is invoked right before an action within this module is executed.
     * 本方法会在本模块被请求的动作执行之前执行。
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     * 本方法将会触发EVENT_BEFORE_ACTION事件，本方法的返回值决定了动作是否会继续执行。
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     * 假如动作不能被运行，在befordAction代码内部应当提供必要的输出或重定向？？？
     *
     * If you override this method, your code should look like the following:
     * 假如你重写了本方法，代码应该看以前想如下的例子：
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     * 返回的布尔值决定动作是否会继续执行
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action within this module is executed.
     * 本方法在动作结束之后执行。
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     * 本方法会触发EVENT_AFTER_ACTION事件，本方法的返回值会被当作动作的返回值。
     *
     * If you override this method, your code should look like the following:
     * 如果你重写本方法，那么代码应该看起来像相面这样：
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }
}
