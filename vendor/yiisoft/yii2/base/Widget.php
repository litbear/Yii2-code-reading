<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use ReflectionClass;

/**
 * Widget is the base class for widgets.
 * Widget类是小部件的基类
 *
 * @property string $id ID of the widget.
 * 字符串，小部件的id
 * @property \yii\web\View $view The view object that can be used to render views or view files. Note that the
 * type of this property differs in getter and setter. See [[getView()]] and [[setView()]] for details.
 * \yii\web\View 视图实例，试图对象可以用来渲染视图或视图文件。注意，本属性的设置与读取方法不同，阅读两个方法
 * 获取更多信息。
 * @property string $viewPath The directory containing the view files for this widget. This property is
 * read-only.
 * 字符串，包含本小部件视图文件的文件夹。只读属性。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Widget extends Component implements ViewContextInterface
{
    /**
     * @var integer a counter used to generate [[id]] for widgets.
     * 整型 用于生成id的计数器
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * 字符串，自动生成小部件ID的前缀
     * @see getId()
     * 参见getId()方法
     */
    public static $autoIdPrefix = 'w';
    /**
     * @var Widget[] the widgets that are currently being rendered (not ended). This property
     * is maintained by [[begin()]] and [[end()]] methods.
     * Widget类组成的数组。
     * @internal
     */
    public static $stack = [];


    /**
     * Begins a widget.
     * 开始创建一个小部件
     * This method creates an instance of the calling class. It will apply the configuration
     * to the created instance. A matching [[end()]] call should be called later.
     * 本方法创建一个调用本方法的类实例，并用给定的配置数组实例化。与本方法匹配的end()方法将在稍后调用。
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return static the newly created widget instance
     * 静态的创建一个新的小部件实例
     */
    public static function begin($config = [])
    {
        $config['class'] = get_called_class();
        /* @var $widget Widget */
        $widget = Yii::createObject($config);
        // 缓存小部件Widget或其子类实例
        static::$stack[] = $widget;

        return $widget;
    }

    /**
     * Ends a widget.
     * Note that the rendering result of the widget is directly echoed out.
     * 结束小部件。注意，小部件的渲染结果将被直接echo出来
     * @return static the widget instance that is ended.
     * 静态得结束一个小部件实例
     * @throws InvalidCallException if [[begin()]] and [[end()]] calls are not properly nested
     */
    public static function end()
    {
        if (!empty(static::$stack)) {
            // 从缓存栈中取出，后进后出
            $widget = array_pop(static::$stack);
            // 调用begin()和调用end()的类必须是一个
            if (get_class($widget) === get_called_class()) {
                // 运行子类的run()方法（本类也没有run）
                echo $widget->run();
                return $widget;
            } else {
                throw new InvalidCallException("Expecting end() of " . get_class($widget) . ", found " . get_called_class());
            }
        } else {
            throw new InvalidCallException("Unexpected " . get_called_class() . '::end() call. A matching begin() is not found.');
        }
    }

    /**
     * Creates a widget instance and runs it.
     * The widget rendering result is returned by this method.
     * 创建小部件实例并运行之。小部件的渲染结果由本方法返回。
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return string the rendering result of the widget.
     * 字符串，小部件渲染后的结果
     * @throws \Exception
     */
    public static function widget($config = [])
    {
        ob_start();
        ob_implicit_flush(false);
        try {
            /* @var $widget Widget */
            // 获取widget子类的类名
            $config['class'] = get_called_class();
            $widget = Yii::createObject($config);
            /**
             * 不论是使用了Widgrt::widget()方法还是使用了Widget::end()方法，
             * 最后都会调用Widget::run()方法，而小部件基类中没有写run()方法
             * 因此，基本可以断定小部件基类是不能够单独使用的。
             */
            $out = $widget->run();
        } catch (\Exception $e) {
            // close the output buffer opened above if it has not been closed already
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        // 此处拼接了当前缓冲区的内容和run()方法运行的结果
        return ob_get_clean() . $out;
    }

    private $_id;

    /**
     * Returns the ID of the widget.
     * 返回小部件的ID
     * @param boolean $autoGenerate whether to generate an ID if it is not set previously
     * 布尔值，假如小部件ID之前未指定，是否为其自动生成一个
     * @return string ID of the widget.
     */
    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_id === null) {
            $this->_id = static::$autoIdPrefix . static::$counter++;
        }

        return $this->_id;
    }

    /**
     * Sets the ID of the widget.
     * 为小部件设置ID
     * @param string $value id of the widget.
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    private $_view;

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * 返回用来渲染视图或视图文件的视图实例。本类的[[render()]]和[[renderFile()]]
     * 方法会使用该视图实例实现真正的视图渲染工作。
     * @return \yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

        return $this->_view;
    }

    /**
     * Sets the view object to be used by this widget.
     * 设置本小部件使用的视图实例。
     * @param View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Executes the widget.
     * 解析小部件为字符串
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
    }

    /**
     * Renders a view.
     * The view to be rendered can be specified in one of the following formats:
     * 渲染视图：
     * 待渲染的视图路径可以被指定为以下几种格式
     *
     * - path alias (e.g. "@app/views/site/index");
     * - 路径别名
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - 双斜线开头的，以应用视图目录为基准的绝对路径
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the currently
     *   active module.
     * - 单鞋先开头的，以模块视图目录为基准的绝对路径
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     * - 以小部件下的视图目录为基准的相对路径
     *
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function render($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * 渲染视图文件
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
     * Returns the directory containing the view files for this widget.
     * The default implementation returns the 'views' subdirectory under the directory containing the widget class file.
     * 返回本小部件视图文件所在的文件夹，默认会返回当前小部件所在文件夹的views子文件夹
     * @return string the directory containing the view files for this widget.
     */
    public function getViewPath()
    {
        $class = new ReflectionClass($this);

        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
    }
}
