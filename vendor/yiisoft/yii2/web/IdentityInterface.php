<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * IdentityInterface is the interface that should be implemented by a class providing identity information.
 * IdentityInterface 是应该被提供身份信息的类实现的接口。
 *
 * This interface can typically be implemented by a user model class. For example, the following
 * code shows how to implement this interface by a User ActiveRecord class:
 * 这个接口通常被用户模型类实现。例如，下列代码演示了用户AR类如何实现本接口：
 *
 * ~~~
 * class User extends ActiveRecord implements IdentityInterface
 * {
 *     public static function findIdentity($id)
 *     {
 *         return static::findOne($id);
 *     }
 *
 *     public static function findIdentityByAccessToken($token, $type = null)
 *     {
 *         return static::findOne(['access_token' => $token]);
 *     }
 *
 *     public function getId()
 *     {
 *         return $this->id;
 *     }
 *
 *     public function getAuthKey()
 *     {
 *         return $this->authKey;
 *     }
 *
 *     public function validateAuthKey($authKey)
 *     {
 *         return $this->authKey === $authKey;
 *     }
 * }
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface IdentityInterface
{
    /**
     * Finds an identity by the given ID.
     * 根据指定id查找并生成用户身份对象。
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id);

    /**
     * Finds an identity by the given token.
     * 根据指定令牌查找并生成用户身份对象
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * 令牌的格式。本参数的值依赖于实现。例如：[[\yii\filters\auth\HttpBearerAuth]]会将本参数设置为
     * yii\filters\auth\HttpBearerAuth
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null);

    /**
     * Returns an ID that can uniquely identify a user identity.
     * 返回一个可以唯一区分用户的id
     * @return string|integer an ID that uniquely identifies a user identity.
     */
    public function getId();

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     * 返回一个可以用来检查给定身份id有效性的权限密钥
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     * 该权限密钥必须是对每个用户唯一的，并且固定不变的以用于验证用户身份。
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     * 该权限密钥的长度必须足够大以用来抵御潜在的用户身份攻击。
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * 本方法需要开启User::enableAutoLogin属性
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey();

    /**
     * Validates the given auth key.
     * 验证一个给定的权限密钥
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * 此方法需要开启User::enableAutoLogin属性
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey);
}
