<template>
  <div 
    class="custom-field mbl"
    v-if="customfields.length > 0"
  >
    <div 
      class="field"
      v-for="field in customfields"
      :key="field.id"
    >
      <div class="field-name">
        <a-icon :type="typeIcons[field.type]" /> {{ field.name }}
      </div>
      <div class="field-val">
        <a-input
          v-if="field.type == 'text'"
          v-model="fieldVal[field.id]"
          style="width: 100%;"
          @keyup.enter="fieldValChange(field.id, $event)"
          @blur="fieldValChange(field.id, $event)"
        />

        <a-input-number 
          v-if="field.type == 'number'" 
          v-model="fieldVal[field.id]"
          style="width: 100%;"
          @keyup.enter="fieldValChange(field.id, $event)"
          @blur="fieldValChange(field.id, $event)"
        />
        
        <LiCheckBox
          v-if="field.type == 'checkbox'"
          :checked="fieldVal[field.id] && fieldVal[field.id] !== '0' ? true : false"
          @change="checkboxValChange"
          :value="field.id"
          :label="fieldVal[field.id] ? $t('form.checkbox_checked') : $t('form.checkbox_uncheck')"
          :styles="{height: '30px', lineHeight: '30px'}"
        />
          

        <a-date-picker 
          v-if="field.type == 'datetime'"
          v-model="fieldVal[field.id]"
          @change="customDateFieldChange(field.id)" 
        />
      
        <a-select
          v-if="field.type == 'dropdown'"
          style="width: 100%;"
          :default-value="{ key: fieldVal[field.id] }"
          v-model="fieldVal[field.id]"
          @change="dropdownOptionChange"
        >
          <a-select-option
            value=""
            key=""
            :fieldid="field.id"
          >
            --
          </a-select-option>
          <a-select-option
            v-for="option in field.options"
            :value="option.id"
            :key="option.id"
            :fieldid="field.id"
          >
            {{ option.val }}
          </a-select-option>
        </a-select>
      </div>
    </div>
  </div>
</template>
  
<script>
  import api from "@/api";
  import moment from 'moment-timezone';
  import LiCheckBox from '@/components/form/LiCheckBox.vue';
  moment.tz.setDefault("Asia/Shanghai");

    const TYPE_ICON = {
        checkbox: 'check-square',
        dropdown: 'unordered-list',
        datetime: 'calendar',
        number: 'number',
        text: 'file-text',
    };

    export default {
        components: {
          LiCheckBox
        },
        props: {
          cardId: {
            type: Number,
            default: 0,
            required: false
          },

          customfields: {
            type: Array,
            default: () => [],
            required: false
          },

          customfieldvals: {
            type: Object,
            default: () => {},
            required: false
          },

          wrapStyle: {
            type: Object,
            default: () => {},
            required: false
          }
        },
        data() {
          return {
            typeIcons: TYPE_ICON,
            fieldVal: {},
          };
        },
        created() {
          for (let i = 0; i < this.customfields.length; i ++) {
            const field = this.customfields[i];
            if (field.id in this.customfieldvals) {
              if (field.type == 'datetime') {
                this.fieldVal[field.id] = moment(this.customfieldvals[field.id]);
              } else if (field.type == 'dropdown') {
                const opts = field.options;
                let showVal = "";
                for (let k = 0; k < opts.length; k ++) {
                  let opt = opts[k];
                  let cardFieldVal = this.customfieldvals[field.id];
                  if (opt.id == cardFieldVal) {
                    showVal = opt.val;
                    break;
                  }
                }
                this.fieldVal[field.id] = showVal;
              } else {
                this.fieldVal[field.id] = this.customfieldvals[field.id];
              }
            } else {
              if (field.type == 'datetime') {
                this.fieldVal[field.id] = null;
              } else {
                this.fieldVal[field.id] = "";
              }
            }
          }
        },

        computed: {
            
        },
        methods: {
          fieldValChange: function(fieldId, evt) {
            let fieldVal = this.fieldVal[fieldId];
            api.setCardFieldVal({query: {cardId: this.cardId, id: fieldId}, data: {val: fieldVal}}).then(() => {
              this.$emit('field_val_change', fieldId, fieldVal);
              evt.target.blur();
            }).catch(e => {
              console.error(e);
            });
          },

          customDateFieldChange: function(fieldId) {
            const dateMoment = this.fieldVal[fieldId];
            const year = dateMoment.year();
            const month = dateMoment.month() + 1; // month 返回0到11之间的数字
            const day = dateMoment.date();

            let date = String(year) + '-' + String(month) + '-' + String(day);

            api.setCardFieldVal({query: {cardId: this.cardId, id: fieldId}, data: {val: date}}).then(() => {
              this.$emit('field_val_change', fieldId, date);
            }).catch(e => {
              console.error(e);
            });
          },

          dropdownOptionChange: function(val, opts) {
            const fieldId = opts.data.attrs.fieldid;
            const fieldVal = val;
            api.setCardFieldVal({query: {cardId: this.cardId, id: fieldId}, data: {val: fieldVal}}).then(() => {
              this.$emit('field_val_change', fieldId, fieldVal);
            }).catch(e => {
              console.error(e);
            });
          },

          checkboxValChange: function(fieldId, val) {
            api.setCardFieldVal({query: {cardId: this.cardId, id: fieldId}, data: {val: val}}).then(() => {
              this.fieldVal[fieldId] = val;
              this.$emit('field_val_change', fieldId, val);
            }).catch(e => {
              console.error(e);
            });
          }
        }
    }
</script>
<style scoped lang="less">
.custom-field {
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  .field {
    display: flex;
    .field-name {
      width: 30%;
      border-bottom: 1px solid #d9d9d9;
      border-right: 1px solid #d9d9d9;
      padding: 10px 20px;
      height: 48px;
    }
    .field-val {
      flex: 1;
      border-bottom: 1px solid #d9d9d9;
      border-right: 1px solid #d9d9d9;
      padding: 10px 20px;
      height: 48px;
    }
    .field-val:last-child {
      border-right: none;
    }
    &:last-child {
      .field-name {
        border-bottom: none;
      }
      .field-val {
        border-bottom: none;
      }
    }
  }
}

.checkbox {
  height: 30px;
  line-height: 30px;
}
</style>
