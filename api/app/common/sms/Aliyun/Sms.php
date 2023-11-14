<?php
namespace app\common\sms\Aliyun;

use app\common\sms\SmsInterface;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use support\bootstrap\Log;

class Sms implements SmsInterface
{

    /**
     * @var Dysmsapi
     */
    private $client;

    private $signName;

    public function __construct(Dysmsapi $client, $signName)
    {
        $this->client = $client;
        $this->signName = $signName;
    }

    public function send($mobile, $tplCode, array $params)
    {
        $logger = Log::channel('sms');
        $sendSmsRequest = new SendSmsRequest([
            'phoneNumbers' => $mobile,
            'signName' => $this->signName,
            'templateCode' => $tplCode,
            'templateParam' => json_encode($params)
        ]);
        $runtime = new RuntimeOptions([]);

        try {
            $this->client->sendSmsWithOptions($sendSmsRequest, $runtime);
            return true;
        } catch (\Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            $logger->error(
                sprintf(
                    'An exception occurred when send sms. code: %d, msg: %s, param: %s', 
                    $error->getCode(),
                    $error->getMessage(),
                    json_encode(func_get_args())
                ),
                $error->getTrace()
            );
            throw $error;
        }
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

}
