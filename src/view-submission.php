<?php if( !isset( $_GET['sub_id'] ) ): ?>
  <div class="wrap">
		<h1>Moderação de Submissões</h1>
    <div class="notice">
      <p>
        Nenhum item correspondente
      </p>
    </div>
  </div>

<?php else: 
  $sub_id = $_GET['sub_id'];
  $form_id = Ninja_Forms()->form( )->sub($sub_id)->get()->get_form_id();
  $fields = Ninja_Forms()->form( $form_id )->get_fields();
  $hidden_field_types = apply_filters( 'ninja_forms_sub_hidden_field_types', array('submit') );
  $sub = Ninja_Forms()->form( )->get_sub($sub_id);
  $form_name = Ninja_Forms()->form( $form_id )->get_settings()['title'];

  $item = "";
  foreach ($fields as $key => $field) {
    if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;
    $key = $field->get_setting( 'key' );
    $label = $field->get_setting( 'label' );
    $value = $sub->get_field_value( $key );
    $value = is_array($value) ? implode($value, ' - ') : $value;
    $item .= "<li>
                <span class='label'>$label</span>
                <div class='value'>$value</div>
              </li>
            ";
  }
  ?>
  <div class="wrap">
		<h1>Moderação de Submissões</h1>
    <br>
    <div 
        id="submission-result-notice"
        class="notice is-dismissible notice-hidden">
      <p></p>
      <button
          onClick="dismissNotice(this)"
          type="button"
          class="notice-dismiss">
        <span class="screen-reader-text">Dispensar aviso.</span>
      </button>
    </div>
    <div id="poststuff">
      <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content" class="post-body-content">
          <ul class="tainacan-ninja-form-answers">
            <?php echo $item; ?>
          </ul>
        </div>
        <div id="postbox-container-1" class="postbox-container">
          <br>
          <div id="metabox" class="postbox">
            <h2 class="hndle ui-sortable-handle"><span><?php echo $form_name; ?></span></h2>
            <div class="inside">
              <div class="main">
                <p>Esta é a submissão de número #<em><?php echo $sub->get_seq_num(); ?></em> ao formulário <em><?php echo $form_name; ?></em>. Use os botões abaixo para enviá-la ao Tainacan como um item Público ou como um Rascunho.</p>
              </div>
            </div>
            <div id="major-publishing-actions">
                <button 
                  type='button'
                  class='button button-primary'
                  onClick='call_ajax("ajax_request", ["<?php echo $sub_id; ?>", "<?php echo $form_id; ?>", true], this)'
                >
                  <img class="loading hide" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
                  Publicar
                </button>

                <button 
                  type='button'
                  class='button' 
                  onClick='call_ajax("ajax_request", ["<?php echo $sub_id; ?>", "<?php echo $form_id; ?>", false], this);'
                  >
                    <img class="loading hide" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
                    Rascunho
                </button>
              </div>
          </div>
        </div>
      </div>
    </div>
 </div>
<?php endif; ?>