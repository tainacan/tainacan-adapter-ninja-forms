
function call_ajax(action, data, e, callback_sucess, callback_err) {
  //This does the ajax request
  e.disabled = true;
  var loading_el = e.getElementsByClassName('loading')[0];
  loading_el.style.display='inline';  
  jQuery.ajax({
    url: ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
    data: {
        'action': action,
        'data' : data
    },
    success:function(data) {
      loading_el.style.display='none';
      typeof callback_sucess === 'function' && callback_sucess(data, e);
    },
    error: function(errorThrown) {
      loading_el.style.display='none';
      typeof callback_err === 'function' && callback_err(errorThrown, e);
    }
  });
}
