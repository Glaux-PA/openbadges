<?php


import('lib.pkp.classes.handler.PKPHandler');

class OpenBadgesHandler extends Handler{
	static $plugin;

	/** @var boolean */
	var $_readOnly;


    /**
	 * Constructor
	 */
	
	function __construct() {
		parent::__construct();
		/*
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('fetchGrid', 'fetchRow', 'addFunder', 'editFunder', 'updateFunder', 'deleteFunder')
		);
		*/
	}
	
    //
	// Getters/Setters
	//
	/**
	 * Set the Funder plugin.
	 * @param $plugin OpenBadgesPlugin
	 */
	
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}
	

	/**
	 * Get the submission associated
	 * @return Submission
	 */
	
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}
	
    //
	// Overridden template methods
	//

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	/*
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
	*/


	/**		
	 * Update a submision badges
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateBadges($args, $request) {
		
		$context = $request->getContext();
		$submission = $this->getSubmission();
		#$submissionId = $submission->getId();

		echo "Cambios guardados exitosamente con parámetros:";
		$serial = serialize($request);
		print_r($request->getUserVar('submissionId'));
		print_r($request->getUserVar('badges'));


		echo "\n";
		$submissionId = $request->getUserVar('submissionId');
		$submissionDAO = \DAORegistry::getDAO('SubmissionDAO');
		$submission = $submissionDAO->getById($submissionId);
		print_r("SubmisionID: " . $submissionId . "\n");
		echo "\n";
		echo "Submision: ";
		print_r($submission);
		/*
		if (is_null($submission)) {
			continue;
		}
		*/

		//TODO: comprobar que existe a submission con ese id


		$publicationId = $submission->getLatestPublication()->getId();
		echo "\n";
		echo "Publicacion: ";
		print_r($publicationId);



		// Obtener una instancia del DAO de publicaciones
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publication = $publicationDao->getById($publicationId);


		// Verificar si se encontró la publicación
	if ($publication) {


		// Acceder a los datos de la publicación
		//$settingValue = $publication->getData('enabledBadge0');

		// Obtener los settings actuales de la publicación
		$settings = $publication->getSettings();
		echo "Settings de la publicacion: \n";
		print_r($settings);

		if($settingValue){
			// Actualizar un valor de configuración
			$publication->updateSetting('enabledBadge0', 'true');
			//$publicationDao->updateObject($publication);

			// Eliminar un valor de configuración
			//$publication->deleteSetting('setting_key');
			//$publicationDao->updateObject($publication);

		}else{
			// Crear un nuevo setting
			$settingKey = 'enabledBadge0';
			$settingValue = 'false';
			$publication->updateSetting($settingKey, $settingValue);
			//$newSetting = new PublicationSetting($settingKey, $settingValue);

			// Agregar el nuevo setting a la publicación
			//$publication->addSetting($newSetting);

			// Guardar los cambios en la base de datos
			//$publicationDao->updateObject($publication);
			

		}

		$publicationDao->updateObject($publication);
		echo "Valor para el setting enabledBadge0: ";
		print($publication->getData('enabledBadge0'));

	} else {
		// La publicación no fue encontrada
	}




		return false;

    }




}