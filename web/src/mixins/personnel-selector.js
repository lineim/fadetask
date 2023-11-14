import { mapActions, mapState } from 'vuex';
import Api from '@/api';

export default {
  data() {
    return {
     
    };
  },
  created() {
  },
  computed: {
    ...mapState('personnelSelector', {
      orgTreeData: state => state.orgTreeData,
      usersData: state => state.usersData,
    })
  },
  methods: {
    ...mapActions('personnelSelector', ['getTreeData', 'getUsersData']),
    searchUser(value) {
      value = value === '' ? -1 : value;
      this.username = value;
      setTimeout(() => {
        if (this.username === value) {
          Api.getUsers({
            params: {
              keyword: this.username
            }
          }).then(res=> {
            this.users = res;
          })
        }
      }, 200);
    },
    onSelect () {
      this.users = [];
    }
  },
};