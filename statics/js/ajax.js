
function call_ajax(action, data) {
  //This does the ajax request
  jQuery.ajax({
    url: ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
    data: {
        'action': action,
        'data' : data
    },
    success:function(data) {
      console.log(data);
    },
    error: function(errorThrown) {
      console.log(errorThrown);
    }
  });
}
