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
	 * Set the Badge plugin.
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
		
		$submissionId = $request->getUserVar('submissionId');

		$submissionDAO = DAORegistry::getDAO('SubmissionDAO');
		$submission = $submissionDAO->getById($submissionId);


		// Obtener una instancia del DAO de publicaciones
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publication = $publicationDao->getById($submission->getCurrentPublication()->getId());

		$badges = $request->getUserVar('badges');

        import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
        $badgeSubmissionDao = new BadgeSubmissionDAO();
        DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
        import('plugins.generic.openbadges.classes.BadgeSubmission');


		foreach ($badges as $badge) {
			$badgeSubmission = new BadgeSubmission();
			$badgeSubmissionID = (int) $badge['badgeSubmissionID'];
			$badgeSubmission = $badgeSubmissionDao->getById($badgeSubmissionID);
			$badgeSubmission->setBadgeUrl($badge['url']);
			$isActive = (bool)$badge['isActive'];
			$badgeSubmission->setStatus($isActive);
			$badgeSubmissionDao->updateObject($badgeSubmission);
		}


      // Redirige a una página de éxito o vuelve al formulario
	  $dispatcher = $request->getDispatcher();
	  #$dispatcher->handleRedirect();

	  return true;
    }

    /**
     * Maneja la carga del formulario.
     */
    public function tuMetodoManejadorDelFormulario($args, $request) {
        // Lógica para cargar y mostrar el formulario
        // Recupera los datos necesarios y asigna a la plantilla
        $templateMgr = TemplateManager::getManager($request);
        // Cargar datos para el formulario...

        // Asigna los datos a la plantilla
        $templateMgr->display('ruta/a/badgesform.tpl');
    }




}