/**
   * NinjaForms
   */
  jQuery(document).on( 'nfFormReady', function( e, layoutView ) {

    // Create a new object for custom validation of a custom field.
    var tainacanSubmitController = Marionette.Object.extend( {

      initialize: function() {
        // On the Form Submission's field validaiton...
        var submitChannel = Backbone.Radio.channel( 'submit' );
        this.listenTo( submitChannel, 'validate:field', this.validateRequired );

        // on the Field's model value change...
        var fieldsChannel = Backbone.Radio.channel( 'fields' );
        this.listenTo( fieldsChannel, 'change:modelValue', this.validateRequired );
      },

      validateRequired: function( model ) {
        if( model.attributes.element_class=='tnc-ctr-URL' ) {
          if ( /^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/.test(model.get( 'value' )) ) {
            Backbone.Radio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'custom-field-error' );
          } else {
            Backbone.Radio.channel( 'fields' ).request( 'add:error', model.get( 'id' ), 'custom-field-error', 'Esse campo deve conter uma URL válida' );
          }
        }

        // Only validate a specific fields type.
        if( 'custom' != model.get( 'type' ) ) return;

        // Only validate if the field is marked as required?
        if( 0 == model.get( 'required' ) ) return;

        // Check if Model has a value
        // if( model.get( 'value' ) ) {
        //     // Remove Error from Model
        //     Backbone.Radio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'custom-field-error' );
        // } else {
        //     // Add Error to Model
        //     Backbone.Radio.channel( 'fields' ).request( 'add:error', model.get( 'id' ), 'custom-field-error', 'This is an error message' );
        // }
      }
    });
    
    // On Document Ready...
    jQuery( document ).ready( function( $ ) {
      // Instantiate our custom field's controller, defined above.
      new tainacanSubmitController();
    });
  });