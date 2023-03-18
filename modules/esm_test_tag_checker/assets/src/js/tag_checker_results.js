(function ($, Drupal) {
  Drupal.behaviors.tagCheckerResults = {
    attach: function (context, settings) {                                      // eslint-disable-line no-unused-vars
      let idTable = document.getElementById("ids-table");
      let togglebtn = document.getElementById("id-table-toggle-btn");


      console.log(togglebtn);
      togglebtn.addEventListener("click", () => {
        idTable.classList.toggle('show-all');
      });
    }
  };
})(jQuery, Drupal);                                                             // eslint-disable-line no-undef
