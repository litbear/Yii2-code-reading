<?php

namespace app\controllers;

use yii\di\Container;
use yii\web\Controller;

/**
 * 依赖注入的测试控制器
 */
class DiController extends Controller {

    public function actionTest() {
//        var_dump(\yii\di\Instance::of(null));die;
        // 创建一个DI容器
        $container = new Container;

// 为Connection指定一个数组作为依赖，当需要Connection的实例时，
// 使用这个数组进行创建
        $container->set('yii\db\Connection', [
            'dsn' => 'sqlite:C:\foobar\testdrive.db',
            'charset' => 'utf8',
            'tablePrefix' => 'tbl_',
        ]);

// 在需要使用接口 UserFinderInterface 时，采用UserFinder类实现
        $container->set('app\models\UserFinderInterface', [
            'class' => 'app\models\UserFinder',
        ]);

// 为UserLister定义一个别名
        $container->set('userLister', 'app\models\UserLister');

// 获取这个UserList的实例
        $lister = $container->get('userLister');
        var_dump('succ');
        die;
    }

}
