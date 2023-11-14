<template>
  <div>
    <a-page-header
      style="border-bottom: 1px solid rgb(235, 237, 240)"
      title="登录设置"
      back-icon=""
      @back="() => null"
    />
  
    <a-tabs default-active-key="1">
      <a-tab-pane
        key="1"
        tab="钉钉登录"
      >
        <Dingtalk />
      </a-tab-pane>
      <a-tab-pane
        key="2"
        tab="企业微信登录"
        force-render
      >
        <Wework />
      </a-tab-pane>
    </a-tabs>
  </div>
</template>
<script>
import Dingtalk from '@/components/login-setting/Dingtalk.vue';
import Wework from '@/components/login-setting/Wework.vue';

export default {
  components: {
    Dingtalk,
    Wework
  }
}
</script>
