<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * ActiveRecordInterface
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
interface ActiveRecordInterface
{
    /**
     * Returns the primary key **name(s)** for this AR class.
     * 返回本AR类主键的字段名
     *
     * Note that an array should be returned even when the record only has a single primary key.
     * 注意，即使主键只有一个，返回的也是数组。
     *
     * For the primary key **value** see [[getPrimaryKey()]] instead.
     * 如何获取主键的值，请残月[[getPrimaryKey()]] 方法。
     *
     * @return string[] the primary key name(s) for this AR class.
     */
    public static function primaryKey();

    /**
     * Returns the list of all attribute names of the record.
     * 返回关联数据库表所有字段的列名
     * @return array list of attribute names.
     * 数组
     */
    public function attributes();

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * 返回命名的属性值？？？
     * 假如本条记录是查询的结果集，并且属性未被载入，则会返回null
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name);

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name.
     * @param mixed $value the attribute value.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value);

    /**
     * Returns a value indicating whether the record has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the record has an attribute with the specified name.
     */
    public function hasAttribute($name);

    /**
     * Returns the primary key value(s).
     * 返回主键的值
     * @param boolean $asArray whether to return the primary key value as an array. If true,
     * the return value will be an array with attribute names as keys and attribute values as values.
     * Note that for composite primary keys, an array will always be returned regardless of this parameter value.
     * 是否返回主键键值对组成的数组。注意，对联合主键来说，无论本参数是什么都会返回数组。
     * @return mixed the primary key value. An array (attribute name => attribute value) is returned if the primary key
     * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
     * the key value is null).
     */
    public function getPrimaryKey($asArray = false);

    /**
     * Returns the old primary key value(s).
     * This refers to the primary key value that is populated into the record
     * after executing a find method (e.g. find(), findOne()).
     * The value remains unchanged even if the primary key attribute is manually assigned with a different value.
     * 返回旧的主键值。这指的是在执行完查找方法后 插入到记录中 的主键的值 。
     * @param boolean $asArray whether to return the primary key value as an array. If true,
     * the return value will be an array with column name as key and column value as value.
     * If this is false (default), a scalar value will be returned for non-composite primary key.
     * 是否返回主键键值对组成的数组。注意，对联合主键来说，无论本参数是什么都会返回数组。
     * @property mixed The old primary key value. An array (column name => column value) is
     * returned if the primary key is composite. A string is returned otherwise (null will be
     * returned if the key value is null).
     * @return mixed the old primary key value. An array (column name => column value) is returned if the primary key
     * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
     * the key value is null).
     */
    public function getOldPrimaryKey($asArray = false);

    /**
     * Returns a value indicating whether the given set of attributes represents the primary key for this model
     * 判断给定的数组是否代表了本模型的主键。
     * @param array $keys the set of attributes to check
     * @return boolean whether the given set of attributes represents the primary key for this model
     */
    public static function isPrimaryKey($keys);

    /**
     * Creates an [[ActiveQueryInterface]] instance for query purpose.
     * 为查询数据库创建一个[[ActiveQueryInterface]] 实例
     *
     * The returned [[ActiveQueryInterface]] instance can be further customized by calling
     * methods defined in [[ActiveQueryInterface]] before `one()` or `all()` is called to return
     * populated ActiveRecord instances. For example,
     * 返回的[[ActiveQueryInterface]] 实例可以在调用`QueryInterface::one()` 或 `QueryInterface::all()` 方法之前进一步的通过调用
     * 自身内部的方法自定义，例如：
     *
     * ```php
     * // find the customer whose ID is 1
     * $customer = Customer::find()->where(['id' => 1])->one();
     *
     * // find all active customers and order them by their age:
     * $customers = Customer::find()
     *     ->where(['status' => 1])
     *     ->orderBy('age')
     *     ->all();
     * ```
     *
     * This method is also called by [[BaseActiveRecord::hasOne()]] and [[BaseActiveRecord::hasMany()]] to
     * create a relational query.
     * 本方法同样被[[BaseActiveRecord::hasOne()]] 两个方法调用 [[BaseActiveRecord::hasMany()]]用来创建关系查询。
     *
     * You may override this method to return a customized query. For example,
     * 你可以重写本方法返回一个自定义的查询对象。例如：
     *
     * ```php
     * class Customer extends ActiveRecord
     * {
     *     public static function find()
     *     {
     *         // use CustomerQuery instead of the default ActiveQuery
     *         return new CustomerQuery(get_called_class());
     *     }
     * }
     * ```
     *
     * The following code shows how to apply a default condition for all queries:
     * 以下代码演示了如何为所有查询应用一个默认的条件：
     *
     * ```php
     * class Customer extends ActiveRecord
     * {
     *     public static function find()
     *     {
     *         return parent::find()->where(['deleted' => false]);
     *     }
     * }
     *
     * // Use andWhere()/orWhere() to apply the default condition
     * // 使用 andWhere()/orWhere() 方法应用默认查询条件。如下：
     * // SELECT FROM customer WHERE `deleted`=:deleted AND age>30
     * $customers = Customer::find()->andWhere('age>30')->all();
     *
     * // Use where() to ignore the default condition
     * // 使用 where()方法忽略默认的查询条件。
     * // SELECT FROM customer WHERE age>30
     * $customers = Customer::find()->where('age>30')->all();
     *
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface]] instance.
     * 返回值是一个新创建的ActiveQueryInterface实例。
     */
    public static function find();

    /**
     * Returns a single active record model instance by a primary key or an array of column values.
     * 通过主键或一系列限制条件，返回一个活动记录模型的实例。
     *
     * The method accepts:
     * 本方法接受：
     *
     *  - a scalar value (integer or string): query by a single primary key value and return the
     *    corresponding record (or null if not found).
     *  - 一个标量值（int或字符串类型）：使用主键值查询并返回相应的结果，null表示没找到。
     *  - a non-associative array: query by a list of primary key values and return the
     *    first record (or null if not found).
     *  - 一个索引数组：查找每一个主键，返回第一个值，null表示没找到。
     *  - an associative array of name-value pairs: query by a set of attribute values and return a single record
     *    matching all of them (or null if not found). Note that `['id' => 1, 2]` is treated as a non-associative array.
     *  - 返回索引数组，键值对形式：使用一系列的属性值查询，返回所有结果集中的一条结果，null表示没找到。注意`['id' => 1, 2]`
     *    会被当作是索引数组。
     *
     * That this method will automatically call the `one()` method and return an [[ActiveRecordInterface|ActiveRecord]]
     * instance. For example,
     * 本方法会自动调用 `QueryInterface::one()` 方法并返回一个[[ActiveRecordInterface|ActiveRecord]]实例。例如：
     *
     * ```php
     * // find a single customer whose primary key value is 10
     * // 主键为10的一个用户
     * $customer = Customer::findOne(10);
     *
     * // the above code is equivalent to:
     * // 上一条等同于主键大于等于10的一个用户
     * $customer = Customer::find()->where(['id' => 10])->one();
     *
     * // find the first customer whose age is 30 and whose status is 1
     * $customer = Customer::findOne(['age' => 30, 'status' => 1]);
     *
     * // the above code is equivalent to:
     * $customer = Customer::find()->where(['age' => 30, 'status' => 1])->one();
     * ```
     *
     * @param mixed $condition primary key value or a set of column values
     * @return static|null ActiveRecord instance matching the condition, or null if nothing matches.
     * 返回一个符合条件的AR实例，null说明未发现匹配
     */
    public static function findOne($condition);

    /**
     * Returns a list of active record models that match the specified primary key value(s) or a set of column values.
     * 返回一个附和指定条件的AR模型（对象）构成的集合
     *
     * The method accepts:
     * 本方法接受以下几种条件：
     *
     *  - a scalar value (integer or string): query by a single primary key value and return an array containing the
     *    corresponding record (or an empty array if not found).
     *  - 标量值，字符串或数组
     *  - a non-associative array: query by a list of primary key values and return the
     *    corresponding records (or an empty array if none was found).
     *    Note that an empty condition will result in an empty result as it will be interpreted as a search for
     *    primary keys and not an empty `WHERE` condition.
     *  - 矢量值，索引数组。注意：空数组做参数会导致空结果。空数组被解读为按主键查找，而不是按空WHERE条件查找。
     *  - an associative array of name-value pairs: query by a set of attribute values and return an array of records
     *    matching all of them (or an empty array if none was found). Note that `['id' => 1, 2]` is treated as
     *    a non-associative array.
     *  - 矢量值，关联数组，键值对
     *
     * This method will automatically call the `all()` method and return an array of [[ActiveRecordInterface|ActiveRecord]]
     * instances. For example,
     * 本方法会自动调用`QueryInterface::all()`方法并返回一个 [[ActiveRecordInterface|ActiveRecord]] 实例的集合，例如：
     *
     * ```php
     * // find the customers whose primary key value is 10
     * $customers = Customer::findAll(10);
     * //等同于
     * 
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['id' => 10])->all();
     *
     * // find the customers whose primary key value is 10, 11 or 12.
     * $customers = Customer::findAll([10, 11, 12]);
     * //等同于
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['id' => [10, 11, 12]])->all();
     *
     * // find customers whose age is 30 and whose status is 1
     * $customers = Customer::findAll(['age' => 30, 'status' => 1]);
     * //等同于
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['age' => 30, 'status' => 1])->all();
     * ```
     *
     * @param mixed $condition primary key value or a set of column values
     * @return array an array of ActiveRecord instance, or an empty array if nothing matches.
     */
    public static function findAll($condition);

    /**
     * Updates records using the provided attribute values and conditions.
     * 使用给定的条件和属性值更新记录。
     * For example, to change the status to be 1 for all customers whose status is 2:
     * 例如，将所有status为2的用户的属性值修改为1
     *
     * ```php
     * Customer::updateAll(['status' => 1], ['status' => '2']);
     * ```
     *
     * @param array $attributes attribute values (name-value pairs) to be saved for the record.
     * Unlike [[update()]] these are not going to be validated.
     * 待更新的键值对，与[[update()]]方法不同，本方法不会进行合法性验证。
     * @param array $condition the condition that matches the records that should get updated.
     * Please refer to [[QueryInterface::where()]] on how to specify this parameter.
     * An empty condition will match all records.
     * 用于匹配待更新数据的条件。请参考[[QueryInterface::where()]]配置本参数。空数组会匹配所有记录。
     * @return integer the number of rows updated
     */
    public static function updateAll($attributes, $condition = null);

    /**
     * Deletes records using the provided conditions.
     * WARNING: If you do not specify any condition, this method will delete ALL rows in the table.
     * 使用给定的条件删除记录。注意：假如没给任何条件，本方法将会删除整张表。
     *
     * For example, to delete all customers whose status is 3:
     * 例如，删除所有status为3的用户：
     *
     * ```php
     * Customer::deleteAll([status = 3]);
     * ```
     *
     * @param array $condition the condition that matches the records that should get deleted.
     * Please refer to [[QueryInterface::where()]] on how to specify this parameter.
     * An empty condition will match all records.
     * 数组，删除条件。请参考[[QueryInterface::where()]] 来了解如何配置参数。
     * @return integer the number of rows deleted
     */
    public static function deleteAll($condition = null);

    /**
     * Saves the current record.
     * 保存当前记录
     *
     * This method will call [[insert()]] when [[getIsNewRecord()|isNewRecord]] is true, or [[update()]]
     * when [[getIsNewRecord()|isNewRecord]] is false.
     * 当[[getIsNewRecord()|isNewRecord]]为真时执行[[insert()]] 为假时执行[[update()]]
     *
     * For example, to save a customer record:
     * 例如：
     *
     * ```php
     * $customer = new Customer; // or $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->save();
     * ```
     *
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param array $attributeNames list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function save($runValidation = true, $attributeNames = null);

    /**
     * Inserts the record into the database using the attribute values of this record.
     * 将本记录插入到数据库中
     *
     * Usage example:
     *
     * ```php
     * $customer = new Customer;
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->insert();
     * ```
     *
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * 默认为执行验证
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the attributes are valid and the record is inserted successfully.
     * 返回值为布尔值
     */
    public function insert($runValidation = true, $attributes = null);

    /**
     * Saves the changes to this active record into the database.
     * 将本条记录所做的更改保存到数据库中
     *
     * Usage example:
     * 例子：
     *
     * ```php
     * $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->update();
     * ```
     *
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * 在保存记录前是否执行验证。默认为执行，假如验证失败，本条记录不会存入数据库，且本方法返回false
     * @param array $attributeNames list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * 需要保存到数据库的属性名（字段名），默认为空，表示所有从数据库中加载的属性都要被保存。
     * @return integer|boolean the number of rows affected, or false if validation fails
     * or updating process is stopped for other reasons.
     * 返回int或布尔值，影响的行数，或者false。
     * Note that it is possible that the number of rows affected is 0, even though the
     * update execution is successful.
     * 注意，影响的行数可能为0
     */
    public function update($runValidation = true, $attributeNames = null);

    /**
     * Deletes the record from the database.
     * 从数据库中删除记录
     *
     * @return integer|boolean the number of rows deleted, or false if the deletion is unsuccessful for some reason.
     * Note that it is possible that the number of rows deleted is 0, even though the deletion execution is successful.
     * int类型或者是布尔类型。int类型表示删除的行数，false表示由于某种原因未删除成功。注意，删除的行数也可能为0，0也表示
     * 成功（这就意味着要用严格等于号了呗）
     */
    public function delete();

    /**
     * Returns a value indicating whether the current record is new (not saved in the database).
     * 判断当前记录是不是新记录（没被保存到数据库过）
     * @return boolean whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord();

    /**
     * Returns a value indicating whether the given active record is the same as the current one.
     * Two [[getIsNewRecord()|new]] records are considered to be not equal.
     * 返回一个值用以表示给定的AR类与当前的AR类是否相同。两个[[getIsNewRecord()|new]]记录是相同的
     * @param static $record record to compare to
     * @return boolean whether the two active records refer to the same row in the same database table.
     */
    public function equals($record);

    /**
     * Returns the relation object with the specified name.
     * A relation is defined by a getter method which returns an object implementing the [[ActiveQueryInterface]]
     * (normally this would be a relational [[ActiveQuery]] object).
     * It can be declared in either the ActiveRecord class itself or one of its behaviors.
     * 返回指定关系名称的关联对象。关系由一个getter方法定义，该方法返回一个[[ActiveQueryInterface]]接口的实例。
     * （通常情况下是一个相关的[[ActiveQuery]]对象。）关系既可以被AR类本身定义，也可以被AR类的一个行为定义。
     * @param string $name the relation name
     * 关系的名称
     * @param boolean $throwException whether to throw exception if the relation does not exist.
     * @return ActiveQueryInterface the relational query object
     */
    public function getRelation($name, $throwException = true);

    /**
     * Establishes the relationship between two records.
     * 为两条记录建立关系
     *
     * The relationship is established by setting the foreign key value(s) in one record
     * to be the corresponding primary key value(s) in the other record.
     * The record with the foreign key will be saved into database without performing validation.
     * 记录之间的关系随着外键的确定而确定。本AR记录的外键是关联记录的主键，带有外键的记录在保存时
     * 不会执行合法性验证
     *
     * If the relationship involves a junction table, a new row will be inserted into the
     * junction table which contains the primary key values from both records.
     * 假如关系中包括一个连接表。那么一个包含双方记录主键的新行会被插入到连接表【多对多】
     *
     * This method requires that the primary key value is not null.
     * 本方法要求主键不能为空
     *
     * @param string $name the case sensitive name of the relationship.
     * @param static $model the record to be linked with the current one.
     * @param array $extraColumns additional column values to be saved into the junction table.
     * This parameter is only meaningful for a relationship involving a junction table
     * (i.e., a relation set with `[[ActiveQueryInterface::via()]]`.)
     */
    public function link($name, $model, $extraColumns = []);

    /**
     * Destroys the relationship between two records.
     * 销毁两条记录之间的关联
     *
     * The record with the foreign key of the relationship will be deleted if `$delete` is true.
     * Otherwise, the foreign key will be set null and the record will be saved without validation.
     * 假如 `$delete` 参数为真，那么本条记录与其他记录的外键会被删除。反之，则会被设为null，同时记录
     * 会被无验证地保存。
     *
     * @param string $name the case sensitive name of the relationship.
     * 区分大小写的（关系名）
     * @param static $model the model to be unlinked from the current one.
     * 要与本模型解除关系的模型名
     * @param boolean $delete whether to delete the model that contains the foreign key.
     * If false, the model's foreign key will be set null and saved.
     * If true, the model containing the foreign key will be deleted.
     */
    public function unlink($name, $model, $delete = false);

    /**
     * Returns the connection used by this AR class.
     * 为本AR类返回数据库连接
     * @return mixed the database connection used by this AR class.
     */
    public static function getDb();
}
