(function ($, Drupal) {
  Drupal.behaviors.tagCheckerResults = {
    attach: function (context, settings) {
      let idTable = document.getElementById("ids-table");
      let togglebtn = document.getElementById("id-table-toggle-btn");


      console.log(togglebtn);
      togglebtn.addEventListener("click", (e) => {
        idTable.classList.toggle('show-all');
      });
    }
  };
})(jQuery, Drupal);
