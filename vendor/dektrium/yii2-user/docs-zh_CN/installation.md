安装
============

本文档会指导你如何通过**composer**安装`Yii2-user`，安装按简单，只需需要三步：

第一步： 使用`composer`下载`Yii2-user`
-----------------------------------------

将 `"dektrium/yii2-user": "0.9.*@dev"`添加到项目**composer.json**文件的`require`元素中，并运行`composer update` 命令下载并安装Yii2-user.

第二步：配置应用
------------------------------------

> **注意：** 确保在配置文件中不要启用 `user`应用组件（译者注：不然会无法登录）

向配置文件中假如以下几行：

```php
'modules' => [
    'user' => [
        'class' => 'dektrium\user\Module',
    ],
],
```

第三步: 迁移数据库
------------------------------

> **注意：** 确保你已经正确配置了**db**用户组件（译者注：就是数据库链接）。

在下载和配置了`Yii2-user`组件之后，最后就是使用`migration`迁移数据库了：

```bash
$ php yii migrate/up --migrationPath=@vendor/dektrium/yii2-user/migrations
```

FAQ
---

**安装失败`vendor/dektrium/yii2-user`文件夹下没有文件**

*从`composer.json`文件中移除`Yii2-user`的版本约束，再次尝试运行`composer update`命令。在`composer`移除`Yii2-user`之后，重新添加版本号，再次安装。

**点击登录按钮无法登录，重定向到登录表单页面**

*从配置文件中移除`user`用户组件*
