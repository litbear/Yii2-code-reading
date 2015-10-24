#以基本版的web应用为例
##入口脚本的主要工作
yii2-app-basic/web/index.php
    这是项目的入口文件，实例化了`yii\web\Application())`对象，这似乎是唯一一个用new实例化的对象？引入了`vendor`下的`yii.php`文件，该文件定义了全局的`Yii`类，`Yii`类又继承自`BaseYii`类。
```php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');
(new yii\web\Application($config))->run();
```

##yii\web\Application类的父类
```
yii\web\Application 
-> yii\base\Application //应用基类
-> yii\base\Module //模块基类
-> yii\di\ServiceLocator //服务定位器
-> yii\base\Component //组件，获得事件行为特性
-> yii\base\Object //Object类 获得getter(),setrer()特性
```

##Application类的主要结构
在yii\base\Application类的构造器中有以下代码片段：
```php
    public function __construct($config = [])
    {
        Yii::$app = $this;
        ...
    }
```
因此，在全局的Yii类中，几个静态属性的初始值就已经确定了：
```php
Yii = {
    public static $classMap = require('path/to/classes.php');
    public static $container = new yii\di\Container();
    public static $app = new yii\web\Application($config);
    public static $aliases = ['@yii' => __DIR__];
}
```