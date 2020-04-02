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
require_once('src/TainacanFieldsListSelectNF.php');
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Plugin {

	public function __construct() {
		add_action("admin_menu", [$this, "add_theme_menu_item"], 20);
		add_action('admin_enqueue_scripts', [$this, 'get_static_files']);
		add_action('wp_enqueue_scripts', [$this, 'public_get_static_files']);
		add_action('wp_ajax_ajax_request', [$this, 'ajax_request'] );
		$this->load_register_fields();
	}

	public function load_register_fields() {
		if (class_exists('Ninja_Forms')) {
			add_filter( 'ninja_forms_field_type_sections', [$this, 'tainacanAddSectionNF']);
			add_filter( 'ninja_forms_register_fields', [$this, 'registerFieldsNF']);
			\Ninja_Forms::instance()->plugins_loaded();
		}
	}

	function tainacanAddSectionNF($sections) {
		//iterate over all collection and create a section for each
		$sections['tainacan'] = array(
			'id' => 'tainacan',
			'nicename' => __( 'Tainacan', 'ninja-forms' ),
			'fieldTypes' => array(),
		);
		return $sections;
	}

	function registerFieldsNF($fields) {
		$name = 'relationship-collection';
		$lable = "Relacionamento";
		$field = new \TainacanAdapterNF\Tainacan_NF_Fields_ListSelect($name, $label);
		$fields['relationship-collection'] =  $field;
		return $fields;
	}

	function public_get_static_files() {
		wp_enqueue_script(
			'ninja-forms-regex',
			plugins_url('statics/js/ninja-forms-regex.js',__FILE__ ),
			array('jquery')
		);
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
			"Moderação de Submissões",
			"Moderação de Submissões",
			'manage_options',
			'tainacan-ninja-forms',
			[$this, "display"]
		);
		
		add_submenu_page(
			'tainacan-ninja-forms',
			'View Item', 
			'View Item', 
			'manage_options',
			'tainacan-ninja-forms-view',
			[$this, "display_view"]
		);

		add_submenu_page(
			'tainacan-ninja-forms',
			'View Item', 
			'View Item', 
			'manage_options',
			'tainacan-ninja-forms-config',
			[$this, "display_config"]
		);
	}

	public function display_config() {
		if ( isset($_REQUEST['id_collection']) ) {
			update_option('id_collection_relationship_tainacan_adapter', $_REQUEST['id_collection']);
		}
		?>
			<form method="POST">
				<input type="text" name="id_collection" value="<?php echo get_option('id_collection_relationship_tainacan_adapter', 0); ?>" />
				<input type="submit" value="Salvar">
			<form>
		<?php
		
	}

	public function display_view() {
		include('src/view-submission.php');
	}
	
	public function display()
	{
		$is_sub = (!isset($_GET['tab']) || $_GET['tab'] == 'sub');
		$form_param = (isset($_GET['form_id']) ? "&form_id=".$_GET['form_id'] : '' );
		
		
		?>
			<div class="wrap">
				<h1>Moderação de Submissões</h1>
				<br>
				<form method="get">
					<input name="page" value="tainacan-ninja-forms" type="hidden"/>
					<?php	$this->add_filters(); ?>
				</form>
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
			<p>Estas são as submissões feitas ao formulário que ainda não foram enviadas ao Tainacan.</p>
			<?php $this->display_submissions_table(); ?>
		<?php
	}

	function display_mapper_page() {
		if ( ! isset($_GET['form_id']) ) {
			echo'<p><em>Nenhum formulário carregado ainda.</em></p>';
			return;
		}

		$form_id = $_GET['form_id'];

		$subListTable = new Sub_List_Table($form_id);
		$subListTable->prepare_items();
		$tainacanAdapterNF = new Tainacan_Adapter_NF($subListTable->get_columns());
		?>
			<form method="post" action="?page=tainacan-ninja-forms&tab=mapper&form_id=<?php echo $form_id; ?>">
				<?php	$tainacanAdapterNF->display_config_collection($form_id); ?>
				<input class="button" aria-label="" type="submit" value="Aplicar coleção">
			</form>	
			<br>	
			<form method="post" action="?page=tainacan-ninja-forms&tab=mapper&form_id=<?php echo $form_id; ?>">
				<?php	$tainacanAdapterNF->display($form_id); ?>
				<br>
				<input class="button button-primary" type="submit" value="Salvar mapeamento">
			</form>
		<?php
	}

	public function display_submissions_table()
	{
		if ( ! isset($_GET['form_id']) ) {
			echo'<p><em>Nenhum formulário carregado ainda.</em></p>';
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

		/**
     * Prepare the items for the table to process
     *
     * @return Void
     */
		public function prepare_items()
		{
			$this->fields = Ninja_Forms()->form( $this->form_id )->get_fields();
			$columns = $this->get_columns(true, 5);
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
		public function get_columns($add_option=false, $limit_of_col = false)
		{
			$columns = array();
			$count = 1;

			$hidden_field_types = apply_filters( 'ninja_forms_sub_hidden_field_types', array('submit', 'html', 'recaptcha', 'spam', 'hr') );
			foreach ($this->fields as $id => $field) {
				if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;
				$key = $field->get_setting( 'key' );
				$label = $field->get_setting( 'label' );
				$columns[$key] = $label;
				if ($limit_of_col !==false && $count++ >= $limit_of_col) break;
			}

			if($add_option) {
				$columns['options'] = "Opções";
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
					
					//pega o "Label" quando for um "options", pois o "Value" do ninja form não aceita caracteres especiais.
					if( $field->get_setting('options') !==null && is_array($field->get_setting('options')) ) {
						$value = $sub->get_field_value($key);
						$options = $field->get_setting('options');
						foreach($options as $option) {
							if(is_array($value)) {
								foreach($value as $idx => $v) {
									if ($v == $option['value']) {
										$item[$key][$idx] = $option['label'];
									}
								}
							} else {
								if ($value == $option['value']) {
									$item[$key] = $option['label'];
									break;
								}
							}
						}
					}

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

				$buttonView = "
					<a href='?page=tainacan-ninja-forms-view&sub_id=$id' class='button button-small button-secondary'>
						Visualizar
					</a>
				";

				ob_start();
				?>
					<button 
						type='button'
						class='button button-small'
						onClick='call_ajax("ajax_request", ["<?php echo $id; ?>", "<?php echo $form_id; ?>", true], this)'
					>
					<img class="loading hide" style="margin-bottom: -4px;" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
					 Publicar
					</button>
				<?php 
				$buttonPublish = ob_get_clean();

				ob_start();
				?>
					<button 
						type='button'
						class='button button-small' 
						onClick='call_ajax("ajax_request", ["<?php echo $id; ?>", "<?php echo $form_id; ?>", false], this)'
						>
							<img class="loading hide" style="margin-bottom: -4px;" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" />
							Rascunho
						</button>
				<?php
				$buttonDraft = ob_get_clean();

				return "$buttonView $buttonPublish $buttonDraft";
			}
			
			if( isset( $item[ $column_name ] ) ) {
				return is_array($item[ $column_name ]) ? implode($item[ $column_name ], " - ") : $item[ $column_name ];
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

	protected function get_field_id_by_key( $field_key, $form_id )
	{
			global $wpdb;

			$field_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}nf3_fields WHERE `key` = '{$field_key}' AND `parent_id` = {$form_id}" );

			return $field_id;
	}

	public function NF_2_TNC($id_sub, $form_id, $publish) {
		$sub = Ninja_Forms()->form( $form_id )->get_sub($id_sub);
		$mapper = get_option("tainacan_adapter_NF_mapper-$form_id", []);
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection-$form_id", NULL);
		$errors = [];

		$collections_repository = \Tainacan\Repositories\Collections::get_instance();
		$items_repository = \Tainacan\Repositories\Items::get_instance();
		$metadatum_repository = \Tainacan\Repositories\Metadata::get_instance();
		$item_metadata_repository = \Tainacan\Repositories\Item_Metadata::get_instance();
		

		$collection = $collections_repository->fetch($this->TNC_collection_id);
		$item = new \Tainacan\Entities\Item();
		$item->set_status("auto-draft");
		
		$item->set_collection($collection);
		if ($item->validate()) {
			$items_repository->insert( $item );
			foreach($mapper as $key => $metada) {
				if ($metada == '') continue;
				$value = $sub->get_field_value($key);

				//pega o "Label" quando for um "options", pois o "Value" do ninja form não aceita caracteres especiais.
				$fields_id = $this->get_field_id_by_key($key, $form_id);
				$field = Ninja_Forms()->form( $form_id )->get_field($fields_id);

				if ($field->get_setting( 'type' ) == 'file_upload') {
					$handle_document_erros = $this->handle_document($value, $item);
					if( !empty( $handle_document_erros )) {
						$errors[] = $handle_document_erros;
					}
				} else {
					if( $field->get_setting('options') !==null && is_array($field->get_setting('options')) ) {
						$options = $field->get_setting('options');
						foreach($options as $option) {
							if(is_array($value)) {
								foreach($value as $idx => $v) {
									if ($v == $option['value']) {
										$value[$idx] = $option['label'];
									}
								}
							} else {
								if ($value == $option['value']) {
									$value = $option['label'];
									break;
								}
							}
						}
					}
					if( $field->get_setting( 'type' ) == 'date' ) {
						$value = date('Y-m-d', strtotime($value));
					}
					$metadatum = $metadatum_repository->fetch($metada);
					$itemMetadada = new \Tainacan\Entities\Item_Metadata_Entity($item, $metadatum);
					$itemMetadada->set_value($value);
					if ($itemMetadada->validate()) {
						$itemMetadada = $item_metadata_repository->insert($itemMetadada);
					} else {
						$errors[] = ["description" => $itemMetadada->get_errors(), "value" => $value] ;
					}
				}
			}
		} else {
			$errors[] = $item->get_errors();
		}

		if( empty( $errors ) ) {
			if ($publish === true) 
				$item->set_status("publish");
			else 
				$item->set_status("draft");
			
			if ( $item->validate() ) {
				$items_repository->insert( $item );
				wp_trash_post($id_sub);
				return ["sucess"=>true];
			}
			$errors[] = $item->get_errors();
		}
		return ["sucess"=>false, "errors"=>$errors];
	}

	private function handle_document($values, $item_inserted) {
		$errors = [];
		foreach($values as $key => $value) {
			$correct_value = trim($value);
		}
		
		if( filter_var($correct_value, FILTER_VALIDATE_URL) ) {
			$TainacanMedia = \Tainacan\Media::get_instance();
			$id = $TainacanMedia->insert_attachment_from_url($correct_value, $item_inserted->get_id());

			if(!$id) {
				$errors[] = ["description" => "erro ao criar anexor do arquivo", "value" => $correct_value];
			}

			$item_inserted->set_document( $id );
			$item_inserted->set_document_type( 'attachment' );
			if( $item_inserted->validate() ) {
				$items_repository = \Tainacan\Repositories\Items::get_instance();
				//$item_inserted = $items_repository->update($item_inserted);
				$thumb_id = $items_repository->get_thumbnail_id_from_document($item_inserted);
				if (!is_null($thumb_id)) {
					set_post_thumbnail( $item_inserted->get_id(), (int) $thumb_id );
				}
			} else {
				$errors[] = ["description" => $item_inserted->get_errors(), "value" => $correct_value] ;
			}
		} else {
			$errors[] = ["description" => "URL inválida", "value" => $correct_value];
		}
		return $errors;
	}

	public function display_config_collection($form_id) {

		if ( isset( $_POST['tnc_collection'] ) ) {
			update_option("tainacan_adapter_NF_collection-$form_id", $_POST['tnc_collection']);
		}
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection-$form_id", $this->TNC_collection_id);
		$collections_repository = \Tainacan\Repositories\Collections::get_instance();
		$collections = $collections_repository->fetch([], 'OBJECT');
		$options='';
		foreach($collections as $col) {
			$options .=  ' <option ' . ($this->TNC_collection_id == $col->get_id()?'selected':'') . ' value="' . $col->get_id() . '">' . $col->get_name() . '</option>';
		}
		?>
				<select
					aria-label="Coleção"
					id="tnc_collection"
					name="tnc_collection"
					value="<?php echo $this->TNC_collection_id; ?>"
				>
					<?php echo $options; ?>
				</select>
		<?php
	}

	public function display($form_id) {
		if ( isset( $_POST['adapter'] ) ) {
			update_option("tainacan_adapter_NF_mapper-$form_id", $_POST['adapter']['mapper']);
		}
		$mapper = get_option("tainacan_adapter_NF_mapper-$form_id", []);
		$this->TNC_collection_id = get_option("tainacan_adapter_NF_collection-$form_id", $this->TNC_collection_id);
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
						<option value=""> Não mapeado </option>
						<option value="document"> Documento </option>
						<?php echo $options; ?>
					</select>
				</div>
			<?php
		}
	}
} 

$TainacanNFCovid19 = new \TainacanAdapterNF\Plugin();