<?php if( !isset( $_GET['sub_id'] ) ): ?>
  <div class="notice">
    <p>
      nenhum item corresponde
    </p>
  </div>

<?php else: 
  $sub_id = $_GET['sub_id']; 
  $sub = Ninja_Forms()->form( )->get_sub($sub_id);
  ?>
  <div>
    SINGLE ITEM
  </div>
<?php endif; ?>