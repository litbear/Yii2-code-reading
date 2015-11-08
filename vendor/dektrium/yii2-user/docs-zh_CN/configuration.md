配置
=============

> 注意：本章节还在开发中。

可用的配置参数
-------------------------------

#### enableFlashMessages (Type: `boolean`, Default value: `true`)

If this option is set to `true`, module will show flash messages using integrated widget. Otherwise you will need to
handle it using your own widget, like provided in
[yii advanced template](https://github.com/yiisoft/yii2-app-advanced/blob/master/frontend/widgets/Alert.php). The keys
for those messages are `success`, `info`, `danger`, `warning`.

---

#### enableRegistration (Type: `boolean`, Default value: `true`)

如果设为`false`,则不能再注册新用户。注册页面会抛出HttpNotFoundException`异常，然而为了管理员能在管理员界面创建新用户，用户（邮件）验证还是会继续启用的。

---

#### enableGeneratingPassword (Type: `boolean`, Default value: `false`)

当本属性为`truw`时，注册页面的密码域会被隐藏，并且密码会由服务器自动生成。产生的八位密码会被发送到用户邮箱中。

---

#### enableConfirmation (Type: `boolean`, Default value: `true`)

假如本属性为`true`，模块会向用户填写的电子邮箱发送一封包含确认链接的邮件，用户只有点击确认链接，才能完成注册。

> 注意：要开启本功能，必须配置**mail**组件。

---

#### enableUnconfirmedLogin (Type: `boolean`, Default value: `false`)

假如本属性为`true`，用户即使没有点击邮件中的确认链接，也可以登录。

---

#### enablePasswordRecovery (Type: `boolean`, Default value: `true`)

假如本属性为`true`，用户就可以找回忘记的密码。

---

#### emailChangingStrategy (Type: `integer`, Default value: `\dektrium\user\Module::STRATEGY_DEFAULT`)

When user tries change his password, there are three ways how this change will happen:
当用户试图修改密码时，会有以下三种情境：

- `STRATEGY_DEFAULT` 默认情境，发送确认消息到新的用户邮箱，用户必须点击确认链接。
- `STRATEGY_INSECURE` 修改邮箱而不经过任何确认
- `STRATEGY_SECURE` 确认信息将会发到新旧两个邮箱内，必须全部点击。

---

#### confirmWithin (Type: `integer`, Default value: `86400` (24 hours))

用户注册确认信息的失效时间，超时后用户必须在指定的页面向服务器请求新的确认信息.

---

#### rememberFor (Type: `integer`, Default value: `1209600` (2 weeks))

记住永固登陆状态的秒数。

---

#### recoverWithin (Type: `integer`, Default value: `21600` (6 hours))

用户找回密码确认信息的失效时间，超时后用户必须在指定的页面向服务器请求新的确认信息.

---

#### admins (Type: `array`, Default value: `[]`)

Yii2-user为管理员提供了特殊的页面用来管理和添加用户。你需要指定管理员的用户名。

---

#### cost (Type: `integer`, Default value: `10`)

Cost parameter used by the Blowfish hash algorithm. The higher the value of cost, the longer it takes to generate the
hash and to verify a password against it. Higher `cost` therefore slows down a brute-force attack. For best protection
against brute for attacks, set it to the highest value that is tolerable on production servers. The time taken to
compute the hash doubles for every increment by one of `cost`.
Cost参数用于河豚加密算法。

---

#### urlPrefix (Type: `string`, Default value: `user`)

用户模块URL的前缀

#### urlPrefix (Type: `array`, Default value: `[]`)

用于URL管理的规则

配置文件示例：
---------------------

以下配置应在主配置文件中设置：


```php
...
'modules' => [
    ...
    'user' => [
        'class' => 'dektrium\user\Module',
        'enableUnconfirmedLogin' => true,
        'confirmWithin' => 21600,
        'cost' => 12,
        'admins' => ['admin']
    ],
    ...
],
...
```
