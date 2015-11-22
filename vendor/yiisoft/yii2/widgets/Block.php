<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\base\Widget;

/**
 * Block records all output between [[begin()]] and [[end()]] calls and stores it in [[\yii\base\View::$blocks]].
 * for later use.
 * Block视图块类记录了所有[[begin()]]方法[[end()]]方法之间的输出，并将其储存在[[\yii\base\View::$blocks]]
 * 中以供使用。
 *
 * [[\yii\base\View]] component contains two methods [[\yii\base\View::beginBlock()]] and [[\yii\base\View::endBlock()]].
 * The general idea is that you're defining block default in a view or layout:
 * [[\yii\base\View]]视图组件包含两个方法[[\yii\base\View::beginBlock()]]和[[\yii\base\View::endBlock()]]
 * 通常情况下在视图或者布局文件中国定义一个视图块
 *
 * ```php
 * <?php $this->beginBlock('messages', true) ?>
 * Nothing.
 * <?php $this->endBlock() ?>
 * ```
 *
 * And then overriding default in sub-views:
 * 在子视图中可以重写视图块
 *
 * ```php
 * <?php $this->beginBlock('username') ?>
 * Umm... hello?
 * <?php $this->endBlock() ?>
 * ```
 *
 * Second parameter defines if block content should be outputted which is desired when rendering its content but isn't
 * desired when redefining it in subviews.
 * 第二个参数决定了在渲染的同时是否呈现其内容。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Block extends Widget
{
    /**
     * @var boolean whether to render the block content in place. Defaults to false,
     * meaning the captured block content will not be displayed.
     * 布尔值，是否呈现视图块中的内容，默认为false，意味着捕获视图块而不显示。
     */
    public $renderInPlace = false;


    /**
     * Starts recording a block.
     * 开始记录视图块
     */
    public function init()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Ends recording a block.
     * This method stops output buffering and saves the rendering result as a named block in the view.
     * 停止记录视图块，并将其保存在绑定视图的视图块栈中
     */
    public function run()
    {
        $block = ob_get_clean();
        if ($this->renderInPlace) {
            echo $block;
        }
        $this->view->blocks[$this->getId()] = $block;
    }
}
