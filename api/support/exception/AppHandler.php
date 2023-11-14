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
namespace support\exception;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException as AppBusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;
use Webman\Exception\ExceptionHandler;

/**
 * Class Handler
 * @package support\exception
 */
class AppHandler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    public $showMsgExceptions = [
        AppBusinessException::class,
        AccessDeniedException::class,
        InvalidParamsException::class,
        ResourceNotFoundException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception) : Response
    {

        $msg = $exception->getMessage();
        $trace = $exception->getTraceAsString();
        $code = $exception->getCode();

        if (!$this->shouldShowExceptionMsg($exception) && !$this->_debug) {
            $msg = 'Server internal error';
            $code = 500;
        }

        if ($request->expectsJson()) {
            $json = ['code' => $code, 'msg' => $msg];
            $this->_debug && $json['traces'] = $trace;

            return new Response(
                200, 
                ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
        
        $error = $this->_debug ? nl2br((string)$exception) : 'Server internal error';
        return new Response(500, [], $error);
    }

    protected function shouldShowExceptionMsg(Throwable $exception)
    {
        $showExceptionMsg = false;
        foreach ($this->showMsgExceptions as $exc) {
            if ($exception instanceof $exc) {
                $showExceptionMsg = true;
                break;
            }
        }
        return $showExceptionMsg;
    }

}