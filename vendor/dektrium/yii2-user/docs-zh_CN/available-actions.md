动作清单
=========================

Yii2-user包含了一系列的动作，可以通过URL去访问它们。下面是可用动作的路由与简短描述组成的表格。你可以通过使用`\yii\helpers\Url::to()`方法生成URL。

|Route                          |Description                                                            |
|-------------------------------|-----------------------------------------------------------------------|
|**/user/registration/register**| 显示注册表单                                                          |
|**/user/registration/resend**  | 显示重发表单                                                          |
|**/user/registration/confirm** | 确认用户 (需要 *id* 和 *token* 两个get参数)                           |
|**/user/security/login**       | 显示登录表单                                                          |
|**/user/security/logout**      | 注销用户 (只能通过POST方式访问)                                       |
|**/user/recovery/request**     | Displays recovery request form                                        |
|**/user/recovery/reset**       | Displays password reset form (requires *id* and *token* query params) |
|**/user/settings/profile**     | Displays profile settings form                                        |
|**/user/settings/account**     | Displays account settings form (email, username, password)            |
|**/user/settings/networks**    | Displays social network accounts settings page                        |
|**/user/profile/show**         | Displays user's profile (requires *id* query param)                   |
|**/user/admin/index**          | Displays user management interface                                    |
