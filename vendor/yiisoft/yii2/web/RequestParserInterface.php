<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

/**
 * Interface for classes that parse the raw request body into a parameters array.
 * 所有解析原始HTTP请求体的解析器都必须实现本方法
 *
 * @author Dan Schmidt <danschmidt5189@gmail.com>
 * @since 2.0
 */
interface RequestParserInterface
{
    /**
     * Parses a HTTP request body.
     * 解析请求体
     * @param string $rawBody the raw HTTP request body.
     * @param string $contentType the content type specified for the request body.
     * @return array parameters parsed from the request body
     */
    public function parse($rawBody, $contentType);
}
