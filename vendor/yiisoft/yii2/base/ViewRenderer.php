<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ViewRenderer is the base class for view renderer classes.
 * 模板引擎的基类，抽象类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class ViewRenderer extends Component
{
    /**
     * Renders a view file.
     * 渲染视图文件
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     * 本方法由[[View]]类中的方法调用以渲染试图，子类必须实现本方法。
     *
     * @param View $view the view object used for rendering the file.
     * @param string $file the view file.
     * @param array $params the parameters to be passed to the view file.
     * @return string the rendering result
     */
    abstract public function render($view, $file, $params);
}
