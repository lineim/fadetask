<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace support;

/**
 * Class Request
 * @package support
 */
class Request extends \Webman\Http\Request
{

    /**
     * Parse post.
     *
     * @return void
     */
    protected function parsePost()
    {
        $body_buffer = $this->rawBody();
        $this->_data['post'] = $this->_data['files'] = array();
        if ($body_buffer === '') {
            return;
        }
        $cacheable = static::$_enableCache && !isset($body_buffer[1024]);
        if ($cacheable && isset(static::$_postCache[$body_buffer])) {
            $this->_data['post'] = static::$_postCache[$body_buffer];
            return;
        }
        $content_type = $this->header('content-type');
        if ($content_type !== null && \preg_match('/boundary="?(\S+)"?/', $content_type, $match)) {
            $http_post_boundary = '--' . $match[1];
            $this->parseUploadFiles($http_post_boundary);
            return;
        }
        if (false !== stripos($content_type, 'application/json')) {
            $this->_data['post'] = \json_decode($body_buffer, true);
        } else {
            \parse_str($body_buffer, $this->_data['post']);
        }
        
        if ($cacheable) {
            static::$_postCache[$body_buffer] = $this->_data['post'];
            if (\count(static::$_postCache) > 256) {
                unset(static::$_postCache[key(static::$_postCache)]);
            }
        }
    }

}