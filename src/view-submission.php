<?php if( !isset( $_GET['sub_id'] ) ): ?>
  <div class="wrap">
		<h1>Tainacan Adapter for Ninja Forms</h1>
    <div class="notice">
      <p>
        nenhum item corresponde
      </p>
    </div>
  </div>

<?php else: 
  $sub_id = $_GET['sub_id'];
  $form_id = Ninja_Forms()->form( )->sub($sub_id)->get()->get_form_id();
  $fields = Ninja_Forms()->form( $form_id )->get_fields();
  $sub = Ninja_Forms()->form( )->get_sub($sub_id);
  $form_name = Ninja_Forms()->form( $form_id )->get_settings()['title'];

  $item = "";
  foreach ($fields as $key => $field) {
    $key = $field->get_setting( 'key' );
    $label = $field->get_setting( 'label' );
    $value = $sub->get_field_value( $key );
    $item .= "<li>
                <span class='label'>$label</span>
                <div class='value'>$value</div>
              </li>
            ";
  }
  ?>
  <div class="wrap">
		<h1>Tainacan Adapter for Ninja Forms</h1>
    <div id="poststuff">
      <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content" class="post-body-content">
          <ul class="tainacan-ninja-form-answers">
            <?php echo $item; ?>
          </ul>
        </div>
        <div id="postbox-container-1" class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="metabox" class="postbox">
              <button type="button" class="handlediv" aria-expanded="true">
                <span class="screen-reader-text">Metabox collapse</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle"><span><?php echo $form_name; ?></span></h2>
              <div class="inside">
                <div class="main">
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
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>                                     
 </div>
<?php endif; ?>