<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * BaseFileHelper provides concrete implementation for [[FileHelper]].
 *
 * Do not use BaseFileHelper. Use [[FileHelper]] instead.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alex Makarov <sam@rmcreative.ru>
 * @since 2.0
 */
class BaseFileHelper
{
    const PATTERN_NODIR = 1;
    const PATTERN_ENDSWITH = 4;
    const PATTERN_MUSTBEDIR = 8;
    const PATTERN_NEGATIVE = 16;
    const PATTERN_CASE_INSENSITIVE = 32;

    /**
     * @var string the path (or alias) of a PHP file containing MIME type information.
     */
    public static $mimeMagicFile = '@yii/helpers/mimeTypes.php';


    /**
     * Normalizes a file/directory path.
     * The normalization does the following work:
     *
     * - Convert all directory separators into `DIRECTORY_SEPARATOR` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     *
     * @param string $path the file/directory path to be normalized
     * @param string $ds the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string the normalized file/directory path
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        // the path may contain ".", ".." or double slashes, need to clean them up
        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    /**
     * Returns the localized version of a specified file.
     * 返回本地化后的文件
     *
     * The searching is based on the specified language code. In particular,
     * a file with the same name will be looked for under the subdirectory
     * whose name is the same as the language code. For example, given the file "path/to/view.php"
     * and language code "zh-CN", the localized file will be looked for as
     * "path/to/zh-CN/view.php". If the file is not found, it will try a fallback with just a language code that is
     * "zh" i.e. "path/to/zh/view.php". If it is not found as well the original file will be returned.
     * 以指定的语言代码搜索文件，一般来说，会搜索视图文件就下以语言代码命名的文件夹下的同名文件。
     * 例如"path/to/view.php"文件的中文版本在"path/to/zh-CN/view.php"处。如果没找到，
     * 则会试着调用回调函数找zh文件夹下的同名文件，假如还没有招到，则返回源文件。
     *
     * If the target and the source language codes are the same,
     * the original file will be returned.
     * 假如目标文件与源文件一致，则还会返回源文件。
     *
     * @param string $file the original file
     * @param string $language the target language that the file should be localized to.
     * If not set, the value of [[\yii\base\Application::language]] will be used.
     * @param string $sourceLanguage the language that the original file is in.
     * If not set, the value of [[\yii\base\Application::sourceLanguage]] will be used.
     * 源文件语言，假如未设置，则使用应用配置中的语言代码。
     * @return string the matching localized file, or the original file if the localized version is not found.
     * If the target and the source language codes are the same, the original file will be returned.
     */
    public static function localize($file, $language = null, $sourceLanguage = null)
    {
        // 确定目标文件语言
        if ($language === null) {
            $language = Yii::$app->language;
        }
        // 确定源文件语言
        if ($sourceLanguage === null) {
            $sourceLanguage = Yii::$app->sourceLanguage;
        }
        if ($language === $sourceLanguage) {
            return $file;
        }
        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);
        if (is_file($desiredFile)) {
            return $desiredFile;
        } else {
            // 尝试截取语言代码前两位再次拼接
            $language = substr($language, 0, 2);
            if ($language === $sourceLanguage) {
                return $file;
            }
            $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);

            return is_file($desiredFile) ? $desiredFile : $file;
        }
    }

    /**
     * Determines the MIME type of the specified file.
     * This method will first try to determine the MIME type based on
     * [finfo_open](http://php.net/manual/en/function.finfo-open.php). If the `fileinfo` extension is not installed,
     * it will fall back to [[getMimeTypeByExtension()]] when `$checkExtension` is true.
     * @param string $file the file name.
     * @param string $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](http://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param boolean $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return string the MIME type (e.g. `text/plain`). Null is returned if the MIME type cannot be determined.
     * @throws InvalidConfigException when the `fileinfo` PHP extension is not installed and `$checkExtension` is `false`.
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        if ($magicFile !== null) {
            $magicFile = Yii::getAlias($magicFile);
        }
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            } else {
                throw new InvalidConfigException('The fileinfo PHP extension is not installed.');
            }
        }
        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);

        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);

            if ($result !== false) {
                return $result;
            }
        }

        return $checkExtension ? static::getMimeTypeByExtension($file, $magicFile) : null;
    }

    /**
     * Determines the MIME type based on the extension name of the specified file.
     * This method will use a local map between extension names and MIME types.
     * @param string $file the file name.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return string the MIME type. Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeTypeByExtension($file, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);

        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            }
        }

        return null;
    }

    /**
     * Determines the extensions by given MIME type.
     * This method will use a local map between extension names and MIME types.
     * @param string $mimeType file MIME type.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return array the extensions corresponding to the specified MIME type
     */
    public static function getExtensionsByMimeType($mimeType, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);
        return array_keys($mimeTypes, mb_strtolower($mimeType, 'utf-8'), true);
    }

    private static $_mimeTypes = [];

    /**
     * Loads MIME types from the specified file.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return array the mapping from file extensions to MIME types
     */
    protected static function loadMimeTypes($magicFile)
    {
        if ($magicFile === null) {
            $magicFile = static::$mimeMagicFile;
        }
        $magicFile = Yii::getAlias($magicFile);
        if (!isset(self::$_mimeTypes[$magicFile])) {
            self::$_mimeTypes[$magicFile] = require($magicFile);
        }
        return self::$_mimeTypes[$magicFile];
    }

    /**
     * Copies a whole directory as another one.
     * The files and sub-directories will also be copied over.
     * 复制整个文件夹到指定的路径。同时复制子文件夹和文件。
     * @param string $src the source directory
     * 字符串，原文件夹
     * @param string $dst the destination directory
     * 字符串，目标文件夹
     * @param array $options options for directory copy. Valid options are:
     * 复制文件夹时的选项。
     *
     * - dirMode: integer, the permission to be set for newly copied directories. Defaults to 0775.
     * - dirMode: 整型。目标文件夹的属性，默认为0755
     * - fileMode:  integer, the permission to be set for newly copied files. Defaults to the current environment setting.
     * - fileMode:  整型，目标文件的属性，默认为umask的设置值。
     * - filter: callback, a PHP callback that is called for each directory or file.
     *   The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     *   The callback can return one of the following values:
     * - filter: 回调函数，会被每个文件夹和文件调用的回调函数。回调函数的签名为`function ($path)` ，参数 `$path` 为待过滤
     * 的路径，回调函数的返回值为以下几种：
     *
     *   * true: the directory or file will be copied (the "only" and "except" options will be ignored)
     *   * true: 将会被复制的文件或文件夹，忽略 "only" 和 "except"参数。
     *   * false: the directory or file will NOT be copied (the "only" and "except" options will be ignored)
     *   * false: 将不会复制的文件或文件夹，忽略 "only" 和 "except"参数。
     *   * null: the "only" and "except" options will determine whether the directory or file should be copied
     *   * null: 由"only" 和 "except"参数决定是否复制文件和文件夹
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     *   A path matches a pattern if it contains the pattern string at its end.
     *   For example, '.php' matches all file paths ending with '.php'.
     *   Note, the '/' characters in a pattern matches both '/' and '\' in the paths.
     *   If a file path matches a pattern in both "only" and "except", it will NOT be copied.
     * - only: 数组，待复制的文件必须要符合本参数的模式，待复制文件路径以本参数中的某元素结尾，即为符合本参数。
     *   例如：'.php' 匹配所有以'.php'结尾的路径，注意：正斜线在本参数中既匹配正斜线也匹配反斜线。假如某文件路径
     *   既出现在"only" 选项中，又出现在 "except"参数中。则该文件不会被复制。
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
     *   A path matches a pattern if it contains the pattern string at its end.
     *   Patterns ending with '/' apply to directory paths only, and patterns not ending with '/'
     *   apply to file paths only. For example, '/a/b' matches all file paths ending with '/a/b';
     *   and '.svn/' matches directory paths ending with '.svn'. Note, the '/' characters in a pattern matches
     *   both '/' and '\' in the paths.
     * - except: 数组，满足本参数的文件路径将不会被复制。以本参数中某元素结尾的文件路径即为匹配本参数。
     *   以正斜线结尾的规则只适用于文件夹路径，不以正斜线结尾的规则只适用于文件路径。例如。'/a/b'匹配所有
     *   以'/a/b'结尾的文件路径，而'.svn/'匹配所有以'.svn/'结尾的文件夹路径。注意，正斜线在本参数中既匹配
     *   正斜线也匹配反斜线。
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to true.
     * - caseSensitive: 布尔值， "only" 选项域"except" 选项是否区分大小写。默认为true 区分。
     * - recursive: boolean, whether the files under the subdirectories should also be copied. Defaults to true.
     * - recursive: 布尔值，是否递归地复制文件夹下的文件。默认为true
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   If the callback returns false, the copy operation for the sub-directory or file will be cancelled.
     *   The signature of the callback should be: `function ($from, $to)`, where `$from` is the sub-directory or
     *   file to be copied from, while `$to` is the copy target.
     * - beforeCopy: 回调函数，在复制子文件夹和文件之前调用。假如回调函数返回false，则会停止复制当前子文件夹或文件的
     *   复制。回调函数签名为`function ($from, $to)`参数`$from`为待复制文件或文件夹，参数`$to` 为目标文件或文件夹。
     * - afterCopy: callback, a PHP callback that is called after each sub-directory or file is successfully copied.
     *   The signature of the callback should be: `function ($from, $to)`, where `$from` is the sub-directory or
     *   file copied from, while `$to` is the copy target.
     * - afterCopy:回调函数，在复制子文件夹和文件之后调用。回调函数签名为`function ($from, $to)`参数`$from`为待复制
     * 文件或文件夹，参数`$to` 为目标文件或文件夹。
     * @throws \yii\base\InvalidParamException if unable to open directory
     */
    public static function copyDirectory($src, $dst, $options = [])
    {
        if (!is_dir($dst)) {
            // 如果目标文件夹不存在，在递归创建之
            static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidParamException("Unable to open directory: $src");
        }
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($src);
            // 本方法仅影响了$options['except']和$options['only']两个元素
            $options = self::normalizeOptions($options);
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;
            // 通过了filterPath方法，才能进一步操作
            if (static::filterPath($from, $options)) {
                /**
                 *  如果开启了$options['beforeCopy']复制前回调，且未通过回调函数
                 *  的验证，则不做进一步处理，跳过。
                 */
                if (isset($options['beforeCopy']) && !call_user_func($options['beforeCopy'], $from, $to)) {
                    continue;
                }
                // 是文件，则复制并设置权限。
                if (is_file($from)) {
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        @chmod($to, $options['fileMode']);
                    }
                    // 是文件夹 则递归调用。
                } else {
                    static::copyDirectory($from, $to, $options);
                }
                // 最后执行复制后回调（仅执行一次？）
                if (isset($options['afterCopy'])) {
                    call_user_func($options['afterCopy'], $from, $to);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Removes a directory (and all its content) recursively.
     * @param string $dir the directory to be deleted recursively.
     * @param array $options options for directory remove. Valid options are:
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     */
    public static function removeDirectory($dir, $options = [])
    {
        if (!is_dir($dir)) {
            return;
        }
        if (isset($options['traverseSymlinks']) && $options['traverseSymlinks'] || !is_link($dir)) {
            if (!($handle = opendir($dir))) {
                return;
            }
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    static::removeDirectory($path, $options);
                } else {
                    unlink($path);
                }
            }
            closedir($handle);
        }
        if (is_link($dir)) {
            unlink($dir);
        } else {
            rmdir($dir);
        }
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     * 返回指定文件夹及其子文件夹下找到的文件。
     * @param string $dir the directory under which the files will be looked for.
     * 字符串，待搜索的文件夹
     * @param array $options options for file searching. Valid options are:
     * 数组，寻找文件的选项，有效的有以下几项：
     *
     * - filter: callback, a PHP callback that is called for each directory or file.
     *   The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     *   The callback can return one of the following values:
     * - filter: 回调函数，每个文件夹或文件都会调用的回调函数，回调函数的签名为`function ($path)`
     *   其中`$path`是待过滤文件或文件夹的全路径，回调函数会返回以下值：
     *
     *   * true: the directory or file will be returned (the "only" and "except" options will be ignored)
     *   * true: 相应的文件或文件夹会被返回（忽略"only" 与 "except"选项 ）
     *   * false: the directory or file will NOT be returned (the "only" and "except" options will be ignored)
     *   * false: 相应的文件或文件夹不会被返回（忽略"only" 与 "except"选项 ）
     *   * null: the "only" and "except" options will determine whether the directory or file should be returned
     *   * null: 由"only" 与 "except"选项 决定是否返回文件或文件夹
     *
     * - except: array, list of patterns excluding from the results matching file or directory paths.
     *   Patterns ending with '/' apply to directory paths only, and patterns not ending with '/'
     *   apply to file paths only. For example, '/a/b' matches all file paths ending with '/a/b';
     *   and '.svn/' matches directory paths ending with '.svn'.
     *   If the pattern does not contain a slash /, it is treated as a shell glob pattern and checked for a match against the pathname relative to $dir.
     *   Otherwise, the pattern is treated as a shell glob suitable for consumption by fnmatch(3) with the FNM_PATHNAME flag: wildcards in the pattern will not match a / in the pathname.
     *   For example, "views/*.php" matches "views/index.php" but not "views/controller/index.php".
     *   A leading slash matches the beginning of the pathname. For example, "/*.php" matches "index.php" but not "views/start/index.php".
     *   An optional prefix "!" which negates the pattern; any matching file excluded by a previous pattern will become included again.
     *   If a negated pattern matches, this will override lower precedence patterns sources. Put a backslash ("\") in front of the first "!"
     *   for patterns that begin with a literal "!", for example, "\!important!.txt".
     *   Note, the '/' characters in a pattern matches both '/' and '\' in the paths.
     * - only: array, list of patterns that the file paths should match if they are to be returned. Directory paths are not checked against them.
     *   Same pattern matching rules as in the "except" option are used.
     *   If a file path matches a pattern in both "only" and "except", it will NOT be returned.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to true.
     * - recursive: boolean, whether the files under the subdirectories should also be looked for. Defaults to true.
     * @return array files found under the directory. The file list is sorted.
     * @throws InvalidParamException if the dir is invalid.
     */
    public static function findFiles($dir, $options = [])
    {
        // 不是文件夹会报错
        if (!is_dir($dir)) {
            throw new InvalidParamException("The dir argument must be a directory: $dir");
        }
        // 去掉右侧的文件夹分隔符
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        // 如果没设置基准文件夹，则使用父文件夹，并规格化选项。
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($dir);
            $options = self::normalizeOptions($options);
        }
        $list = [];
        $handle = opendir($dir);
        if ($handle === false) {
            throw new InvalidParamException("Unable to open directory: $dir");
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($path, $options)) {
                if (is_file($path)) {
                    $list[] = $path;
                    // 不是文件则视情况递归查找
                } elseif (!isset($options['recursive']) || $options['recursive']) {
                    $list = array_merge($list, static::findFiles($path, $options));
                }
            }
        }
        closedir($handle);

        return $list;
    }

    /**
     * Checks if the given file path satisfies the filtering options.
     * 检查给定的文件路径是否满足过滤选项。
     * @param string $path the path of the file or directory to be checked
     * 字符串，待检查的文件或文件夹路径
     * @param array $options the filtering options. See [[findFiles()]] for explanations of
     * the supported options.
     * 数组，过滤选项，参见[[findFiles()]]方法获取各选项的解释。
     * @return boolean whether the file or directory satisfies the filtering options.
     */
    public static function filterPath($path, $options)
    {
        // 如果有回调函数 则执行回调函数，以回调函数的结果决定是否合法
        if (isset($options['filter'])) {
            $result = call_user_func($options['filter'], $path);
            if (is_bool($result)) {
                return $result;
            }
        }

        // 如果排除选项和only选项都是空的 则返回合法
        if (empty($options['except']) && empty($options['only'])) {
            return true;
        }

        $path = str_replace('\\', '/', $path);

        // 检测是否该被排除
        if (!empty($options['except'])) {
            if (($except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['except'])) !== null) {
                return $except['flags'] & self::PATTERN_NEGATIVE;
            }
        }

        // 检测应该包括的文件
        if (!empty($options['only']) && !is_dir($path)) {
            if (($except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['only'])) !== null) {
                // don't check PATTERN_NEGATIVE since those entries are not prefixed with !
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Creates a new directory.
     * 创建新的文件夹
     *
     * This method is similar to the PHP `mkdir()` function except that
     * it uses `chmod()` to set the permission of the created directory
     * in order to avoid the impact of the `umask` setting.
     * 本方法与PHP原生方法`mkdir()`类似，然而本方法会使用 `chmod()`为被创建
     * 的文件夹设置权限，以避免umask设置的影响。
     *
     * @param string $path path of the directory to be created.
     * 字符串，待创建文件夹的路径
     * @param integer $mode the permission to be set for the created directory.
     * 整型，待创建文件夹的权限
     * @param boolean $recursive whether to create parent directories if they do not exist.
     * 布尔值，是否递归创建父文件夹
     * @return boolean whether the directory is created successfully
     * 布尔值，是否创建成功。
     * @throws \yii\base\Exception if the directory could not be created.
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            $result = mkdir($path, $mode);
            chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \yii\base\Exception("Failed to create directory '$path': " . $e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    /**
     * Performs a simple comparison of file or directory names.
     *
     * Based on match_basename() from dir.c of git 1.8.5.3 sources.
     *
     * @param string $baseName file or directory name to compare with the pattern
     * @param string $pattern the pattern that $baseName will be compared against
     * @param integer|boolean $firstWildcard location of first wildcard character in the $pattern
     * @param integer $flags pattern flags
     * @return boolean whether the name matches against pattern
     */
    private static function matchBasename($baseName, $pattern, $firstWildcard, $flags)
    {
        if ($firstWildcard === false) {
            if ($pattern === $baseName) {
                return true;
            }
        } elseif ($flags & self::PATTERN_ENDSWITH) {
            /* "*literal" matching against "fooliteral" */
            $n = StringHelper::byteLength($pattern);
            if (StringHelper::byteSubstr($pattern, 1, $n) === StringHelper::byteSubstr($baseName, -$n, $n)) {
                return true;
            }
        }

        $fnmatchFlags = 0;
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $fnmatchFlags |= FNM_CASEFOLD;
        }

        return fnmatch($pattern, $baseName, $fnmatchFlags);
    }

    /**
     * Compares a path part against a pattern with optional wildcards.
     *
     * Based on match_pathname() from dir.c of git 1.8.5.3 sources.
     *
     * @param string $path full path to compare
     * @param string $basePath base of path that will not be compared
     * @param string $pattern the pattern that path part will be compared against
     * @param integer|boolean $firstWildcard location of first wildcard character in the $pattern
     * @param integer $flags pattern flags
     * @return boolean whether the path part matches against pattern
     */
    private static function matchPathname($path, $basePath, $pattern, $firstWildcard, $flags)
    {
        // match with FNM_PATHNAME; the pattern has base implicitly in front of it.
        if (isset($pattern[0]) && $pattern[0] == '/') {
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
            if ($firstWildcard !== false && $firstWildcard !== 0) {
                $firstWildcard--;
            }
        }

        $namelen = StringHelper::byteLength($path) - (empty($basePath) ? 0 : StringHelper::byteLength($basePath) + 1);
        $name = StringHelper::byteSubstr($path, -$namelen, $namelen);

        if ($firstWildcard !== 0) {
            if ($firstWildcard === false) {
                $firstWildcard = StringHelper::byteLength($pattern);
            }
            // if the non-wildcard part is longer than the remaining pathname, surely it cannot match.
            if ($firstWildcard > $namelen) {
                return false;
            }

            if (strncmp($pattern, $name, $firstWildcard)) {
                return false;
            }
            $pattern = StringHelper::byteSubstr($pattern, $firstWildcard, StringHelper::byteLength($pattern));
            $name = StringHelper::byteSubstr($name, $firstWildcard, $namelen);

            // If the whole pattern did not have a wildcard, then our prefix match is all we need; we do not need to call fnmatch at all.
            if (empty($pattern) && empty($name)) {
                return true;
            }
        }

        $fnmatchFlags = FNM_PATHNAME;
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $fnmatchFlags |= FNM_CASEFOLD;
        }

        return fnmatch($pattern, $name, $fnmatchFlags);
    }

    /**
     * Scan the given exclude list in reverse to see whether pathname
     * should be ignored.  The first match (i.e. the last on the list), if
     * any, determines the fate.  Returns the element which
     * matched, or null for undecided.
     * 从集合中最后依次执行匹配。从给定的排除列表中反向查找【不一定对】指定的
     * 路径名是否应该别排除。第一个匹配的【列表中的最后一个】，如果有的话，决定了
     * 本路径名是否被过滤。返回匹配的元素，如果不确定是否匹配则返回null
     *
     * Based on last_exclude_matching_from_list() from dir.c of git 1.8.5.3 sources.
     * 基于 git 1.8.5.3版源代码中的dir.c 文件的last_exclude_matching_from_list()方法修改而来
     *
     * @param string $basePath
     * 基准文件夹
     * @param string $path
     * 待过滤文件夹
     * @param array $excludes list of patterns to match $path against
     * 过滤选项数组
     * @return string null or one of $excludes item as an array with keys: 'pattern', 'flags'
     * @throws InvalidParamException if any of the exclude patterns is not a string or an array with keys: pattern, flags, firstWildcard.
     */
    private static function lastExcludeMatchingFromList($basePath, $path, $excludes)
    {
        // 反向遍历数组
        foreach (array_reverse($excludes) as $exclude) {
            if (is_string($exclude)) {
                $exclude = self::parseExcludePattern($exclude, false);
            }
            if (!isset($exclude['pattern']) || !isset($exclude['flags']) || !isset($exclude['firstWildcard'])) {
                throw new InvalidParamException('If exclude/include pattern is an array it must contain the pattern, flags and firstWildcard keys.');
            }
            if ($exclude['flags'] & self::PATTERN_MUSTBEDIR && !is_dir($path)) {
                continue;
            }

            if ($exclude['flags'] & self::PATTERN_NODIR) {
                if (self::matchBasename(basename($path), $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                    return $exclude;
                }
                continue;
            }

            if (self::matchPathname($path, $basePath, $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                return $exclude;
            }
        }

        return null;
    }

    /**
     * Processes the pattern, stripping special characters like / and ! from the beginning and settings flags instead.
     * 执行匹配，一开始就会剥离如正斜线 / 和叹号 ! 这样的特殊字符。
     * @param string $pattern
     * 字符串，规则
     * @param boolean $caseSensitive
     * 布尔值，是否大小写
     * @throws \yii\base\InvalidParamException
     * @return array with keys: (string) pattern, (int) flags, (int|boolean) firstWildcard
     * 返回含有以下元素的数组(string) pattern, (int) flags, (int|boolean) firstWildcard
     */
    private static function parseExcludePattern($pattern, $caseSensitive)
    {
        if (!is_string($pattern)) {
            throw new InvalidParamException('Exclude/include pattern must be a string.');
        }

        $result = [
            'pattern' => $pattern,
            'flags' => 0,
            'firstWildcard' => false,
        ];

        if (!$caseSensitive) {
            $result['flags'] |= self::PATTERN_CASE_INSENSITIVE;
        }

        if (!isset($pattern[0])) {
            return $result;
        }

        /** 
         * 如果规则以'!'开头，则为结果的flags加上一个PATTERN_NEGATIVE常量
         * 就是“取反”？并截取从·第二位包括第二位直到末尾的自字符串。
         */
        if ($pattern[0] == '!') {
            $result['flags'] |= self::PATTERN_NEGATIVE;
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
        }
        /**
         * 如果剩下的字符串【注意是去掉开头!剩下的字符串】不为空，
         * 且以正斜线结尾，则去掉末尾正斜线，取子字符串，并为flags
         * 增加一项“是文件夹”
         */
        if (StringHelper::byteLength($pattern) && StringHelper::byteSubstr($pattern, -1, 1) == '/') {
            $pattern = StringHelper::byteSubstr($pattern, 0, -1);
            $result['flags'] |= self::PATTERN_MUSTBEDIR;
        }
        /**
         * 如果剩下的字符串中再也没有正斜线了，则为flags中加入
         * “没有父文件夹”标记
         */
        if (strpos($pattern, '/') === false) {
            $result['flags'] |= self::PATTERN_NODIR;
        }
        /**
         * 查询首次出现通配符的位置，如果剩下的字符串以*开头，且*之后
         * 的子字符串再也找不到通配符了，则加入“停止搜索通配符”标记
         */
        $result['firstWildcard'] = self::firstWildcardInPattern($pattern);
        if ($pattern[0] == '*' && self::firstWildcardInPattern(StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern))) === false) {
            $result['flags'] |= self::PATTERN_ENDSWITH;
        }
        $result['pattern'] = $pattern;

        return $result;
    }

    /**
     * Searches for the first wildcard character in the pattern.
     * 在规则中搜搜第一个通配符
     * @param string $pattern the pattern to search in
     * 字符串，待搜索的通配符
     * @return integer|boolean position of first wildcard character or false if not found
     * 整型或布尔值，第一个通配符的位置，没找到返回false。
     */
    private static function firstWildcardInPattern($pattern)
    {
        $wildcards = ['*', '?', '[', '\\'];
        $wildcardSearch = function ($r, $c) use ($pattern) {
            $p = strpos($pattern, $c);

            // 这里搞得这么晦涩就是因为min()方法的参数不能为bool类型。
            return $r === false ? $p : ($p === false ? $r : min($r, $p));
        };

        // 深入理解array_reduce函数 最后返回的是$wildcards中的元素最早出现的位置。
        return array_reduce($wildcards, $wildcardSearch, false);
    }

    /**
     * 规格化选项
     * @param array $options raw options
     * @return array normalized options
     */
    private static function normalizeOptions(array $options)
    {
        if (!array_key_exists('caseSensitive', $options)) {
            $options['caseSensitive'] = true;
        }
        // 将每一个排除规则规格化
        if (isset($options['except'])) {
            foreach ($options['except'] as $key => $value) {
                if (is_string($value)) {
                    $options['except'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }
        // 将每一个only规则规格化
        if (isset($options['only'])) {
            foreach ($options['only'] as $key => $value) {
                if (is_string($value)) {
                    $options['only'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }
        // 返回规格化后的规则
        return $options;
    }
}
