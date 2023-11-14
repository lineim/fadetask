<?php
/**
 * This file is part of lineim kanban project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.lineim.com
 */
namespace app\common\oss;

use Monolog\Logger;

interface OssInterface
{

    /**
     * 获取写入Bucket的STS临时授权信息.
     * 
     * @param int $expire
     * 
     * @return array;
     */
    public function getWriteKeys(int $expire) : array;

    /**
     * 获取读取Bucket的STS临时授权信息.
     * 
     * @param int $expire
     * 
     * @return array;
     */
    public function getReadKeys(int $expire) : array;

    /**
     * 获取上传到OSS的url.
     * 
     * @param string $object  去掉bucket名称的文件全路径, 包含文件名.
     * @param int    $timeout 链接超时时间.
     * 
     * @return string
     * 
     * @throws OssException
     */
    public function getUploadUrl(string $object, int $timeout = 3600) : string;

    /**
     * 获取下载OSS的文件的url.
     * 
     * @param string $object  去掉bucket名称的文件全路径, 包含文件名.
     * @param int    $timeout 链接超时时间.
     * 
     * @return string
     * 
     * @throws OssException
     */
    public function getDownloadUrl(string $object, int $timeout = 3600) : string;

    /**
     * 设置Oss存储桶.
     * 
     * @param string bucket 桶名称.
     * 
     * @return void
     */
    public function setBucket(string $bucket) : void;

    /**
     * 设置Logger, 设置Logger后, 发生异常记录日志.
     * 
     * @param Logger $logger 日志记录实体.
     * 
     * @return void
     */
    public function setLogger(Logger $logger) : void;

}
