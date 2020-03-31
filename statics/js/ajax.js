
function call_ajax(action, data, e, callback_sucess, callback_err) {
  //This does the ajax request
  jQuery.ajax({
    url: ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
    data: {
        'action': action,
        'data' : data
    },
    success:function(data) {
      typeof callback_sucess === 'function' && callback_sucess(data, e);
    },
    error: function(errorThrown) {
      typeof callback_err === 'function' && callback_err(errorThrown, e);
    }
  });
}
