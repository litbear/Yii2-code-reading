<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\behaviors;

use Yii;
use Closure;
use yii\base\Behavior;
use yii\base\Event;

/**
 * AttributeBehavior automatically assigns a specified value to one or multiple attributes of an ActiveRecord
 * object when certain events happen.
 * 当特定的事件被触发的时候，AttributeBehavior类为一个或多个ActiveRecord对象的属性自动分配属性值。
 *
 * To use AttributeBehavior, configure the [[attributes]] property which should specify the list of attributes
 * that need to be updated and the corresponding events that should trigger the update. Then configure the
 * [[value]] property with a PHP callable whose return value will be used to assign to the current attribute(s).
 * For example,
 * 要是用AttributeBehavior类，需要先根据 待更新的属性指定属性列表和触发更新数据库的事件 配置[[attributes]]属性，
 * 然后为[[value]]属性配置PHP回调函数，回调函数的返回值被分配给当前属性。
 *
 * ~~~
 * use yii\behaviors\AttributeBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => AttributeBehavior::className(),
 *             'attributes' => [
 *                 ActiveRecord::EVENT_BEFORE_INSERT => 'attribute1',
 *                 ActiveRecord::EVENT_BEFORE_UPDATE => 'attribute2',
 *             ],
 *             'value' => function ($event) {
 *                 return 'some value';
 *             },
 *         ],
 *     ];
 * }
 * ~~~
 *
 * @author Luciano Baraglia <luciano.baraglia@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AttributeBehavior extends Behavior
{
    /**
     * @var array list of attributes that are to be automatically filled with the value specified via [[value]].
     * The array keys are the ActiveRecord events upon which the attributes are to be updated,
     * and the array values are the corresponding attribute(s) to be updated. You can use a string to represent
     * a single attribute, or an array to represent a list of attributes. For example,
     * 一个将被[[value]]自动填充的属性数组。
     * 数组的键是根据待更新的属性决定的ActiveRecord事件。数组的值是待更新的属性名。可以用字符串表示一个属性，
     * 用数组表示多个属性。
     *
     * ```php
     * [
     *     ActiveRecord::EVENT_BEFORE_INSERT => ['attribute1', 'attribute2'],
     *     ActiveRecord::EVENT_BEFORE_UPDATE => 'attribute2',
     * ]
     * ```
     */
    public $attributes = [];
    /**
     * @var mixed the value that will be assigned to the current attributes. This can be an anonymous function
     * or an arbitrary value. If the former, the return value of the function will be assigned to the attributes.
     * The signature of the function should be as follows,
     * 将要分配给当前属性的值。可以使你敏函数或者任意的值。假如是前者，将会分配给属性函数的返回值。匿名函数的签名
     * 如下：
     *
     * ```php
     * function ($event)
     * {
     *     // return value will be assigned to the attribute
     * }
     * ```
     */
    public $value;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_fill_keys(array_keys($this->attributes), 'evaluateAttributes');
    }

    /**
     * Evaluates the attribute value and assigns it to the current attributes.
     * 为属性求值，并分配给当前属性。
     * @param Event $event
     */
    public function evaluateAttributes($event)
    {
        if (!empty($this->attributes[$event->name])) {
            $attributes = (array) $this->attributes[$event->name];
            $value = $this->getValue($event);
            foreach ($attributes as $attribute) {
                // ignore attribute names which are not string (e.g. when set by TimestampBehavior::updatedAtAttribute)
                if (is_string($attribute)) {
                    $this->owner->$attribute = $value;
                }
            }
        }
    }

    /**
     * Returns the value of the current attributes.
     * This method is called by [[evaluateAttributes()]]. Its return value will be assigned
     * to the attributes corresponding to the triggering event.
     * 返回当前属性的值，本方法由[[evaluateAttributes()]]调用，返回值会分配给相应的触发事件。
     * @param Event $event the event that triggers the current attribute updating.
     * @return mixed the attribute value
     */
    protected function getValue($event)
    {
        return $this->value instanceof Closure ? call_user_func($this->value, $event) : $this->value;
    }
}
