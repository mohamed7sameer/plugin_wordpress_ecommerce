jQuery(function ($) {
  var $list = $("#bookapp-components");
  if (!$list.length) {
    return;
  }

  var nextIndex = parseInt($list.data("next-index"), 10) || 0;

  $list.sortable({
    handle: ".bookapp-comp-handle",
    placeholder: "bookapp-comp-placeholder",
    forcePlaceholderSize: true,
  });

  $("#bookapp-add-component").on("click", function (e) {
    e.preventDefault();
    var type = $("#bookapp-comp-type").val();
    var tmpl = $("#tmpl-bookapp-comp-" + type).html();
    if (!tmpl) {
      return;
    }
    var $row = $(tmpl.replace(/__i__/g, nextIndex++));
    $list.append($row);
    $row.find("input, textarea, select").first().trigger("focus");
  });

  $list.on("click", ".bookapp-comp-remove", function (e) {
    e.preventDefault();
    if (window.confirm("امسح الكومبوننت ده؟ (مش هيتمسح فعليًا غير لما تدوس حفظ)")) {
      $(this).closest(".bookapp-comp").remove();
    }
  });

  $list.on("click", ".bookapp-comp-toggle", function (e) {
    e.preventDefault();
    $(this).closest(".bookapp-comp").toggleClass("bookapp-comp-closed");
  });

  $list.on("change", ".bookapp-comp-enabled input[type=checkbox]", function () {
    $(this)
      .closest(".bookapp-comp")
      .toggleClass("bookapp-comp-disabled", !this.checked);
  });
});
