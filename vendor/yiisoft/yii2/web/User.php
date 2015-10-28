<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;

/**
 * User is the class for the "user" application component that manages the user authentication status.
 * User类是一个管理用户权限状态的应用组件。
 *
 * You may use [[isGuest]] to determine whether the current user is a guest or not.
 * If the user is a guest, the [[identity]] property would return null. Otherwise, it would
 * be an instance of [[IdentityInterface]].
 * 可能你使用过Yii::$app->user->isGuest 来判断当前的用户是否是访客。假如用户是访客，那么
 * identity属性将会返回null。否则该属性将会返回一个IdentityInterface接口的实例
 *
 * You may call various methods to change the user authentication status:
 * 你可以通过一系列的方法去改变用户的权限状态，
 *
 * - [[login()]]: sets the specified identity and remembers the authentication status in session and cookie.
 * - [[login()]]: 为用户设置指定的身份并将权限状态保存在session或cookie里。
 * - [[logout()]]: marks the user as a guest and clears the relevant information from session and cookie.
 * - [[logout()]]: 将用户标记为访客，并且从session和cookie中清除掉相关信息。
 * - [[setIdentity()]]: changes the user identity without touching session or cookie.
 *   This is best used in stateless RESTful API implementation.
 * - [[setIdentity()]]: 不通过session和cookie而改变用户的身份，此方法最好被用在无状态的restful api的实现上。
 *
 * Note that User only maintains the user authentication status. It does NOT handle how to authenticate
 * a user. The logic of how to authenticate a user should be done in the class implementing [[IdentityInterface]].
 * You are also required to set [[identityClass]] with the name of this class.
 * 注意，User类只维护用户的权限状态。并不负责验证权限，验证用户权限的逻辑应该在实现了[[IdentityInterface]]
 * 接口的类中完成。你也需要设置identityClass通过本类的名字？？（没理解）
 * 
 *
 * User is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->user`.
 * User组件被当作是[[\yii\web\Application]]中默认的应用组件，你可以通过`Yii::$app->user`访问
 * 本类的对象。
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 * 你可以通过在配置项的components 元素中增加一个数组达到更改配置的目的，如下所示：
 *
 * ~~~
 * 'user' => [
 *     'identityClass' => 'app\models\User', // User must implement the IdentityInterface
 *     'enableAutoLogin' => true,
 *     // 'loginUrl' => ['user/login'],
 *     // ...
 * ]
 * ~~~
 *
 * @property string|integer $id The unique identifier for the user. If null, it means the user is a guest.
 * This property is read-only.
 * $id 用户的唯一标识。假如为null，则说明是访客，只读属性。
 * @property IdentityInterface|null $identity The identity object associated with the currently logged-in
 * user. `null` is returned if the user is not logged in (not authenticated).
 * $identity 一个IdentityInterface实例或者为null，一个与当前登录用户有关的身份对象，如果为null则表示用户未登录。
 * @property boolean $isGuest Whether the current user is a guest. This property is read-only.
 * $isGuest 表示当前用户是否为访客，只读属性。
 * @property string $returnUrl The URL that the user should be redirected to after login. Note that the type
 * of this property differs in getter and setter. See [[getReturnUrl()]] and [[setReturnUrl()]] for details.
 * $returnUrl 字符串，表示用户在登陆后需要跳转的地址。注意，这里的属性和相应的getter，setter方法不同，详情参阅
 * [[getReturnUrl()]] 和 [[setReturnUrl()]]
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class User extends Component
{
    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    const EVENT_AFTER_LOGIN = 'afterLogin';
    const EVENT_BEFORE_LOGOUT = 'beforeLogout';
    const EVENT_AFTER_LOGOUT = 'afterLogout';

    /**
     * @var string the class name of the [[identity]] object.
     * [[identity]]对象的全限定类名。
     */
    public $identityClass;
    /**
     * @var boolean whether to enable cookie-based login. Defaults to false.
     * Note that this property will be ignored if [[enableSession]] is false.
     * 布尔值，决定是否开启基于cookie的用户验证，默认为不开启。注意，假如
     * enableSession属性设置为false，那么该属性会被忽略。
     */
    public $enableAutoLogin = false;
    /**
     * @var boolean whether to use session to persist authentication status across multiple requests.
     * You set this property to be false if your application is stateless, which is often the case
     * for RESTful APIs.
     * 布尔值，决定是否开启session保存用户的验证信息。假如你的应用是无状态的，可设置为false，通常像RESTful 接口。
     */
    public $enableSession = true;
    /**
     * @var string|array the URL for login when [[loginRequired()]] is called.
     * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
     * The first element of the array should be the route to the login action, and the rest of
     * the name-value pairs are GET parameters used to construct the login URL. For example,
     *  字符串或数组，[[loginRequired()]] 被调用时的URL（其实就是登录页的URL），假如给的是数组，则会调用
     * [[UrlManager::createUrl()]] 创建出相应的URL。数组的第一个元素应为登录动作的路由，其余的键值对用来
     * 构造登录地址的get参数，例如：
     *
     * ~~~
     * ['site/login', 'ref' => 1]
     * ~~~
     *
     * If this property is null, a 403 HTTP exception will be raised when [[loginRequired()]] is called.
     * 假如该属性为null，那么调用[[loginRequired()]]的时候会触发一个状态码为403的http错误。
     */
    public $loginUrl = ['site/login'];
    /**
     * @var array the configuration of the identity cookie. This property is used only when [[enableAutoLogin]] is true.
     * 数组，身份认证信息cookie的配置，该属性仅当[[enableAutoLogin]] 为true时使用。
     * @see Cookie
     */
    public $identityCookie = ['name' => '_identity', 'httpOnly' => true];
    /**
     * @var integer the number of seconds in which the user will be logged out automatically if he
     * remains inactive. If this property is not set, the user will be logged out after
     * the current session expires (c.f. [[Session::timeout]]).
     * Note that this will not work if [[enableAutoLogin]] is true.
     * 权限超时，int类型，用户登陆后长期无操作后权限失效的时间秒数。假如本属性未被设置，则使用session的失效时间
     * 注意，当enableAutoLogin为true时，本属性无效。
     */
    public $authTimeout;
    /**
     * @var integer the number of seconds in which the user will be logged out automatically
     * regardless of activity.
     * Note that this will not work if [[enableAutoLogin]] is true.
     * 绝对权限超时，用户登录后失效的秒数，有操作也会失效。
     * 注意，当enableAutoLogin为true时，本属性无效。
     */
    public $absoluteAuthTimeout;
    /**
     * @var boolean whether to automatically renew the identity cookie each time a page is requested.
     * This property is effective only when [[enableAutoLogin]] is true.
     * When this is false, the identity cookie will expire after the specified duration since the user
     * is initially logged in. When this is true, the identity cookie will expire after the specified duration
     * since the user visits the site the last time.
     * @see enableAutoLogin
     * 布尔值，是否每次请求都要自动更新身份认证的cookie。本属性只有在当enableAutoLogin为true时生效。
     * 当本属性值为false时，cookie中的身份验证信息会在用户第一次访问后的指定时间内失效
     * 当本属性值为true时，cookie中的身份验证信息会在用户最后一次访问后的指定时间内失效。
     */
    public $autoRenewCookie = true;
    /**
     * @var string the session variable name used to store the value of [[id]].
     * 用来储存id的session变量
     */
    public $idParam = '__id';
    /**
     * @var string the session variable name used to store the value of expiration timestamp of the authenticated state.
     * This is used when [[authTimeout]] is set.
     * 用来存放身份认证信息失效时间戳的session变量名，在authTimeout被设置后使用。
     */
    public $authTimeoutParam = '__expire';
    /**
     * @var string the session variable name used to store the value of absolute expiration timestamp of the authenticated state.
     * This is used when [[absoluteAuthTimeout]] is set.
     * 用来存放身份认证信息失效的绝对时间戳的session变量名，在absoluteAuthTimeout被设置后使用
     */
    public $absoluteAuthTimeoutParam = '__absoluteExpire';
    /**
     * @var string the session variable name used to store the value of [[returnUrl]].
     * 字符串，用来储存[[returnUrl]]值的session变量名。
     */
    public $returnUrlParam = '__returnUrl';

    private $_access = [];


    /**
     * Initializes the application component.
     * 初始化应用组件
     */
    public function init()
    {
        parent::init();

        // 父类构造方法结束后必须配置好identityClass属性，否则抛异常
        if ($this->identityClass === null) {
            throw new InvalidConfigException('User::identityClass must be set.');
        }
        if ($this->enableAutoLogin && !isset($this->identityCookie['name'])) {
            throw new InvalidConfigException('User::identityCookie must contain the "name" element.');
        }
    }

    private $_identity = false;

    /**
     * Returns the identity object associated with the currently logged-in user.
     * When [[enableSession]] is true, this method may attempt to read the user's authentication data
     * stored in session and reconstruct the corresponding identity object, if it has not done so before.
     * 返回一个与当前登录用户有关的身份对象实例。当enableSession为true的时候，本方法将会尝试读取储存在session
     * 中的用户认证信息，并重建相应的身份对象实例，假如enableSession为false的时候，就不会这么做
     * @param boolean $autoRenew whether to automatically renew authentication status if it has not been done so before.
     * This is only useful when [[enableSession]] is true.
     * 是否会自动更新权限状态，假如之前还没有这么做过，仅当enableSession为true时有效。
     * @return IdentityInterface|null the identity object associated with the currently logged-in user.
     * `null` is returned if the user is not logged in (not authenticated).
     * @see login()
     * @see logout()
     */
    public function getIdentity($autoRenew = true)
    {
        if ($this->_identity === false) {
            if ($this->enableSession && $autoRenew) {
                $this->_identity = null;
                $this->renewAuthStatus();
            } else {
                return null;
            }
        }

        return $this->_identity;
    }

    /**
     * Sets the user identity object.
     * 设置用户身份对象
     *
     * Note that this method does not deal with session or cookie. You should usually use [[switchIdentity()]]
     * to change the identity of the current user.
     * 注意这个方法并不处理session和cookies。你通常会用到switchIdentity()方法干煸当前用户的身份。
     *
     * @param IdentityInterface|null $identity the identity object associated with the currently logged user.
     * If null, it means the current user will be a guest without any associated identity.
     * 与当前登录用户有关的身份对象，假如为null，则意味着当前用户是一个不具备身份信息的访客。
     * @throws InvalidValueException if `$identity` object does not implement [[IdentityInterface]].
     */
    public function setIdentity($identity)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
            $this->_access = [];
        } elseif ($identity === null) {
            $this->_identity = null;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
        }
    }

    /**
     * Logs in a user.
     * 用户登录
     *
     * After logging in a user, you may obtain the user's identity information from the [[identity]] property.
     * If [[enableSession]] is true, you may even get the identity information in the next requests without
     * calling this method again.
     * 在用户登录之后，你可能需要从identity属性中获取用户身份信息。假如enableSession属性为真，你甚至可以在下次
     * 请求时获取登录信息而无需重新调用本方法。
     *
     * The login status is maintained according to the `$duration` parameter:
     * 登录状态被$duration参数维护：
     *
     * - `$duration == 0`: the identity information will be stored in session and will be available
     *   via [[identity]] as long as the session remains active.
     * - $duration == 0 ： 身份验证信息会被储存到session中，通过identity属性在session的生命周期中保持可用。
     * - `$duration > 0`: the identity information will be stored in session. If [[enableAutoLogin]] is true,
     *   it will also be stored in a cookie which will expire in `$duration` seconds. As long as
     *   the cookie remains valid or the session is active, you may obtain the user identity information
     *   via [[identity]].
     * - $duration > 0 ： 身份认证信息会被储存到session中，假如enableAutoLogin属性为true，那么身份认证信息
     * 同样会储存在cookie中，期限为$duration秒。只要cookie或者session有效，就可以通过identity获取用户身份认证信息。
     *
     * Note that if [[enableSession]] is false, the `$duration` parameter will be ignored as it is meaningless
     * in this case.
     * 注意，假如enableSession 属性为false，那么 $duration 参数会被忽略。
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * Defaults to 0, meaning login till the user closes the browser or the session is manually destroyed.
     * If greater than 0 and [[enableAutoLogin]] is true, cookie-based login will be supported.
     * Note that if [[enableSession]] is false, this parameter will be ignored.
     * @return boolean whether the user is logged in
     */
    public function login(IdentityInterface $identity, $duration = 0)
    {
        if ($this->beforeLogin($identity, false, $duration)) {
            $this->switchIdentity($identity, $duration);
            //到这里就是登录成功了，些日志
            $id = $identity->getId();
            $ip = Yii::$app->getRequest()->getUserIP();
            if ($this->enableSession) {
                $log = "User '$id' logged in from $ip with duration $duration.";
            } else {
                $log = "User '$id' logged in from $ip. Session not enabled.";
            }
            Yii::info($log, __METHOD__);
            $this->afterLogin($identity, false, $duration);
        }

        return !$this->getIsGuest();
    }

    /**
     * Logs in a user by the given access token.
     * This method will first authenticate the user by calling [[IdentityInterface::findIdentityByAccessToken()]]
     * with the provided access token. If successful, it will call [[login()]] to log in the authenticated user.
     * If authentication fails or [[login()]] is unsuccessful, it will return null.
     * @param string $token the access token
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface|null the identity associated with the given access token. Null is returned if
     * the access token is invalid or [[login()]] is unsuccessful.
     */
    public function loginByAccessToken($token, $type = null)
    {
        /* @var $class IdentityInterface */
        $class = $this->identityClass;
        $identity = $class::findIdentityByAccessToken($token, $type);
        if ($identity && $this->login($identity)) {
            return $identity;
        } else {
            return null;
        }
    }

    /**
     * Logs in a user by cookie.
     *
     * This method attempts to log in a user using the ID and authKey information
     * provided by the [[identityCookie|identity cookie]].
     */
    protected function loginByCookie()
    {
        $value = Yii::$app->getRequest()->getCookies()->getValue($this->identityCookie['name']);
        if ($value === null) {
            return;
        }

        $data = json_decode($value, true);
        if (count($data) !== 3 || !isset($data[0], $data[1], $data[2])) {
            return;
        }

        list ($id, $authKey, $duration) = $data;
        /* @var $class IdentityInterface */
        $class = $this->identityClass;
        $identity = $class::findIdentity($id);
        if ($identity === null) {
            return;
        } elseif (!$identity instanceof IdentityInterface) {
            throw new InvalidValueException("$class::findIdentity() must return an object implementing IdentityInterface.");
        }

        if ($identity->validateAuthKey($authKey)) {
            if ($this->beforeLogin($identity, true, $duration)) {
                $this->switchIdentity($identity, $this->autoRenewCookie ? $duration : 0);
                $ip = Yii::$app->getRequest()->getUserIP();
                Yii::info("User '$id' logged in from $ip via cookie.", __METHOD__);
                $this->afterLogin($identity, true, $duration);
            }
        } else {
            Yii::warning("Invalid auth key attempted for user '$id': $authKey", __METHOD__);
        }
    }

    /**
     * Logs out the current user.
     * This will remove authentication-related session data.
     * If `$destroySession` is true, all session data will be removed.
     * @param boolean $destroySession whether to destroy the whole session. Defaults to true.
     * This parameter is ignored if [[enableSession]] is false.
     * @return boolean whether the user is logged out
     */
    public function logout($destroySession = true)
    {
        $identity = $this->getIdentity();
        if ($identity !== null && $this->beforeLogout($identity)) {
            $this->switchIdentity(null);
            $id = $identity->getId();
            $ip = Yii::$app->getRequest()->getUserIP();
            Yii::info("User '$id' logged out from $ip.", __METHOD__);
            if ($destroySession && $this->enableSession) {
                Yii::$app->getSession()->destroy();
            }
            $this->afterLogout($identity);
        }

        return $this->getIsGuest();
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return boolean whether the current user is a guest.
     * @see getIdentity()
     */
    public function getIsGuest()
    {
        return $this->getIdentity() === null;
    }

    /**
     * Returns a value that uniquely represents the user.
     * @return string|integer the unique identifier for the user. If null, it means the user is a guest.
     * @see getIdentity()
     */
    public function getId()
    {
        $identity = $this->getIdentity();

        return $identity !== null ? $identity->getId() : null;
    }

    /**
     * Returns the URL that the browser should be redirected to after successful login.
     *
     * This method reads the return URL from the session. It is usually used by the login action which
     * may call this method to redirect the browser to where it goes after successful authentication.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
     * Please refer to [[setReturnUrl()]] on accepted format of the URL.
     * @return string the URL that the user should be redirected to after login.
     * @see loginRequired()
     */
    public function getReturnUrl($defaultUrl = null)
    {
        $url = Yii::$app->getSession()->get($this->returnUrlParam, $defaultUrl);
        if (is_array($url)) {
            if (isset($url[0])) {
                return Yii::$app->getUrlManager()->createUrl($url);
            } else {
                $url = null;
            }
        }

        return $url === null ? Yii::$app->getHomeUrl() : $url;
    }

    /**
     * Remembers the URL in the session so that it can be retrieved back later by [[getReturnUrl()]].
     * @param string|array $url the URL that the user should be redirected to after login.
     * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
     * The first element of the array should be the route, and the rest of
     * the name-value pairs are GET parameters used to construct the URL. For example,
     *
     * ~~~
     * ['admin/index', 'ref' => 1]
     * ~~~
     */
    public function setReturnUrl($url)
    {
        Yii::$app->getSession()->set($this->returnUrlParam, $url);
    }

    /**
     * Redirects the user browser to the login page.
     *
     * Before the redirection, the current URL (if it's not an AJAX url) will be kept as [[returnUrl]] so that
     * the user browser may be redirected back to the current page after successful login.
     *
     * Make sure you set [[loginUrl]] so that the user browser can be redirected to the specified login URL after
     * calling this method.
     *
     * Note that when [[loginUrl]] is set, calling this method will NOT terminate the application execution.
     *
     * @param boolean $checkAjax whether to check if the request is an AJAX request. When this is true and the request
     * is an AJAX request, the current URL (for AJAX request) will NOT be set as the return URL.
     * @return Response the redirection response if [[loginUrl]] is set
     * @throws ForbiddenHttpException the "Access Denied" HTTP exception if [[loginUrl]] is not set
     */
    public function loginRequired($checkAjax = true)
    {
        $request = Yii::$app->getRequest();
        if ($this->enableSession && (!$checkAjax || !$request->getIsAjax())) {
            $this->setReturnUrl($request->getUrl());
        }
        if ($this->loginUrl !== null) {
            $loginUrl = (array) $this->loginUrl;
            if ($loginUrl[0] !== Yii::$app->requestedRoute) {
                return Yii::$app->getResponse()->redirect($this->loginUrl);
            }
        }
        throw new ForbiddenHttpException(Yii::t('yii', 'Login Required'));
    }

    /**
     * This method is called before logging in a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * 此方法默认在用户登录前被调用，默认会实现触发EVENT_BEFORE_LOGIN事件的操作
     * 假如你重写了此方法，请确保调用父类实现，以保证事件的触发。
     * 【只是预留一个触发事件的触发器，类内并没有什么事件被绑定】
     * @param IdentityInterface $identity the user identity information
     * 用户的身份信息
     * @param boolean $cookieBased whether the login is cookie-based
     * 是否基于cookie验证
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     * 用户能够保持登录状态的时长。假如为0，那就意味着用户关闭浏览器或session自动销毁
     * @return boolean whether the user should continue to be logged in
     */
    protected function beforeLogin($identity, $cookieBased, $duration)
    {
        $event = new UserEvent([
            'identity' => $identity,
            'cookieBased' => $cookieBased,
            'duration' => $duration,
        ]);
        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);

        return $event->isValid;
    }

    /**
     * This method is called after the user is successfully logged in.
     * The default implementation will trigger the [[EVENT_AFTER_LOGIN]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * 登录后事件触发器，其余同前。
     * @param IdentityInterface $identity the user identity information
     * @param boolean $cookieBased whether the login is cookie-based
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        $this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent([
            'identity' => $identity,
            'cookieBased' => $cookieBased,
            'duration' => $duration,
        ]));
    }

    /**
     * This method is invoked when calling [[logout()]] to log out a user.
     * The default implementation will trigger the [[EVENT_BEFORE_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * 登出前事件触发器，其余同前。
     * @param IdentityInterface $identity the user identity information
     * @return boolean whether the user should continue to be logged out
     */
    protected function beforeLogout($identity)
    {
        $event = new UserEvent([
            'identity' => $identity,
        ]);
        $this->trigger(self::EVENT_BEFORE_LOGOUT, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after a user is logged out via [[logout()]].
     * The default implementation will trigger the [[EVENT_AFTER_LOGOUT]] event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * 登出后事件触发器，其余同前。
     * @param IdentityInterface $identity the user identity information
     */
    protected function afterLogout($identity)
    {
        $this->trigger(self::EVENT_AFTER_LOGOUT, new UserEvent([
            'identity' => $identity,
        ]));
    }

    /**
     * Renews the identity cookie.
     * This method will set the expiration time of the identity cookie to be the current time
     * plus the originally specified cookie duration.
     */
    protected function renewIdentityCookie()
    {
        $name = $this->identityCookie['name'];
        $value = Yii::$app->getRequest()->getCookies()->getValue($name);
        if ($value !== null) {
            $data = json_decode($value, true);
            if (is_array($data) && isset($data[2])) {
                $cookie = new Cookie($this->identityCookie);
                $cookie->value = $value;
                $cookie->expire = time() + (int) $data[2];
                Yii::$app->getResponse()->getCookies()->add($cookie);
            }
        }
    }

    /**
     * Sends an identity cookie.
     * This method is used when [[enableAutoLogin]] is true.
     * It saves [[id]], [[IdentityInterface::getAuthKey()|auth key]], and the duration of cookie-based login
     * information in the cookie.
     * @param IdentityInterface $identity
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * @see loginByCookie()
     */
    protected function sendIdentityCookie($identity, $duration)
    {
        $cookie = new Cookie($this->identityCookie);
        $cookie->value = json_encode([
            $identity->getId(),
            $identity->getAuthKey(),
            $duration,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $cookie->expire = time() + $duration;
        Yii::$app->getResponse()->getCookies()->add($cookie);
    }

    /**
     * Switches to a new identity for the current user.
     * 为当前用户切换到一个新的身份。
     *
     * When [[enableSession]] is true, this method may use session and/or cookie to store the user identity information,
     * according to the value of `$duration`. Please refer to [[login()]] for more details.
     * 当enableSession 为真时，本方法会使用session 且/或 cookies 来储存用户身份认证信息，通过$duration的值。
     * 请参考login()获取更多信息。
     *
     * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
     * when the current user needs to be associated with the corresponding identity information.
     * 本方法主要被 [[login()]], [[logout()]] 和 [[loginByCookie()]]三个方法调用，在当前用户需要与
     * 相应的身份认证信息相关联时。
     *
     * @param IdentityInterface|null $identity the identity information to be associated with the current user.
     * If null, it means switching the current user to be a guest.
     * 与当前登录用户有关的身份对象，假如为null，则将当前的on过户切换为访客。
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * This parameter is used only when `$identity` is not null.
     * 用户可以保持已登录状态的秒数。仅当$identity非空时有效
     */
    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);

        if (!$this->enableSession) {
            return;
        }

        $session = Yii::$app->getSession();
        if (!YII_ENV_TEST) {
            $session->regenerateID(true);
        }
        // 移除session中的id和失效时间戳
        $session->remove($this->idParam);
        $session->remove($this->authTimeoutParam);

        if ($identity) {
            $session->set($this->idParam, $identity->getId());
            if ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
            if ($this->absoluteAuthTimeout !== null) {
                $session->set($this->absoluteAuthTimeoutParam, time() + $this->absoluteAuthTimeout);
            }
            if ($duration > 0 && $this->enableAutoLogin) {
                $this->sendIdentityCookie($identity, $duration);
            }
        } elseif ($this->enableAutoLogin) {
            Yii::$app->getResponse()->getCookies()->remove(new Cookie($this->identityCookie));
        }
    }

    /**
     * Updates the authentication status using the information from session and cookie.
     *
     * This method will try to determine the user identity using the [[idParam]] session variable.
     *
     * If [[authTimeout]] is set, this method will refresh the timer.
     *
     * If the user identity cannot be determined by session, this method will try to [[loginByCookie()|login by cookie]]
     * if [[enableAutoLogin]] is true.
     */
    protected function renewAuthStatus()
    {
        $session = Yii::$app->getSession();
        // 判断当前用户是否有session，或者session已经激活，并尝试取出用户id
        $id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

        // session中的用户id是否为null进行相应处理
        if ($id === null) {
            $identity = null;
        } else {
            /* @var $class IdentityInterface */
            $class = $this->identityClass;
            /*
             *  根据用户id从数据源取出相应的用户对象
             * 【注意格式是IdentityInterface对象】
             */
            $identity = $class::findIdentity($id);
        }

        // 为本对象设置用户信息对象
        $this->setIdentity($identity);

        // 假如取回了用户信息，且 权限超时和绝对权限超时中有一个不为空
        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            // 最后一次操作超时时间戳或null
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            // 绝对超时时间戳或null
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            // 如果超出了最后一次操作的超时时间或绝对超时时间，则登出（不销毁session）
            if ($expire !== null && $expire < time() || $expireAbsolute !== null && $expireAbsolute < time()) {
                $this->logout(false);
            } elseif ($this->authTimeout !== null) {
                // 否则重新设置最后一次操作的超时时间
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
        }

        /*
         * 假如开启了cookie验证，首先判断是否是访客
         * 如果是访客，尝试cookie登录。否则判断是否
         * 自动更新cookie，是则更新cookie
         */
        if ($this->enableAutoLogin) {
            if ($this->getIsGuest()) {
                $this->loginByCookie();
            } elseif ($this->autoRenewCookie) {
                $this->renewIdentityCookie();
            }
        }
    }

    /**
     * Checks if the user can perform the operation as specified by the given permission.
     *
     * Note that you must configure "authManager" application component in order to use this method.
     * Otherwise an exception will be thrown.
     *
     * @param string $permissionName the name of the permission (e.g. "edit post") that needs access check.
     * @param array $params name-value pairs that would be passed to the rules associated
     * with the roles and permissions assigned to the user. A param with name 'user' is added to
     * this array, which holds the value of [[id]].
     * @param boolean $allowCaching whether to allow caching the result of access check.
     * When this parameter is true (default), if the access check of an operation was performed
     * before, its result will be directly returned when calling this method to check the same
     * operation. If this parameter is false, this method will always call
     * [[\yii\rbac\ManagerInterface::checkAccess()]] to obtain the up-to-date access result. Note that this
     * caching is effective only within the same request and only works when `$params = []`.
     * @return boolean whether the user can perform the operation as specified by the given permission.
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        if ($allowCaching && empty($params) && isset($this->_access[$permissionName])) {
            return $this->_access[$permissionName];
        }
        $access = $this->getAuthManager()->checkAccess($this->getId(), $permissionName, $params);
        if ($allowCaching && empty($params)) {
            $this->_access[$permissionName] = $access;
        }

        return $access;
    }

    /**
     * Returns auth manager associated with the user component.
     *
     * By default this is the `authManager` application component.
     * You may override this method to return a different auth manager instance if needed.
     * @return \yii\rbac\ManagerInterface
     * @since 2.0.6
     */
    protected function getAuthManager()
    {
        return Yii::$app->getAuthManager();
    }
}
