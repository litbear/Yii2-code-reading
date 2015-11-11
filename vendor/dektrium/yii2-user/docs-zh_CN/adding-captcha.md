向表单中添加验证码
==========================

向表单中添加验证码分为以下三步：

1. 在模型中添加验证码域和验证规则
2. 在视图中显示验证码域
3. 在控制器中增加验证码动作

本教程会向你示范如何将验证码域添加到你的注册表单中，你可以按照以下方法将验证码添加到任何表单中。

1. 向模型中添加验证码域和验证规则
---------------------------------------------


首先，重写继承自\dektrium\user\models\RegistrationForm的注册表单，然后为本表单添加名为**captcha**的字段和验证规则


```php

    <?php

    namespace app\models;

    class RegistrationForm extends \dektrium\user\models\RegistrationForm
    {
        /**
         * @var string
         */
        public $captcha;
        /**
         * @inheritdoc
         */
        public function rules()
        {
            $rules = parent::rules();
            $rules[] = ['captcha', 'required'];
            $rules[] = ['captcha', 'captcha'];
            return $rules;
        }
    }
    
```

2. 在视图中增加一个小部件
----------------------------

Before doing this step you have to configure view application component as described in guide. After this done you have
to create new file named `register.php` in `@app/views/user/registration`. Now you have to add widget to registration
form, just copy and paste following code into newly created view file.
在进行这一步之前，请确保你已经按照向导中描述配置了视图应用组件。之后，在`@app/views/user/registration`中创建一个名为`register.php`的文件，现在你已经将小部件添加到注册表单中了，只需将下列代码复制粘贴到新建的视图文件中即可。

```php

    <?php

    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use yii\captcha\Captcha;

    /**
     * @var yii\web\View $this
     * @var yii\widgets\ActiveForm $form
     * @var app\models\RegistrationForm $model
     */
    $this->title = Yii::t('user', 'Sign up');
    $this->params['breadcrumbs'][] = $this->title;
    ?>
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= Html::encode($this->title) ?></h3>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'registration-form',
                    ]); ?>

                    <?= $form->field($model, 'username') ?>

                    <?= $form->field($model, 'email') ?>

                    <?= $form->field($model, 'password')->passwordInput() ?>

                    <?= $form->field($model, 'captcha')->widget(Captcha::className(), [
                        'captchaAction' => ['/site/captcha']
                    ]) ?>

                    <?= Html::submitButton(Yii::t('user', 'Sign up'), ['class' => 'btn btn-success btn-block']) ?>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            <p class="text-center">
                <?= Html::a(Yii::t('user', 'Already registered? Sign in!'), ['/user/security/login']) ?>
            </p>
        </div>
    </div>
    
```


3. 向控制器中添加动作
----------------------------------

为了使验证码正常工作，你需要向`app\controllers\SiteController`中添加验证码动作。可能在Yii2应用模板中已经自动添加了。

```php

    <?php
    
    namespace app\controllers;

    class SiteController extends \yii\web\Controller
    {
        ...
        public function actions()
        {
            return [
                'captcha' => [
                    'class' => 'yii\captcha\CaptchaAction',
                ],
            ];
        }
        ...
    }
    
```