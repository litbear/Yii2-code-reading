重写视图
================

当你想使用Yii2-user时，可能会发现你需要重写由此模块提供的默认是图。虽然视图名是不可配置的，但Yii2还是提供了一种使用主题的方法覆盖视图。你需要像如下代码这样配置视图组件。

```php
...
'components' => [
    'view' => [
        'theme' => [
            'pathMap' => [
                '@dektrium/user/views' => '@app/views/user'
            ],
        ],
    ],
],
...
```

上面的`pathMap`表示，所有`@dektrium/user/views`下的模板将首先去 `@app/views/user` 目录下搜索，并且后者目录累的模板忏悔替代前者的。

例如：
-------

下面演示的是一个重写注册页面的例子。首先，确保你已经正确配置了应用组件。

为了覆盖注册视图的文件，应该创建 `@app/views/user/registration/register.php`.文件，打开该文件并粘贴下列代码：

```php
<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View              $this
 * @var yii\widgets\ActiveForm    $form
 * @var dektrium\user\models\User $user
 */

$this->title = Yii::t('user', 'Sign up');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="alert alert-success">
    <p>This view file has been overriden!</p>
</div>
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

然后打开注册页面确认你看到了这句话**'This view file has been overrided!'**。假如你没看到，请确保你已经正确的配置了视图组件，且在正确的目录下创建了视图文件。
