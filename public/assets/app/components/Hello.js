export default {
  name: 'hello',
  template: `
  <div class="hello">
    <h1>{{ msg }}</h1>
  </div>
  `,
  data () {
    return {
      msg: 'Welcome to Your Vue.js App'
    }
  }
}
