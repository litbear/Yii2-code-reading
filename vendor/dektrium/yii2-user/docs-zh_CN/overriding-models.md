重写模型
=================

当你使用Yii2-user创建应用的时候，你会发现有时需要重写模型或表单。本章描述了如何使用Yii2-user重写表单。此外，你可以为模型绑定更多的行为或事件，因为[Dependency Injection container](https://github.com/yiisoft/yii2/blob/master/docs/guide/concept-di-container.md)。

假如你决定重写用户类并改变注册进程，让我们在`@app/models`目录下创建一个新类：

```php
namespace app\models;

use dektrium\user\models\User as BaseUser;

class User extends BaseUser
{
    public function register()
    {
        // do your magic
    }
}
```

为了让Yii2-user使用你定义的类，请按如下所述配置模块：

```php
...
'user' => [
    'class' => 'dektrium\user\Module',
    'modelMap' => [
        'User' => 'app\models\User',
    ],
],
...
```

绑定行为或事件句柄
--------------------------------------

Yii2-user允许你为任何模型绑定行为或事件句柄，为达到此目的可如此设置模型映射：

```php
[
    ...
    'user' => [
        'class' => 'dektrium\user\Module',
        'modelMap' => [
            'User' => [
                'class' => 'app\models\User',
                'on user_create_init' => function () {
                    // do you magic
                },
                'as foo' => [
                    'class' => 'Foo',
                ],
            ],
        ],
    ],
    ...
]
```

