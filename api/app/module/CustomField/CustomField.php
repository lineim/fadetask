<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\module\CustomField;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\common\toolkit\ArrayHelper;
use app\model\CustomField as ModelCustomField;
use app\model\CustomFieldOption;
use app\module\BaseModule;
use support\Db;

class CustomField extends BaseModule
{

    const SUPPORT_TYPE_CHECKBOX = 'checkbox';
    const SUPPORT_TYPE_DROPDOWN = 'dropdown';
    const SUPPORT_TYPE_TEXT     = 'text';
    const SUPPORT_TYPE_DATETIME = 'datetime';
    const SUPPORT_TYPE_NUMBER   = 'number';

    const MAX_COUNT = 10; // 每个类型两个字段
    const MAX_NAME_LEN = 32; // 自定义字段名称最大长度

    public function getField($id, $fields = ['*'])
    {
        $field = ModelCustomField::where('id', $id)
            ->first($fields);
        if (!$field) {
            return $field;
        }
        if ($field->type == self::SUPPORT_TYPE_DROPDOWN) {
            $options = CustomFieldOption::where('field_id', $id)->get();
            $field->options = $options;
        }
        return $field;
    }

    public function fieldExist($id) : bool
    {
        return ModelCustomField::where('id', $id)->exists();
    }

    /**
     * 创建自定义字段.
     *
     * @param $kanbanId    int    看板id.
     * @param $userId      int    用户id.
     * @param $name        string 字段名称.
     * @param $type        string 字段类型.
     * @param $showOnFront mixed  会转换成0或者1.
     *
     * @return int 失败返回0, 成功返回新字段id.
     *
     * @throws AccessDeniedException
     * @throws BusinessException
     * @throws InvalidParamsException
     */
    public function create(int $kanbanId, int $userId, string $name, string $type, $showOnFront) : int
    {
        if (!$this->getKanbanModule()->isAdmin($kanbanId, $userId)) {
            throw new AccessDeniedException();
        }
        if (mb_strlen($name) > self::MAX_NAME_LEN || mb_strlen($name) < 1) {
            throw new InvalidParamsException('custom_fields.error.name_error');
        }
        if (!in_array($type, $this->getSupportTypes())) {
            throw new BusinessException('custom_fields.not_support_type');
        }

        if ($this->kanbanHasField($kanbanId, $name, $type)) {
            throw new BusinessException('custom_fields.existed');
        }

        if ($this->getCustomFieldCount($kanbanId) >= self::MAX_COUNT) {
            throw new BusinessException('custom_fields.error.max_count');
        }

        $count = $this->getCustomFieldCount($kanbanId);

        $model = new ModelCustomField();
        $model->name = $name;
        $model->type = $type;
        $model->user_id = $userId;
        $model->kanban_id = $kanbanId;
        $model->show_on_card_front = $showOnFront ? 1 : 0;
        $model->created_time = time();
        $model->sort = $count + 1;

        $saved = $model->save();

        return $saved ? $model->id : 0;
    }

    /**
     * 修改自定义字段.
     *
     * @param $fieldId    integer 字段id.
     * @param $updateData array   修改内容，只能修name和showOnFront.
     * @param $userId     integer 用户id.
     * @return int 更新响应条数.
     *
     * @throws AccessDeniedException
     * @throws BusinessException
     * @throws InvalidParamsException
     * @throws ResourceNotFoundException
     */
    public function update(int $fieldId, array $updateData, int $userId) : int
    {
        if (isset($updateData['name'])) {
            $name = $updateData['name'];
            if (mb_strlen($name) > self::MAX_NAME_LEN || mb_strlen($name) < 1) {
                throw new InvalidParamsException('custom_fields.error.name_error');
            }
        }
        if (isset($updateData['showOnFront'])) {
            $updateData['show_on_card_front'] = $updateData['showOnFront'] ? 1 : 0;
        }

        $field = $this->getField($fieldId, ['id', 'name', 'kanban_id']);
        if (!$field) {
            throw new ResourceNotFoundException('custom_fields.not_found');
        }

        if (!$this->getKanbanModule()->isAdmin($field->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }

        $updateData = ArrayHelper::parts($updateData, ['name', 'show_on_card_front']);
        if (!$updateData) {
            return 0;
        }

        if (!empty($updateData['name'])) {
            $exist = ModelCustomField::where('kanban_id', $field->kanban_id)
                ->where('id', '!=', $fieldId)
                ->where('name', $updateData['name'])
                ->exists();
            if ($exist) {
                throw new BusinessException('custom_fields.name_exist');
            }
        }

        $updateData['updated_time'] = time();
        
        return ModelCustomField::where('id', $fieldId)->update($updateData);
    }

    /**
     * 根据字段id的顺序刷新所有字段的排序.
     * 
     * @param array $fieldIds array 字段id列表, 顺序会按照id在数组中的顺序自增排列.
     * @return boolean
     *
     * @throws BusinessException
     */
    public function sortByIdSeq(array $fieldIds)
    {
        $sort = 1;
        Db::beginTransaction();
        try {
            foreach ($fieldIds as $id) {
                $updated = ModelCustomField::where('id', $id)->update(['sort' => $sort, 'updated_time' => time()]);
                if (false === $updated) {
                    throw new BusinessException('system_error');
                }
                $sort ++;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            $this->getLogger()->error(sprintf('update custome field failed, id %d, sort %d', $id, $sort));
            throw $e;
        }        
    }

    /**
     * 删除自定义字段.
     *
     * @param $id int 字段id.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function delCustomField($id) : bool
    {
        Db::beginTransaction();
        try {
            $delField = ModelCustomField::where('id', $id)->delete();
            if (false === $delField) {
                throw new \Exception('Del Custom Field Failed!');
            }
            $delVal = $this->getTaskCustomeFieldModule()->delByFieldId($id);
            if (false === $delVal) {
                throw new \Exception('Del Card Custom Field Val Failed!');
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function addDropdownFieldOption($fieldId, $userId, $val, $color = '') : int
    {
        $field = $this->getField($fieldId, ['id', 'type', 'kanban_id']);
        if (!$field) {
            throw new ResourceNotFoundException('custom_fields.not_found');
        }
        if (!$this->getKanbanModule()->isAdmin($field->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        if ($field->type !== self::SUPPORT_TYPE_DROPDOWN) {
            throw new BusinessException('custom_fields.not_support_type');
        }

        $model = new CustomFieldOption();
        $model->field_id = $fieldId;
        $model->val = $val;
        $model->color = $color;
        $model->user_id = $userId;
        $model->updated_time = 0;
        $model->created_time = time();

        return !$model->save() ? 0 : $model->id;
    }

    public function batchAddDropdownFieldOptions($fieldId, $userId, array $optionNames) : bool
    {
        $field = $this->getField($fieldId, ['id', 'type', 'kanban_id']);
        if (!$field) {
            throw new ResourceNotFoundException('custom_fields.not_found');
        }
        if (!$this->getKanbanModule()->isAdmin($field->kanban_id, $userId)) {
            throw new AccessDeniedException();
        }
        if ($field->type !== self::SUPPORT_TYPE_DROPDOWN) {
            throw new BusinessException('custom_fields.not_support_type');
        }
        $options = [];
        foreach ($optionNames as $name) {
            $options[] = [
                'field_id' => $fieldId,
                'val' => $name,
                'color' => '', // 预留字段
                'user_id' => $userId,
                'updated_time' => 0,
                'created_time' => time(),
            ];
        }
        return CustomFieldOption::insert($options);
    }

    public function updateDropdownFieldVal($id, $val, $color = '')
    {
        return CustomFieldOption::where('id', $id)
            ->update(['id' => $id, 'val' => $val, 'updated_time' => time()]);
    }

    public function getDropdwonFieldOption($id, array $field = ['*'])
    {
        return CustomFieldOption::where('id', $id)->first($field);
    }

    /**
     * 删除选项, 删除时需要清理相关卡片的自定义字段的值.
     *
     * @param integer $id 下拉自定义字段的选型id.
     *
     * @return boolean
     * @throws \Exception
     */
    public function delDropdownFieldVal($id)
    {
        $option = $this->getDropdwonFieldOption($id, ['field_id']);
        if (!$option) {
            return true;
        }
        Db::beginTransaction();
        try {
            $delOption = CustomFieldOption::where('id', $id)->delete();
            if (false === $delOption) {
                throw new \Exception('Del Custom Field Dropdown Option Failed!');
            }
            $delVal = $this->getTaskCustomeFieldModule()->delSpecifiedFieldVal($option->field_id, $id);
            if (false === $delVal) {
                throw new \Exception('Del Card Custom Field Dropdown Option Val Failed!');
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function getCustomFieldCount($kanbanId)
    {
        return ModelCustomField::where('kanban_id', $kanbanId)->count();
    }

    public function getKanbanCustomFields($kanbanId, $field = ['*'])
    {
        if (!in_array('*', $field) && !in_array('type', $field)) {
            $field[] = 'type';
        }
        $field[] = 'id';
        $fields = ModelCustomField::where('kanban_id', $kanbanId)
            ->orderBy('sort', 'ASC')
            ->get($field);
        $dropdownFieldIds = [];
        foreach ($fields as $field) {
            if ($field->type == self::SUPPORT_TYPE_DROPDOWN) {
                $dropdownFieldIds[] = $field->id;
            }
        }
        $options = [];
        if ($dropdownFieldIds) {
            $options = CustomFieldOption::whereIn('field_id', $dropdownFieldIds)->get(['id', 'field_id', 'val', 'color']);
            $options = ArrayHelper::group($options->toArray(), 'field_id');
        }
        foreach ($fields as &$field) {
            if ($field->type == self::SUPPORT_TYPE_DROPDOWN) {
                $field->options = isset($options[$field->id]) ? $options[$field->id] : [];
            }
        }
        
        return $fields;
    }

    public function kanbanHasField($kanbanId, $name, $type)
    {
        return ModelCustomField::where('kanban_id', $kanbanId)
            ->where('name', $name)
            ->where('type', $type)
            ->exists();
    }

    public function getSupportTypes()
    {
        return [
            self::SUPPORT_TYPE_CHECKBOX,
            self::SUPPORT_TYPE_DROPDOWN,
            self::SUPPORT_TYPE_TEXT,
            self::SUPPORT_TYPE_DATETIME,
            self::SUPPORT_TYPE_NUMBER,
        ];
    }

}
