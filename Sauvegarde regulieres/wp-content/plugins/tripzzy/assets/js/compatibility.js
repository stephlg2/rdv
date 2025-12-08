jQuery(document).ready(function ($) {
  $(document).on("click", ".tripzzy-use-forcefully", function (e) {
    e.preventDefault();
    var data = {
      tripzzy_nonce: tripzzy_compatibility.tripzzy_nonce,
      value: $(this).data("use"),
      action: "tripzzy_use_forcefully",
    };
    $.ajax({
      type: "POST",
      url: tripzzy_compatibility.ajax_url,
      data: data,
      beforeSend: function beforeSend() {},
      success: function success(data) {
        location.reload();
      },
    });
  });
});
