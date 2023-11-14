<?php
namespace support\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\common\monitor\StatisticClient;

class CallMonitor implements MiddlewareInterface
{
    public function process(Request $request, callable $next) : Response
    {
        StatisticClient::tick('api', $request->path());
        // 统计的产生，接口调用是否成功、错误码、错误日志
        $success = true; $code = 0; $msg = '';

        $response = $next($request);
        $body = $response->rawBody();

        $bodyArr = @json_decode($body, true);
        if (!$bodyArr && empty($response->file)) {
            $code = 500;
            $success = false;
            $msg = $body;
        } elseif ($bodyArr && !in_array($bodyArr['code'], [0, 401, 403])) {
            $code = $bodyArr['code'];
            $msg = $bodyArr['msg'] ?? '';
            $success = false;
        }
        // 上报结果
        StatisticClient::report('api', str_replace('/', '_', $request->path()), $success, $code, $msg);

        return $response;
    }
}
