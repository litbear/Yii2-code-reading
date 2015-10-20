<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Object;
use yii\base\InvalidConfigException;

/**
 * UrlRule represents a rule used by [[UrlManager]] for parsing and generating URLs.
 *
 * To define your own URL parsing and creation logic you can extend from this class
 * and add it to [[UrlManager::rules]] like this:
 *
 * ~~~
 * 'rules' => [
 *     ['class' => 'MyUrlRule', 'pattern' => '...', 'route' => 'site/index', ...],
 *     // ...
 * ]
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UrlRule extends Object implements UrlRuleInterface
{
    /**
     * Set [[mode]] with this value to mark that this rule is for URL parsing only
     * 表示该类处于解析URL模式
     */
    const PARSING_ONLY = 1;
    /**
     * Set [[mode]] with this value to mark that this rule is for URL creation only
     * 表示该类处于生成URL模式
     * 【其他非1，非2的值都视为既解析又生成】
     */
    const CREATION_ONLY = 2;

    /**
     * @var string the name of this rule. If not set, it will use [[pattern]] as the name.
     * 路由规则的名称 未设置的话 会用pattern代替
     */
    public $name;
    /**
     * @var string the pattern used to parse and create the path info part of a URL.
     * 用于解析请求或生成URL的模式，通常是正则表达式
     * @see host
     */
    public $pattern;
    /**
     * @var string the pattern used to parse and create the host info part of a URL (e.g. `http://example.com`).
     * 【用于解析或创建URL时，处理主机信息的部分，如 http://www.digpage.com】
     * @see pattern
     */
    public $host;
    /**
     * @var string the route to the controller action
     * 【指向controller 和 action 的路由】
     */
    public $route;
    /**
     * @var array the default GET parameters (name => value) that this rule provides.
     * 【以一组键值对数组指定若干GET参数，在当前规则用于解析请求时，这些GET参数会被注入到 $_GET 中去】
     * When this rule is used to parse the incoming request, the values declared in this property
     * will be injected into $_GET.
     */
    public $defaults = [];
    /**
     * @var string the URL suffix used for this rule.
     * 伪静态页的后缀 如果没设置，就使用[[UrlManager::suffix]] 
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page.
     * If not, the value of [[UrlManager::suffix]] will be used.
     */
    public $suffix;
    /**
     * @var string|array the HTTP verb (e.g. GET, POST, DELETE) that this rule should match.
     * Use array to represent multiple verbs that this rule may match.
     * If this property is not set, the rule can match any verb.
     * Note that this property is only used when parsing a request. It is ignored for URL creation.
     * 【指定当前规则适用的HTTP方法，如 GET, POST, DELETE 等。
     * 可以使用数组表示同时适用于多个方法。
     * 如果未设定，表明当前规则适用于所有方法。
     * 当然，这个属性仅在解析请求时有效，在生成URL时是无效的。】
     */
    public $verb;
    /**
     * @var integer a value indicating if this rule should be used for both request parsing and URL creation,
     * parsing only, or creation only.
     * If not set or 0, it means the rule is both request parsing and URL creation.
     * If it is [[PARSING_ONLY]], the rule is for request parsing only.
     * If it is [[CREATION_ONLY]], the rule is for URL creation only.
     * 【表明当前规则的工作模式，取值可以是 0, PARSING_ONLY, CREATION_ONLY。未设定时等同于0。】
     */
    public $mode;
    /**
     * @var boolean a value indicating if parameters should be url encoded.
     * 【表明URL中的参数是否需要进行url编码，默认是进行。】
     */
    public $encodeParams = true;

    /**
     * @var string the template for generating a new URL. This is derived from [[pattern]] and is used in generating URL.
     * 【用于生成新URL的模板】
     */
    private $_template;
    /**
     * @var string the regex for matching the route part. This is used in generating URL.
     * 【一个用于匹配路由部分的正则表达式，用于生成URL】
     * 
     */
    private $_routeRule;
    /**
     * @var array list of regex for matching parameters. This is used in generating URL.
     * 【用于保存一组匹配参数的正则表达式，用于生成URL】
     */
    private $_paramRules = [];
    /**
     * @var array list of parameters used in the route.
     * 【保存一组路由中使用的参数】
     */
    private $_routeParams = [];


    /**
     * Initializes this rule.
     */
    public function init()
    {
        // 必须要有URL pattern 否则无意义
        if ($this->pattern === null) {
            throw new InvalidConfigException('UrlRule::pattern must be set.');
        }
        // 必须要有对应的路由 否则无意义
        if ($this->route === null) {
            throw new InvalidConfigException('UrlRule::route must be set.');
        }
        /**
         * 【如果定义了一个或多个verb，说明规则仅适用于特定的HTTP方法。
         * 既然是HTTP方法，那就要全部大写。 verb的定义可以是字符串
         * （单一的verb）或数组（单一或多个verb）。】
         */
        if ($this->verb !== null) {
            if (is_array($this->verb)) {
                foreach ($this->verb as $i => $verb) {
                    $this->verb[$i] = strtoupper($verb);
                }
            } else {
                $this->verb = [strtoupper($this->verb)];
            }
        }
        /*
         * name为空，则使用pattern代替
         */
        if ($this->name === null) {
            $this->name = $this->pattern;
        }

        $this->pattern = trim($this->pattern, '/');
        $this->route = trim($this->route, '/');

        if ($this->host !== null) {
            $this->host = rtrim($this->host, '/');
            $this->pattern = rtrim($this->host . '/' . $this->pattern, '/');
        } elseif ($this->pattern === '') {
            $this->_template = '';
            $this->pattern = '#^$#u';

            return;
        } elseif (($pos = strpos($this->pattern, '://')) !== false) {
            if (($pos2 = strpos($this->pattern, '/', $pos + 3)) !== false) {
                $this->host = substr($this->pattern, 0, $pos2);
            } else {
                $this->host = $this->pattern;
            }
        } else {
            $this->pattern = '/' . $this->pattern . '/';
        }

        if (strpos($this->route, '<') !== false && preg_match_all('/<(\w+)>/', $this->route, $matches)) {
            foreach ($matches[1] as $name) {
                $this->_routeParams[$name] = "<$name>";
            }
        }

        $tr = [
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ];
        $tr2 = [];
        if (preg_match_all('/<(\w+):?([^>]+)?>/', $this->pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1][0];
                $pattern = isset($match[2][0]) ? $match[2][0] : '[^\/]+';
                if (array_key_exists($name, $this->defaults)) {
                    $length = strlen($match[0][0]);
                    $offset = $match[0][1];
                    if ($offset > 1 && $this->pattern[$offset - 1] === '/' && (!isset($this->pattern[$offset + $length]) || $this->pattern[$offset + $length] === '/')) {
                        $tr["/<$name>"] = "(/(?P<$name>$pattern))?";
                    } else {
                        $tr["<$name>"] = "(?P<$name>$pattern)?";
                    }
                } else {
                    $tr["<$name>"] = "(?P<$name>$pattern)";
                }
                if (isset($this->_routeParams[$name])) {
                    $tr2["<$name>"] = "(?P<$name>$pattern)";
                } else {
                    $this->_paramRules[$name] = $pattern === '[^\/]+' ? '' : "#^$pattern$#u";
                }
            }
        }

        $this->_template = preg_replace('/<(\w+):?([^>]+)?>/', '<$1>', $this->pattern);
        $this->pattern = '#^' . trim(strtr($this->_template, $tr), '/') . '$#u';

        if (!empty($this->_routeParams)) {
            $this->_routeRule = '#^' . strtr($this->route, $tr2) . '$#u';
        }
    }

    /**
     * Parses the given request and returns the corresponding route and parameters.
     * 解析URL
     * @param UrlManager $manager the URL manager
     * @param Request $request the request component
     * @return array|boolean the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        if ($this->mode === self::CREATION_ONLY) {
            return false;
        }

        if (!empty($this->verb) && !in_array($request->getMethod(), $this->verb, true)) {
            return false;
        }

        $pathInfo = $request->getPathInfo();
        $suffix = (string) ($this->suffix === null ? $manager->suffix : $this->suffix);
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    // suffix alone is not allowed
                    return false;
                }
            } else {
                return false;
            }
        }

        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }

        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }
        foreach ($this->defaults as $name => $value) {
            if (!isset($matches[$name]) || $matches[$name] === '') {
                $matches[$name] = $value;
            }
        }
        $params = $this->defaults;
        $tr = [];
        foreach ($matches as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                $tr[$this->_routeParams[$name]] = $value;
                unset($params[$name]);
            } elseif (isset($this->_paramRules[$name])) {
                $params[$name] = $value;
            }
        }
        if ($this->_routeRule !== null) {
            $route = strtr($this->route, $tr);
        } else {
            $route = $this->route;
        }

        Yii::trace("Request parsed with URL rule: {$this->name}", __METHOD__);

        return [$route, $params];
    }

    /**
     * Creates a URL according to the given route and parameters.
     * 生成URL
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     * @return string|boolean the created URL, or false if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {
        if ($this->mode === self::PARSING_ONLY) {
            return false;
        }

        $tr = [];

        // match the route part first
        if ($route !== $this->route) {
            if ($this->_routeRule !== null && preg_match($this->_routeRule, $route, $matches)) {
                foreach ($this->_routeParams as $name => $token) {
                    if (isset($this->defaults[$name]) && strcmp($this->defaults[$name], $matches[$name]) === 0) {
                        $tr[$token] = '';
                    } else {
                        $tr[$token] = $matches[$name];
                    }
                }
            } else {
                return false;
            }
        }

        // match default params
        // if a default param is not in the route pattern, its value must also be matched
        foreach ($this->defaults as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                continue;
            }
            if (!isset($params[$name])) {
                return false;
            } elseif (strcmp($params[$name], $value) === 0) { // strcmp will do string conversion automatically
                unset($params[$name]);
                if (isset($this->_paramRules[$name])) {
                    $tr["<$name>"] = '';
                }
            } elseif (!isset($this->_paramRules[$name])) {
                return false;
            }
        }

        // match params in the pattern
        foreach ($this->_paramRules as $name => $rule) {
            if (isset($params[$name]) && !is_array($params[$name]) && ($rule === '' || preg_match($rule, $params[$name]))) {
                $tr["<$name>"] = $this->encodeParams ? urlencode($params[$name]) : $params[$name];
                unset($params[$name]);
            } elseif (!isset($this->defaults[$name]) || isset($params[$name])) {
                return false;
            }
        }

        $url = trim(strtr($this->_template, $tr), '/');
        if ($this->host !== null) {
            $pos = strpos($url, '/', 8);
            if ($pos !== false) {
                $url = substr($url, 0, $pos) . preg_replace('#/+#', '/', substr($url, $pos));
            }
        } elseif (strpos($url, '//') !== false) {
            $url = preg_replace('#/+#', '/', $url);
        }

        if ($url !== '') {
            $url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
        }

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Returns list of regex for matching parameter.
     * @return array parameter keys and regexp rules.
     *
     * @since 2.0.6
     */
    protected function getParamRules()
    {
        return $this->_paramRules;
    }
}
