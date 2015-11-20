<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * The web Response class represents an HTTP response
 * 响应HTTP请求的类
 *
 * It holds the [[headers]], [[cookies]] and [[content]] that is to be sent to the client.
 * It also controls the HTTP [[statusCode|status code]].
 * 本类控制了HTTP头，cookies和内容向客户端的输出，同样控制HTTP的状态码。
 *
 * Response is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->response`.
 * Response类可以被当作一个应用组件进行配置，并且使用`Yii::$app->response`进行访问
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 * 你可以通过向应用配置文件的`components`元素下加入以下内容以修改本类的配置：
 *
 * ~~~
 * 'response' => [
 *     'format' => yii\web\Response::FORMAT_JSON,
 *     'charset' => 'UTF-8',
 *     // ...
 * ]
 * ~~~
 *
 * @property CookieCollection $cookies The cookie collection. This property is read-only.
 * CookieCollection 类实例，cookie管理对象，只读属性。
 * @property string $downloadHeaders The attachment file name. This property is write-only.
 * 字符串，附件文件名，只写属性。
 * @property HeaderCollection $headers The header collection. This property is read-only.
 * HeaderCollection 类实例，HTTP头的管理对象，只读属性。
 * @property boolean $isClientError Whether this response indicates a client error. This property is
 * read-only.
 * 布尔值，判断响应是否指示一个客户端错误，只读属性。
 * @property boolean $isEmpty Whether this response is empty. This property is read-only.
 * 布尔值，判断响应是否为空，只读属性。
 * @property boolean $isForbidden Whether this response indicates the current request is forbidden. This
 * property is read-only.
 * 布尔值，判断响应是否禁止本次请求，只读属性。
 * @property boolean $isInformational Whether this response is informational. This property is read-only.
 * 布尔值，判断响应是否是信息性的，只读属性。
 * @property boolean $isInvalid Whether this response has a valid [[statusCode]]. This property is read-only.
 * 布尔值，通过[[statusCode]]判断响应是否是合法的，只读属性。
 * @property boolean $isNotFound Whether this response indicates the currently requested resource is not
 * found. This property is read-only.
 * 布尔值，判断响应是否指示当前请求的资源未找到，只读属性。
 * @property boolean $isOk Whether this response is OK. This property is read-only.
 * 布尔值，判断响应是否是OK的，只读属性。
 * @property boolean $isRedirection Whether this response is a redirection. This property is read-only.
 * 布尔值，判断响应是否是重定向的，只读属性。
 * @property boolean $isServerError Whether this response indicates a server error. This property is
 * read-only.
 * 布尔值，判断响应是否发生服务器错误，只读属性。
 * @property boolean $isSuccessful Whether this response is successful. This property is read-only.
 * 布尔值，判断响应是否是成功的，只读属性。
 * @property integer $statusCode The HTTP status code to send with the response.
 * 整型，HTTP状态码。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Response extends \yii\base\Response
{
    /**
     * @event ResponseEvent an event that is triggered at the beginning of [[send()]].
     * 发送前事件名称
     */
    const EVENT_BEFORE_SEND = 'beforeSend';
    /**
     * @event ResponseEvent an event that is triggered at the end of [[send()]].
     * 发送后事件名称
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * @event ResponseEvent an event that is triggered right after [[prepare()]] is called in [[send()]].
     * You may respond to this event to filter the response content before it is sent to the client.
     * 在[[prepare()]]方法执行后触发的事件名称，在[[send()]]中调用。可能需要响应本事件以在将响应内容发送到
     * 客户端前对其进行过滤。
     */
    const EVENT_AFTER_PREPARE = 'afterPrepare';
    const FORMAT_RAW = 'raw';
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';

    /**
     * @var string the response format. This determines how to convert [[data]] into [[content]]
     * when the latter is not set. The value of this property must be one of the keys declared in the [[formatters] array.
     * By default, the following formats are supported:
     * 字符串，响应的格式，本属性决定了在 [[content]]属性未设置时如何将[[data]]属性转换为[[content]]属性，
     * 本属性的值必须是[[formatters]数组中定义的键，默认支持以下几种格式。
     *
     * - [[FORMAT_RAW]]: the data will be treated as the response content without any conversion.
     *   No extra HTTP header will be added.
     * - [[FORMAT_RAW]]: data属性会被当作响应内容不加转换，不增加额外的HTTP头。
     * - [[FORMAT_HTML]]: the data will be treated as the response content without any conversion.
     *   The "Content-Type" header will set as "text/html".
     * - [[FORMAT_HTML]]: data属性会被当作响应内容不加转换，但"Content-Type"HTTP头会被设置为"text/html"
     * - [[FORMAT_JSON]]: the data will be converted into JSON format, and the "Content-Type"
     *   header will be set as "application/json".
     * - [[FORMAT_JSON]]: data属性会被转换为JSON格式，并且"Content-Type"HTTP头会被设置为"application/json"
     * - [[FORMAT_JSONP]]: the data will be converted into JSONP format, and the "Content-Type"
     *   header will be set as "text/javascript". Note that in this case `$data` must be an array
     *   with "data" and "callback" elements. The former refers to the actual data to be sent,
     *   while the latter refers to the name of the JavaScript callback.
     * - [[FORMAT_JSONP]]: data属性会被转换为JSONP格式，并且"Content-Type"HTTP头会被设置为"text/javascript"
     *   注意，如果选择了这种格式，`$data`变量中必须含有"data"元素和"callback" 元素，前者是被发送的数据，后者
     *   是JavaScript的回调函数名。
     * - [[FORMAT_XML]]: the data will be converted into XML format. Please refer to [[XmlResponseFormatter]]
     *   for more details.
     * - [[FORMAT_XML]]: data属性会被转换为XML格式，请参考[[XmlResponseFormatter]]获取更多细节。
     *
     * You may customize the formatting process or support additional formats by configuring [[formatters]].
     * 可以通过配置[[formatters]]定制更多格式。
     * @see formatters
     */
    public $format = self::FORMAT_HTML;
    /**
     * @var string the MIME type (e.g. `application/json`) from the request ACCEPT header chosen for this response.
     * This property is mainly set by [[\yii\filters\ContentNegotiator]].
     * 字符串，从请求头ACCEPT字段获取的MINE类型，本属性主要由[[\yii\filters\ContentNegotiator]]设置
     */
    public $acceptMimeType;
    /**
     * @var array the parameters (e.g. `['q' => 1, 'version' => '1.0']`) associated with the [[acceptMimeType|chosen MIME type]].
     * This is a list of name-value pairs associated with [[acceptMimeType]] from the ACCEPT HTTP header.
     * This property is mainly set by [[\yii\filters\ContentNegotiator]].
     * 数组，与[[acceptMimeType|chosen MIME type]]相关的参数，本属性是与从HTTP头ACCEPT字段出获得的[[acceptMimeType]]属性
     * 相关的键值对集合
     */
    public $acceptParams = [];
    /**
     * @var array the formatters for converting data into the response content of the specified [[format]].
     * The array keys are the format names, and the array values are the corresponding configurations
     * for creating the formatter objects.
     * 数组，转换data属性的目标格式的集合。数组元素名为格式名，元素值为创造指定的格式对象的配置数组。
     * @see format
     */
    public $formatters = [];
    /**
     * @var mixed the original response data. When this is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * 多种格式，原始的响应信息。本属性不为空时，在发送响应的时候，将会以[[format]]属性格式转换为[[content]]属性
     * @see content
     */
    public $data;
    /**
     * @var string the response content. When [[data]] is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * 字符串，响应内容。[[data]]属性不为空时，将会在发送响应时按照[[format]]被转换为[[content]]
     * @see data
     */
    public $content;
    /**
     * @var resource|array the stream to be sent. This can be a stream handle or an array of stream handle,
     * the begin position and the end position. Note that when this property is set, the [[data]] and [[content]]
     * properties will be ignored by [[send()]].
     * 资源或者数组，发送的数据流。可以是数据流句柄或者数据流句柄组成的数组，数据流开始位置和结束位置。注意，当设置
     * 本属性时，[[send()]]方法将会忽略[[data]] 和 [[content]]属性
     */
    public $stream;
    /**
     * @var string the charset of the text response. If not set, it will use
     * the value of [[Application::charset]].
     * 字符串，响应内容的字符集，使用[[Application::charset]]的值。
     */
    public $charset;
    /**
     * @var string the HTTP status description that comes together with the status code.
     * @see httpStatuses
     */
    public $statusText = 'OK';
    /**
     * @var string the version of the HTTP protocol to use. If not set, it will be determined via `$_SERVER['SERVER_PROTOCOL']`,
     * or '1.1' if that is not available.
     * HTTP协议版本，未设置本属性，则由全局变量`$_SERVER['SERVER_PROTOCOL']决定，或者'1.1'版本不可用。
     */
    public $version;
    /**
     * @var boolean whether the response has been sent. If this is true, calling [[send()]] will do nothing.
     * 布尔值，判断响应是否被发送，如果值为true，则调用[[send()]]不会做任何事。
     */
    public $isSent = false;
    /**
     * @var array list of HTTP status codes and the corresponding texts
     * HTTP状态码及响应描述的集合
     */
    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var integer the HTTP status code to send with the response.
     * 向客户端发送的响应码
     */
    private $_statusCode = 200;
    /**
     * @var HeaderCollection
     * HTTP头集合
     */
    private $_headers;


    /**
     * Initializes this component.
     */
    public function init()
    {
        // 确定HTTP版本号
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
        // 确定响应字符集
        if ($this->charset === null) {
            $this->charset = Yii::$app->charset;
        }
        // 确定响应内容格式，与默认格式列表覆盖合并
        $this->formatters = array_merge($this->defaultFormatters(), $this->formatters);
    }

    /**
     * @return integer the HTTP status code to send with the response.
     * 获取HTTP状态码
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Sets the response status code.
     * This method will set the corresponding status text if `$text` is null.
     * 设置HTTP响应状态码，本方法将会在`$text`为空的情况下设置响应的装填文字。
     * @param integer $value the status code
     * @param string $text the status text. If not set, it will be set automatically based on the status code.
     * @throws InvalidParamException if the status code is invalid.
     */
    public function setStatusCode($value, $text = null)
    {
        // 默认状态码200
        if ($value === null) {
            $value = 200;
        }
        $this->_statusCode = (int) $value;
        if ($this->getIsInvalid()) {
            throw new InvalidParamException("The HTTP status code is invalid: $value");
        }
        if ($text === null) {
            $this->statusText = isset(static::$httpStatuses[$this->_statusCode]) ? static::$httpStatuses[$this->_statusCode] : '';
        } else {
            $this->statusText = $text;
        }
    }

    /**
     * Returns the header collection.
     * The header collection contains the currently registered HTTP headers.
     * 返回一个HTTP响应头集合对象，响应头集合对象包含了当前注册的HTTP响应头
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection;
        }
        return $this->_headers;
    }

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        // 触发发送前事件
        $this->trigger(self::EVENT_BEFORE_SEND);
        // 将data转换为content
        $this->prepare();
        // 触发准备后事件
        $this->trigger(self::EVENT_AFTER_PREPARE);
        // 发送响应头和cookies
        $this->sendHeaders();
        // 发送响应内容，根据stream讨论发送的方式
        $this->sendContent();
        // 触发响应后事件
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }

    /**
     * Clears the headers, cookies, content, status code of the response.
     */
    public function clear()
    {
        $this->_headers = null;
        $this->_cookies = null;
        $this->_statusCode = 200;
        $this->statusText = 'OK';
        $this->data = null;
        $this->stream = null;
        $this->content = null;
        $this->isSent = false;
    }

    /**
     * Sends the response headers to the client
     * 向客户端发送响应头
     */
    protected function sendHeaders()
    {
        // 发送过响应头，则跳过
        if (headers_sent()) {
            return;
        }
        // 填写响应状态码和响应状态描述
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} $statusCode {$this->statusText}");
        if ($this->_headers) {
            $headers = $this->getHeaders();
            // 获取已注册的所有HTTP响应头，遍历并以覆盖的方式设置
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    header("$name: $value", $replace);
                    $replace = false;
                }
            }
        }
        // 向客户端发送cookies
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     * 向客户端发送cookies
     */
    protected function sendCookies()
    {
        if ($this->_cookies === null) {
            return;
        }
        $request = Yii::$app->getRequest();
        // 必须要有cookies加密算子
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        // 遍历所有cookies加密并设置之
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1  && isset($validationKey)) {
                // 安全类很繁杂，单独看
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * Sends the response content to the client
     * 向客户端发送响应内容
     */
    protected function sendContent()
    {
        // 没设置发送（局部数据流）就直接echo出来
        if ($this->stream === null) {
            // 吐槽：看了半天，用的居然是echo……不然还能是什么呢？
            echo $this->content;

            return;
        }

        // 设置脚本执行的最大时间
        set_time_limit(0); // Reset time limit for big files
        // 设置响应分块的大小
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * Sends a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param string $filePath the path of the file to be sent.
     * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     */
    public function sendFile($filePath, $attachmentName = null, $options = [])
    {
        if (!isset($options['mimeType'])) {
            $options['mimeType'] = FileHelper::getMimeTypeByExtension($filePath);
        }
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
        $handle = fopen($filePath, 'rb');
        $this->sendStreamAsFile($handle, $attachmentName, $options);

        return $this;
    }

    /**
     * Sends the specified content as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param string $content the content to be sent. The existing [[content]] will be discarded.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     * @throws HttpException if the requested range is not satisfiable
     */
    public function sendContentAsFile($content, $attachmentName, $options = [])
    {
        $headers = $this->getHeaders();

        $contentLength = StringHelper::byteLength($content);
        $range = $this->getHttpRange($contentLength);

        if ($range === false) {
            $headers->set('Content-Range', "bytes */$contentLength");
            throw new HttpException(416, 'Requested range not satisfiable');
        }

        list($begin, $end) = $range;
        if ($begin != 0 || $end != $contentLength - 1) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $begin-$end/$contentLength");
            $this->content = StringHelper::byteSubstr($content, $begin, $end - $begin + 1);
        } else {
            $this->setStatusCode(200);
            $this->content = $content;
        }

        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);

        $this->format = self::FORMAT_RAW;

        return $this;
    }

    /**
     * Sends the specified stream as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param resource $handle the handle of the stream to be sent.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - `fileSize`: the size of the content to stream this is useful when size of the content is known
     *    and the content is not seekable. Defaults to content size using `ftell()`.
     *    This option is available since version 2.0.4.
     *
     * @return $this the response object itself
     * @throws HttpException if the requested range cannot be satisfied.
     */
    public function sendStreamAsFile($handle, $attachmentName, $options = [])
    {
        $headers = $this->getHeaders();
        if (isset($options['fileSize'])) {
            $fileSize = $options['fileSize'];
        } else {
            fseek($handle, 0, SEEK_END);
            $fileSize = ftell($handle);
        }

        $range = $this->getHttpRange($fileSize);
        if ($range === false) {
            $headers->set('Content-Range', "bytes */$fileSize");
            throw new HttpException(416, 'Requested range not satisfiable');
        }

        list($begin, $end) = $range;
        if ($begin != 0 || $end != $fileSize - 1) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $begin-$end/$fileSize");
        } else {
            $this->setStatusCode(200);
        }

        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']), $end - $begin + 1);

        $this->format = self::FORMAT_RAW;
        $this->stream = [$handle, $begin, $end];

        return $this;
    }

    /**
     * Sets a default set of HTTP headers for file downloading purpose.
     * @param string $attachmentName the attachment file name
     * @param string $mimeType the MIME type for the response. If null, `Content-Type` header will NOT be set.
     * @param boolean $inline whether the browser should open the file within the browser window. Defaults to false,
     * meaning a download dialog will pop up.
     * @param integer $contentLength the byte length of the file being downloaded. If null, `Content-Length` header will NOT be set.
     * @return $this the response object itself
     */
    public function setDownloadHeaders($attachmentName, $mimeType = null, $inline = false, $contentLength = null)
    {
        $headers = $this->getHeaders();

        $disposition = $inline ? 'inline' : 'attachment';
        $headers->setDefault('Pragma', 'public')
            ->setDefault('Accept-Ranges', 'bytes')
            ->setDefault('Expires', '0')
            ->setDefault('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->setDefault('Content-Disposition', "$disposition; filename=\"$attachmentName\"");

        if ($mimeType !== null) {
            $headers->setDefault('Content-Type', $mimeType);
        }

        if ($contentLength !== null) {
            $headers->setDefault('Content-Length', $contentLength);
        }

        return $this;
    }

    /**
     * Determines the HTTP range given in the request.
     * @param integer $fileSize the size of the file that will be used to validate the requested HTTP range.
     * @return array|boolean the range (begin, end), or false if the range request is invalid.
     */
    protected function getHttpRange($fileSize)
    {
        if (!isset($_SERVER['HTTP_RANGE']) || $_SERVER['HTTP_RANGE'] === '-') {
            return [0, $fileSize - 1];
        }
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $matches)) {
            return false;
        }
        if ($matches[1] === '') {
            $start = $fileSize - $matches[2];
            $end = $fileSize - 1;
        } elseif ($matches[2] !== '') {
            $start = $matches[1];
            $end = $matches[2];
            if ($end >= $fileSize) {
                $end = $fileSize - 1;
            }
        } else {
            $start = $matches[1];
            $end = $fileSize - 1;
        }
        if ($start < 0 || $start > $end) {
            return false;
        } else {
            return [$start, $end];
        }
    }

    /**
     * Sends existing file to a browser as a download using x-sendfile.
     *
     * X-Sendfile is a feature allowing a web application to redirect the request for a file to the webserver
     * that in turn processes the request, this way eliminating the need to perform tasks like reading the file
     * and sending it to the user. When dealing with a lot of files (or very big files) this can lead to a great
     * increase in performance as the web application is allowed to terminate earlier while the webserver is
     * handling the request.
     *
     * The request is sent to the server through a special non-standard HTTP-header.
     * When the web server encounters the presence of such header it will discard all output and send the file
     * specified by that header using web server internals including all optimizations like caching-headers.
     *
     * As this header directive is non-standard different directives exists for different web servers applications:
     *
     * - Apache: [X-Sendfile](http://tn123.org/mod_xsendfile)
     * - Lighttpd v1.4: [X-LIGHTTPD-send-file](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Lighttpd v1.5: [X-Sendfile](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Nginx: [X-Accel-Redirect](http://wiki.nginx.org/XSendfile)
     * - Cherokee: [X-Sendfile and X-Accel-Redirect](http://www.cherokee-project.com/doc/other_goodies.html#x-sendfile)
     *
     * So for this method to work the X-SENDFILE option/module should be enabled by the web server and
     * a proper xHeader should be sent.
     *
     * **Note**
     *
     * This option allows to download files that are not under web folders, and even files that are otherwise protected
     * (deny from all) like `.htaccess`.
     *
     * **Side effects**
     *
     * If this option is disabled by the web server, when this method is called a download configuration dialog
     * will open but the downloaded file will have 0 bytes.
     *
     * **Known issues**
     *
     * There is a Bug with Internet Explorer 6, 7 and 8 when X-SENDFILE is used over an SSL connection, it will show
     * an error message like this: "Internet Explorer was not able to open this Internet site. The requested site
     * is either unavailable or cannot be found.". You can work around this problem by removing the `Pragma`-header.
     *
     * **Example**
     *
     * ~~~
     * Yii::$app->response->xSendFile('/home/user/Pictures/picture1.jpg');
     * ~~~
     *
     * @param string $filePath file name with full path
     * @param string $attachmentName file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - xHeader: string, the name of the x-sendfile header. Defaults to "X-Sendfile".
     *
     * @return $this the response object itself
     */
    public function xSendFile($filePath, $attachmentName = null, $options = [])
    {
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
        if (isset($options['mimeType'])) {
            $mimeType = $options['mimeType'];
        } elseif (($mimeType = FileHelper::getMimeTypeByExtension($filePath)) === null) {
            $mimeType = 'application/octet-stream';
        }
        if (isset($options['xHeader'])) {
            $xHeader = $options['xHeader'];
        } else {
            $xHeader = 'X-Sendfile';
        }

        $disposition = empty($options['inline']) ? 'attachment' : 'inline';
        $this->getHeaders()
            ->setDefault($xHeader, $filePath)
            ->setDefault('Content-Type', $mimeType)
            ->setDefault('Content-Disposition', "{$disposition}; filename=\"{$attachmentName}\"");

        $this->format = self::FORMAT_RAW;

        return $this;
    }

    /**
     * Redirects the browser to the specified URL.
     *
     * This method adds a "Location" header to the current response. Note that it does not send out
     * the header until [[send()]] is called. In a controller action you may use this method as follows:
     *
     * ~~~
     * return Yii::$app->getResponse()->redirect($url);
     * ~~~
     *
     * In other places, if you want to send out the "Location" header immediately, you should use
     * the following code:
     *
     * ~~~
     * Yii::$app->getResponse()->redirect($url)->send();
     * return;
     * ~~~
     *
     * In AJAX mode, this normally will not work as expected unless there are some
     * client-side JavaScript code handling the redirection. To help achieve this goal,
     * this method will send out a "X-Redirect" header instead of "Location".
     *
     * If you use the "yii" JavaScript module, it will handle the AJAX redirection as
     * described above. Otherwise, you should write the following JavaScript code to
     * handle the redirection:
     *
     * ~~~
     * $document.ajaxComplete(function (event, xhr, settings) {
     *     var url = xhr.getResponseHeader('X-Redirect');
     *     if (url) {
     *         window.location = url;
     *     }
     * });
     * ~~~
     *
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
     * - a string representing a URL (e.g. "http://example.com")
     * - a string representing a URL alias (e.g. "@example.com")
     * - an array in the format of `[$route, ...name-value pairs...]` (e.g. `['site/index', 'ref' => 1]`).
     *   Note that the route is with respect to the whole application, instead of relative to a controller or module.
     *   [[Url::to()]] will be used to convert the array into a URL.
     *
     * Any relative URL will be converted into an absolute one by prepending it with the host info
     * of the current request.
     *
     * @param integer $statusCode the HTTP status code. Defaults to 302.
     * See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html>
     * for details about HTTP status code
     * @param boolean $checkAjax whether to specially handle AJAX (and PJAX) requests. Defaults to true,
     * meaning if the current request is an AJAX or PJAX request, then calling this method will cause the browser
     * to redirect to the given URL. If this is false, a `Location` header will be sent, which when received as
     * an AJAX/PJAX response, may NOT cause browser redirection.
     * @return $this the response object itself
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true)
    {
        if (is_array($url) && isset($url[0])) {
            // ensure the route is absolute
            $url[0] = '/' . ltrim($url[0], '/');
        }
        $url = Url::to($url);
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = Yii::$app->getRequest()->getHostInfo() . $url;
        }

        if ($checkAjax) {
            if (Yii::$app->getRequest()->getIsPjax()) {
                $this->getHeaders()->set('X-Pjax-Url', $url);
            } elseif (Yii::$app->getRequest()->getIsAjax()) {
                $this->getHeaders()->set('X-Redirect', $url);
            } else {
                $this->getHeaders()->set('Location', $url);
            }
        } else {
            $this->getHeaders()->set('Location', $url);
        }

        $this->setStatusCode($statusCode);

        return $this;
    }

    /**
     * Refreshes the current page.
     * The effect of this method call is the same as the user pressing the refresh button of his browser
     * (without re-posting data).
     *
     * In a controller action you may use this method like this:
     *
     * ~~~
     * return Yii::$app->getResponse()->refresh();
     * ~~~
     *
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     * @return Response the response object itself
     */
    public function refresh($anchor = '')
    {
        return $this->redirect(Yii::$app->getRequest()->getUrl() . $anchor);
    }

    private $_cookies;

    /**
     * Returns the cookie collection.
     * Through the returned cookie collection, you add or remove cookies as follows,
     * 返回cookie集合，通过cookie管理集合，你需要向下面这样添加或移除cookies
     *
     * ~~~
     * // add a cookie
     * // 添加cookies
     * $response->cookies->add(new Cookie([
     *     'name' => $name,
     *     'value' => $value,
     * ]);
     *
     * // remove a cookie
     * // 移除cookies
     * $response->cookies->remove('name');
     * // alternatively
     * // 如下亦可
     * unset($response->cookies['name']);
     * ~~~
     *
     * @return CookieCollection the cookie collection.
     */
    public function getCookies()
    {
        if ($this->_cookies === null) {
            $this->_cookies = new CookieCollection;
        }
        return $this->_cookies;
    }

    /**
     * @return boolean whether this response has a valid [[statusCode]].
     * 布尔值，判断本次响应是否含有无效的状态码
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * @return boolean whether this response is informational
     */
    public function getIsInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * @return boolean whether this response is successful
     */
    public function getIsSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * @return boolean whether this response is a redirection
     */
    public function getIsRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * @return boolean whether this response indicates a client error
     */
    public function getIsClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * @return boolean whether this response indicates a server error
     */
    public function getIsServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * @return boolean whether this response is OK
     */
    public function getIsOk()
    {
        return $this->getStatusCode() == 200;
    }

    /**
     * @return boolean whether this response indicates the current request is forbidden
     */
    public function getIsForbidden()
    {
        return $this->getStatusCode() == 403;
    }

    /**
     * @return boolean whether this response indicates the currently requested resource is not found
     */
    public function getIsNotFound()
    {
        return $this->getStatusCode() == 404;
    }

    /**
     * @return boolean whether this response is empty
     */
    public function getIsEmpty()
    {
        return in_array($this->getStatusCode(), [201, 204, 304]);
    }

    /**
     * @return array the formatters that are supported by default
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML => 'yii\web\HtmlResponseFormatter',
            self::FORMAT_XML => 'yii\web\XmlResponseFormatter',
            self::FORMAT_JSON => 'yii\web\JsonResponseFormatter',
            self::FORMAT_JSONP => [
                'class' => 'yii\web\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }

    /**
     * Prepares for sending the response.
     * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
     * 发送响应前的准备，默认的实现是将[[data]] 属性转换为[[content]] 属性，并设置HTTP响应头。
     * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
     */
    protected function prepare()
    {
        // stream属性不为空则会跳过本方法
        if ($this->stream !== null) {
            return;
        }

        // 如果指定的格式在格式列表中能找到则进行处理
        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
            // 实例化格式转换器
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = Yii::createObject($formatter);
            }
            // 格式转换器必须是ResponseFormatterInterface的实例
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
            // 在格式列表中找不到，则使用data原样输出
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$this->format}");
        }

        // 转换后的content元素不能是数组，如果是对象，则调用对象自身的__toString()方法
        if (is_array($this->content)) {
            throw new InvalidParamException("Response content must not be an array.");
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidParamException("Response content must be a string or an object implementing __toString().");
            }
        }
    }
}
