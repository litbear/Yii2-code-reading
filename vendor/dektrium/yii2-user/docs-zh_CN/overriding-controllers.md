重写控制器
======================

默认的Yii2-user控制器提供了一系列的功能足以满足一般的使用。但是你可能会需要扩展一些功能与逻辑以适应你的需求。

Step 1: 创建新的控制器
-----------------------------

首先，在你自己的命名空间下创建新的控制器（推荐使用 `app\controllers\user`），并从你想要使用的类继承，

例如，假如你想重写`AdminController`类，你需要创建`app\controllers\user\AdminController` 并继承自
it from `dektrium\user\controllers\AdminController`：

```php
namespace app\controllers\user;

use dektrium\user\controllers\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    public function actionCreate()
    {
        // do your magic
    }
}
```

Step 2: 将你的控制器加入到控制器映射表中
---------------------------------------------

 为了让Yii2-user模块知道你的控制器，你需要将如下代码添加到模块的控制器映射表中。

```php
...
'modules' => [
    ...
    'user' => [
        'class' => 'dektrium\user\Module',
        'controllerMap' => [
            'admin' => 'app\controllers\user\AdminController'
        ],
        ...
    ],
    ...
],
...
```
