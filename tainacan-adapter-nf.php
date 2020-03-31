<?php
/*
Plugin Name: Tainacan-adapter-ninja-forms
Plugin URI: tainacan.org
Description: Plugin for tainacan submissions Ninja Forms
Author: Media Lab / UFG
Version: 0.0.1
Text Domain: tainacan-adapter-nf
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
namespace TainacanAdapterNF;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Plugin {

	public function __construct() {
		add_action("admin_menu", [$this, "add_theme_menu_item"], 20);
		add_action('admin_enqueue_scripts', [$this, 'get_static_files']);
		add_action( 'wp_ajax_ajax_request', [$this, 'ajax_request'] );
	}

	function get_static_files() {
		$main_css = plugins_url('statics/css/main.css',__FILE__ );
		$main_js = plugins_url('statics/js/ajax.js',__FILE__ );

		//wp_register_style( 'tainacan_nf_main', $main_css );
		wp_enqueue_style( 'tainacan_nf_main', $main_css );
		
		wp_enqueue_script(
			'example-ajax-script',
			$main_js,
			array('jquery')
		);
		
		wp_localize_script(
			'example-ajax-script',
			'example_ajax_obj',
			array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
		);
	}
	
	function add_theme_menu_item() {
		add_submenu_page(
			'tainacan_admin',
			"Ninja Forms Adapter",
			"Ninja Forms Adapter",
			'manage_options',
			'tainacan-ninja-forms',
			[$this, "display"]
		);
		$this->get_static_files();
	}
	
	public function display()
	{
		$is_sub = (!isset($_GET['tab']) || $_GET['tab'] == 'sub');
		$form_param = (isset($_GET['form_id']) ? "&form_id=".$_GET['form_id'] : '' );
		
		
		?>
			<div class="wrap">
				<h1>Tainacan Adapter for Ninja Forms</h1>
				
				<div class="tainacan-ninja-tabs">
					<h2 class="nav-tab-wrapper">
						<a href="?page=tainacan-ninja-forms<?php echo $form_param;?>&tab=sub"    class="nav-tab <?php echo ( $is_sub ? 'nav-tab-active':''); ?>">Submissões</a>
						<a href="?page=tainacan-ninja-forms<?php echo $form_param;?>&tab=mapper" class="nav-tab <?php echo ( !$is_sub ? 'nav-tab-active':''); ?>">Mapeamento</a>
					</h2>
					<div class="tabs-content">
						<?php
							if( $is_sub ) {
								$this->display_submissions_page();
							} else {
								$this->display_mapper_page();
							}
						?>
					</div>
				</div>
			</div>
		<?php
	}
	
	function display_submissions_page()
	{
		?>
			<form method="get">
				<input name="page" value="tainacan-ninja-forms" type="hidden"/>
				<?php	$this->add_filters(); ?>
				<?php	$this->display_table(); ?>
			</form>
		<?php
	}

	function display_mapper_page() {
		if ( ! isset($_GET['form_id']) ) {
			echo'<p>Nenhum formulário carregado ainda. Volte para a aba anterior e escolha um.</p>';
			return;
		}

		$form_id = $_GET['form_id'];

		$subListTable = new Sub_List_Table($form_id);
		$subListTable->prepare_items();
		$tainacanAdapterNF = new Tainacan_Adapter_NF($subListTable->get_columns());
		?>
			<form method="post" action="?page=tainacan-ninja-forms&tab=mapper&form_id=<?php echo $form_id; ?>">
				<?php	$tainacanAdapterNF->display_config_collection(); ?>
				<input class="button button-primary" type="submit" value="Aplicar coleção">
			</form>	
			<br>	
			<form method="post" action="?page=tainacan-ninja-forms&tab=mapper&form_id=<?php echo $form_id; ?>">
				<?php	$tainacanAdapterNF->display(); ?>
				<br>
				<input class="button button-primary" type="submit" value="Salvar mapeamento">
			</form>
		<?php
	}

	public function display_table()
	{
		if ( ! isset($_GET['form_id']) ) {
			echo'<p>Nenhum formulário carregado ainda.</p>';
			return;
		}

		$form_id = $_GET['form_id'];

		$subListTable = new Sub_List_Table($form_id);
		$subListTable->prepare_items();
		$subListTable->display();
	}

	public function add_filters()
	{
		$forms = Ninja_Forms()->form()->get_forms();

		$form_options = array();
		foreach( $forms as $form ){
				$form_options[ $form->get_id() ] = $form->get_setting( 'title' );
		}
		$form_options = apply_filters( 'ninja_forms_submission_filter_form_options', $form_options );
		asort($form_options);

		// make sure form_id isset and is a number
		if( isset( $_GET[ 'form_id' ] ) && ctype_digit( $_GET[ 'form_id' ] ) ) {
				$form_selected = intval($_GET[ 'form_id' ]);
		} else {
				$form_selected = 0;
		}
		?>
		<select name="form_id" id="form_id">
				<option value="0"><?php esc_html_e( '- Select a form', 'ninja-forms' ); ?></option>
				<?php foreach( $form_options as $id => $title ): ?>
						<option value="<?php echo $id; ?>" <?php if( $id == $form_selected ) echo 'selected'; ?>>
								<?php echo $title . " ( ID: " . $id . " )"; ?>
						</option>
				<?php endforeach; ?>
		</select>
		<input class="button button-primary" type="submit" value="Aplicar formulário">
		<?php
	}

	function ajax_request() {
		if ( isset( $_REQUEST['data'])) {
			$data = $_REQUEST['data'];
			$tainacanAdapterNF = new Tainacan_Adapter_NF();
			wp_send_json($tainacanAdapterNF->NF_2_TNC($data[0], $data[1], $data[2]=='true'));
		}
		wp_send_json_error("erro ao processar item!");
    die();
	}
}

class Sub_List_Table extends \WP_List_Table
{
		private $form_id = null;
		private $fields = [];
		private $data = [];

		public function __construct($form_id) {
			parent::__construct();
			$this->form_id = $form_id;
		}

		//function get_columns()
		//{
		//	return $this->columns;
		//}

		/**
     * Prepare the items for the table to process
     *
     * @return Void
     */
		public function prepare_items()
		{
			$this->fields = Ninja_Forms()->form( $this->form_id )->get_fields();
			$columns = $this->get_columns(true);
			$hidden = $this->get_hidden_columns();
			$sortable = $this->get_sortable_columns();

			$this->data = $this->table_data();
			usort( $this->data, array( &$this, 'sort_data' ) );

			$perPage = 15;
			$currentPage = $this->get_pagenum();
			$totalItems = count($this->data);

			$this->set_pagination_args( array(
					'total_items' => $totalItems,
					'per_page'    => $perPage
			) );

			$data = array_slice($this->data,(($currentPage-1)*$perPage),$perPage);

			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->items = $data;
		}

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
		public function get_columns($add_option=false)
		{
			$columns = array();
			$hidden_field_types = apply_filters( 'ninja_forms_sub_hidden_field_types', array('submit') );
			foreach ($this->fields as $id => $field) {
				if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;
				$key = $field->get_setting( 'key' );
				$label = $field->get_setting( 'label' );
				$columns[$key] = $label;
			}

			if($add_option) {
				$columns['options'] = "opções";
			}


			return $columns;
		}

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

		/**
		 * Define the sortable columns
		 *
		 * @return Array
		 */
		public function get_sortable_columns()
		{
			return array();
			//return array('title' => array('title', false));
		}

    /**
     * Get the table data
     *
     * @return Array
     */
		private function table_data()
		{
			$data = array();
			$subs = Ninja_Forms()->form( $this->form_id )->get_subs();
			foreach	($subs as $id => $sub ) {
				$item = [];
				$item['id'] = $id;
				foreach ($this->fields as $key => $field) {
					$key = $field->get_setting( 'key' );
					$item[$key] = $sub->get_field_value($key);
				}
				
				$data[] = $item;
			}

			return $data;
		}

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
			if ($column_name == 'options') {
				$id=$item['id'];
				$form_id = $this->form_id;

				$buttonView = 
				"<button
					type='button'
					class='button button-small'>
					Visualizar
				</button>";

				ob_start();
				?>
					<button 
						type='button'
						class='button button-small'
						onClick='call_ajax("ajax_request", ["<?php echo $id; ?>", "<?php echo $form_id; ?>", true], this, 
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
				<?php 
				$buttonPublish = ob_get_clean();

				ob_start();
				?>
					<button 
						type='button'
						class='button button-small' 
						onClick='call_ajax("ajax_request", ["<?php echo $id; ?>", "<?php echo $form_id; ?>", false], this,
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
				<?php
				$buttonDraft = ob_get_clean();

				//$buttonDraft = "<button type='button' onClick='call_ajax(\"ajax_request\", [\"$id\", \"$form_id\", false]);'>Rascunho </button>";
				return "$buttonView $buttonPublish $buttonDraft";
			}
			
			if( isset( $item[ $column_name ] ) ) {
				return $item[ $column_name ];
			}
			
			return print_r( $item, true ) ;
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
				// Set defaults
				return 1;
        /*$orderby = 'title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;*/
    }
}

class Tainacan_Adapter_NF {

	private $TNC_collection_id = NULL;
	private $NF_columns = [];

	function __construct($NF_columns=[]) {
		$this->NF_columns = $NF_columns;
	}

	public function NF_2_TNC($id_sub, $form_id, $publish) {
		$sub = Ninja_Forms()->form( $form_id )->get_sub($id_sub);
		$mapper = get_option("tainacan_adapter_NF_mapper", []);
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection", NULL);
		$errors = [];

		$collections_repository = \Tainacan\Repositories\Collections::get_instance();
		$items_repository = \Tainacan\Repositories\Items::get_instance();
		$metadatum_repository = \Tainacan\Repositories\Metadata::get_instance();
		$item_metadata_repository = \Tainacan\Repositories\Item_Metadata::get_instance();
		

		$collection = $collections_repository->fetch($this->TNC_collection_id);
		$item = new \Tainacan\Entities\Item();
		if ($publish === true)
			$item->set_status("publish");
		
		$item->set_collection($collection);
		if ($item->validate()) {
			$items_repository->insert( $item );
			foreach($mapper as $key => $metada) {
				$value = $sub->get_field_value($key);
				$metadatum = $metadatum_repository->fetch($metada);
				$itemMetadada = new \Tainacan\Entities\Item_Metadata_Entity($item, $metadatum);
				$itemMetadada->set_value($value);
				if ($itemMetadada->validate()) {
					$itemMetadada = $item_metadata_repository->insert($itemMetadada);
				} else {
					$errors[] = $itemMetadada->get_errors();
				}
			}
		} else {
			$errors[] = $item->get_errors();
		}
		if( empty( $errors ) ) {
			wp_trash_post($id_sub);
			return ["sucess"=>true];
		}
		return ["sucess"=>false, "errors"=>$errors];
	}

	public function display_config_collection() {
		if ( isset( $_POST['tnc_collection'] ) ) {
			update_option("tainacan_adapter_NF_collection", $_POST['tnc_collection']);
		}
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection", $this->TNC_collection_id);
		$collections_repository = \Tainacan\Repositories\Collections::get_instance();
		$collections = $collections_repository->fetch([], 'OBJECT');
		$options='';
		foreach($collections as $col) {
			$options .=  ' <option ' . ($this->TNC_collection_id == $col->get_id()?'selected':'') . ' value="' . $col->get_id() . '">' . $col->get_name() . '</option>';
		}
		?>
				<label for="tnc_collection"> Coleção </label>
				<select
					id="tnc_collection"
					name="tnc_collection"
					value="<?php echo $this->TNC_collection_id; ?>"
				>
					<?php echo $options; ?>
				</select>
		<?php
	}

	public function display() {
		if ( isset( $_POST['adapter'] ) ) {
			update_option("tainacan_adapter_NF_mapper", $_POST['adapter']['mapper']);
		}
		$mapper = get_option("tainacan_adapter_NF_mapper", []);
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection", $this->TNC_collection_id);
		if( $this->TNC_collection_id == NULL )
			return;
		$metadatum_repository = \Tainacan\Repositories\Metadata::get_instance();
		$collection = new \Tainacan\Entities\Collection( $this->TNC_collection_id );
		$metadataList = $metadatum_repository->fetch_by_collection( $collection, [] );
		
		foreach($this->NF_columns as $NF_key => $NF_label) {
			$value =  isset($mapper[$NF_key]) ? $mapper[$NF_key] : '';
			$options = "";
			foreach($metadataList as $metadata) {
				$options .=  ' <option ' . ($value == $metadata->get_id()?'selected':'') . ' value="' . $metadata->get_id() . '">' . $metadata->get_name() . '</option>';
			}
			?>
				<div class="tainacan-ninja-control">
					<label for="<?php echo $NF_key; ?>"> <?php echo $NF_label; ?> </label>
					<select 
						id="<?php echo $NF_key; ?>"
						name="<?php echo "adapter[mapper][$NF_key]"; ?>"
						value="<?php echo $value; ?>"
					>
						<?php echo $options; ?>
					</select>
				</div>
			<?php
		}
	}
} 

$TainacanNFCovid19 = new \TainacanAdapterNF\Plugin();