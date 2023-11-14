<template>
  <div>
    <a-row>
      <a-col :span="3">
        <a-menu
          v-model="current"
          mode="inline"
        >
          <a-menu-item key="user">
            <router-link :to="{name: 'SettingUser'}">
              <a-icon type="user" />用户管理
            </router-link>
          </a-menu-item>
          <a-menu-item key="loginLog">
            <router-link :to="{name: 'LoginLog'}">
              <a-icon type="container" />登录日志
            </router-link>
          </a-menu-item>
          <a-menu-item key="loginSetting">
            <router-link :to="{name: 'SettingLogin'}">
              <a-icon type="login" />登录设置
            </router-link>
          </a-menu-item>
          <a-menu-item key="mailSetting">
            <router-link :to="{name: 'SettingLogin'}">
              <a-icon type="mail" />邮箱设置
            </router-link>
          </a-menu-item>
          <a-menu-item key="sysSetting">
            <a-icon type="setting" />站点设置
          </a-menu-item>
        </a-menu>
      </a-col>
      <a-col 
        :offset="1"
        :span="20" 
        :style="{'background-color': '#ffffff'}"
      >
        <router-view />
      </a-col>
    </a-row>
  </div>
</template>
<script>
export default {
    data() {
        return {
            current: ['user'],
        };
    }
}
</script>