<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Response represents the response of an [[Application]] to a [[Request]].
 * Response类代表应用对请求的响应
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Response extends Component
{
    /**
     * @var integer the exit status. Exit statuses should be in the range 0 to 254.
     * The status 0 means the program terminates successfully.
     * 整型，退出状态，退出状态码应在0~254之间，0意为成功结束。
     */
    public $exitStatus = 0;


    /**
     * Sends the response to client.
     * 向客户端发送响应
     */
    public function send()
    {
    }

    /**
     * Removes all existing output buffers.
     * 清理缓冲区所有输出内容
     */
    public function clearOutputBuffers()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }
}
