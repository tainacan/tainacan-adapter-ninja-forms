<?php

namespace TainacanAdapterNF;

class Tainacan_NF_Fields_ListSelect extends \NF_Abstracts_List {
	protected $_name = 'relationship-collection';
	protected $_type = 'relationship-collection';
	protected $_nicename = 'relationship-collection';
	protected $_section = 'tainacan';
	
	protected $_settings = array( 'config' );


	

	protected $_templates = array( 'listcountry', 'listselect' );

	public function __construct($name, $label, $section='tainacan') {
			parent::__construct();
			$this->_settings_all_fields['id'] = [
				"name"=>"key1",
      	"type"=>"textbox",
      	"label"=>"Field Key",
      	"width"=>"full",
      	"group"=>"administration",
      	"value"=>"",
      	"help"=>"Creates a unique key to identify and target your field for custom development."];
			$this->_name = $name;
			$this->_type = $name;
			$this->_section = $section;
			$this->_nicename = __( $label, 'ninja-forms' );
			$this->_settings[ 'options' ][ 'group' ] = '';
			//$this->_settings[ 'config' ][ 'collection_id' ] = 955;
			add_filter( 'ninja_forms_render_options_' . $this->_type, array( $this, 'filter_options'   ), 10, 2 );
	}

	public function admin_form_element( $id, $value ) {
		return "teste";
	}

	public function filter_options( $options, $settings ) {
		$default_value = ( isset( $settings[ 'default' ] ) ) ? $settings[ 'default' ] : '';
		$options = $this->get_options(); // Overwrite the default list options.
		foreach( $options as $key => $option ){
			if( $default_value != $option[ 'value' ] ) continue;
			$options[ $key ][ 'selected' ] = 1;
		}
		usort( $options, array($this,'sort_options_by_label') );
		return $options;
	}

	private function sort_options_by_label( $option_a, $option_b ) {
		return strcasecmp( $option_a['label'], $option_b['label'] );
	}

	private function get_options() {
		$order = 0;
		$options = array();
		$options[] = array(
			'label' => '[' . __( 'selecione um valor', 'tainacan-ninja-forms' ) . ']',
			'value' => '',
			'calc' => '',
			'selected' => 0,
			'order' => $order,
		);

		$collection_id = get_option('id_collection_relationship_tainacan_adapter', false);

		if ($collection_id == false) 
			return $options;

		$items_repository = \Tainacan\Repositories\Items::get_instance();
		$items = $items_repository->fetch(['posts_per_page'=>-1], $collection_id, 'OBJECT');
		foreach ($items as $item) {
			$options[] = array(
				'label'  => $item->get_title(),
				'value' => $item->get_id(),
				'calc' => '',
				'selected' => 0,
				'order' => $order
			);
		}

		return $options;
	}
}