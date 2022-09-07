/* globals Chart:false, feather:false */
'use strict'
import('./vueapp.js')

if (window.jQuery) {
    $(window).on("load", function() {
        $(".page-loader").delay(200).fadeOut("slow")
    })
} else {
    window.alert('jQuery missing!')
}

function delete_item(id) {
  if (window.confirm('Are you sure you want to delete this item?')) {
    window.document.getElementById(id).submit()
  }
}

function submit_formY(id) {
  var validator = window.document.getElementById(id).data('bootstrapValidator')
  validator.validate()
  if (validator.isValid()) {
    window.document.getElementById(id).submit()
  }
}

function submit_formX(id) {
  window.document.getElementById(id).bootstrapValidator('validate')
  if (window.document.getElementById(id).bootstrapValidator('isValid')) {
    window.document.getElementById(id).submit()
  }
}

function submit_form(id) {
  window.document.getElementById(id).submit()
}

function click_button(id) {
    window.document.getElementById(id).click()
}

(function () {
  feather.replace()

  // Side menu replace main menu on < lg screens
  let side_menu = window.document.getElementById('sidebarMenu')
  if (side_menu) {
    $("#menuToggler").attr('data-target', '#sidebarMenu')
    $("#menuToggler").attr('aria-controls', 'sidebarMenu')
  }

  /* Configure select2 for form's select fields */
  if (window.document.getElementById('position_contract')) {
      $('#position_contract').select2();
  }
  if (window.document.getElementById('option_stock')) {
      $('#option_stock').select2();
  }
  if (window.document.getElementById('trade_unit_symbol')) {
      $('#trade_unit_symbol').select2();
  }
  if (window.document.getElementById('statement_tradeUnit')) {
      $('#statement_tradeUnit').select2();
  }
  if (window.document.getElementById('trade_statement_tradeUnit')) {
      $('#trade_statement_tradeUnit').select2();
  }
  if (window.document.getElementById('option_trade_statement_tradeUnit')) {
      $('#option_trade_statement_tradeUnit').select2();
  }
  if (window.document.getElementById('position_tradeUnit')) {
    $('#position_tradeUnit').select2();
}

  // Bootstrap Tooltip component setup
  $('[data-toggle="tooltip"]').tooltip()

  // Graph.js graphs
  // Disable automatic style injection
  if (Chart.platform) {
    Chart.platform.disableCSSInjection = true;
  }
  /*
  let ctx = window.document.getElementById('myChart')
  if (ctx) draw_chart(ctx)
  let ctx2 = window.document.getElementById('myChart2')
  if (ctx2) draw_chart2(ctx2)
  */
  if (window.graphs) {
    window.graphs.forEach(function(item, index, array) {
      new Chart(item.context, { type: item.type, data: item.data, options: item.options });
    });
  } else {
/*      window.alert('no graph here!') */
  }

}())
