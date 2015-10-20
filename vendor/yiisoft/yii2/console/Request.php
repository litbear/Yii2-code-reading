<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\console;

/**
 * The console Request represents the environment information for a console application.
 * 命令行的Request类
 *
 * It is a wrapper for the PHP `$_SERVER` variable which holds information about the
 * currently running PHP script and the command line arguments given to it.
 * 这是对PHP中记录了当前脚本文件信息及命令行参数的$_SERVER 全局变量的封装，
 *
 * @property array $params The command line arguments. It does not include the entry script name.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Request extends \yii\base\Request
{
    // 命令行参数
    private $_params;


    /**
     * Returns the command line arguments.
     * 获得命令行参数
     * @return array the command line arguments. It does not include the entry script name.
     */
    public function getParams()
    {
        if (!isset($this->_params)) {
            if (isset($_SERVER['argv'])) {
                $this->_params = $_SERVER['argv'];
                /**
                 * 将数组开头第一个元素移除 $_SERVER['argv']中 第一个
                 * 元素为脚本文件名 如 php cmd.php aaa bbb ccc
                 * 这条命令产生的$_SERVER['argv'] 为 ['cmd.php' , 'aaa' , 'bbb' , 'ccc']
                 */
                array_shift($this->_params);
            } else {
                $this->_params = [];
            }
        }

        return $this->_params;
    }

    /**
     * Sets the command line arguments.
     * @param array $params the command line arguments
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * Resolves the current request into a route and the associated parameters.
     * 实现父类抽象函数 将参数解析为路由 和 参数
     * @return array the first element is the route, and the second is the associated parameters.
     */
    public function resolve()
    {
        /**
         * 首个元素作为路由
         */
        $rawParams = $this->getParams();
        if (isset($rawParams[0])) {
            $route = $rawParams[0];
            array_shift($rawParams);
        } else {
            $route = '';
        }

        $params = [];
        /**
         * 参数要求 以-- 开头 形如 --foo --bar=xxx
         */
        foreach ($rawParams as $param) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                // 还要排除yii\console\Application::OPTION_APPCONFIG 即 'appconfig'
                if ($name !== Application::OPTION_APPCONFIG) {
                    // 【 对于仅有参数名，没有参数值的，视参数值为 true】
                    $params[$name] = isset($matches[3]) ? $matches[3] : true;
                }
            } else {
                $params[] = $param;
            }
        }

        return [$route, $params];
    }
}
