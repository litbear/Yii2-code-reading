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
        var_dump('=========');
        Yii::setAlias('@xxx','C:/zzz/xxx');
        Yii::setAlias('@xxx/aaa','C:/zzz/yyy');
//        Yii::setAlias('@xxx',null);
//        echo Yii::getAlias('@yii/aaa/bbb');
        var_dump(Yii::getAlias('@xxx/aaa/bbb'));
        die;     
        
    }

    public function actionEvent() {
        echo '这是事件处理<br/>';

        $person = new Person();

        $this->on('SayHello', [$person, 'say_hello'], '你好，朋友');
        $this->on('SayHello', function() {
            echo '第二次触发' . '</br>';
        });

        $this->on('SayGoodBye', ['app\models\Person', 'say_goodbye'], '再见了，我的朋友');

        $this->on('GoodNight', function() {
            echo '晚安！';
        });


        $this->trigger('SayHello');
        $this->trigger('SayGoodBye');
        $this->trigger('GoodNight');
    }

    public function actionFooBar($foo) {
        var_dump($foo);
        die;
        echo 'actionID';
        die;
    }

}
