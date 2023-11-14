<?php
namespace app\common\toolkit;

use app\model\IdG as ModelIdG;
use Exception;

class IdG
{

    protected $lock;
    protected $shmId;
    protected $step = 1000;


    public function getId($type)
    {
        if (!$this->lock->acquire()) {
            return false;
        }
        $this->initShm();
        if (!$this->hasBizId($type)) {
            $this->initBizId($type);
        }

        if ($currentId = $this->getIdFromShm($type)) {
            return $currentId;
        }
        $this->initBizId($type);
        $this->lock->release();

        return $this->getIdFromShm($type);
    }

    protected function getIdFromShm($type)
    {
        $idRange = shm_get_var($this->shmId, crc32($type));
        $currentId = $idRange['current'];
        $max = $idRange['max'];

        if ($currentId + 1 > $max) {
            $this->initBizId($type);
            return false;
        }
        $idRange['current'] = $currentId + 1;
        shm_put_var($this->shmId, crc32($type), $idRange);
        return $currentId;
    }

    protected function initBizId($type)
    {
        $idRange = $this->getIdRangeFromDb($type);
        if (!shm_put_var($this->shmId, crc32($type), $idRange)) {
            throw new Exception('put shm error');
        }
    }

    protected function getIdRangeFromDb($type)
    {
        $idRange = ModelIdG::where('type', $type)->first();
        // Todo: 
        $current = $idRange->current;
        $newCurrent = $current + $this->step;

        $idRange->current = $newCurrent;
        $idRange->save();
        return ['current' => $current, 'max' => $newCurrent];
    }

    protected function initShm()
    {
        $this->shmId = shm_attach(1);
        if (!$this->shmId) {
            throw new Exception('attach shm error');
        }
    }

    protected function hasBizId($type)
    {
        return shm_has_var($this->shmId, crc32($type));
    }

    public function setStep($step)
    {
        $step = (int) $step;
        if ($step < 10) {
            throw new Exception('step must bt 10');
        }
        $this->step = $step;
    }

}
