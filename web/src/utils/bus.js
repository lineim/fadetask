export default {
  install(Vue) {
    const bus = new Vue()
    Object.defineProperties(bus, {
      on: {
        get() {
          return this.$on
        }
      },
      once: {
        get() {
          return this.$once
        }
      },
      off: {
        get() {
          return this.$off
        }
      },
      emit: {
        get() {
          return this.$emit
        }
      }
    })
    Vue.bus = bus
    Object.defineProperty(Vue.prototype, "$bus", {
      get() {
        return bus
      }
    });
  }
}