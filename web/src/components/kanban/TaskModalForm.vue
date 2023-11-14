<template>
  <a-modal
    :title="title"
    :visible="visible"
    :confirm-loading="confirmLoading"
    :cancel-text="cancelText"
    :ok-text="submitText"
    @ok="handleOk"
    @cancel="handleCancel"
  >
    <a-form :form="form">
      <a-form-item
        :label-col="formItemLayout.labelCol"
        :wrapper-col="formItemLayout.wrapperCol"
        label="Name"
      >
        <a-input
          v-decorator="[
            'username',
            { rules: [{ required: true, message: 'Please input your name' }] },
          ]"
          placeholder="Please input your name"
        />
      </a-form-item>
      <a-form-item
        :label-col="formItemLayout.labelCol"
        :wrapper-col="formItemLayout.wrapperCol"
        label="Nickname"
      >
        <a-input
          v-decorator="[
            'nickname',
            { rules: [{ required: checkNick, message: 'Please input your nickname' }] },
          ]"
          placeholder="Please input your nickname"
        />
      </a-form-item>
      <!-- <a-form-item :label-col="formTailLayout.labelCol" :wrapper-col="formTailLayout.wrapperCol">
                <a-checkbox :checked="checkNick" @change="handleChange">
                    Nickname is required
                </a-checkbox>
            </a-form-item>
            <a-form-item :label-col="formTailLayout.labelCol" :wrapper-col="formTailLayout.wrapperCol">
                <a-button type="primary" @click="check">
                    Check
                </a-button>
            </a-form-item> -->
    </a-form>
  </a-modal>
</template>

<script>
const formItemLayout = {
  labelCol: { span: 4 },
  wrapperCol: { span: 20 },
};
const formTailLayout = {
  labelCol: { span: 4 },
  wrapperCol: { span: 8, offset: 4 },
};
export default {
    props: [
        'task',
        'visible',
        'flowId',
    ], 
    data() {
        return {
            title: "New Task",
            cancelText: "Cancel",
            submitText: "Submit",
            ModalText: "test",
            confirmLoading: false,
            formItemLayout,
            formTailLayout,
            form: this.$form.createForm(this, { title: 'dynamic_rule' }),
        };
    },
    methods: {
        checkNick: function() {
            return true;
        },
        handleCancel: function() {
            // this.form.resetFields();
            this.$emit('canceled'); // 付组件通过v-on:canceled 监听当前组件canceled事件
        },
        handleOk: function() {
            this.confirmLoading = true;
            setTimeout(() => {
                this.confirmLoading = false;
                this.$emit('submited', 'new task');
            }, 2000);
        }
    }
}
</script>