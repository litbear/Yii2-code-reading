<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;
use app\models\Person;

class SiteController extends Controller {

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex() {
        return $this->render('index');
    }

    public function actionLogin() {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
                    'model' => $model,
        ]);
    }

    public function actionLogout() {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact() {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
                    'model' => $model,
        ]);
    }

    public function actionAbout() {
        return $this->render('about');
    }

    /**
     * 第一个方法
     */
    public function actionSay($message = 'hello') {
//        echo 'xsxs';die;
        return $this->render('say', ['message' => $message]);
    }

    public function actionEntry() {
        $model = new EntryForm;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // 验证 $model 收到的数据
            // 做些有意义的事 ...

            return $this->render('entry-confirm', ['model' => $model]);
        } else {
            // 无论是初始化显示还是数据验证错误
            return $this->render('entry', ['model' => $model]);
        }
    }

    public function actionTest() {
//        var_dump(\Yii::$app->log);
//        die;
//        $model = new \app\models\Country;
// 显示为 "Name"
//        echo $model->getAttributeLabel('name');die;
//        var_dump('=========');
//        Yii::setAlias('@xxx','C:/zzz/xxx');
//        Yii::setAlias('@xxx/aaa','C:/zzz/yyy');
//        Yii::setAlias('@xxx',null);
//        echo Yii::getAlias('@yii/aaa/bbb');
//        var_dump(Yii::getAlias('@xxx/aaa/bbb'));
//        die;     
        $container = new \yii\di\Container;
//        $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
//        $container->set('foo', 'yii\db\Connection');
//        $container->set('yii\db\Connection', [
//            'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//            'username' => 'root',
//            'password' => '',
//            'charset' => 'utf8',
//        ]);
//        $container->set('db', [
//            'class' => 'yii\db\Connection',
//            'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//            'username' => 'root',
//            'password' => '',
//            'charset' => 'utf8',
//        ]);
//        $container->set('db', function ($container, $params, $config) {
//            return new \yii\db\Connection($config);
//        });
//        $container->set('pageCache', new FileCache);
        var_dump(\yii\di\Instance::of(null));
    }

    public function actionEvent() {

        $person = new Person;

// 使用PHP全局函数作为handler来进行绑定
//        $person->on(Person::EVENT_GREET, 'person_say_hello');

// 使用对象$obj的成员函数say_hello来进行绑定
        $person->on(Person::EVENT_GREET, [$person,'say_hello'],'hello');

// 使用类Greet的静态成员函数say_hello进行绑定
//        $person->on(Person::EVENT_GREET, ['app\helper\Greet', 'say_hello']);

// 使用匿名函数
//        $person->on(Person::EVENT_GREET, function ($event) {
//            echo 'Hello';
//        });
        $person->trigger(Person::EVENT_GREET);
    }

    public function actionFooBar($foo) {
        var_dump($foo);
        die;
        echo 'actionID';
        die;
    }

    public function actionExt(){
        var_dump(Yii::$app->extensions);
    }
    
    public function actionComponents(){
        var_dump(Yii::$app->getComponents(true));die;
    }
}
