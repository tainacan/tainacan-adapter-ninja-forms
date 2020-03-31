<?php if( !isset( $_GET['sub_id'] ) ): ?>
  <div class="notice">
    <p>
      nenhum item corresponde
    </p>
  </div>

<?php else: 
  $sub_id = $_GET['sub_id'];
  $form_id = Ninja_Forms()->form( )->sub($sub_id)->get()->get_form_id();
  $fields = Ninja_Forms()->form( $form_id )->get_fields();
  $sub = Ninja_Forms()->form( )->get_sub($sub_id);

  $item = "";
  foreach ($fields as $key => $field) {
    $key = $field->get_setting( 'key' );
    $label = $field->get_setting( 'label' );
    $value = $sub->get_field_value( $key );
    $item .= "<li>
                <span>$label</span>
                <p>$value</p>
              </li>
            ";
  }
  ?>
  <div>
    <ul>
      <?php echo $item; ?>
    </ul>
  </div>

  <div>
    <button 
      type='button'
      class='button button-small'
      onClick='call_ajax("ajax_request", ["<?php echo $sub_id; ?>", "<?php echo $form_id; ?>", true], this, 
        function(data, e) {
          if (data.sucess) {
            console.log("OK-adicionado!");
            e.closest("tr").remove();
          }
        }
      )'
    >
      <img class="loading hide" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
      Publicar
    </button>

    <button 
      type='button'
      class='button button-small' 
      onClick='call_ajax("ajax_request", ["<?php echo $sub_id; ?>", "<?php echo $form_id; ?>", false], this,
        function(data, e) {
          if (data.sucess) {
            console.log("OK-adicionado como rascunho!");
            e.closest("tr").remove();
          }
        }
      );'
      >
        <img class="loading hide" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
        Rascunho
    </button>

  </div>


<?php endif; ?>