jQuery(function ($) {
  $(".bookapp-color").wpColorPicker({
    change: function (event, ui) {
      var hex = ui.color.toString();
      var $scale = $(event.target).closest("td").find(".bookapp-scale");
      if ($scale.length) {
        paintScale($scale, hex);
      }
    },
  });

  $(document).on("click", ".bookapp-pick", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var $input = $btn.siblings(".bookapp-image");
    var $preview = $btn.siblings(".bookapp-image-preview");
    var frame = wp.media({ title: "اختيار صورة", multiple: false });
    frame.on("select", function () {
      var att = frame.state().get("selection").first().toJSON();
      $input.val(att.url);
      $preview.html(
        '<br><img src="' +
          att.url +
          '" alt="" style="max-width:220px;margin-top:8px">'
      );
    });
    frame.open();
  });

  function paintScale($el, hex) {
    var scale = mixScale(hex);
    var html = "";
    $.each(scale, function (step, color) {
      html +=
        '<span title="' +
        step +
        " " +
        color +
        '" style="background:' +
        color +
        '"></span>';
    });
    $el.html(html);
  }

  function mixScale(hex) {
    var base = hexToRgb(hex);
    var white = [255, 255, 255];
    var black = [0, 0, 0];
    var map = {
      50: [white, 0.92],
      100: [white, 0.8],
      200: [white, 0.6],
      300: [white, 0.4],
      400: [white, 0.2],
      500: null,
      600: [black, 0.12],
      700: [black, 0.28],
      800: [black, 0.46],
      900: [black, 0.64],
    };
    var out = {};
    $.each(map, function (step, mix) {
      out[step] = mix ? rgbToHex(blend(base, mix[0], mix[1])) : normalizeHex(hex);
    });
    return out;
  }

  function hexToRgb(hex) {
    hex = normalizeHex(hex).replace("#", "");
    return [
      parseInt(hex.slice(0, 2), 16),
      parseInt(hex.slice(2, 4), 16),
      parseInt(hex.slice(4, 6), 16),
    ];
  }

  function blend(from, to, amount) {
    return [
      Math.round(from[0] + (to[0] - from[0]) * amount),
      Math.round(from[1] + (to[1] - from[1]) * amount),
      Math.round(from[2] + (to[2] - from[2]) * amount),
    ];
  }

  function rgbToHex(rgb) {
    return (
      "#" +
      rgb
        .map(function (n) {
          var h = Math.max(0, Math.min(255, n)).toString(16);
          return h.length === 1 ? "0" + h : h;
        })
        .join("")
        .toUpperCase()
    );
  }

  function normalizeHex(value) {
    var m = String(value || "").match(/^#?([0-9a-fA-F]{6})$/);
    return m ? "#" + m[1].toUpperCase() : "#000000";
  }
});
