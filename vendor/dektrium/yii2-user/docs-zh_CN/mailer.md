Mailer组件
======

Yii2-user引入了一个特殊的组件名叫Mailer，本组件用来在以下四种情境下发送邮件。

- 当`enableGeneratingPassword`属性为真时，用来发送包含由服务器生成密码的欢迎信息
- 当`enableConfirmation` 属性为真时，向新注册用户发送包含确认链接的邮件。
- 更改邮箱的确认信息
- 找回密码信息

配置
-------------

Mailer 可以像如下这样配置：

```php
...
'user' => [
    'class' => 'dektrium\user\Module',
    'mailer' => [
        'sender'                => 'no-reply@myhost.com', // or ['no-reply@myhost.com' => 'Sender name']
        'welcomeSubject'        => 'Welcome subject',
        'confirmationSubject'   => 'Confirmation subject',
        'reconfirmationSubject' => 'Email change subject',
        'recoverySubject'       => 'Recovery subject',
],
...
```