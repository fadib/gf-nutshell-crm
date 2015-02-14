<?php

GFForms::include_feed_addon_framework();

class GFNutshell extends GFFeedAddOn {

	protected $_version = GF_NUTSHELL_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Nutshell CRM Add-On';
	protected $_short_title = 'Nutshell CRM';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_nutshell', 'gravityforms_nutshell_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_nutshell';
	protected $_capabilities_form_settings = 'gravityforms_nutshell';
	protected $_capabilities_uninstall = 'gravityforms_nutshell_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFNutshell();
		}

		return self::$_instance;
	}

	public function init() {
		parent::init();
	}

	public function init_admin(){
		parent::init_admin();

		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	//------- AJAX FUNCTIONS ------------------//

	public function init_ajax(){
		parent::init_ajax();
	}

	public function maybe_create_menu( $menus ){
		$current_user = wp_get_current_user();
		return $menus;
	}

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Nutshell Account Information', 'gravityformsnutshell' ),
				'fields'      => array(
					array(
						'name'              => 'username',
						'label'             => __( 'Username', 'gravityformsnutshell' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					),
					array(
						'name'              => 'apiKey',
						'label'             => __( 'API KEY', 'gravityformsnutshell' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					)
				)
			),
		);

	}

	public function feed_settings_fields() {
		return array(
			array(
				'title'       => __( 'Nutshell Feed Settings', 'gravityformsnutshell' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformsnutshell' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformsnutshell' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsnutshell' ),
					),
					array(
						'name'    => 'assignTo',
						'label'   => __( 'Assign To', 'gravityformsnutshell' ),
						'type'    => 'select',
						'choices' => $this->get_nutshell_assignee(),
					),
					array(
						'name'    => 'accountName',
						'label'   => __( 'Account: Name', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),
					array(
						'name'    => 'accountURL',
						'label'   => __( 'Account: URL', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),					
					array(
						'name'    => 'contactName',
						'label'   => __( 'Contact: Name', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),
					array(
						'name'    => 'contactJobTitle',
						'label'   => __( 'Contact: Job Title', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),
					array(
						'name'    => 'contactEmail',
						'label'   => __( 'Contact: Email', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),
					array(
						'name'    => 'contactPhone',
						'label'   => __( 'Contact: Phone', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
					),
					array(
						'name'    => 'leadSource',
						'label'   => __( 'Lead: Source', 'gravityformsnutshell' ),
						'type'    => 'select',
						'choices' => $this->get_nutshell_sources(),
					),
					array(
						'name'    => 'leadWebsite',
						'label'   => __( 'Lead: Website', 'gravityformsnutshell' ),
						'type'    => 'text',
						'class'    => 'medium merge-tag-support',
						'tooltip'  => '<h6>' . __( 'Lead: Website', 'gravityformsnutshell' ) . '</h6>' . __( 'Assign this lead to a website. Example: topseos.com', 'gravityformsnutshell' ),
					),					
					array(
						'name'    => 'leadMessage',
						'label'   => __( 'Message', 'gravityformsnutshell' ),
						'type'    => 'textarea',
						'class'    => 'medium merge-tag-support mt-position-right',
					)
				)
			)
		);
	}

	public function feed_list_columns() {
		return array(
			'feedName'		=> __( 'Name', 'gravityformsnutshell' ),
		);
	}

	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( ! $this->is_valid_credentials() ) {
			?>
			<div><?php echo sprintf(
					__( 'To get started, please configure your %sNutshell Settings.%s', 'gravityformsnutshell' ),
					'<a href="' . $this->get_plugin_settings_url() . '">', '</a>'
				); ?>
			</div>

			<?php
			return;
		}

		parent::feed_edit_page( $form, $feed_id );
	}

	public function get_nutshell_assignee() {
		$api = $this->get_api();
		if ( ! $api ) {
			return;
		}
		
		$sources_nutshell = $api->findUsers();
		$sources = array();

		foreach ( $sources_nutshell as $s ) {

			$sources[] = array(
				'label' => $s->name,
				'value' => $s->id
			);

		}
		
		return $sources;
	}

	public function get_nutshell_sources() {

		$api = $this->get_api();

		if ( ! $api ) {
			return;
		}
		
		$sources_nutshell = $api->findSources();

		$sources = array();

		foreach ( $sources_nutshell as $s ) {

			$sources[] = array(
				'label' => $s->name,
				'value' => $s->id
			);

		}
		
		return $sources;

	}

	public function process_feed( $feed, $entry, $form ) {

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {
		$accountName 		= GFCommon::replace_variables( $feed['meta']['accountName'], $form, $entry );
		$accountURL 		= GFCommon::replace_variables( $feed['meta']['accountURL'], $form, $entry );
		$contactName 		= GFCommon::replace_variables( $feed['meta']['contactName'], $form, $entry );
		$contactJobTitle 	= GFCommon::replace_variables( $feed['meta']['contactJobTitle'], $form, $entry );
		$contactEmail 		= GFCommon::replace_variables( $feed['meta']['contactEmail'], $form, $entry );
		$contactPhone 		= GFCommon::replace_variables( $feed['meta']['contactPhone'], $form, $entry );
		$leadMessage 		= GFCommon::replace_variables( $feed['meta']['leadMessage'], $form, $entry );

		$this->send_lead( $feed, array(
			'assignTo' => $feed['meta']['assignTo'],
			'accountName' => $accountName,
			'accountURL' => $accountURL,
			'contactName' => $contactName,
			'contactJobTitle' => $contactJobTitle,
			'contactEmail' => $contactEmail,
			'contactPhone' => $contactPhone,
			'leadMessage' => $leadMessage,
			'leadSource' => $feed['meta']['leadSource'],
			'leadWebsite' => $feed['meta']['leadWebsite'],
		));
	}

	public function is_valid_credentials() {
		$api = $this->get_api();
		return $api ? true : false;

	}

	private function send_lead( $feed, $params ) {
		
		$api = $this->get_api();
		
		try {
				
			// Create Contact
			$nutshell_params = array(
				'contact' => array(
					'name' => $params['contactName'],
					'email' => array( $params['contactEmail'] ),
					'phone' => array( $params['contactPhone'] ),
					'owner' => array(
						'entityType' => 'Users',
						'id' => $params['assignTo'],
					),
				),
			);
		
			$newContact = $api->newContact( $nutshell_params );
			$newContactId = $newContact->id;
		
			// Create Account
			$nutshell_params = array(
				'account' => array(
					'name' => $params['accountName'],
					'industryId' => 1,
					'url' => array( $params['accountURL'] ),
					'contacts' => array(
						array(
							'id' => $newContactId,
							'relationship' => $params['contactJobTitle']
						),
					),
					'owner' => array(
						'entityType' => 'Users',
						'id' => $params['assignTo'],
					),
				),
			);
			$newAccount = $api->newAccount( $nutshell_params );
			$newAccountId = $newAccount->id;
		
			$nutshell_params = array(
				'lead' => array(
					'primaryAccount' => array( 'id' => $newAccountId ),
					'confidence' => 0,
					'market' => array( 'id' => 1 ),
					'contacts' => array(
						array(
							'id' => $newContactId,
						),
					),
					'sources' => array( array( 'id' => $params['leadSource'] ), ),
					'assignee' => array(
						'entityType' => 'Users',
						'id' => $params['assignTo'],
					),
					'customFields' => array(
						'Website' => $params['leadWebsite'],
						'Descriptions' => 'test',
					)
				),
			);

			$newLead = $api->newLead( $nutshell_params );	
			$newLeadId = $newLead->id;
			
			$entity_params = array(
				'entityType' => 'Leads',
				'id' => $newLeadId,
			);
		
			$note_params = array(
				'entityType' => 'Notes',
				'note' => 'Message from Lead: ' . str_replace("<br />", "", $params['leadMessage']),
			);

			$newNote = $api->newNote( $entity_params, $note_params );
			
		} catch (Exception $e) {
			
		}
		
	}

	public function get_api() {
		require_once( $this->get_base_path() . '/api/NutshellApi.php' );

		// Nutshell REST API version
		$ApiVersion = '2010-04-01';

		// Set our AccountSid and AuthToken
		$settings = $this->get_plugin_settings();
		
		if ( !$settings )
			return false;
		
		// Instantiate a new Nutshell Rest Client
		$client            = new NutshellApi( $settings['username'], $settings['apiKey'] );

		return $client;
	}
	
}