<?php
declare(strict_types=1);
namespace app\tests\User;

use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\module\User;
use app\tests\Base;
use app\tests\Common\DataGenerater;
use support\Db;

class UserTest extends Base
{

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
        $this->truncateTables('user');
    }

    public function testGetByUserIdsWithEmptyUids()
    {
        $this->expectException(InvalidParamsException::class);
        $this->getUserModule()->getByUserIds([]);
    }

    public function testGetByUserIds()
    {
        $this->genareateUser('unittestuuid');
        $users = $this->getUserModule()->getByUserIds([1], ['id', 'uuid', 'name']);
        $this->assertNotEmpty($users);
        foreach ($users as $user) {
            $this->assertNotEmpty($user);
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('uuid', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayNotHasKey('email', $user);
        }
    }

    public function testIsLoginDanger()
    {
        $password = '!@QWaszx';
        $email = 'helv@lineim.com';
        $user = [
            'email' => $email,
            'mobile' => '13800000000',
            'name' => '单元测试',
            'password' => $password,
        ];

        $this->getUserModule()->reg($user);
        $user = $this->getUserModule()->getByEmail($email);
        $this->getUserModule()->markVerified($user->uuid);

        for ($i = 0; $i < 5; $i ++) {
            $this->getUserModule()->login($email, 'wrongpass', '127.0.0.1');
        }
        $user = $this->getUserModule()->getByEmail($email);
        $this->assertNotEmpty($user);
        $isDangerUser = $this->getUserModule()->isLoginDanger($user->id);
        $this->assertTrue($isDangerUser);
        $this->getUserModule()->login($email, $password, '127.0.0.1');
        $isDangerUser = $this->getUserModule()->isLoginDanger($user->id);
        $this->assertFalse($isDangerUser);
        for ($i = 0; $i < 5; $i ++) {
            $this->getUserModule()->login($email, 'wrongpass', '127.0.0.1');
        }
        $isDangerUser = $this->getUserModule()->isLoginDanger($user->id);
        $this->assertTrue($isDangerUser);

        // 一分钟后，可重新登录
        DB::table('login_log')->update(['created_time' => time() - 60]);
        $isDangerUser = $this->getUserModule()->isLoginDanger($user->id);
        $this->assertFalse($isDangerUser);
    }

    public function testIsSysAdmin()
    {
        $uuid = 'unittestuuid';
        $this->genareateUser($uuid);
        $isSysAdmin = $this->getUserModule()->isSysAdmin(1);
        $this->assertFalse($isSysAdmin);
        $this->getUserModule()->markAsSysAdmin(1);
        $isSysAdmin = $this->getUserModule()->isSysAdmin(1);
        $this->assertTrue($isSysAdmin);
    }

    public function testMarkVerifiedWithNoUser()
    {
        $this->expectException(BusinessException::class);
        $this->getUserModule()->markVerified('not exist uuid');
    }

    public function testMarkUnVerifiedWithNoUser()
    {
        $this->expectException(BusinessException::class);
        $this->getUserModule()->markUnverified('not exist uuid');
    }

    public function testMarkVerifiedSuccess()
    {
        $uuid = 'unittestuuid';
        $this->genareateUser($uuid);
        $markVerified = $this->getUserModule()->markVerified($uuid);
        $this->assertTrue($markVerified);
        $reMarkVerified = $this->getUserModule()->markVerified($uuid);
        $this->assertTrue($reMarkVerified);

        $user = $this->getUserModule()->getByUuid($uuid);
        $this->assertEquals(User::VERIFIED, $user->verified);
    }

    public function testMarkUnVerifiedSuccess()
    {
        $uuid = 'unittestuuid';
        $this->genareateUser($uuid);
        $this->getUserModule()->markVerified($uuid);
        $markUnVerified = $this->getUserModule()->markUnVerified($uuid);
        $this->assertTrue($markUnVerified);
        $remarkUnVerified = $this->getUserModule()->markUnVerified($uuid);
        $this->assertTrue($remarkUnVerified);

        $user = $this->getUserModule()->getByUuid($uuid);
        $this->assertEquals(User::UN_VERIFIED, $user->verified);
    }

    public function testUpdatePasswordWithNotExistUser()
    {
        $this->expectException(BusinessException::class);
        $this->getUserModule()->updatePassword('not exist user', 'passwordss');
    }

    public function testUpdatePasswordWithLenLeRequired()
    {
        $uuid = $newPassword = '12345';
        $this->genareateUser($uuid);
        $this->expectException(InvalidParamsException::class);
        $this->getUserModule()->updatePassword($uuid, $newPassword);
    }

    public function testUpdatePasswordSuccess()
    {
        $uuid = 'unittestuuid';
        $this->genareateUser($uuid);
        $newPassword = 'passwordforunit';
        $update = $this->getUserModule()->updatePassword($uuid, $newPassword);
        $this->assertTrue($update);
        $this->getUserModule()->markVerified($uuid);
        $user = $this->getUserModule()->getByUuid($uuid, ['email']);
        $login = $this->getUserModule()->login($user->email, $newPassword, '127.0.0.1');
        $this->assertTrue($login);
    }

    public function testUpdatePasswordByOldPassword_User_Not_Exist()
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->getUserModule()->updatePasswordByOldPassword('not exist user', 'oldpass', 'newpass');
    }

    public function testUpdatePasswordByOldPassword_Old_Pass_Error()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('当前密码不正确！');

        $uuid = 'unittestuuid';
        $this->genareateUserV2(['uuid' => $uuid, 'passhash' => password_hash('unittest', PASSWORD_BCRYPT)]);

        $this->getUserModule()->updatePasswordByOldPassword($uuid, 'error old pass', 'newpass@123');
    }

    public function testUpdatePasswordByOldPassword_New_Pass_Error()
    {
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('password format error');

        $uuid = 'unittestuuid';
        $this->genareateUserV2(['uuid' => $uuid, 'passhash' => password_hash('unittest', PASSWORD_BCRYPT)]);

        $this->getUserModule()->updatePasswordByOldPassword($uuid, 'unittest', '123');
    }

    public function testUpdatePasswordByOldPassword_Success()
    {
        $uuid = 'unittestuuid';
        $newPassword = 'unittestpas@123';
        $this->genareateUserV2(['uuid' => $uuid, 'passhash' => password_hash('unittest', PASSWORD_BCRYPT)]);

        $this->getUserModule()->updatePasswordByOldPassword($uuid, 'unittest', 'unittestpas@123');

        $user = $this->getUserModule()->getByUuid($uuid, ['passhash']);
        $this->assertTrue(password_verify($newPassword, $user->passhash));
    }

    public function testIsEmailUsed()
    {
        $email = 'unittest@example.com';
        $this->assertFalse($this->getUserModule()->isEmailUsed($email));

        $this->genareateUserV2(['email' => $email]);
        $this->assertTrue($this->getUserModule()->isEmailUsed($email));
    }

    public function testIsMobileUsed()
    {
        $mobile = '18000000000';
        $this->assertFalse($this->getUserModule()->isMobileUsed($mobile));

        $this->genareateUserV2(['mobile' => $mobile]);
        $this->assertTrue($this->getUserModule()->isMobileUsed($mobile));
    }

    public function testUpdateUserName()
    {
        $id = $this->genareateUser();
        $validName = '12345678';
        $update = $this->getUserModule()->updateUser($id, ['name' => $validName]);
        $this->assertEquals(1, $update);

        $tooLongName = '123456789';
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('user.name.invalid');
        $update = $this->getUserModule()->updateUser($id, ['name' => $tooLongName]);
    }

    protected function genareateUser($uuid = 'unittestuuid')
    {
        $user = [
            'uuid' => $uuid,
            'email' => 'unittest@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest',
            'mobile' => '18011112222',
            'hired_time' => time(),
            'title' => 'test',
            'created_time' => time()
        ];
        return DataGenerater::userGenerater($user);
    }

    protected function genareateUserV2(array $user)
    {
        return DataGenerater::userGenerater($user);
    }

}
