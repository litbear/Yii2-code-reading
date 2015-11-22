<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use Yii;
use yii\base\InvalidCallException;
use yii\base\Widget;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * ActiveForm is a widget that builds an interactive HTML form for one or multiple data models.
 * ActiveForm活动表单类是为一个或多个数据模型创建交互式HTML表单的小部件类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActiveForm extends Widget
{
    /**
     * @var array|string $action the form action URL. This parameter will be processed by [[\yii\helpers\Url::to()]].
     * 数组或字符串，表单控制器动作的URL，本参数由[[\yii\helpers\Url::to()]]方法处理
     * @see method for specifying the HTTP method for this form.
     */
    public $action = '';
    /**
     * @var string the form submission method. This should be either 'post' or 'get'. Defaults to 'post'.
     * 字符串，提交表单的HTTP方法。POST或GET方法，默认为POST
     *
     * When you set this to 'get' you may see the url parameters repeated on each request.
     * This is because the default value of [[action]] is set to be the current request url and each submit
     * will add new parameters instead of replacing existing ones.
     * You may set [[action]] explicitly to avoid this:
     * 当参数设为GET方法的时候，你会发现，每个请求都带着URL参数。那是因为表单显示和提交的动作URL会设置到
     * 当前请求和每个提交中
     *
     * ```php
     * $form = ActiveForm::begin([
     *     'method' => 'get',
     *     'action' => ['controller/action'],
     * ]);
     * ```
     */
    public $method = 'post';
    /**
     * @var array the HTML attributes (name-value pairs) for the form tag.
     * 数组，以键值对形式定义的form标签HML属性
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     * 查看\yii\helpers\Html::renderTagAttributes()以获取更多关于如何渲染HTML标签属性的详情。
     */
    public $options = [];
    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * 字符串，调用[[field()]]方法创建新字段时默认的字段类名。
     * @see fieldConfig
     */
    public $fieldClass = 'yii\widgets\ActiveField';
    /**
     * @var array|\Closure the default configuration used by [[field()]] when creating a new field object.
     * 数组或闭包，[[field()]]穿件新字段对象时默认使用的配置数组。
     * This can be either a configuration array or an anonymous function returning a configuration array.
     * 既可以是配置数组也可以是返回配置数组的匿名函数。
     * If the latter, the signature should be as follows,
     * 假如是后者，函数签名应如下：
     *
     * ```php
     * function ($model, $attribute)
     * ```
     *
     * The value of this property will be merged recursively with the `$options` parameter passed to [[field()]].
     * 本属性的值会被[[field()]]方法传递的`$options`参数递归的覆盖。
     *
     * @see fieldClass
     */
    public $fieldConfig = [];
    /**
     * @var boolean whether to perform encoding on the error summary.
     * 布尔值，在错误摘要中是否执行编码
     */
    public $encodeErrorSummary = true;
    /**
     * @var string the default CSS class for the error summary container.
     * 字符串，错误摘要容器的默认CSS类
     * @see errorSummary()
     */
    public $errorSummaryCssClass = 'error-summary';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is required.
     * 相关属性为必填时，字段容器的CSS类
     */
    public $requiredCssClass = 'required';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute has validation error.
     * 相关属性合法性出错时，字段容器的CSS类
     */
    public $errorCssClass = 'has-error';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is successfully validated.
     * 相关属性合法性检验成功时，字段容器的CSS类
     */
    public $successCssClass = 'has-success';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is being validated.
     * 相关属性正在被检验时，字段容器的CSS类
     */
    public $validatingCssClass = 'validating';
    /**
     * @var boolean whether to enable client-side data validation.
     * If [[ActiveField::enableClientValidation]] is set, its value will take precedence for that input field.
     * 布尔值，是否开启客户端的数据合法性检验。假如设置了[[ActiveField::enableClientValidation]]属性
     * 对应的字段值会优先考虑彼属性
     */
    public $enableClientValidation = true;
    /**
     * @var boolean whether to enable AJAX-based data validation.
     * If [[ActiveField::enableAjaxValidation]] is set, its value will take precedence for that input field.
     * 布尔值，是否开启AJAX检验。假如设置了[[ActiveField::enableAjaxValidation]]属性
     * 对应的字段值会优先考虑彼属性
     */
    public $enableAjaxValidation = false;
    /**
     * @var boolean whether to hook up yii.activeForm JavaScript plugin.
     * 布尔值，是否绑定名为yii.activeForm的JS插件
     * This property must be set true if you want to support client validation and/or AJAX validation, or if you
     * want to take advantage of the yii.activeForm plugin. When this is false, the form will not generate
     * any JavaScript.
     * 假如要执行客户端或AJAX验证或者利用yii.activeForm插件，本属性必须为true。反之，表单不会生成任何JS代码
     */
    public $enableClientScript = true;
    /**
     * @var array|string the URL for performing AJAX-based validation. This property will be processed by
     * [[Url::to()]]. Please refer to [[Url::to()]] for more details on how to configure this property.
     * If this property is not set, it will take the value of the form's action attribute.
     * 数组或字符串，执行AJAX验证的URL。本属性主要由[[Url::to()]]方法是用，更多关于如何配置本属性的
     * 细节请参考该方法。假如未设置本属性，则会使用action属性。
     */
    public $validationUrl;
    /**
     * @var boolean whether to perform validation when the form is submitted.
     * 布尔值，提交的时候是否执行验证。
     */
    public $validateOnSubmit = true;
    /**
     * @var boolean whether to perform validation when the value of an input field is changed.
     * If [[ActiveField::validateOnChange]] is set, its value will take precedence for that input field.
     * 布尔值，输入字段改变的时候，是否触发合法性验证。假如设置了[[ActiveField::validateOnChange]]属性
     * 对应的字段值会优先考虑彼属性
     */
    public $validateOnChange = true;
    /**
     * @var boolean whether to perform validation when an input field loses focus.
     * If [[ActiveField::$validateOnBlur]] is set, its value will take precedence for that input field.
     * 布尔值，相应字段失去焦点时，是否执行合法性验证。[[ActiveField::$validateOnBlur]]属性如果被设置了，则会
     * 被优先考虑
     */
    public $validateOnBlur = true;
    /**
     * @var boolean whether to perform validation while the user is typing in an input field.
     * If [[ActiveField::validateOnType]] is set, its value will take precedence for that input field.
     * 布尔值，用户键入字段的时候是否执行合法性验证。[[ActiveField::validateOnType]]如果被设置了，则优先考虑
     * @see validationDelay
     */
    public $validateOnType = false;
    /**
     * @var integer number of milliseconds that the validation should be delayed when the user types in the field
     * and [[validateOnType]] is set true.
     * If [[ActiveField::validationDelay]] is set, its value will take precedence for that input field.
     * 整型，用户输入后执行验证前延迟的毫秒数。前提是[[validateOnType]]为真。优先考虑对应的[[ActiveField::validationDelay]]属性。
     */
    public $validationDelay = 500;
    /**
     * @var string the name of the GET parameter indicating the validation request is an AJAX request.
     * 字符串，表明AJAX验证请求的GET参数。
     */
    public $ajaxParam = 'ajax';
    /**
     * @var string the type of data that you're expecting back from the server.
     * 字符串，期待服务器返回的数据类型。
     */
    public $ajaxDataType = 'json';
    /**
     * @var boolean whether to scroll to the first error after validation.
     * 布尔值，光标是否跳转到第一个非法输入？？？
     * @since 2.0.6
     */
    public $scrollToError = true;
    /**
     * @var array the client validation options for individual attributes. Each element of the array
     * represents the validation options for a particular attribute.
     * 数组，个体属性的客户端验证选项，数组的每个元素代表着一个特殊属性的验证配置
     * @internal
     */
    public $attributes = [];

    /**
     * @var ActiveField[] the ActiveField objects that are currently active
     * 当前处于活动状态的ActiveField 实例集合
     */
    private $_fields = [];


    /**
     * Initializes the widget.
     * This renders the form open tag.
     * 初始化小部件，渲染表单标签
     */
    public function init()
    {
        // 将本小部件的ID写入到form的HTML属性中
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        echo Html::beginForm($this->action, $this->method, $this->options);
    }

    /**
     * Runs the widget.
     * This registers the necessary javascript code and renders the form close tag.
     * 运行小部件，本方法为表单注册了必要的JS代码，并且渲染了form表单关闭标签
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching
     */
    public function run()
    {
        // 假如字段对象集合为空，则会抛出开始字段与结束字段不匹配的异常
        if (!empty($this->_fields)) {
            throw new InvalidCallException('Each beginField() should have a matching endField() call.');
        }

        if ($this->enableClientScript) {
            $id = $this->options['id'];
            $options = Json::htmlEncode($this->getClientOptions());
            $attributes = Json::htmlEncode($this->attributes);
            //获取关联视图
            $view = $this->getView();
            // 为资源包绑定视图
            ActiveFormAsset::register($view);
            // 为视图注册JS标签
            $view->registerJs("jQuery('#$id').yiiActiveForm($attributes, $options);");
        }

        echo Html::endForm();
    }

    /**
     * Returns the options for the form JS widget.
     * @return array the options
     */
    protected function getClientOptions()
    {
        $options = [
            'encodeErrorSummary' => $this->encodeErrorSummary,
            'errorSummary' => '.' . implode('.', preg_split('/\s+/', $this->errorSummaryCssClass, -1, PREG_SPLIT_NO_EMPTY)),
            'validateOnSubmit' => $this->validateOnSubmit,
            'errorCssClass' => $this->errorCssClass,
            'successCssClass' => $this->successCssClass,
            'validatingCssClass' => $this->validatingCssClass,
            'ajaxParam' => $this->ajaxParam,
            'ajaxDataType' => $this->ajaxDataType,
            'scrollToError' => $this->scrollToError,
        ];
        if ($this->validationUrl !== null) {
            $options['validationUrl'] = Url::to($this->validationUrl);
        }

        // only get the options that are different from the default ones (set in yii.activeForm.js)
        return array_diff_assoc($options, [
            'encodeErrorSummary' => true,
            'errorSummary' => '.error-summary',
            'validateOnSubmit' => true,
            'errorCssClass' => 'has-error',
            'successCssClass' => 'has-success',
            'validatingCssClass' => 'validating',
            'ajaxParam' => 'ajax',
            'ajaxDataType' => 'json',
            'scrollToError' => true,
        ]);
    }

    /**
     * Generates a summary of the validation errors.
     * If there is no validation error, an empty error summary markup will still be generated, but it will be hidden.
     * @param Model|Model[] $models the model(s) associated with this form
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - header: string, the header HTML for the error summary. If not set, a default prompt string will be used.
     * - footer: string, the footer HTML for the error summary.
     *
     * The rest of the options will be rendered as the attributes of the container tag. The values will
     * be HTML-encoded using [[\yii\helpers\Html::encode()]]. If a value is null, the corresponding attribute will not be rendered.
     * @return string the generated error summary
     * @see errorSummaryCssClass
     */
    public function errorSummary($models, $options = [])
    {
        Html::addCssClass($options, $this->errorSummaryCssClass);
        $options['encode'] = $this->encodeErrorSummary;
        return Html::errorSummary($models, $options);
    }

    /**
     * Generates a form field.
     * A form field is associated with a model and an attribute. It contains a label, an input and an error message
     * and use them to interact with end users to collect their inputs for the attribute.
     * @param Model $model the data model
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object. These are properties of [[ActiveField]]
     * or a subclass, depending on the value of [[fieldClass]].
     * @return ActiveField the created ActiveField object
     * @see fieldConfig
     */
    public function field($model, $attribute, $options = [])
    {
        $config = $this->fieldConfig;
        if ($config instanceof \Closure) {
            $config = call_user_func($config, $model, $attribute);
        }
        if (!isset($config['class'])) {
            $config['class'] = $this->fieldClass;
        }
        return Yii::createObject(ArrayHelper::merge($config, $options, [
            'model' => $model,
            'attribute' => $attribute,
            'form' => $this,
        ]));
    }

    /**
     * Begins a form field.
     * This method will create a new form field and returns its opening tag.
     * You should call [[endField()]] afterwards.
     * @param Model $model the data model
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object
     * @return string the opening tag
     * @see endField()
     * @see field()
     */
    public function beginField($model, $attribute, $options = [])
    {
        $field = $this->field($model, $attribute, $options);
        $this->_fields[] = $field;
        return $field->begin();
    }

    /**
     * Ends a form field.
     * This method will return the closing tag of an active form field started by [[beginField()]].
     * @return string the closing tag of the form field
     * @throws InvalidCallException if this method is called without a prior [[beginField()]] call.
     */
    public function endField()
    {
        $field = array_pop($this->_fields);
        if ($field instanceof ActiveField) {
            return $field->end();
        } else {
            throw new InvalidCallException('Mismatching endField() call.');
        }
    }

    /**
     * Validates one or several models and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ~~~
     * $model = new Post;
     * $model->load($_POST);
     * if (Yii::$app->request->isAjax) {
     *     Yii::$app->response->format = Response::FORMAT_JSON;
     *     return ActiveForm::validate($model);
     * }
     * // ... respond to non-AJAX request ...
     * ~~~
     *
     * To validate multiple models, simply pass each model as a parameter to this method, like
     * the following:
     *
     * ~~~
     * ActiveForm::validate($model1, $model2, ...);
     * ~~~
     *
     * @param Model $model the model to be validated
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     *
     * When this method is used to validate multiple models, this parameter will be interpreted
     * as a model.
     *
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validate($model, $attributes = null)
    {
        $result = [];
        if ($attributes instanceof Model) {
            // validating multiple models
            $models = func_get_args();
            $attributes = null;
        } else {
            $models = [$model];
        }
        /* @var $model Model */
        foreach ($models as $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, $attribute)] = $errors;
            }
        }

        return $result;
    }

    /**
     * Validates an array of model instances and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code for tabular input.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ~~~
     * // ... load $models ...
     * if (Yii::$app->request->isAjax) {
     *     Yii::$app->response->format = Response::FORMAT_JSON;
     *     return ActiveForm::validateMultiple($models);
     * }
     * // ... respond to non-AJAX request ...
     * ~~~
     *
     * @param array $models an array of models to be validated.
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validateMultiple($models, $attributes = null)
    {
        $result = [];
        /* @var $model Model */
        foreach ($models as $i => $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, "[$i]" . $attribute)] = $errors;
            }
        }

        return $result;
    }
}
