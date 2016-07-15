<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Controller is the base class for classes containing controller logic.
 * Controller 类是所有包含控制器逻辑的类的基类
 * 继承自Component类 因此具有事件 行为等特性
 *
 * @property Module[] $modules All ancestor modules that this controller is located within. This property is
 * read-only.
 * @property string $route The route (module ID, controller ID and action ID) of the current request. This
 * property is read-only.
 * @property string $uniqueId The controller ID that is prefixed with the module ID (if any). This property is
 * read-only.
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 * @property string $viewPath The directory containing the view files for this controller. This property is
 * read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Controller extends Component implements ViewContextInterface
{
    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     * 动作事件，在执行控制器动作之前执行的事件，可以通过设置 [[ActionEvent::isValid]] 
     * 属性为false来取消控制器动作的执行
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     * 在控制器动作执行之后执行的事件
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var string the ID of this controller.
     * 控制器的id
     */
    public $id;
    /**
     * @var Module $module the module that this controller belongs to.
     * 控制器属于的那个module
     */
    public $module;
    /**
     * @var string the ID of the action that is used when the action ID is not specified
     * in the request. Defaults to 'index'.
     * 默认动作名称
     */
    public $defaultAction = 'index';
    /**
     * @var string|boolean the name of the layout to be applied to this controller's views.
     * This property mainly affects the behavior of [[render()]].
     * Defaults to null, meaning the actual layout value should inherit that from [[module]]'s layout value.
     * If false, no layout will be applied.
     * 本控制器应用的布局文件，接受string或boolean类型，本属性主要影响render()行为
     * 默认为空，意味着从[[module]]处继承（没错，是继承，module是行为？）布局文件，假如
     * 设为false。则表示没有布局文件
     */
    public $layout;
    /**
     * @var Action the action that is currently being executed. This property will be set
     * by [[run()]] when it is called by [[Application]] to run an action.
     * 当前被执行的动作。当Application调用动作时，该属性会在run()方法中被设置，
     */
    public $action;

    /**
     * @var View the view object that can be used to render views or view files.
     * 被用作渲染的视图对象
     */
    private $_view;


    /**
     * @param string $id the ID of this controller.
     * 控制器id
     * @param Module $module the module that this controller belongs to.
     * 控制器所在的module对象
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * 用来初始化对象的键值对属性数组
     */
    public function __construct($id, $module, $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

    /**
     * Declares external actions for the controller.
     * 声明控制器的外部动作
     * This method is meant to be overwritten to declare external actions for the controller.
     * It should return an array, with array keys being action IDs, and array values the corresponding
     * action class names or action configuration arrays. For example,
     * 本方法将被复写，用来声明本控制器的外部动作，本方法会返回一个【以动作id为键，相应的动作类名或
     * 动作配置数组为值】的数组，例如：
     *
     * ~~~
     * return [
     *     'action1' => 'app\components\Action1',
     *     'action2' => [
     *         'class' => 'app\components\Action2',
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ~~~
     *
     * [[\Yii::createObject()]] will be used later to create the requested action
     * using the configuration provided here.
     * 稍后，[[\Yii::createObject()]]将会利用这里提供的配置去创建被请求的动作
     */
    public function actions()
    {
        return [];
    }

    /**
     * Runs an action within this controller with the specified action ID and parameters.
     * If the action ID is empty, the method will use [[defaultAction]].
     * 通过指定的动作id和参数运行本控制器相应的动作。假如动作id为空，则使用[[defaultAction]]
     * @param string $id the ID of the action to be executed.
     * @param array $params the parameters (name-value pairs) to be passed to the action.
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()
     */
    public function runAction($id, $params = [])
    {
        /*
         *  $this->createAction($id);会得到一个动作类或内联动作类的实例
         *  class InlineAction extends Action 
         *  class Action extends Component
         */
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }

        Yii::trace("Route to run: " . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction = $this->action;
        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules
        /*
         * 获取所有父类的module，依次调用他们的beforeAction
         * 【获取当前控制器的所以的模块，并执行每个模块的beforeAction来检查当前的action是否可以执行，
         * 注意：getModules返回的数组顺序为：从父模块到子模块，
         * 所以在执行beforeAction的时候，先检查最外层的父模块，然后检查子模块。
         *
         * 然而在执行afterAction的时候，顺序就反过来了，先执行子模块，最后执行父模块。】
         */
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        /**
         * 如果$runAction 始终标记为true且当前控制器的
         * beforeAction($action)也通过，则执行action
         */
        if ($runAction && $this->beforeAction($action)) {
            // run the action
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        $this->action = $oldAction;

        return $result;
    }

    /**
     * Runs a request specified in terms of a route.
     * The route can be either an ID of an action within this controller or a complete route consisting
     * of module IDs, controller ID and action ID. If the route starts with a slash '/', the parsing of
     * the route will start from the application; otherwise, it will start from the parent module of this controller.
     * @param string $route the route to be handled, e.g., 'view', 'comment/view', '/admin/comment/view'.
     * 待使用的路由
     * @param array $params the parameters to be passed to the action.
     * 传入动作的参数
     * @return mixed the result of the action.
     * @see runAction()
     */
    public function run($route, $params = [])
    {
        $pos = strpos($route, '/');
        //路由中没找到'/' 则直接调用runAction()
        if ($pos === false) {
            return $this->runAction($route, $params);
            // '/'在中间则调用模块module的runAction()
        } elseif ($pos > 0) {
            return $this->module->runAction($route, $params);
            // '/'在开头，则调用当前的应用来处理路由
        } else {
            return Yii::$app->runAction(ltrim($route, '/'), $params);
        }
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[Action]] when it begins to run with the given parameters.
     * 将参数绑定到动作上。在带着给定参数开始运行时，本方法由Action类的实例调用。
     * @param Action $action the action to be bound with parameters.
     * @param array $params the parameters to be bound to the action.
     * @return array the valid parameters that the action can run with.
     */
    public function bindActionParams($action, $params)
    {
        return [];
    }

    /**
     * Creates an action based on the given action ID.
     * 使用给定的动作id创建动作
     * The method first checks if the action ID has been declared in [[actions()]]. If so,
     * it will use the configuration declared there to create the action object.
     * If not, it will look for a controller method whose name is in the format of `actionXyz`
     * where `Xyz` stands for the action ID. If found, an [[InlineAction]] representing that
     * method will be created and returned.
     * 本方法首先会检查actions()中是否定义了动作id。假如定义了，将会使用定义的配置去创建动作对象。
     * 假如没定义，就会找本控制器中哪个方法是以'actionXyz'形式命名的，'Xyz'就是动作id。假如找到了，
     * 那么就是内联的动作会被创建和返回
     * @param string $id the action ID.
     * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
     */
    public function createAction($id)
    {
        // id为空？那么使用默认动作
        if ($id === '') {
            $id = $this->defaultAction;
        }

        /*
         *  查找外部动作 假如发现了，以下方数组为例：
         * [
         *     'action1' => 'app\components\Action1',
         *     'action2' => [
         *         'class' => 'app\components\Action2',
         *         'property1' => 'value1',
         *         'property2' => 'value2',
         *     ],
         * ];
         * Yii::createObject 返回：return static::$container->get($type, $params);
         * 如果在外部动作数组没找到，则找本控制器内部的数组，并用实例化InlineAction类
         * 如果都没找到 则返回null
         */
        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
            /*
             * action id由：a到z、0到9、\、-、_ 这五种字符组成，
             * 并且不能包含“--”
             * 并且不能以“-”为开头或结尾
             *
             * 先以“-”把id分隔为数组，再以“ ”连接到字符串，把每个单词首字母大写，最后把“ ”去掉，并和"action"连接
             * 如;
             * 1、new-post-v-4
             * 2、['new','post','v','4']
             * 3、new post v 4
             * 4、New Post V 4
             * 5、NewPostV4
             * 6、actionNewPostV4
             */
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

    /**
     * This method is invoked right before an action is executed.
     * 本方法在动作执行前被执行
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     * 本方法将会触发[[EVENT_BEFORE_ACTION]] 事件，本方法的返回值将会决定动作是否会继续被执行。
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     * 假如动作不会执行，那么请求将会由本方法内部的代码负责处理。处理的方式是给出必要的输出提示或者跳转请求。另外，响应
     * 内容是空的。
     *
     * If you override this method, your code should look like the following:
     * 假如重写了本方法，需要像这样组织代码：
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
     * @return boolean whether the action should continue to run.
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
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

    /**
     * Returns all ancestor modules of this controller.
     * The first module in the array is the outermost one (i.e., the application instance),
     * while the last is the innermost one.
     * 获取当前控制器的所有父模块module。第一个模块是最外部的模块(也就是application实例)，
     * 最后一个是最内部的模块。
     * @return Module[] all ancestor modules that this controller is located within.
     */
    public function getModules()
    {
        $modules = [$this->module];
        $module = $this->module;
        while ($module->module !== null) {
            array_unshift($modules, $module->module);
            $module = $module->module;
        }
        return $modules;
    }

    /**
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
    }

    /**
     * Returns the route of the current request.
     * @return string the route (module ID, controller ID and action ID) of the current request.
     */
    public function getRoute()
    {
        return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
    }

    /**
     * Renders a view and applies layout if available.
     * 渲染视图并对其应用布局文件
     *
     * The view to be rendered can be specified in one of the following formats:
     * 待渲染的布局文件可以是以下几种形式之一（View类翻译过了，这里简要说明）:
     *
     * - path alias (e.g. "@app/views/site/index");
     * - 别名
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - 双斜线开头的，以app根目录为基准的绝对路径
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
     * - 单斜线开头的，以本模块为基准的绝对路径
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     * - 相对路径
     *
     * To determine which layout should be applied, the following two steps are conducted:
     * 判断要应用哪个模板，需要经过以下几步：
     *
     * 1. In the first step, it determines the layout name and the context module:
     * 1. 第一步，判断模型名称和模块上下文。
     *
     * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
     * - 判断本控制器的[[layout]]属性值是否为字符串，如果是，则使用为布局文件，并且将当前模块确定为模块上下文。
     * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
     *   module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
     *   are used as the layout name and the context module, respectively. If such a module is not found
     *   or the corresponding layout is not a string, it will return false, meaning no applicable layout.
     * - 如果[[layout]]属性为空，则在该控制器的所有祖先模块中从子孙到祖先依次搜索[[layout]]属性，直到找到有字符串属性值
     *   的，使用为布局文件，对应的模块使用为模块上下文。假如，一直没找到[[layout]]属性有值的模块，则不应用布局文件。
     *
     * 2. In the second step, it determines the actual layout file according to the previously found layout name
     *    and context module. The layout name can be:
     * 2. 第二步，获取上一步取得的布局文件真是的路径以及模块上下文，布局文件的名称可以为：
     *
     * - a path alias (e.g. "@app/views/layouts/main");
     * - 别名
     * - an absolute path (e.g. "/main"): the layout name starts with a slash. The actual layout file will be
     *   looked for under the [[Application::layoutPath|layout path]] of the application;
     * - 单斜线开头的 在应用下搜索的布局文件
     * - a relative path (e.g. "main"): the actual layout file will be looked for under the
     *   [[Module::layoutPath|layout path]] of the context module.
     * - 相对路径，在当前模块上下文处的view文件夹下的布局文件
     *
     * If the layout name does not contain a file extension, it will use the default one `.php`.
     * 假如布局文件没有扩展名，默认为`.php`
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * These parameters will not be available in the layout.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file or the layout file does not exist.
     */
    public function render($view, $params = [])
    {
        /**
         *  先调用View类的方法渲染相应的视图
         * （不带布局文件）以字符串形式返回
         */
        $content = $this->getView()->render($view, $params, $this);
        // 再渲染内容，并返回结果
        return $this->renderContent($content);
    }

    /**
     * Renders a static string by applying a layout.
     * 渲染静态字符串，并为之应用布局文件
     * @param string $content the static string being rendered
     * @return string the rendering result of the layout with the given static string as the `$content` variable.
     * If the layout is disabled, the string will be returned back.
     * @since 2.0.1
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            // 再次调用renderFile()方法，渲染布局文件，并分配刚刚返回的字符串为$content变量
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        } else {
            return $content;
        }
    }

    /**
     * Renders a view without applying layout.
     * This method differs from [[render()]] in that it does not apply any layout.
     * 不应用布局文件的渲染
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderPartial($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a path alias.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderFile($file, $params = [])
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]], [[renderPartial()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * 返回可以用来渲染视图或视图文件的view对象。[[render()]], [[renderPartial()]] 
     * 和 [[renderFile()]]方法会使用该view对象实现真正的视图渲染过程
     * @return View|\yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }
        return $this->_view;
    }

    /**
     * Sets the view object to be used by this controller.
     * @param View|\yii\web\View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Returns the directory containing view files for this controller.
     * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
     * [[viewPath]] directory.
     * @return string the directory containing the view files for this controller.
     */
    public function getViewPath()
    {
        return $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
    }

    /**
     * Finds the applicable layout file.
     * 找到该应用的布局文件
     * @param View $view the view object to render the layout file.
     * @return string|boolean the layout file path, or false if layout is not needed.
     * Please refer to [[render()]] on how to specify this parameter.
     * @throws InvalidParamException if an invalid path alias is used to specify the layout.
     */
    public function findLayoutFile($view)
    {
        $module = $this->module;
        if (is_string($this->layout)) {
            $layout = $this->layout;
        } elseif ($this->layout === null) {
            // 一直往上找
            while ($module !== null && $module->layout === null) {
                $module = $module->module;
            }
            if ($module !== null && is_string($module->layout)) {
                $layout = $module->layout;
            }
        }

        if (!isset($layout)) {
            return false;
        }

        if (strncmp($layout, '@', 1) === 0) {
            $file = Yii::getAlias($layout);
        } elseif (strncmp($layout, '/', 1) === 0) {
            $file = Yii::$app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
        } else {
            $file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $view->defaultExtension;
        if ($view->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }
}
