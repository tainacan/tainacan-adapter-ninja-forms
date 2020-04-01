
function call_ajax(action, data, e) {
  
  e.disabled = true;

  const parent_el = e.parentElement;
  var children = [];
  for (var i = 0; i < parent_el.children.length; i++) {
    parent_el.children[i].disabled = true
    children.push(parent_el.children[i]);
  }

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
      
      if (data.sucess) {
        e.disabled = true;
        e.style.setProperty("font-weight", "bold");
        
        if (e.innerText === 'Publicar')
          e.innerText = 'Publicado';
        else if (e.innerText === 'Rascunho')
          e.innerText = 'Salvo como Rascunho';

        presentNotice('notice-success', 'Enviado para o Tainacan com sucesso.')
      } else {
        presentNotice('notice-error', 'Um ou mais erros ocorreram ao tentar enviar esta submissão para o Tainacan. Os detalhes são: <pre><code>' + JSON.stringify(data.errors) + '</code></pre>')
      }
    },
    error: function(errorThrown) {
      loading_el.classList.add('hide');
      children.forEach(function(element) { return element.disabled = false });
      presentNotice('notice-error', '<pre style="white-space: normal;"><code>' + JSON.stringify(errorThrown.errors) + '</code></pre>')
    }
  });
}

function dismissNotice(e) {
  e.parenElement.classList.add('notice-hidden');
}

function presentNotice(typeClass, message) {
  var notice = document.getElementById('submission-result-notice');

  if (notice) {
    notice.firstElementChild.innerHTML = message;
    notice.classList.add(typeClass);
    notice.classList.remove('notice-hidden');
  }
}