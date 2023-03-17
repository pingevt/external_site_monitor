(function ($, Drupal) {
  Drupal.behaviors.tagCheckerResults = {
    attach: function attach(context, settings) {
      var idTable = document.getElementById("ids-table");
      var togglebtn = document.getElementById("id-table-toggle-btn");
      console.log(togglebtn);
      togglebtn.addEventListener("click", function (e) {
        idTable.classList.toggle('show-all');
      });
    }
  };
})(jQuery, Drupal);

//# sourceMappingURL=tag_checker_results.js.map
