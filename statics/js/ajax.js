
function call_ajax(action, data, e) {
  
  e.disabled = true;

  const parent_el = e.parentElement;
  var children = [];
  for (var i = 0; i < parent_el.children.length; i++)
    children.push(parent_el.children[i]);

  var loading_el = e.getElementsByClassName('loading')[0];
  loading_el.classList.remove('hide');  

  jQuery.ajax({
    url: ajaxurl,
    data: {
        'action': action,
        'data' : data
    },
    success:function(data) {
      loading_el.classList.add('hide');
      children.forEach(function(element) { return element.disabled = true });
      
      if (data.sucess) {
        e.disabled = true;
        e.style.setProperty("font-weight", "bold");
        
        if (e.innerText === 'Publicar')
          e.innerText = 'Publicado';
        else if (e.innerText === 'Rascunho')
          e.innerText = 'Salvo como Rascunho';
      }
    },
    error: function(errorThrown) {
      loading_el.classList.add('hide');
    }
  });
}
