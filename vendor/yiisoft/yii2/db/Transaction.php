<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling [[Connection::beginTransaction()]].
 *
 * The following code is a typical example of using transactions (note that some
 * DBMS may not support transactions):
 *
 * ~~~
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch (Exception $e) {
 *     $transaction->rollBack();
 * }
 * ~~~
 *
 * @property boolean $isActive Whether this transaction is active. Only an active transaction can [[commit()]]
 * or [[rollBack()]]. This property is read-only.
 * @property string $isolationLevel The transaction isolation level to use for this transaction. This can be
 * one of [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]] but also a string
 * containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is
 * write-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Transaction extends \yii\base\Object
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     * 以下是事务的四种隔离级别
     */
    const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const READ_COMMITTED = 'READ COMMITTED';
    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const REPEATABLE_READ = 'REPEATABLE READ';
    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * @var Connection the database connection that this transaction is associated with.
     */
    public $db;

    /**
     * @var integer the nesting level of the transaction. 0 means the outermost level.
     */
    private $_level = 0;


    /**
     * Returns a value indicating whether this transaction is active.
     * 活跃事务三要素，等级大于0，绑定数据库链接，数据库链接活跃
     * 摘抄一段解释【_level > 0 。这是由于为0是，要么是刚刚初始化， 要么是所有的事务已经提交或回滚了。
     * 也就是说，只有调用过了 begin() 但还没有调用过匹配的 commit() 或 rollBack() 的事务对象，才是有效的。】
     * @return boolean whether this transaction is active. Only an active transaction
     * can [[commit()]] or [[rollBack()]].
     */
    public function getIsActive()
    {
        return $this->_level > 0 && $this->db && $this->db->isActive;
    }

    /**
     * Begins a transaction.
     * @param string|null $isolationLevel The [isolation level][] to use for this transaction.
     * This can be one of [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]] but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgreSQL, where setting the isolation level before the transaction
     * has no effect. You have to call [[setIsolationLevel()]] in this case after the transaction has started.
     *
     * > Note: Some DBMS allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature
     * you may need to set the isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     * @throws InvalidConfigException if [[db]] is `null`.
     */
    public function begin($isolationLevel = null)
    {
        /*
         * Connection中 beginTransaction($isolationLevel = null)方法的这句
         * $transaction = $this->_transaction = new Transaction(['db' => $this]);
         */
        if ($this->db === null) {
            throw new InvalidConfigException('Transaction::db must be set.');
        }
        $this->db->open();

        // 最外层的事务
        if ($this->_level == 0) {
            // 给定了个隔离级别就设定之
            if ($isolationLevel !== null) {
                $this->db->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }
            Yii::trace('Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''), __METHOD__);

            // 触发事务开始事件
            $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
            $this->db->pdo->beginTransaction();
            $this->_level = 1;

            return;
        }

        /*
         * 当 _level > 0 时，表示的是嵌套的事务，并非最外层的事务。
         * 对此，Yii使用 SQL 的 SAVEPOINT 和 ROLLBACK TO SAVEPOINT
         * 来实现设置事务保存点和回滚到保存点的操作。
         */
        /**
         * 摘抄：
         * 1，事务对象初始化时，设 _level 为0，表示如果要启用事务， 这是一个最外层的事务。
         * 
         * 2，每当调用 Transaction::begin() 来启用具体事务时， _level 自增1。 
         *    表示如再启用事务，将是层级为1的嵌套事务。
         * 
         * 3，每当调用 Transaction::commit() 或 Transaction::rollBack() 时，
         *    _level 自减1，表示当前层级的事务处理完毕，返回上一层级的事务中。
         * 
         * 4，当调用了一次 begin() 且还没有调用匹配的 commit() 或 rollBack() ，
         *    就再次调用 begin() 时，会使事务进行更深一层级的嵌套中。
         */
        // 事务级别大于0 表示是嵌套事务
        $schema = $this->db->getSchema();
        // 判断数据库是否支持嵌套事务
        if ($schema->supportsSavepoint()) {
            Yii::trace('Set savepoint ' . $this->_level, __METHOD__);
            // 创建事务保存点
            $schema->createSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not started: nested transaction not supported', __METHOD__);
        }
        // 一旦调用这个方法 事物级别$this->_level 就会自增1
        $this->_level++;
    }

    /**
     * Commits a transaction.
     * @throws Exception if the transaction is not active
     */
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        // 事务级别自减1
        $this->_level--;
        // 如果自减后等于0，说明是最外层事务，则实行commit操作
        if ($this->_level == 0) {
            Yii::trace('Commit transaction', __METHOD__);
            $this->db->pdo->commit();
            $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);
            return;
        }

        // 自减后大于0 那么是内层事务，执行释放保存点操作
        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::trace('Release savepoint ' . $this->_level, __METHOD__);
            $schema->releaseSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not committed: nested transaction not supported', __METHOD__);
        }
    }

    /**
     * Rolls back a transaction.
     * @throws Exception if the transaction is not active
     */
    public function rollBack()
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }

        // 回滚也会使事物级别自减1，如果自减后为0，说明是最外层事务，调用rollback操作
        $this->_level--;
        if ($this->_level == 0) {
            Yii::trace('Roll back transaction', __METHOD__);
            $this->db->pdo->rollBack();
            $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);
            return;
        }

        // 自减后大于1，说明是内层操作，执行回滚保存点操作
        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::trace('Roll back to savepoint ' . $this->_level, __METHOD__);
            $schema->rollBackSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not rolled back: nested transaction not supported', __METHOD__);
            // throw an exception to fail the outer transaction
            throw new Exception('Roll back failed: nested transaction not supported.');
        }
    }

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However this is not supported by all DBMS so you might rather specify the isolation level directly
     * when calling [[begin()]].
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]] but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * @throws Exception if the transaction is not active
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel($level)
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }
        Yii::trace('Setting transaction isolation level to ' . $level, __METHOD__);
        $this->db->getSchema()->setTransactionIsolationLevel($level);
    }
}
