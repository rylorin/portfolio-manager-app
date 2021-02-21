const App = () => import('./components/App.js');
const Hello = () => import('./components/Hello.js');

if (window.document.getElementById('vueApp')) {
    var vueApp = new Vue({
      el: '#vueApp',
      components: {
        App,
        Hello
      },
    //  template: '<App/>',
      data: {
        message: 'Hello Vue!'
      }
    })
}
