#认证
---------------------

认证，是验证用户身份的过程。通常会使用用户标识（用户名或者电子邮箱）和密钥来验证用户的合法性，鉴权是登陆功能的基础。
Yii提供了一个包含一系列组件的鉴权框架来支持登陆功能。要想使用这个框架，你需要做以下工作：

- 配置`user`用户组件
- 创建一个`yii\web\IdentityInterface`接口的实现类。

---------------------
##配置`yii\web\User`用户组件

`user`用户组件管理着用户身份验证状态。这需要你指定一个包含实际验证逻辑的身份类。在下面的应用配置中，身份类呗配置成了``app\models\User`，该类将在下一小节详细解释。
```php
return [
    'components' => [
        'user' => [
            'identityClass' => 'app\models\User',
        ],
    ],
];
```
---------------------
##实现`yii\web\IdentityInterface`接口

身份类必须实现`yii\web\IdentityInterface`接口，该接口包含以下方法：

- `findIdentity()`: 根据指定的用户ID查找相应的身份类实例。当你需要使用session来维持登录状态的时候会用到这个方法。
- `findIdentityByAccessToken()`: 
根据指定的权限令牌查找相应的身份类实例。该方法用于 通过单个加密令牌认证用户的时候（比如无状态的RESTful应用）。
- `getId()`: 返回代表本用户实例的用户ID。
- `getAuthKey()`: 返回一个用于Cookie验证的认证密钥。验证密钥储存在cookie中，将来跟服务器端的密钥进行比对，以确认用户登录cookie的合法性。
- `validateAuthKey()`: 用于实现验证cookie中密钥的逻辑。

用不到的方法，可以实现一个空函数。例如，假如你的应用是一个纯无状态RESTful应用，你只需要实现`findIdentityByAccessToken()`和`getId()`这两个方法。其余方法都可以留空。 
以下示例是一个继承了Active Record类，并与数据库中的`user`表相关联的`yii\web\IdentityInterface`实现。
```php
<?php

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return 'user';
    }

    /**
     * 根据指定用户ID查找用户实例
     *
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * 根据指定权限令牌查找用户实例。
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string 当前权限令牌
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}
?>
```

正如前面说过，假如你的应用是基于cookie登录的，你只需实现`getAuthKey()`和`validateAuthKey()`两个方法。因此，你可以使用下列代码生成和储存用户登录密钥 
```php
class User extends ActiveRecord implements IdentityInterface
{
    ......
    
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
            }
            return true;
        }
        return false;
    }
}
```

注意：不要将这里的用户身份类与`yii\web\User`类相混淆。前者是用来实现授权逻辑的类，经常继承自Active Record类与数据库中储存用户信息的表相关联。后者是用来管理用户认证状态的组件类。

---------------------
##使用用户组建`yii\web\User`类

在`user`应用组件方面，你主要用到`yii\web\User`。  
你可以使用`Yii::$app->user->identity`检测当前用户身份。如果是已登录用户，则会返回一个身份类的实例，反之则会返回`null`（也就是游客）。以下代码为您演示了如何取回其他的权限相关信息：
```php
// 当前用户的身份实例。未认证用户则为 Null 。
$identity = Yii::$app->user->identity;

// 当前用户的ID。 未认证用户则为 Null 。
$id = Yii::$app->user->id;

// 判断当前用户是否是游客（未认证的）
$isGuest = Yii::$app->user->isGuest;
```
你可以使用以下代码登陆用户：
```php
// 使用指定用户名获取用户身份实例。
// 请注意，如果需要的话您可能要检验密码
$identity = User::findOne(['username' => $username]);

// 登录用户
Yii::$app->user->login($identity);
```

`yii\web\User::login()`方法将当前用户的身份登记到`yii\web\User`组件中。假如开启了`session`，将会把用户身份保存在`session`中，是用户在`session`等生命周期内维持已登录。假如开启了基于`cookie`的认证（例如勾选了“记住我”），则用户身份同样会保存在`cookie`中，从而使用户在`cookie`有效期内保持登录状态。  
要想使用`cookie`登陆，你需要在应用配置文件中将`yii\web\User::enableAutoLogin`设为`true`。你还需要在`yii\web\User::login()`方法中 传递有效期（记住登录状态的时长）参数。  
要注销用户，只需调用：

```php
Yii::$app->user->logout();
```

--------------------------
##认证事件

`yii\web\User`类在登录和注销期间会触发以下几个事件：

- `EVENT_BEFORE_LOGIN`: 在`yii\web\User::login()`方法开始时触发。如果事件句柄将事件对象的`yii\web\UserEvent::isValid`属性设为`false`， ，那么登录流程将会被取消。
- `EVENT_AFTER_LOGIN`: 在成功登录后触发。
- `EVENT_BEFORE_LOGOUT`: 在`yii\web\User::logout()`方法开始时触发。如果事件句柄将事件对象的`yii\web\UserEvent::isValid`属性设为`false`， ，那么注销流程将会被取消。
- `EVENT_AFTER_LOGOUT`: 在成功注销之后触发。

你可以通过响应这些事件来实现一些类似登录统计、在线人数统计的功能。例如, 在登录后`yii\web\User::EVENT_AFTER_LOGIN`的处理程序，你可以将用户的登录时间和IP记录到`user`表中。