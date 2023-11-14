<?php
namespace app\tests\Kanban;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\module\CustomField\CustomField;
use app\module\Kanban;
use app\module\KanbanMember;
use app\tests\Base;
use app\tests\Common\DataGenerater;
use support\Db;

class KanbanTest extends Base
{
    
    public function testCreateWithEmptyName()
    {
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('kanban.name.error');
        $this->getKanbanModule()->create('', '', 1);
    }

    public function testCreateWithLongName()
    {
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('kanban.name.error');
        $this->getKanbanModule()->create('11111111111111111111111111111111111111111111111111111111111111111_len_64', '', 1);
    }

    public function testCreate()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $this->assertTrue($newKanbanId > 0);
        $kanbanLabels = $this->getKanbanLabelModule()->getKanbanLabels($newKanbanId);
        $defaultLabelColors = $this->getKanbanLabelModule()->getDefaultColors();
        $this->assertEquals($kanbanLabels->count(), count($defaultLabelColors));
        $kanbanLabelColors = $kanbanLabels->pluck('color');
        foreach ($defaultLabelColors as $color) {
            $this->assertTrue($kanbanLabelColors->contains($color));
        }
        $kanbanList = $this->getKanbanModule()->getList($newKanbanId);
        $this->assertEquals($kanbanList->count(), 3);
    }

    public function testCreatewithLongDesc()
    {
        $userId = DataGenerater::userGenerater([]);
        $desc = str_repeat('desc_len_>128', 128);
        $id = $this->getKanbanModule()->create('UnitTest Kanban', $desc, $userId);
        $kanban = $this->getKanbanModule()->get($id);
        $this->assertTrue(mb_strlen($kanban->desc) == 128);

        $desc = str_repeat('desc_len_<128', 1);
        $id = $this->getKanbanModule()->create('UnitTest Kanban', $desc, $userId);
        $kanban = $this->getKanbanModule()->get($id);
        $this->assertEquals(mb_strlen($kanban->desc), mb_strlen($desc));
    }

    public function testClose()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $close = $this->getKanbanModule()->close($newKanbanId);
        $this->assertTrue($close);
        $kanban = $this->getKanbanModule()->get($newKanbanId);
        $this->assertEquals($kanban->is_closed, Kanban::KANBAN_CLOSED);

        $reClose = $this->getKanbanModule()->close($newKanbanId);
        $this->assertTrue($reClose);
        $unClose = $this->getKanbanModule()->unclose($newKanbanId);
        $this->assertTrue($unClose);
        $kanban = $this->getKanbanModule()->get($newKanbanId);
        $this->assertEquals($kanban->is_closed, Kanban::KANBAN_NOT_CLOSED);

        $this->expectException(ResourceNotFoundException::class);
        $notExistKanbanId = 9999999;
        $this->getKanbanModule()->close($notExistKanbanId);
    }

    public function testSetWip()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $list = $this->getKanbanModule()->getKanbanList($newKanbanId);

        foreach ($list as $l) {
            $setWip = $this->getKanbanModule()->setWip($l->id, rand(1, 99), $userId); // Creator set
            $this->assertTrue($setWip);
        }

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_ADMIN);

        foreach ($list as $l) {
            $setWip = $this->getKanbanModule()->setWip($l->id, rand(1, 99), $userId); // admin set
            $this->assertTrue($setWip);
        }
    }

    public function testSetWipWithNegativeWip()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $list = $this->getKanbanModule()->getKanbanList($newKanbanId);

        foreach ($list as $l) {
            $setWip = $this->getKanbanModule()->setWip($l->id, -100, $userId); // Creator set
            $this->assertTrue($setWip);
            $newList = $this->getKanbanModule()->getList($l->id);
            $this->assertEquals(0, $newList->wip);
        }
    }

    public function testSetWipWithOutPermission()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $list = $this->getKanbanModule()->getKanbanList($newKanbanId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);
        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_USER);

        foreach ($list as $l) {
            $setWip = $this->getKanbanModule()->setWip($l->id, rand(1, 99), $userId); // Creator set
        }
    }

    public function testFavoriteKanbanSuccess()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);

        $userIdNotJoin = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->getKanbanModule()->joinKanban($newKanbanId, $userIdNotJoin, Kanban::MEMBER_ROLE_USER);
        $favorite = $this->getKanbanModule()->favorite($newKanbanId, $userIdNotJoin);
        $this->assertTrue($favorite);
    }

    public function testReFavoriteKabnban()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_USER);

        $favorite = $this->getKanbanModule()->favorite($newKanbanId, $userId);
        $this->assertTrue($favorite);
        $favorite = $this->getKanbanModule()->favorite($newKanbanId, $userId);
        $this->assertTrue($favorite);

        $count = Db::table('kanban_favorite')->where('kanban_id', $newKanbanId)->where('user_id', $userId)->count();
        $this->assertEquals(1, $count);
    }

    public function testGetUserFavoriteKanbans()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId1 = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $newKanbanId2 = $this->getKanbanModule()->create('UnitTest Kanban2', 'A kanban for unit test!', $userId);
        $newKanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban2', 'A kanban for unit test!', $userId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->getKanbanModule()->joinKanban($newKanbanId1, $userId, Kanban::MEMBER_ROLE_USER);
        $this->getKanbanModule()->joinKanban($newKanbanId2, $userId, Kanban::MEMBER_ROLE_USER);
        $this->getKanbanModule()->joinKanban($newKanbanId3, $userId, Kanban::MEMBER_ROLE_USER);

        $kanbans = $this->getKanbanModule()->getUserFavorites($userId);        
        $this->assertEquals(0, count($kanbans));

        $this->getKanbanModule()->favorite($newKanbanId1, $userId);
        $this->getKanbanModule()->favorite($newKanbanId2, $userId);
        $this->getKanbanModule()->favorite($newKanbanId3, $userId);
        
        $kanbans = $this->getKanbanModule()->getUserFavorites($userId);
        $this->assertEquals(3, count($kanbans));

        $this->getKanbanModule()->close($newKanbanId2);
        $kanbans = $this->getKanbanModule()->getUserFavorites($userId);
        $this->assertEquals(2, count($kanbans));
        $this->assertNotContains($newKanbanId2, $kanbans->pluck('id')->toArray());
        $this->assertContains($newKanbanId1, $kanbans->pluck('id')->toArray());
        $this->assertContains($newKanbanId3, $kanbans->pluck('id')->toArray());
    }

    public function testFavoriteClosedKanban()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);

        $userIdNotJoin = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->getKanbanModule()->joinKanban($newKanbanId, $userIdNotJoin, Kanban::MEMBER_ROLE_USER);
        $this->getKanbanModule()->close($newKanbanId);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('kanban.closed_err_msg');
        $favorite = $this->getKanbanModule()->favorite($newKanbanId, $userIdNotJoin);

        $this->assertTrue($favorite);
    }

    public function testFavoriteKanbanWithNoPermission()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);

        $userIdNotJoin = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->expectException(AccessDeniedException::class);
        $this->getKanbanModule()->favorite($newKanbanId, $userIdNotJoin);
    }

    public function testIsFavorited()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->getKanbanModule()->joinKanban($newKanbanId, $userId, Kanban::MEMBER_ROLE_USER);

        $isfavorited = $this->getKanbanModule()->isFavorited($newKanbanId, $userId);
        $this->assertFalse($isfavorited);
        $this->getKanbanModule()->favorite($newKanbanId, $userId);
        $isfavorited = $this->getKanbanModule()->isFavorited($newKanbanId, $userId);
        $this->assertTrue($isfavorited);
        $this->getKanbanModule()->unfavorite($newKanbanId, $userId);
        $isfavorited = $this->getKanbanModule()->isFavorited($newKanbanId, $userId);
        $this->assertFalse($isfavorited);
    }

    public function testSetWipWithEmptyList()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('List not found!');

        $this->getKanbanModule()->setWip(1, 100, 1);
    }

    public function testSetWipWithErrorParams()
    {
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('task.wip.too_large');

        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $list = $this->getKanbanModule()->getKanbanList($newKanbanId);

        foreach ($list as $l) {
            $this->getKanbanModule()->setWip($l->id, 100, 1);
        }
    }

    public function testCanCreateKanban()
    {
        $userId = DataGenerater::userGenerater([]);
        $max = Kanban::FREE_USER_MAX_COUNT;
        for ($i = 1; $i <= $max; $i ++) {
            $canCreate = $this->getKanbanModule()->canUserCreate($userId);
            $this->assertTrue($canCreate);
            $kanbanId = $this->getKanbanModule()->create('UnitTest Kanban' . $i, 'A kanban for unit test!', $userId);
        }
        $canCreate = $this->getKanbanModule()->canUserCreate($userId);
        $this->assertFalse($canCreate);
        $this->getKanbanModule()->close($kanbanId);
        $canCreate = $this->getKanbanModule()->canUserCreate($userId);
        $this->assertTrue($canCreate);
    }

    public function testCreateFromWithKanbanNotFound()
    {
        $userId = DataGenerater::userGenerater([]);
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('kanban.not_found');
        $this->getKanbanModule()->createFrom('notfound_uuid', 'UnitTestKanban', '', $userId);
    }

    public function testCreateFromWithNotMember()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $kanban = $this->getKanbanModule()->get($newKanbanId, ['uuid']);
        $userId2 = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getKanbanModule()->createFrom($kanban->uuid, 'UnitTestKanban', '', $userId2);
    }

    public function testCreateFromWithNotAdmin()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $kanban = $this->getKanbanModule()->get($newKanbanId, ['uuid']);
        $userId2 = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );

        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userId2, KanbanMember::MEMBER_ROLE_USER);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getKanbanModule()->createFrom($kanban->uuid, 'UnitTestKanban', '', $userId2);
    }

    public function testCreateFromWithCountLimit()
    {
        $userId = DataGenerater::userGenerater([]);        
        $max = Kanban::FREE_USER_MAX_COUNT;
        for ($i = 1; $i <= $max; $i ++) {
            $canCreate = $this->getKanbanModule()->canUserCreate($userId);
            $this->assertTrue($canCreate);
            $kanbanId = $this->getKanbanModule()->create('UnitTest Kanban' . $i, 'A kanban for unit test!', $userId);
            $kanban = $this->getKanbanModule()->get($kanbanId, ['uuid']);
        }

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('kanban.create.create_limited');
        $this->getKanbanModule()->createFrom($kanban->uuid, 'UnitTestKanban', '', $userId);
    }

    public function testCreateFrom()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $names = ['name type ok dropdown'];
        $dropdown = $this->getCustomFieldModule()->create($newKanbanId, $userId, 'name type ok dropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $options = [
            'Option 1',
            'Option 2',
            'Option 3'
        ];
        $this->getCustomFieldModule()->batchAddDropdownFieldOptions($dropdown, $userId, $options);

        $supportTypes = $this->getCustomFieldModule()->getSupportTypes();
        foreach ($supportTypes as $type) {
            if ($type == CustomField::SUPPORT_TYPE_DROPDOWN) {
                continue;
            }
            $name = 'name type ok ' . $type;
            $names[] = $name;
            $this->getCustomFieldModule()->create($newKanbanId, $userId, $name, $type, true);
        }

        $kanban = $this->getKanbanModule()->get($newKanbanId, ['uuid', 'id', 'name']);
        $userAdmin = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $userRole = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']
        );

        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userAdmin, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userRole, KanbanMember::MEMBER_ROLE_USER);

        $name = 'create from ' . $kanban->name;
        $copiedId = $this->getKanbanModule()->createFrom($kanban->uuid, $name, "", $userAdmin);
        $this->assertTrue($copiedId > 0);
        $copiedKanban = $this->getKanbanModule()->get($copiedId);
        $this->assertEquals($copiedKanban->user_id, $userAdmin);
        $this->assertEquals($copiedKanban->name, $name);
        $members = $this->getKanbanMemberModule()->getMembers($copiedId);

        $memberIds = [$userId, $userAdmin, $userRole];
        $this->assertCount(count($memberIds), $members);
        foreach ($members as $member) {
            $this->assertTrue(in_array($member->member_id, $memberIds));
            if ($member->member_id == $userAdmin) {
                $this->assertEquals($member->role, KanbanMember::MEMBER_ROLE_OWNER);
            } elseif ($member->member_id == $userId) {
                $this->assertEquals($member->role, KanbanMember::MEMBER_ROLE_ADMIN);
            } else {
                $this->assertEquals($member->role, KanbanMember::MEMBER_ROLE_USER);
            }
        }

        $customFields = $this->getCustomFieldModule()->getKanbanCustomFields($copiedId);
        $this->assertCount(count($supportTypes), $customFields);
        foreach ($customFields as $field) {
            $this->assertTrue(in_array($field->name, $names));
            $this->assertTrue(in_array($field->type, $supportTypes));
            if ($field->type == CustomField::SUPPORT_TYPE_DROPDOWN) {
                $this->assertCount(count($options), $field->options);
                foreach ($field->options as $option) {
                    $this->assertTrue(in_array($option['val'], $options));
                }
            }
        }
    }

    public function testListComplete()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $lists = $this->getKanbanModule()->getKanbanList($newKanbanId);
        $listIds = [];
        foreach ($lists as $list) {
            $listIds[] = $list->id;
        }
        shuffle($listIds);
        $listId = array_pop($listIds);
        $set = $this->getKanbanModule()->setListCompleted($listId, $userId, Kanban::IS_COMPLETED_LIST);
        $this->assertEquals(1, $set);
        $this->assertTrue($this->getKanbanModule()->isCompletedList($listId));
        $unset = $this->getKanbanModule()->setListCompleted($listId, $userId, Kanban::NOT_COMPLETED_LIST);
        $this->assertEquals(1, $unset);
        $this->assertFalse($this->getKanbanModule()->isCompletedList($listId));
    }

    public function testUpdateKanbanNameAndDescWithNotMember()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $userNotInKanban = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->expectException(AccessDeniedException::class);
        $this->getKanbanModule()->updateKanbanNameAndDesc($newKanbanId, 'newName', 'desc', $userNotInKanban);
    }

    public function testUpdateKanbanNameAndDescWithNomarlUser()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $userNormal = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userNormal, KanbanMember::MEMBER_ROLE_USER);
        $this->expectException(AccessDeniedException::class);
        $this->getKanbanModule()->updateKanbanNameAndDesc($newKanbanId, 'newName', 'desc', $userNormal);
    }

    public function testUpdateKanbanNameAndDescWithEmptyName()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $userNormal = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userNormal, KanbanMember::MEMBER_ROLE_ADMIN);
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('kanban.name.error');
        $this->getKanbanModule()->updateKanbanNameAndDesc($newKanbanId, '', 'desc', $userNormal);
    }

    public function testUpdateKanbanNameAndDescWithTooLongName()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $userNormal = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userNormal, KanbanMember::MEMBER_ROLE_ADMIN);
        $name = str_repeat('n', Kanban::MAX_NAME_LEN + 1);
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('kanban.name.error');
        $this->getKanbanModule()->updateKanbanNameAndDesc($newKanbanId, $name, 'desc', $userNormal);
    }

    public function testUpdateKanbanNameAndDesc()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $userNormal = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223']
        );
        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userNormal, KanbanMember::MEMBER_ROLE_ADMIN);
        $name = str_repeat('n', Kanban::MAX_NAME_LEN);
        $desc = str_repeat('d', Kanban::MAX_DESC_LEN + 10);
        $update = $this->getKanbanModule()->updateKanbanNameAndDesc($newKanbanId, $name, $desc, $userNormal);
        $this->assertTrue($update);
        $kanban = $this->getKanbanModule()->get($newKanbanId);
        $this->assertEquals($name, $kanban->name);
        $this->assertEquals(mb_substr($desc, 0, Kanban::MAX_DESC_LEN), $kanban->desc);
    }

    public function testGetByIdsAndList()
    {
        $userId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTest Kanban2', 'A kanban for unit test!', $userId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3', 'A kanban for unit test!', $userId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $userId);

        $kanbanIds = [$kanbanId1, $kanbanId2, $kanbanId3, $kanbanId4];
        $this->getKanbanModule()->close($kanbanId3);
        $kanbans = $this->getKanbanModule()->getByIds($kanbanIds);
        $this->assertCount(3, $kanbans);
        foreach ($kanbans as $k) {
            $this->assertNotEquals($kanbanId3, $k->id);
        }
        $kanbans = $this->getKanbanModule()->list();
        $this->assertCount(4, $kanbans);
        foreach ($kanbans as $k) {
            $this->assertTrue(in_array($k->id, $kanbanIds));
        }
    }

    public function testGetKanbanList()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'Archived List');
        $this->assertTrue($listId > 0);
        $this->getKanbanModule()->archiveList($listId, $userId);
        $lists = $this->getKanbanModule()->getKanbanList($newKanbanId);
        foreach ($lists as $l) {
            $this->assertNotEquals($listId, $l->id);
        }

        $this->getKanbanModule()->unarchiveList($listId, $userId);
        $lists = $this->getKanbanModule()->getKanbanList($newKanbanId);
        $this->assertCount(4, $lists);
        $sort = 0;
        foreach ($lists as $l) {
            $this->assertTrue($sort <= $l->sort);
            $sort = $l->sort;
        }
    }

    public function testGetListByIds()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'Archived List');
        $this->assertTrue($listId > 0);
        $this->getKanbanModule()->archiveList($listId, $userId);
        $lists = $this->getKanbanModule()->getKanbanList($newKanbanId);
        $ids = [$listId];
        foreach ($lists as $l) {
            $ids[] = $l->id;
        }
        $lists = $this->getKanbanModule()->getListByIds($ids);
        $this->assertCount(3, $lists);
    }

    public function testGetKanbansList()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'Archived List');
        $this->assertTrue($listId > 0);
        $this->getKanbanModule()->archiveList($listId, $userId);
        $lists = $this->getKanbanModule()->getKanbanList($newKanbanId);
        foreach ($lists as $l) {
            $this->assertNotEquals($listId, $l->id);
        }

        $this->getKanbanModule()->unarchiveList($listId, $userId);
        $lists = $this->getKanbanModule()->getKanbansList([$newKanbanId]);
        $this->assertCount(4, $lists);
        $sort = 0;
        foreach ($lists as $l) {
            $this->assertTrue($sort <= $l->sort);
            $sort = $l->sort;
        }
    }

    public function testGetAllKanbans()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $kanbanId1 = $this->getKanbanModule()->create('like UnitTest Kanban1', 'like kanban for unit test!', $ownerId);
        $kanbanId2 = $this->getKanbanModule()->create('UnitTest Kanban2', 'A kanban for unit like test!', $ownerId);
        $kanbanId3 = $this->getKanbanModule()->create('UnitTest Kanban3like', 'A kanban for unit test! like', $ownerId);
        $kanbanId4 = $this->getKanbanModule()->create('UnitTest Kanban4', 'A kanban for unit test!', $ownerId);
        $this->getKanbanModule()->close($kanbanId4);

        $kanbans = $this->getKanbanModule()->getAllKanbans();
        $this->assertCount(3, $kanbans);
        $kanbans = $this->getKanbanModule()->getAllKanbans('like');
        $this->assertCount(2, $kanbans);
    }

    public function testChangeListNameWithEmptyName()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'List Custom');
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('name error');
        $this->getKanbanModule()->changeListName($listId, '', $userId);
    }

    public function testChangeListNameWithNameTooLong()
    {
        $userId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $userId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'List Custom');
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('name error');
        $name = str_repeat('l', 65);
        $this->getKanbanModule()->changeListName($listId, $name, $userId);
    }

    public function testChangeListNameWithoutPermission()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $ownerId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'List Custom');

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $name = 'new_list_name';
        $this->getKanbanModule()->changeListName($listId, $name, $userId);
    }

    public function testChangeListName()
    {
        $ownerId = DataGenerater::userGenerater([]);
        $newKanbanId = $this->getKanbanModule()->create('UnitTest Kanban1', 'A kanban for unit test!', $ownerId);

        $listId = $this->getKanbanModule()->createList($newKanbanId, 'List Custom');

        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112223'
        ]); 

        $this->getKanbanMemberModule()->joinKanban($newKanbanId, $userId, KanbanMember::MEMBER_ROLE_USER);
        $name = 'new_list_name';
        $change = $this->getKanbanModule()->changeListName($listId, $name, $userId);
        $this->assertTrue($change);
        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals($name, $list->name);

        $changeAgain = $this->getKanbanModule()->changeListName($listId, $name, $userId);
        $this->assertTrue($changeAgain);
        $list = $this->getKanbanModule()->getList($listId);
        $this->assertEquals($name, $list->name);
    }

}
