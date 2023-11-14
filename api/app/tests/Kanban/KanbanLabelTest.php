<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\tests\Kanban;

use app\common\exception\AccessDeniedException;
use app\module\Kanban;
use app\module\KanbanLabel;
use app\tests\Base;
use app\tests\Common\DataGenerater;

class KanbanLabelTest extends Base
{
    protected $ownerId;
    protected $kanbanId;
    protected $adminId;
    protected $memberId;

    protected function setUp() : void
    {
        parent::setUp();
        $this->initKanban();
    }

    public function testNewLabel()
    {
        $newLabel = $this->getKanbanLabelModule()->newLabel($this->kanbanId, "Test Label", KanbanLabel::COLOR_RED, $this->ownerId);
        $this->assertNotEmpty($newLabel);
        $this->assertEquals($newLabel->color, KanbanLabel::COLOR_RED);
        $this->assertEquals($newLabel->sort, 11);

        $newLabel = $this->getKanbanLabelModule()->newLabel($this->kanbanId, "Test1中文 Label1", KanbanLabel::COLOR_BLUE, $this->ownerId);
        $this->assertEquals($newLabel->color, KanbanLabel::COLOR_BLUE);
        $this->assertEquals($newLabel->sort, 12);
        $this->assertEquals($newLabel->name, mb_substr('Test1中文 Label1', 0, 8));

        // 不允许增加多个name为空的同色label
        $newLabel = $this->getKanbanLabelModule()->newLabel($this->kanbanId, "", "#111", $this->memberId);
        $this->assertTrue($newLabel->id > 0);
        $newLabel = $this->getKanbanLabelModule()->newLabel($this->kanbanId, "", "#111", $this->memberId);
        $this->assertFalse($newLabel);

        $this->expectException(AccessDeniedException::class);
        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest3@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']);
        $newLabel = $this->getKanbanLabelModule()->newLabel($this->kanbanId, "", "#111", $userId);
    }

    public function testSortLabels()
    {
        $labels = $this->getKanbanLabelModule()->getKanbanLabels($this->kanbanId);
        $sort = false;

        $ids = [];
        foreach ($labels as $label) {
            $ids[] = $label->id;
            if (false === $sort) {
                $sort = $label->sort;
                continue;
            }
            $this->assertTrue($label->sort < $sort);
        }

        shuffle($ids);
        $tmpIds = $ids;
        $count = $this->getKanbanLabelModule()->sortLabels($this->kanbanId, $ids, $this->memberId);
        $labels = $this->getKanbanLabelModule()->getKanbanLabels($this->kanbanId);

        foreach ($labels as $label) {
            $this->assertEquals($label->id, array_shift($ids));
        }

        $count = $this->getKanbanLabelModule()->sortLabels($this->kanbanId, [], $this->ownerId);
        $this->assertEquals(0, $count);

        $this->expectException(AccessDeniedException::class);
        $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid3',
            'email' => 'unittest3@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest3',
            'mobile' => '18011112224']);
        $this->getKanbanLabelModule()->sortLabels($this->kanbanId, $tmpIds, $userId);
    }

    protected function initKanban()
    {
        $this->ownerId = $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid',
            'email' => 'unittest@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112221']);
        $kanban = $this->getKanbanModule()->create('UnitTest Kanban', 'A kanban for unit test!', $userId);
        $this->kanbanId = $kanbanId = $kanban->id;
        $this->adminId = $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid1',
            'email' => 'unittest1@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest1',
            'mobile' => '18011112222']);
        $this->getKanbanModule()->joinKanban($kanbanId, $userId, Kanban::MEMBER_ROLE_ADMIN);

        $this->memberId = $userId = DataGenerater::userGenerater([
            'uuid' => 'unittestuuid2',
            'email' => 'unittest2@example.com', 
            'passhash' => password_hash('unittest', PASSWORD_BCRYPT),
            'name' => 'unittest2',
            'mobile' => '18011112223']);
        $this->getKanbanModule()->joinKanban($kanbanId, $userId, Kanban::MEMBER_ROLE_USER);
    }

}
