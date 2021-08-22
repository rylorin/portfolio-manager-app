//import Hello from './Hello.js'
const Hello = () => import('./Hello.js');

class TestClassSyntax {

}

export default {
  name: 'app',
  template: `
    <div class="container mx-auto p-4">
      <h1>Hello World</h1>
      <hello></hello>
    </div>
  `,
  components: {
    Hello
  }
}
