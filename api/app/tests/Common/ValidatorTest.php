<?php
declare(strict_types=1);
namespace app\tests\User;

use app\common\toolkit\Validator;
use app\tests\Base;

class ValidatorTest extends Base
{

    public function testPassword()
    {
        $this->assertTrue(Validator::password('12345678'));
        $this->assertTrue(Validator::password('abcdef'));
        $this->assertTrue(Validator::password('abcdefgh'));
        $this->assertFalse(Validator::password('12345'));
        $this->assertFalse(Validator::password('abcde'));
    }

    public function testMobile()
    {
        $this->assertFalse(Validator::mobile('12345'));
        $this->assertFalse(Validator::mobile('15882a46435'));
        $this->assertFalse(Validator::mobile('b5882446435'));
        $this->assertTrue(Validator::mobile('13800000000'));
        $this->assertTrue(Validator::mobile('14800000000'));
        $this->assertTrue(Validator::mobile('15800000000'));
        $this->assertTrue(Validator::mobile('16800000000'));
        $this->assertTrue(Validator::mobile('17800000000'));
        $this->assertTrue(Validator::mobile('18800000000'));
        $this->assertTrue(Validator::mobile('19800000000'));
    }

    public function testEmail()
    {
        $this->assertTrue(Validator::email('noreplay@lineim.com'));
        $this->assertFalse(Validator::email('noreplay@.com'));
        $this->assertFalse(Validator::email('noreplay@lineim'));
        $this->assertFalse(Validator::email('noreplaylineim'));
        $this->assertFalse(Validator::email('noreplaylineim.com'));
        $this->assertFalse(Validator::email('@lineim.com'));
    }

}
