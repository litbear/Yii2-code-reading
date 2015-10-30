<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Link;
use yii\web\Linkable;

/**
 * ArrayableTrait provides a common implementation of the [[Arrayable]] interface.
 * ArrayableTrait 为[[Arrayable]]接口提供了一个通用的实现。
 *
 * ArrayableTrait implements [[toArray()]] by respecting the field definitions as declared
 * in [[fields()]] and [[extraFields()]].
 * ArrayableTrait 通过像[[fields()]] 和 [[extraFields()]]方法中声明……在实现[[toArray()]]方法
 * 【这段没看懂，先读代码】
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
trait ArrayableTrait
{
    /**
     * Returns the list of fields that should be returned by default by [[toArray()]] when no specific fields are specified.
     * 返回一个字段列表
     *
     * A field is a named element in the returned array by [[toArray()]].
     * 字段是一个在[[toArray()]]方法返回的数组中命名的元素
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     * 本方法会返回一个 字段名 或 字段定义 组成的数组。假如是字段名，那么字段名将会被当作对象属性名，字段值会被当作属性值。
     * 如果是后者，那么
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     * 例如，以下代码定义了四个字段
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
     *     'fullName' => function () {
     *         return $this->first_name . ' ' . $this->last_name;
     *     },
     * ];
     * ```
     *
     * In this method, you may also want to return different lists of fields based on some context
     * information. For example, depending on the privilege of the current application user,
     * you may return different sets of visible fields or filter out some fields.
     *
     * The default implementation of this method returns the public object member variables indexed by themselves.
     *
     * @return array the list of field names or field definitions.
     * @see toArray()
     */
    public function fields()
    {
        $fields = array_keys(Yii::getObjectVars($this));
        return array_combine($fields, $fields);
    }

    /**
     * Returns the list of fields that can be expanded further and returned by [[toArray()]].
     *
     * This method is similar to [[fields()]] except that the list of fields returned
     * by this method are not returned by default by [[toArray()]]. Only when field names
     * to be expanded are explicitly specified when calling [[toArray()]], will their values
     * be exported.
     *
     * The default implementation returns an empty array.
     *
     * You may override this method to return a list of expandable fields based on some context information
     * (e.g. the current application user).
     *
     * @return array the list of expandable field names or field definitions. Please refer
     * to [[fields()]] on the format of the return value.
     * @see toArray()
     * @see fields()
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * Converts the model into an array.
     * 将模型转换为数组。
     *
     * This method will first identify which fields to be included in the resulting array by calling [[resolveFields()]].
     * It will then turn the model into an array with these fields. If `$recursive` is true,
     * any embedded objects will also be converted into arrays.
     * 本方法会首先通过调用[[resolveFields()]]方法确认那些字段会被包含在结果数组中。然后会使用这些字段将模型转换为数组。假如
     * `$recursive` 为真，那么任何潜入的对象同样都会被转换为数组【就是递归呗】
     *
     * If the model implements the [[Linkable]] interface, the resulting array will also have a `_link` element
     * which refers to a list of links as specified by the interface.
     * 假如模型实现了[[Linkable]] 接口，那么结果数组将会包含一个`_link`元素，该元素引用一个由接口指定的链接集合
     * 【不该翻译成链表吧】
     *
     * @param array $fields the fields being requested. If empty, all fields as specified by [[fields()]] will be returned.
     * @param array $expand the additional fields being requested for exporting. Only fields declared in [[extraFields()]]
     * will be considered.
     * @param boolean $recursive whether to recursively return array representation of embedded objects.
     * @return array the array representation of the object
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = [];
        foreach ($this->resolveFields($fields, $expand) as $field => $definition) {
            $data[$field] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $field);
        }

        if ($this instanceof Linkable) {
            $data['_links'] = Link::serialize($this->getLinks());
        }

        return $recursive ? ArrayHelper::toArray($data) : $data;
    }

    /**
     * Determines which fields can be returned by [[toArray()]].
     * This method will check the requested fields against those declared in [[fields()]] and [[extraFields()]]
     * to determine which fields can be returned.
     * 确定哪些元素可以被返回给[[toArray()]]
     * 本方法会检查那些定义在 [[fields()]] 和 [[extraFields()]]方法中的被请求的字段，来确定哪些字段可以被返回
     * @param array $fields the fields being requested for exporting
     * @param array $expand the additional fields being requested for exporting
     * @return array the list of fields to be exported. The array keys are the field names, and the array values
     * are the corresponding object property names or PHP callables returning the field values.
     */
    protected function resolveFields(array $fields, array $expand)
    {
        $result = [];

        foreach ($this->fields() as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (empty($fields) || in_array($field, $fields, true)) {
                $result[$field] = $definition;
            }
        }

        if (empty($expand)) {
            return $result;
        }

        foreach ($this->extraFields() as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (in_array($field, $expand, true)) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }
}
