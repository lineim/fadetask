<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\tests\Kanban;

use app\common\exception\ResourceNotFoundException;
use app\tests\Kanban\CustomFieldTest;
use app\module\CustomField\CustomField;

class TaskCustomFieldTest extends CustomFieldTest
{

    public function testSetVal()
    {
        $task = [
            'kanbanId' => $this->kanbanId,
            'title' => '单元测试任务',
            'desc' => 'Unit test'
        ];
        $taskId = $this->getKanbanTaskModule()->createTask($task, $this->memberId);
        
        // 下拉字段设置值
        $fieldId = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'Unit Test', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $optionId1 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option1');
        $optionId2 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option2');
        $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option3');

        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId, $fieldId, $optionId1);
        $this->assertTrue((bool) $set);
        $val = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId, $fieldId);
        $this->assertEquals($optionId1, $val->val);

        // 重复设置
        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId, $fieldId, $optionId2);
        $this->assertTrue((bool) $set);
        $val = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId, $fieldId);
        $this->assertEquals($optionId2, $val->val);

        // CheckBox 设置值
        $fieldId = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'Unit Test', CustomField::SUPPORT_TYPE_CHECKBOX , 1);
        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId, $fieldId, 1);
        $this->assertTrue((bool) $set);
        $val = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId, $fieldId);
        $this->assertEquals(1, $val->val);

        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId, $fieldId, 0);
        $this->assertTrue((bool) $set);
        $val = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId, $fieldId);
        $this->assertEquals(0, $val->val);

        $kanbanCustomFieldsVals = $this->getTaskCustomeFieldModule()->getKanbanCustomFieldsVal($this->kanbanId);
        $this->assertCount(2, $kanbanCustomFieldsVals);
    }

    public function testSetValWithFieldNotExist()
    {
        $task = [
            'kanbanId' => $this->kanbanId,
            'title' => '单元测试任务',
            'desc' => 'Unit test'
        ];
        $taskId = $this->getKanbanTaskModule()->createTask($task, $this->memberId);
        
        // 下拉字段设置值
        $fieldId = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'Unit Test', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $optionId1 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option1');

        $fieldIdNotExist = 111111;

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('custom_fields.error.field_not_found');
        $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId, $fieldIdNotExist, $optionId1);
    }

}
