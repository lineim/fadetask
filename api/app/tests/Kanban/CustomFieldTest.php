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
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\module\CustomField\CustomField;
use app\module\Kanban;
use app\tests\Base;
use app\tests\Common\DataGenerater;
use support\Db;

class CustomFieldTest extends Base
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

    public function testCreatePermissionError()
    {
        // no permission
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getCustomFieldModule()->create($this->kanbanId, $this->memberId, 'aaa', 'checkbox', true);
    }

    public function testNameToLoog()
    {
        $nameLenEQ32 = str_repeat('a', 32);
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, $nameLenEQ32, 'checkbox', true);
        $this->assertTrue($id > 0);


        $nameLenGT32 = str_repeat('b', 33);
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('custom_fields.error.name_error');
        $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, $nameLenGT32, 'checkbox', true);
    }

    public function testNameEmpty()
    {
        $nameEmpty = '';
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('custom_fields.error.name_error');
        $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, $nameEmpty, 'checkbox', true);
    }

    public function testErrorType()
    {
        $supportTypes = $this->getCustomFieldModule()->getSupportTypes();

        foreach ($supportTypes as $key => $type) {
            $name = 'name type ok ' . $key;
            $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->ownerId, $name, $type, true);
            $this->assertTrue($id > 0);
            $field = $this->getCustomFieldModule()->getField($id);
            $this->assertEquals($field->type, $type);
            $this->assertEquals($field->user_id, $this->ownerId);
            $this->assertEquals($field->sort, $key + 1);
        }

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('custom_fields.not_support_type');
        $this->getCustomFieldModule()->create($this->kanbanId, $this->ownerId, 'type error', 'member', true);
    }

    public function testMaxCountError()
    {
        for ($key = 0; $key < 10; $key ++) {
            $name = 'name type ok ' . $key;
            $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, $name, 'number', true);
            $this->assertTrue($id > 0);
            $field = $this->getCustomFieldModule()->getField($id);
            $this->assertEquals($field->user_id, $this->adminId);
            $this->assertEquals($field->sort, $key + 1);
        }

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('custom_fields.error.max_count');
        $this->getCustomFieldModule()->create($this->kanbanId, $this->ownerId, 'max count', 'checkbox', true);
    }

    public function testUpdateWithEmptyName()
    {
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('custom_fields.error.name_error');
        $this->getCustomFieldModule()->update($id, ['name' => ''], $this->ownerId);
    }

    public function testUpdateWithTolongName()
    {
        $name = str_repeat('t', 33);
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $this->expectException(InvalidParamsException::class);
        $this->expectExceptionMessage('custom_fields.error.name_error');
        $this->getCustomFieldModule()->update($id, ['name' => $name], $this->ownerId);
    }


    public function testUpdateWithNoPermission()
    {
        $name = str_repeat('t', 32);
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('access_denied');
        $this->getCustomFieldModule()->update($id, ['name' => $name], $this->memberId);
    }

    public function testUpdateWithFieldNotFound()
    {
        $notfoundId = 123;
        $name = str_repeat('t', 32);
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('custom_fields.not_found');
        $this->getCustomFieldModule()->update($notfoundId, ['name' => $name], $this->adminId);
    }

    public function testUpdateWithNameExists()
    {
        $id1 = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update1', 'checkbox', 1);
        $this->assertTrue($id1 > 0);

        $id2 = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update2', 'checkbox', 1);
        $this->assertTrue($id2 > 0);

        $update2 = $this->getCustomFieldModule()->update($id2, ['name' => 'test for update22'], $this->ownerId);
        $this->assertEquals(1, $update2);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('custom_fields.name_exist');
        $this->getCustomFieldModule()->update($id2, ['name' => 'test for update1'], $this->ownerId);
    }

    public function testUpdateSuccess()
    {
        $name = str_repeat('t', 32);
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $updated = $this->getCustomFieldModule()->update($id, ['name' => $name, 'showOnFront' => false, 'aaa' => 'bbb'], $this->adminId);
        $this->assertEquals(1, $updated);
        $time = time();
        $field = $this->getCustomFieldModule()->getField($id, ['name', 'show_on_card_front', 'updated_time']);
        $this->assertEquals($name, $field->name);
        $this->assertEquals(0, $field->show_on_card_front);
        $this->assertEquals($time, $field->updated_time);

        $time = time();
        $updated = $this->getCustomFieldModule()->update($id, ['showOnFront' => true], $this->adminId);
        $field = $this->getCustomFieldModule()->getField($id, ['name', 'show_on_card_front', 'updated_time']);
        $this->assertEquals($name, $field->name);
        $this->assertEquals(1, $field->show_on_card_front);
        $this->assertEquals($time, $field->updated_time);
    }

    public function testUpdateWithNoDataForUpdate()
    {
        $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'test for update', 'checkbox', 1);
        $updated = $this->getCustomFieldModule()->update($id, [], $this->adminId);
        $this->assertTrue($updated === 0);
    }

    public function testSortByIdSeq()
    {
        $supportTypes = $this->getCustomFieldModule()->getSupportTypes();
        $ids = [];
        foreach ($supportTypes as $key => $type) {
            $name = 'name type ok ' . $key;
            $id = $this->getCustomFieldModule()->create($this->kanbanId, $this->ownerId, $name, $type, true);
            $ids[] = $id;
            $field = $this->getCustomFieldModule()->getField($id);
            $this->assertEquals($field->sort, $key + 1);
        }
        shuffle($ids);
        $sort = $this->getCustomFieldModule()->sortByIdSeq($ids);
        $this->assertTrue($sort);
        foreach ($ids as $key => $id) {
            $field = $this->getCustomFieldModule()->getField($id, ['sort']);
            $this->assertEquals($key + 1, $field->sort);
        }
    }

    public function testAddDropdownFieldOptionWithFieldNotExist()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $dropdownNotExistId = 111111;
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('custom_fields.not_found');
        $this->getCustomFieldModule()->addDropdownFieldOption($dropdownNotExistId, $this->adminId, 'Option1');
    }

    public function testAddDropdownFieldOptionWithNoPermission()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->memberId, 'Option1');
    }

    public function testAddDropdownFieldOptionWithTypeError()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DATETIME, 1);
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('custom_fields.not_support_type');
        $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->adminId, 'Option1');
    }

    public function testBatchAddDropdownFieldOptions()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $options = [
            'Option 1',
            'Option 2',
            'Option 3'
        ];
        $added = $this->getCustomFieldModule()->batchAddDropdownFieldOptions($dropdown, $this->adminId, $options);
        $this->assertTrue((bool) $added);

        $field = $this->getCustomFieldModule()->getField($dropdown);
        $this->assertCount(3, $field->options);
        $inDbOptions = [];
        foreach ($field->options as $op) {
            $inDbOptions[] = $op->val;
        }
        $this->assertEmpty(array_diff($options, $inDbOptions));
    }

    public function testBatchAddDropdownFieldOptionWithFieldNotExist()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $dropdownNotExistId = 111111;
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('custom_fields.not_found');
        $options = ['Option1'];
        $this->getCustomFieldModule()->batchAddDropdownFieldOptions($dropdownNotExistId, $this->adminId, $options);
    }

    public function testBatchAddDropdownFieldOptionWithNoPermission()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(AccessDeniedException::DEFAULT_MSG);
        $options = ['Option1'];
        $this->getCustomFieldModule()->batchAddDropdownFieldOptions($dropdown, $this->memberId, $options);
    }

    public function testBatchAddDropdownFieldOptionWithTypeError()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DATETIME, 1);
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('custom_fields.not_support_type');
        $options = ['Option1'];
        $this->getCustomFieldModule()->batchAddDropdownFieldOptions($dropdown, $this->adminId, $options);
    }

    public function testUpdateDropdownFieldVal()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $option = 'Option1';
        $id = $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->adminId, $option);
        $this->assertTrue($id > 0);

        $newVal = 'Option New';
        $updated = $this->getCustomFieldModule()->updateDropdownFieldVal($id, $newVal);
        $this->assertTrue((bool) $updated);

        $option = $this->getCustomFieldModule()->getDropdwonFieldOption($id);
        $this->assertEquals($newVal, $option->val);
    }

    public function testGetKanbanCustomFields()
    {
        $dropdown = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $optionId1 = $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->adminId, 'Option1');
        $optionId2 = $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->adminId, 'Option2');
        $optionId3 = $this->getCustomFieldModule()->addDropdownFieldOption($dropdown, $this->adminId, 'Option3');

        $dropdown2 = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown2', CustomField::SUPPORT_TYPE_DROPDOWN, 1);
        $optionId1 = $this->getCustomFieldModule()->addDropdownFieldOption($dropdown2, $this->adminId, 'Option1');

        $dropdown3 = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDropdown3', CustomField::SUPPORT_TYPE_DROPDOWN, 1);

        $checkbox = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestCheckbox', CustomField::SUPPORT_TYPE_CHECKBOX, 1);
        $datetime = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestDatetime', CustomField::SUPPORT_TYPE_DATETIME, 1);
        $number = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestNumber', CustomField::SUPPORT_TYPE_NUMBER, 1);
        $text = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'UnitTestText', CustomField::SUPPORT_TYPE_TEXT, 1);

        $fields = $this->getCustomFieldModule()->getKanbanCustomFields($this->kanbanId);
        $this->assertCount(7, $fields);

        foreach ($fields as $field) {
            
            if ($field->type == CustomField::SUPPORT_TYPE_DROPDOWN) {
                if ($field->id == $dropdown) {
                    $this->assertCount(3, $field->options);
                }
                if ($field->id == $dropdown2) {
                    $this->assertCount(1, $field->options);
                }

                if ($field->id == $dropdown3) {
                    $this->assertCount(0, $field->options);
                }                
            }
        }        
    }

    public function testDel()
    {
        $task = [
            'kanbanId' => $this->kanbanId,
            'title' => '单元测试任务',
            'desc' => 'Unit test'
        ];
        $taskId1 = $this->getKanbanTaskModule()->createTask($task, $this->memberId);
        $taskId2 = $this->getKanbanTaskModule()->createTask($task, $this->memberId);
        $taskId3 = $this->getKanbanTaskModule()->createTask($task, $this->memberId);
        $this->assertTrue($taskId1 > 0);
        $this->assertTrue($taskId1 != $taskId2 && $taskId2 > 0);
        $fieldId = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'Unit Test', 'dropdown', 1);
        $fieldId2 = $this->getCustomFieldModule()->create($this->kanbanId, $this->adminId, 'Unit Test', 'checkbox', 1);
        $optionId1 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option1');
        $optionId2 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option2');
        $optionId3 = $this->getCustomFieldModule()->addDropdownFieldOption($fieldId, $this->adminId, 'Option3');

        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId1, $fieldId, $optionId1);
        $this->assertTrue($set > 0);
        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId3, $fieldId, $optionId1);
        $this->assertTrue($set > 0);
        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId2, $fieldId, $optionId3);
        $this->assertTrue($set > 0);
        $set = $this->getTaskCustomeFieldModule()->setVal($this->kanbanId, $taskId1, $fieldId2, '1');
        $this->assertTrue($set > 0);

        $taskFieldVals = $this->getTaskCustomeFieldModule()->getTaskCustomFieldsVal($taskId1);
        foreach ($taskFieldVals as $taskFieldVal) {
            if ($taskFieldVal->task_id == $taskId1 && $taskFieldVal->field_id == $fieldId) {
                $this->assertEquals($taskFieldVal->val, $optionId1);
            }
            if ($taskFieldVal->task_id == $taskId2 && $taskFieldVal->field_id == $fieldId) {
                $this->assertEquals($taskFieldVal->val, $optionId3);
            }

            if ($taskFieldVal->task_id == $taskId1 && $taskFieldVal->field_id == $fieldId2) {
                $this->assertEquals($taskFieldVal->val, '1');
            }
        }

        $delOption1 = $this->getCustomFieldModule()->delDropdownFieldVal($optionId1);
        $this->assertTrue($delOption1 == 1);
        $valNotExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId1, $fieldId);
        $this->assertNull($valNotExists);
        $valNotExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId3, $fieldId);
        $this->assertNull($valNotExists);
        $valExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId1, $fieldId2);
        $this->assertNotNull($valExists);
        $this->assertNotEmpty($valExists);
        $valExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId2, $fieldId);
        $this->assertNotNull($valExists);
        $this->assertNotEmpty($valExists);

        $del = $this->getCustomFieldModule()->delCustomField($fieldId);
        $this->assertTrue($del);

        $valNotExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId1, $fieldId);
        $this->assertNull($valNotExists);
        $valNotExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId2, $fieldId);
        $this->assertNull($valNotExists);

        $valExists = $this->getTaskCustomeFieldModule()->getTaskCustomFieldVal($taskId1, $fieldId2);
        $this->assertNotNull($valExists);
        $this->assertNotEmpty($valExists);
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
