<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use ArrayAccess;
use ArrayObject;
use ArrayIterator;
use ReflectionClass;
use IteratorAggregate;
use yii\helpers\Inflector;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

/**
 * Model is the base class for data models.
 * Model是所有数据模型的基类
 *
 * Model implements the following commonly used features:
 * Model类实现了以下的常用功能：
 *
 * - attribute declaration: by default, every public class member is considered as
 *   a model attribute
 * - 属性声明：通常，每个公共类成员都被当作模型属性。
 * - attribute labels: each attribute may be associated with a label for display purpose
 * - 属性标签：每个属性都会被关联一个用于显示的标签
 * - massive attribute assignment
 * - 大量属性赋值
 * - scenario-based validation
 * - 基于情境的验证
 *
 * Model also raises the following events when performing data validation:
 * 当执行数据验证的时候，Model会触发以下几个事件：
 *
 * - [[EVENT_BEFORE_VALIDATE]]: an event raised at the beginning of [[validate()]]
 * - [[EVENT_BEFORE_VALIDATE]]: 在validate()方法开始执行的事件
 * - [[EVENT_AFTER_VALIDATE]]: an event raised at the end of [[validate()]]
 * - [[EVENT_AFTER_VALIDATE]]: 在validate()方法结尾执行的事件
 * 
 * You may directly use Model to store model data, or extend it with customization.
 * 你可以直接使用Model类储存模型数据，或者个性化的扩展它。
 *
 * @property \yii\validators\Validator[] $activeValidators The validators applicable to the current
 * [[scenario]]. This property is read-only.
 * 适用于当前[[scenario]]【情境的】的检验器，只读属性。
 * @property array $attributes Attribute values (name => value).
 * 属性键值对。
 * @property array $errors An array of errors for all attributes. Empty array is returned if no error. The
 * result is a two-dimensional array. See [[getErrors()]] for detailed description. This property is read-only.
 * 所有属性的错误数组，没有错误返回空数组。本属性是一个二维数组。更多细节请参照getErrors()方法，只读属性。
 * @property array $firstErrors The first errors. The array keys are the attribute names, and the array values
 * are the corresponding error messages. An empty array will be returned if there is no error. This property is
 * read-only.
 * 第一个错误，数组的键是属性名，值是错误信息。假如没有错误将返回空数组，只读属性，
 * @property ArrayIterator $iterator An iterator for traversing the items in the list. This property is
 * read-only.
 * 遍历列表中项目的迭代器。只读属性。
 * @property string $scenario The scenario that this model is in. Defaults to [[SCENARIO_DEFAULT]].
 * 模型所处的情境，默认为[[SCENARIO_DEFAULT]]
 * @property ArrayObject|\yii\validators\Validator[] $validators All the validators declared in the model.
 * This property is read-only.
 * 声明在Model内的所有检验器，只读属性。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Model extends Component implements IteratorAggregate, ArrayAccess, Arrayable
{
    use ArrayableTrait;

    /**
     * The name of the default scenario.
     * 默认情境
     */
    const SCENARIO_DEFAULT = 'default';
    /**
     * @event ModelEvent an event raised at the beginning of [[validate()]]. You may set
     * [[ModelEvent::isValid]] to be false to stop the validation.
     * 在validate()方法开始执行的事件，你可以设置[[ModelEvent::isValid]] 
     * 为false以停止验证。
     */
    const EVENT_BEFORE_VALIDATE = 'beforeValidate';
    /**
     * @event Event an event raised at the end of [[validate()]]
     * 在validate()方法结束时执行的事件
     */
    const EVENT_AFTER_VALIDATE = 'afterValidate';

    /**
     * @var array validation errors (attribute name => array of errors)
     * 所有错误的二维数组
     */
    private $_errors;
    /**
     * @var ArrayObject list of validators
     * 所有验证器的集合
     */
    private $_validators;
    /**
     * @var string current scenario
     * 当前情境
     */
    private $_scenario = self::SCENARIO_DEFAULT;


    /**
     * Returns the validation rules for attributes.
     * 返回属性的验证结果
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     * 验证规则由[[validate()]]方法用来验证属性是否合法。子类可以重写本方法来定义不同的验证规则
     *
     * Each rule is an array with the following structure:
     * 每个验证方法都是如下结构的数组：
     *
     * ~~~
     * [
     *     ['attribute1', 'attribute2'],
     *     'validator type',
     *     'on' => ['scenario1', 'scenario2'],
     *     ...other parameters...
     * ]
     * ~~~
     *
     * where
     *
     *  - attribute list: required, specifies the attributes array to be validated, for single attribute you can pass a string;
     *  - 属性列表：必须参数，待验证属性组成的数组，单一属性可以直接传一个字符串。
     *  - validator type: required, specifies the validator to be used. It can be a built-in validator name,
     *    a method name of the model class, an anonymous function, or a validator class name.
     *  - 验证规则的类型：必须参数，用来指定相应的验证器，你可以使用一个内置的验证器名称，一个model类方法的名称，
     *  一个匿名函数，或者一个验证类的名称。
     *  - on: optional, specifies the [[scenario|scenarios]] array in which the validation
     *    rule can be applied. If this option is not set, the rule will apply to all scenarios.
     *  - 情境，可选参数，指定验证规则应用的一种或多种情境，假如未设置，则应用到所有情境中。
     *  - additional name-value pairs can be specified to initialize the corresponding validator properties.
     *    Please refer to individual validator class API for possible properties.
     *  - 附加的键值对，可以被用来初始化指定的验证器类。请参考单独的验证器类接口。
     *
     * A validator can be either an object of a class extending [[Validator]], or a model class method
     * (called *inline validator*) that has the following signature:
     * 可以验证器同样可以是一个继承自[[Validator]]类的子类，或者一个模型类的方法（又被称作内联控制器）
     * 有以下几个特点：
     *
     * ~~~
     * // $params refers to validation parameters given in the rule
     * // $params指的是条件里给定的验证器参数（没看懂）
     * function validatorName($attribute, $params)
     * ~~~
     *
     * In the above `$attribute` refers to the attribute currently being validated while `$params` contains an array of
     * validator configuration options such as `max` in case of `string` validator. The value of the attribute currently being validated
     * can be accessed as `$this->$attribute`. Note the `$` before `attribute`; this is taking the value of the variable
     * `$attribute` and using it as the name of the property to access.
     * 以上的`$attribute`指的是正在被验证的属性，`$params`包含着验证器的键值对配置，例如：【最长】被用在字符串验证器上。
     * 正在被验证的属性值可以通过`$this->$attribute`的形式访问。注意，`attribute`前的`$`符号,这是指$attribute变量的值，
     * $attribute变量的值就是属性的名。
     *
     * Yii also provides a set of [[Validator::builtInValidators|built-in validators]].
     * Each one has an alias name which can be used when specifying a validation rule.
     * Yii同样提供了一系列的[[Validator::builtInValidators|built-in validators]]。
     * 每一个都有自己的别名，可以被用在指定验证规则的时候。
     *
     * Below are some examples:
     * 以下是一些实例：
     *
     * ~~~
     * [
     *     // built-in "required" validator
     *     // 内置的"required"验证器
     *     [['username', 'password'], 'required'],
     *     // built-in "string" validator customized with "min" and "max" properties
     *     // 内置的字符串验证器，被规定了最长值和最短值
     *     ['username', 'string', 'min' => 3, 'max' => 12],
     *     // built-in "compare" validator that is used in "register" scenario only
     *     // 内置的比较验证器，指定被用在注册情境中
     *     ['password', 'compare', 'compareAttribute' => 'password2', 'on' => 'register'],
     *     // an inline validator defined via the "authenticate()" method in the model class
     *     // 一个内联的验证器，通过模型类中的"authenticate()"方法验证
     *     ['password', 'authenticate', 'on' => 'login'],
     *     // a validator of class "DateRangeValidator"
     *     // 一个名为"DateRangeValidator"的验证器类
     *     ['dateRange', 'DateRangeValidator'],
     * ];
     * ~~~
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions such as `array_merge()`.
     * 注意：为了继承定义在父类中的规则，子类必须使用像`array_merge()`这样的函数合并父类中的规则
     *
     * @return array validation rules
     * @see scenarios()
     */
    public function rules()
    {
        return [];
    }

    /**
     * Returns a list of scenarios and the corresponding active attributes.
     * An active attribute is one that is subject to validation in the current scenario.
     * The returned array should be in the following format:
     * 返回情景列表与相应的活跃属性。活跃属性是在当前情境中服从于验证器的属性。
     * 返回的数组必须为如下格式：
     *
     * ```php
     * [
     *     'scenario1' => ['attribute11', 'attribute12', ...],
     *     'scenario2' => ['attribute21', 'attribute22', ...],
     *     ...
     * ]
     * ```
     *
     * By default, an active attribute is considered safe and can be massively assigned.
     * If an attribute should NOT be massively assigned (thus considered unsafe),
     * please prefix the attribute with an exclamation character (e.g. `'!rank'`).
     * 默认情况下，活动属性被认为是安全的，可大规模赋值的。假如一个属性不能被大规模赋值，
     * 请在它的前面加一个叹号前缀。
     *
     * The default implementation of this method will return all scenarios found in the [[rules()]]
     * declaration. A special scenario named [[SCENARIO_DEFAULT]] will contain all attributes
     * found in the [[rules()]]. Each scenario will be associated with the attributes that
     * are being validated by the validation rules that apply to the scenario.
     * 本方法的默认实现会返回在rules()方法中找到的所有声明的情境。名叫[[SCENARIO_DEFAULT]] 的默认
     * 情境会被包含在 在rules()方法中找到的属性中，每个情境都会被关联该属性。
     *
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = [self::SCENARIO_DEFAULT => []];
        foreach ($this->getValidators() as $validator) {
            foreach ($validator->on as $scenario) {
                $scenarios[$scenario] = [];
            }
            foreach ($validator->except as $scenario) {
                $scenarios[$scenario] = [];
            }
        }
        $names = array_keys($scenarios);

        foreach ($this->getValidators() as $validator) {
            if (empty($validator->on) && empty($validator->except)) {
                foreach ($names as $name) {
                    foreach ($validator->attributes as $attribute) {
                        $scenarios[$name][$attribute] = true;
                    }
                }
            } elseif (empty($validator->on)) {
                foreach ($names as $name) {
                    if (!in_array($name, $validator->except, true)) {
                        foreach ($validator->attributes as $attribute) {
                            $scenarios[$name][$attribute] = true;
                        }
                    }
                }
            } else {
                foreach ($validator->on as $name) {
                    foreach ($validator->attributes as $attribute) {
                        $scenarios[$name][$attribute] = true;
                    }
                }
            }
        }

        foreach ($scenarios as $scenario => $attributes) {
            if (!empty($attributes)) {
                $scenarios[$scenario] = array_keys($attributes);
            }
        }

        return $scenarios;
    }

    /**
     * Returns the form name that this model class should use.
     * 返回使用本模型的表单的名称
     *
     * The form name is mainly used by [[\yii\widgets\ActiveForm]] to determine how to name
     * the input fields for the attributes in a model. If the form name is "A" and an attribute
     * name is "b", then the corresponding input name would be "A[b]". If the form name is
     * an empty string, then the input name would be "b".
     * 表单名称主要用于[[\yii\widgets\ActiveForm]] 类去确定如何为模型中属性的输入域命名。假如表单名称为A
     * 其中一个属性名为b，那么对应的输入域名称就是A[b]。假如表单名称是空字符串，那么输入域的名称就是b
     *
     * By default, this method returns the model class name (without the namespace part)
     * as the form name. You may override it when the model is used in different forms.
     * 默认情况下，本方法返回模型的类名（并非全限定名作为表单名称，在模型被用于不同表单时
     * 你可以重写此方法。
     *
     * @return string the form name of this model class.
     */
    public function formName()
    {
        $reflector = new ReflectionClass($this);

        return $reflector->getShortName();
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * 返回属性名组成的集合。默认情况下本方法返回公共非静态属性，你可以重写办法去改变他的默认行为
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $class = new ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * Returns the attribute labels.
     * 返回属性的标签
     *
     * Attribute labels are mainly used for display purpose. For example, given an attribute
     * `firstName`, we can declare a label `First Name` which is more user-friendly and can
     * be displayed to end users.
     * 属性标签主要被用于显示数据。例如，给定一个名为`firstName`的属性名，你可以将他的标签定义
     * 为`First Name`。我们可以将标签定义的更加用户友好。
     *
     * By default an attribute label is generated using [[generateAttributeLabel()]].
     * This method allows you to explicitly specify attribute labels.
     * 默认情况下属性标签是由[[generateAttributeLabel()]]生成的，本方法允许你显式地
     * 指定属性标签。
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions such as `array_merge()`.
     * 注意，为了继承父类定义的属性标签，子类需要使用例如 `array_merge()`函数覆盖合并父类的标签。
     *
     * @return array attribute labels (name => label)
     * @see generateAttributeLabel()
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the attribute hints.
     * 返回属性的暗示。
     *
     * Attribute hints are mainly used for display purpose. For example, given an attribute
     * `isPublic`, we can declare a hint `Whether the post should be visible for not logged in users`,
     * which provides user-friendly description of the attribute meaning and can be displayed to end users.
     *
     * Unlike label hint will not be generated, if its explicit declaration is omitted.
     *
     * Note, in order to inherit hints defined in the parent class, a child class needs to
     * merge the parent hints with child hints using functions such as `array_merge()`.
     *
     * @return array attribute hints (name => hint)
     * @since 2.0.4
     */
    public function attributeHints()
    {
        return [];
    }

    /**
     * Performs the data validation.
     * 执行数据验证
     *
     * This method executes the validation rules applicable to the current [[scenario]].
     * The following criteria are used to determine whether a rule is currently applicable:
     *
     * - the rule must be associated with the attributes relevant to the current scenario;
     * - the rules must be effective for the current scenario.
     *
     * This method will call [[beforeValidate()]] and [[afterValidate()]] before and
     * after the actual validation, respectively. If [[beforeValidate()]] returns false,
     * the validation will be cancelled and [[afterValidate()]] will not be called.
     *
     * Errors found during the validation can be retrieved via [[getErrors()]],
     * [[getFirstErrors()]] and [[getFirstError()]].
     *
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @param boolean $clearErrors whether to call [[clearErrors()]] before performing validation
     * @return boolean whether the validation is successful without any error.
     * @throws InvalidParamException if the current scenario is unknown.
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        /*
         * 首先清除所有的错误
         */
        if ($clearErrors) {
            $this->clearErrors();
        }

        // 执行验证前事件
        if (!$this->beforeValidate()) {
            return false;
        }

        // 返回情境列表和对应的活跃属性
        $scenarios = $this->scenarios();
        // 返回当前对应的情境，如果在情境列表没找到，会引起异常
        $scenario = $this->getScenario();
        if (!isset($scenarios[$scenario])) {
            throw new InvalidParamException("Unknown scenario: $scenario");
        }

        /*
         *  取出了当前情境对应的活跃属性，跟上面的逻辑语言
         *  不过 $this->activeAttributes() 方法增加了对叹号前缀的处理
         */
        if ($attributeNames === null) {
            $attributeNames = $this->activeAttributes();
        }

        // 分别获取当前情境下的每个验证器，执行验证【每一个验证器都传入了所有待验证的属性？】
        foreach ($this->getActiveValidators() as $validator) {
            $validator->validateAttributes($this, $attributeNames);
        }
        $this->afterValidate();

        return !$this->hasErrors();
    }

    /**
     * This method is invoked before validation starts.
     * The default implementation raises a `beforeValidate` event.
     * You may override this method to do preliminary checks before validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     * @return boolean whether the validation should be executed. Defaults to true.
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    public function beforeValidate()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_VALIDATE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after validation ends.
     * The default implementation raises an `afterValidate` event.
     * You may override this method to do postprocessing after validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     */
    public function afterValidate()
    {
        $this->trigger(self::EVENT_AFTER_VALIDATE);
    }

    /**
     * Returns all the validators declared in [[rules()]].
     * 返回所有定义在rules()中的验证器相应的实例
     *
     * This method differs from [[getActiveValidators()]] in that the latter
     * only returns the validators applicable to the current [[scenario]].
     * 本方法与[[getActiveValidators()]]的不同之处在于后者只返回当前情境下的
     * 验证器实例。
     *
     * Because this method returns an ArrayObject object, you may
     * manipulate it by inserting or removing validators (useful in model behaviors).
     * For example,
     * 因为本方法返回的是ArrayObject 实例，你可以像使用数组那样使用它。
     *
     * ~~~
     * $model->validators[] = $newValidator;
     * ~~~
     *
     * @return ArrayObject|\yii\validators\Validator[] all the validators declared in the model.
     */
    public function getValidators()
    {
        if ($this->_validators === null) {
            $this->_validators = $this->createValidators();
        }
        return $this->_validators;
    }

    /**
     * Returns the validators applicable to the current [[scenario]].
     * @param string $attribute the name of the attribute whose applicable validators should be returned.
     * If this is null, the validators for ALL attributes in the model will be returned.
     * @return \yii\validators\Validator[] the validators applicable to the current [[scenario]].
     */
    public function getActiveValidators($attribute = null)
    {
        $validators = [];
        $scenario = $this->getScenario();
        foreach ($this->getValidators() as $validator) {
            if ($validator->isActive($scenario) && ($attribute === null || in_array($attribute, $validator->attributes, true))) {
                $validators[] = $validator;
            }
        }
        return $validators;
    }

    /**
     * Creates validator objects based on the validation rules specified in [[rules()]].
     * Unlike [[getValidators()]], each time this method is called, a new list of validators will be returned.
     * 根据在[[rules()]]指定的验证规则创建验证器对象。与[[getValidators()]]不同，每次调用本方法，都会返回一个
     * 新的验证器对象。
     * @return ArrayObject validators
     * @throws InvalidConfigException if any validation rule configuration is invalid
     */
    public function createValidators()
    {
        $validators = new ArrayObject;
        /*
         * 循环所有验证规则。如果是验证器实例，则加入队列。
         * 如果是起码有两个元素的数组，则创建验证器，加入队列
         * 【这里不太懂，$this->rules()的元素目前看到的都是数组，
         * 没见过单独一个元素的】
         */
        foreach ($this->rules() as $rule) {
            if ($rule instanceof Validator) {
                $validators->append($rule);
            } elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
                $validator = Validator::createValidator($rule[1], $this, (array) $rule[0], array_slice($rule, 2));
                $validators->append($validator);
            } else {
                throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
            }
        }
        return $validators;
    }

    /**
     * Returns a value indicating whether the attribute is required.
     * This is determined by checking if the attribute is associated with a
     * [[\yii\validators\RequiredValidator|required]] validation rule in the
     * current [[scenario]].
     *
     * Note that when the validator has a conditional validation applied using
     * [[\yii\validators\RequiredValidator::$when|$when]] this method will return
     * `false` regardless of the `when` condition because it may be called be
     * before the model is loaded with data.
     *
     * @param string $attribute attribute name
     * @return boolean whether the attribute is required
     */
    public function isAttributeRequired($attribute)
    {
        foreach ($this->getActiveValidators($attribute) as $validator) {
            if ($validator instanceof RequiredValidator && $validator->when === null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a value indicating whether the attribute is safe for massive assignments.
     * @param string $attribute attribute name
     * @return boolean whether the attribute is safe for massive assignments
     * @see safeAttributes()
     */
    public function isAttributeSafe($attribute)
    {
        return in_array($attribute, $this->safeAttributes(), true);
    }

    /**
     * Returns a value indicating whether the attribute is active in the current scenario.
     * @param string $attribute attribute name
     * @return boolean whether the attribute is active in the current scenario
     * @see activeAttributes()
     */
    public function isAttributeActive($attribute)
    {
        return in_array($attribute, $this->activeAttributes(), true);
    }

    /**
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        return isset($labels[$attribute]) ? $labels[$attribute] : $this->generateAttributeLabel($attribute);
    }

    /**
     * Returns the text hint for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute hint
     * @see attributeHints()
     * @since 2.0.4
     */
    public function getAttributeHint($attribute)
    {
        $hints = $this->attributeHints();
        return isset($hints[$attribute]) ? $hints[$attribute] : '';
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ~~~
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ~~~
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        } else {
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
        }
    }

    /**
     * Returns the first error of every attribute in the model.
     * @return array the first errors. The array keys are the attribute names, and the array
     * values are the corresponding error messages. An empty array will be returned if there is no error.
     * @see getErrors()
     * @see getFirstError()
     */
    public function getFirstErrors()
    {
        if (empty($this->_errors)) {
            return [];
        } else {
            $errors = [];
            foreach ($this->_errors as $name => $es) {
                if (!empty($es)) {
                    $errors[$name] = reset($es);
                }
            }

            return $errors;
        }
    }

    /**
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     */
    public function getFirstError($attribute)
    {
        return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error = '')
    {
        $this->_errors[$attribute][] = $error;
    }

    /**
     * Adds a list of errors.
     * @param array $items a list of errors. The array keys must be attribute names.
     * The array values should be error messages. If an attribute has multiple errors,
     * these errors must be given in terms of an array.
     * You may use the result of [[getErrors()]] as the value for this parameter.
     * @since 2.0.2
     */
    public function addErrors(array $items)
    {
        foreach ($items as $attribute => $errors) {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    $this->addError($attribute, $error);
                }
            } else {
                $this->addError($attribute, $errors);
            }
        }
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$attribute]);
        }
    }

    /**
     * Generates a user friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     * @param string $name the column name
     * @return string the attribute label
     */
    public function generateAttributeLabel($name)
    {
        return Inflector::camel2words($name, true);
    }

    /**
     * Returns attribute values.
     * @param array $names list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes listed in [[attributes()]] will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * @param array $except list of attributes whose value should NOT be returned.
     * @return array attribute values (name => value).
     */
    public function getAttributes($names = null, $except = [])
    {
        $values = [];
        if ($names === null) {
            $names = $this->attributes();
        }
        foreach ($names as $name) {
            $values[$name] = $this->$name;
        }
        foreach ($except as $name) {
            unset($values[$name]);
        }

        return $values;
    }

    /**
     * Sets the attribute values in a massive way.
     * @param array $values attribute values (name => value) to be assigned to the model.
     * @param boolean $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
     * @see safeAttributes()
     * @see attributes()
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values)) {
            $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $this->$name = $value;
                } elseif ($safeOnly) {
                    $this->onUnsafeAttribute($name, $value);
                }
            }
        }
    }

    /**
     * This method is invoked when an unsafe attribute is being massively assigned.
     * The default implementation will log a warning message if YII_DEBUG is on.
     * It does nothing otherwise.
     * @param string $name the unsafe attribute name
     * @param mixed $value the attribute value
     */
    public function onUnsafeAttribute($name, $value)
    {
        if (YII_DEBUG) {
            Yii::trace("Failed to set unsafe attribute '$name' in '" . get_class($this) . "'.", __METHOD__);
        }
    }

    /**
     * Returns the scenario that this model is used in.
     * 返回本模型对应的情境
     *
     * Scenario affects how validation is performed and which attributes can
     * be massively assigned.
     *
     * @return string the scenario that this model is in. Defaults to [[SCENARIO_DEFAULT]].
     */
    public function getScenario()
    {
        return $this->_scenario;
    }

    /**
     * Sets the scenario for the model.
     * Note that this method does not check if the scenario exists or not.
     * The method [[validate()]] will perform this check.
     * @param string $value the scenario that this model is in.
     */
    public function setScenario($value)
    {
        $this->_scenario = $value;
    }

    /**
     * Returns the attribute names that are safe to be massively assigned in the current scenario.
     * @return string[] safe attribute names
     */
    public function safeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $attributes = [];
        foreach ($scenarios[$scenario] as $attribute) {
            if ($attribute[0] !== '!') {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Returns the attribute names that are subject to validation in the current scenario.
     * @return string[] safe attribute names
     */
    public function activeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $attributes = $scenarios[$scenario];
        foreach ($attributes as $i => $attribute) {
            if ($attribute[0] === '!') {
                $attributes[$i] = substr($attribute, 1);
            }
        }

        return $attributes;
    }

    /**
     * Populates the model with the data from end user.
     * The data to be loaded is `$data[formName]`, where `formName` refers to the value of [[formName()]].
     * If [[formName()]] is empty, the whole `$data` array will be used to populate the model.
     * The data being populated is subject to the safety check by [[setAttributes()]].
     * @param array $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by the end user.
     * @param string $formName the form name to be used for loading the data into the model.
     * If not set, [[formName()]] will be used.
     * @return boolean whether `$data` contained some data to populate the models attributes.
     * This only means that there was some data provided. It does not necessarily mean that any field
     * was populated, which may be the case if none of the provided fields is safe in the current [[scenario]].
     */
    public function load($data, $formName = null)
    {
        $scope = $formName === null ? $this->formName() : $formName;
        if ($scope === '' && !empty($data)) {
            $this->setAttributes($data);

            return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Populates a set of models with the data from end user.
     * This method is mainly used to collect tabular data input.
     * The data to be loaded for each model is `$data[formName][index]`, where `formName`
     * refers to the value of [[formName()]], and `index` the index of the model in the `$models` array.
     * If [[formName()]] is empty, `$data[index]` will be used to populate each model.
     * The data being populated to each model is subject to the safety check by [[setAttributes()]].
     * @param array $models the models to be populated. Note that all models should have the same class.
     * @param array $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by end user.
     * @param string $formName the form name to be used for loading the data into the models.
     * If not set, it will use the [[formName()]] value of the first model in `$models`.
     * This parameter is available since version 2.0.1.
     * @return boolean whether at least one of the models is successfully populated.
     */
    public static function loadMultiple($models, $data, $formName = null)
    {
        if ($formName === null) {
            /* @var $first Model */
            $first = reset($models);
            if ($first === false) {
                return false;
            }
            $formName = $first->formName();
        }

        $success = false;
        foreach ($models as $i => $model) {
            /* @var $model Model */
            if ($formName == '') {
                if (!empty($data[$i])) {
                    $model->load($data[$i], '');
                    $success = true;
                }
            } elseif (!empty($data[$formName][$i])) {
                $model->load($data[$formName][$i], '');
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Validates multiple models.
     * This method will validate every model. The models being validated may
     * be of the same or different types.
     * @param array $models the models to be validated
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return boolean whether all models are valid. False will be returned if one
     * or multiple models have validation error.
     */
    public static function validateMultiple($models, $attributeNames = null)
    {
        $valid = true;
        /* @var $model Model */
        foreach ($models as $model) {
            $valid = $model->validate($attributeNames) && $valid;
        }

        return $valid;
    }

    /**
     * Returns the list of fields that should be returned by default by [[toArray()]] when no specific fields are specified.
     *
     * A field is a named element in the returned array by [[toArray()]].
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function ($model) {
     *         return $model->first_name . ' ' . $model->last_name;
     *     },
     * ];
     * ```
     *
     * In this method, you may also want to return different lists of fields based on some context
     * information. For example, depending on [[scenario]] or the privilege of the current application user,
     * you may return different sets of visible fields or filter out some fields.
     *
     * The default implementation of this method returns [[attributes()]] indexed by the same attribute names.
     *
     * @return array the list of field names or field definitions.
     * @see toArray()
     */
    public function fields()
    {
        $fields = $this->attributes();

        return array_combine($fields, $fields);
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        $attributes = $this->getAttributes();
        return new ArrayIterator($attributes);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `isset($model[$offset])`.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->$offset !== null;
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($model[$offset])`.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}
