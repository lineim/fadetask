<template>
  <a-popover 
    :destroy-tooltip-on-hide="true"
    @visibleChange="visibleChange"
    overlay-class-name="custom-field-popover no-arrow-popover"
    placement="bottomLeft" 
    trigger="click"
  >
    <div
      class="popover-title"
      slot="title"
    >
      <span
        class="mrs cursor-pointer"
        v-if="isEditModel"
        @click="closeEditModel"
      ><a-icon type="left" /></span>
      {{ titlePrefix }}{{ title }}
    </div>
    <div
      slot="content"
      class="lg-popover-content"
    >
      <div v-if="!isEditModel">
        <a-empty
          v-if="customfields.length < 1"
          :image="emptyImg"
          :description="$t('custom_fields.empty')"
        />
        
        <a-list
          v-if="customfields.length > 0"
          :data-source="customfields"
          class="custom-field-list"
        >
          <a-list-item
            slot="renderItem"
            slot-scope="item"
            class="custom-field-item"
            style="border-bottom: 0px;"
            @click="editField(item)"
          >
            <a-icon
              :type="typeIconMap[item.type]"
              class="mrs"
            />
            {{ item.name }}
            <a-icon
              type="right"
              class="pull-right"
            />
          </a-list-item>
        </a-list>

        <a-button
          class="mvm"
          type="primary"
          icon="plus"
          @click="newLabel"
          :disabled="!isAdmin"
          block
        >
          {{ $t('custom_fields.new') }}
        </a-button>
      </div>
    
      <div
        v-if="isEditModel"
        class="custom-field-form"
      >
        <a-form-model
          layout="vertical"
          :model="form"
          :rules="rules"
          v-bind="{}"
        >
          <a-form-model-item
            class="mbs"
            :label="$t('custom_fields.form.name')"
            prop="name"
            ref="name"
          >
            <a-input
              v-model="form.name"
              :disabled="!isAdmin"
              v-focus
              ref="customFieldName"
              @keyup.enter="nameOnEnter('customFieldName')"
              @blur="nameOnBlur()"
              :placeholder="$t('custom_fields.form.name_placeholder')"
            />
          </a-form-model-item>
          <a-form-model-item
            class="mbs"
            :label="$t('custom_fields.form.type')"
          >
            <a-select
              label-in-value
              :default-value="{ key: typeDefaultVal }"
              @change="typeChange"
              :disabled="typeDisabled"
            >
              <a-select-option
                v-for="t in supportTypes"
                :value="t.type"
                :key="t.type"
              >
                {{ $t(t.name) }}
              </a-select-option>
            </a-select>
          </a-form-model-item>

          <a-form-model-item
            class="mbs"
            :label="$t('custom_fields.form.options')"
            v-if="showOptions"
          >
            <div class="mbm">
              <div
                class="option"
                v-for="option in dropdowOptions"
                :key="option.id"
              >
                <div class="text">
                  <a-input
                    v-model="option.val"
                    @blur="optionBlur(option.id, option.val)"
                  />
                </div>

                <div
                  v-if="isAdmin"
                >
                  <a-icon
                    class="cursor-pointer"
                    type="delete"
                    @click="delOption(option.id)"
                  />
                </div>
              </div>

              <div
                class="option"
                v-for="(option, index) in addOptions"
                :key="index"
              >
                <div class="text">
                  <a-input
                    v-model="addOptions[index]"
                  />
                </div>
                
                <div class="display-flex">
                  <a-icon
                    class="cursor-pointer"
                    type="delete"
                    @click="addOptionRemoved(index)"
                  />
                </div>
              </div>
            </div>

            <div
              v-if="isAdmin"
              class="d-flex flex-jc-sp"
            >
              <a-input
                class="mrs"
                v-model="currentOption"
                :placeholder="$t('custom_fields.form.new_option')"
                @keyup.enter="newOptions"
                v-focus
              />
              <a-button 
                :disabled="currentOption.length < 1"
                @click="newOptions"
              >
                Add
              </a-button>
            </div>
          </a-form-model-item>

          <a-form-model-item
            class="mbs"
          >
            <a-checkbox
              :checked="showFrontCardChecked"
              @change="showFrontCardChange"
              :disabled="!isAdmin"
            >
              {{ $t('custom_fields.form.show_front') }}
            </a-checkbox>
          </a-form-model-item>
    
          <a-form-model-item class="mb0">
            <a-button
              v-if="form.id <= 0"
              type="primary"
              @click="submit"
              :disabled="(form.name.length < 1 || form.name.length > 32) || btnDisabled"
              block
            >
              {{ $t('form.save') }}
            </a-button>

            <a-popconfirm
              :title="$t('custom_fields.del_field_tips')"
              :ok-text="$t('yes')"
              :cancel-text="$t('no')"
              @confirm="delField(form.id)"
            >
              <a-button
                type="danger"
                block
                v-if="form.id > 0"
                :disabled="!isAdmin"
              >
                {{ $t('delete') }}
              </a-button>
            </a-popconfirm>
          </a-form-model-item>
        </a-form-model>
      </div>

      <a-alert
        v-if="!isAdmin"
        :message="$t('custom_fields.no_permission_tips')"
        banner
      />
    </div>
    <slot name="trigger">
      <a-button 
        size="small" 
        icon="form" 
        style="margin-bottom: 10px;" 
        block
      >
        {{ $t('custom_fields.label') }}
      </a-button>
    </slot>
  </a-popover>
</template>

<script>
    const EMPTY_LABEL = {name: "", type: "checkbox", show_front_card: 1, id: 0};
    const TYPE_ICON = {
        checkbox: 'check-square',
        dropdown: 'unordered-list',
        datetime: 'calendar',
        number: 'number',
        text: 'file-text',
    };
    const EDIT_MODEL_ADD = 'add';
    const EDIT_MODEL_EDIT = 'edit';

    const SUPPORT_TYPES = [
       {
        type: 'checkbox',
        name: 'form.checkbox_name',
       },
       {
        type: 'datetime',
        name: 'form.date_name',
       },
       {
        type: 'dropdown',
        name: 'form.dropdown_name',
       },
       {
        type: 'number',
        name: 'form.number_name',
       },
       {
        type: 'text',
        name: 'form.text_name',
       },
    ];

    import { Empty } from 'ant-design-vue';
    import api from "@/api";
    import i18n from '../../i18n';
    import store from '@/store';
    import { removeArrItem } from '../../utils';

    export default {
        props: {
          kanbanId: {
            type: String,
            default: '',
            required: false
          },

          title: {
            type: String,
            default: '',
            required: true
          },

          customfields: {
            type: Array,
            default: () => [],
            required: false
          },

          colors: {
            type: Array,
            default: () => [],
            required: false
          },

        },
        data() {
          return {
            editModel: false,
            form: EMPTY_LABEL,
            selectedColor: '',
            titlePrefix: '',
            btnDisabled: false,
            emptyImg: Empty.PRESENTED_IMAGE_SIMPLE,
            supportTypes: SUPPORT_TYPES,
            typeIconMap : TYPE_ICON,
            showFrontCardChecked: false,
            typeDisabled: false,
            typeDefaultVal: 'checkbox',
            currentEditField: {},
            dropdowOptions: [],
            addOptions: [],
            showOptions: false,
            currentOption: '',
            rules: {
              name: [
                {required: false, message: "请输入名称", trigger: 'blur'},
                {max: 32, message: "名称长度不能超过32个字符", trigger: 'blur'}
              ]
            }
          };
        },
        created() {
          let colors = this.colors;
          let tmpColors = colors.slice(0, 1);
          this.selectedColor = tmpColors.shift();
          this.showOptions = false;
        },

        computed: {
          isAdmin: function() {
            return store.state.board.user_role_admin;
          },
          isEditModel: function() {
            return this.editModel;
          },
          colorSelected() {
            return function (color) {
                return color == this.selectedColor;
            }
          },
          defaultChecked() {
            const checked = this.form.id > 0 ? this.form.show_front_card : true;
            return checked;
          }
        },
        methods: {

            closeEditModel: function() {
              this.titlePrefix = '';
              this.form = EMPTY_LABEL;
              this.showOptions = false;
              this.editModel = false;
              this.addOptions = [];
            },

            newLabel: function() {
              this.titlePrefix = i18n.t('add');
              this.showFrontCardChecked = false;
              this.typeDisabled = false;
              this.showOptions = false;
              this.dropdowOptions = [];
              this.editModel = EDIT_MODEL_ADD;
              this.typeDefaultVal = 'checkbox';
            },

            editField: function(field) {
              this.titlePrefix = i18n.t('edit');
              this.currentEditField = field.name;
              this.form = field;
              this.form.show_front_card = field.show_on_card_front;
              this.showFrontCardChecked = !!field.show_on_card_front;
              this.editModel = EDIT_MODEL_EDIT;
              this.typeDisabled = true;
              this.typeDefaultVal = field.type;
              this.addOptions = [];
              if (field.type == 'dropdown') {
                this.dropdowOptions = field.options;
                this.showOptions = true;
              } else {
                this.showOptions = false;
              }
            },

            showFrontCardChange: function(e) {
              this.form.show_front_card = e.target.checked;
              this.showFrontCardChecked = !!e.target.checked;
              if (this.form.id > 0) { // 当form.id大于0时，为编辑模式.
                api.editCustomField({query: {id: this.form.id}, data: {showOnFront: e.target.checked}}).then(() => {
                  this.$emit('showfrontchanged', this.form.name);
                }).catch(e => {
                  console.error(e);
                });
              }
            },

            nameOnEnter: function(ref) {
              this.$refs[ref].blur();
            },

            nameOnBlur: function() {
              if (!this.isAdmin || this.form.id < 1) {
                return;
              }
              api.editCustomField({query: {id: this.form.id}, data: {name: this.form.name}}).then(() => {
                this.$emit('namechanged', this.form.name);
              }).catch(e => {
                // this.form.name = this.currentEditField;
                console.error(e);
              });
            },

            newOptions: function() {
              if (this.currentOption.length < 1) {
                return;
              }
              if (this.form.id > 0) {
                api.addFieldOption({query: {fieldId: this.form.id}, data: {val: this.currentOption}}).then((opt) => {
                  this.dropdowOptions.push(opt);
                  this.currentOption = '';
                  this.$emit('optionadded', this.form.id, opt);
                });
              } else {
                let tmp = this.addOptions;
                let opts = [...tmp, this.currentOption];
                this.addOptions = [];
                this.addOptions = opts;
                this.currentOption = '';
              }
            },

            delOption: function(id) {
              api.delCustomFieldOption({query: {id: id}}).then(()=> {
                this.dropdowOptions = removeArrItem(this.dropdowOptions, function(item) {
                  return item.id == id;
                });
                this.$emit('optiondel', this.form.id, id);
              }).catch(e => {
                console.error(e);
              });
            },

            addOptionRemoved: function(index) {
              this.addOptions = this.addOptions.slice(index, 1);
            },

            optionBlur: function(id, val) {
              api.setFieldOption({query:{id: id}, data:{val: val}}).then(() => {
                this.$emit('optionchange', this.form.id, id, val);
              }).catch(e => {
                console.error(e);
              });
            },

            selectColor: function(color) {
              this.selectedColor = color;
            },
            submit: function() {
              this.btnDisabled = true;
              let data = this.form;
              data.options = this.addOptions;
              if (this.editModel == EDIT_MODEL_ADD) {
                api.createCustomField({
                    query: {kanbanId: this.kanbanId},
                    data: data
                }).then((newLabel) => {
                  this.btnDisabled = false;
                  this.closeEditModel();
                  this.restForm();
                  this.$emit('newcustomfield', newLabel);
                  this.$message.success(i18n.t('custom_fields.form.add_success_msg'));
                }).catch((e)=> {
                  console.error(e);
                  this.btnDisabled = false;
                });
              } else {
                api.kanbanLabelEdit({
                  query: {kanbanId: this.kanbanId, id: data.id}, 
                  data: data
                }).then((newLabel) => {
                  this.btnDisabled = false;
                  this.closeEditModel();
                  if (!newLabel) {
                    return;
                  }
                  this.$emit('labelupdated', newLabel);
                });
              }
            },

            restForm: function() {
              this.form.name = '';
              this.form.type = 'checkbox';
            },

            delField: function(id) {
              api.delCustomField({query: {id: id}}).then(() => {
                this.closeEditModel();
                this.$emit('customfieldeleted', id);
              });
            },

            typeChange: function(type) {
              this.form.type = type.key;
              if (type.key == 'dropdown') {
                this.showOptions = true;
              } else {
                this.showOptions = false;
              }
            },

            visibleChange: function(v) {
              if (!v) {
                this.closeEditModel();
              }
            }
        }
    }
</script>

<style scoped>
.modal-label-container {
  max-height: 260px;
  overflow-y: overlay;
}

.modal-label-list {
  height: 37px;
  color: #ffffff;
  overflow-y: overlay;
  /* margin-right: 40px; */
}

.modal-label-color-block {
  border-radius: 10px;
  /* width: 180px; */
}

.custom-field-item {
  padding-left: 5px; 
  padding-right: 5px; 
  padding-top: 8px;
  padding-bottom: 8px;
  margin-bottom: 5px; 
  border-bottom: 0px;
  background: rgb(249, 250, 251);
  cursor: pointer;
  text-overflow :ellipsis; /*让截断的文字显示为点点。还有一个值是clip意截断不显示点点*/
  white-space :nowrap; /*让文字不换行*/
  overflow : hidden; /*超出要隐藏*/
}

.custom-field-item-name {
  text-overflow :ellipsis; /*让截断的文字显示为点点。还有一个值是clip意截断不显示点点*/
  white-space :nowrap; /*让文字不换行*/
  overflow : hidden; /*超出要隐藏*/
  text-align: left;
  display: inline-block;
}
.custom-field-item:hover {
  background: #efefef;
}

.modal-label-edit-icon {
  color: black;
}

.option {
  display: flex;
  align-items: center;
  height: 32px;
  line-height: 32px;
  padding: 0px 10px;
  justify-content: space-between;
}

.option input {
  border: 0px;
}

.option input:focus {
  border: 1px solid #40a9ff;
  -webkit-box-shadow: 0 0 0 2px rgb(24 144 255 / 20%);
  box-shadow: 0 0 0 2px rgb(24 144 255 / 20%);
}

.option .text {
  text-overflow :ellipsis; /*让截断的文字显示为点点。还有一个值是clip意截断不显示点点*/
  white-space :nowrap; /*让文字不换行*/
  overflow : hidden; 
}

.label-pop-color-select {
  margin-right: 10px;
  height: 32px;
  width: 60px;
  display: inline-block;
  text-align: center;
  vertical-align: top;
  line-height: 32px;
  color: #ffffff;
  border-radius: 4px;
  margin-bottom: 10px;
  cursor: pointer;
}

</style>