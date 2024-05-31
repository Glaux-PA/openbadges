<?php

/**
 * @file plugins/generic/openbadges/controllers/OpenBadgesHandler.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class OpenBadgesHandler
 * @ingroup plugins_generic_openbadges
 *
 * @brief Handle Badge form requests.
 */

import('lib.pkp.classes.handler.PKPHandler');

class OpenBadgesHandler extends Handler
{
	static $plugin;

	/** @var boolean */
	var $_readOnly;


	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
		//TODO ADD secutity
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

	static function setPlugin($plugin)
	{
		self::$plugin = $plugin;
	}


	/**
	 * Get the submission associated
	 * @return Submission
	 */

	function getSubmission()
	{
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}


	//
	// Overridden template methods
	//

	//TODO ADD secutity
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
	function updateBadges($args, $request)
	{

		$submissionId = $request->getUserVar('submissionId');

		$submissionDAO = DAORegistry::getDAO('SubmissionDAO');
		$submission = $submissionDAO->getById($submissionId);
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
			$isActive = isset($badge['isActive']) ? (bool)$badge['isActive'] : false;
			$badgeSubmission->setStatus($isActive);
			$badgeSubmissionDao->updateObject($badgeSubmission);
		}


		// TODO Redirige a una página de éxito o vuelve al formulario
		#$dispatcher = $request->getDispatcher();
		#$dispatcher->handleRedirect();
		#$dispatcher->handleRedirect(null, null, "submissions/$submissionId"); // Ajusta esta línea según tu ruta de formulario

		$templateMgr = TemplateManager::getManager($request);
		#$templateMgr->display(self::$plugin->getTemplateResource('badgesform.tpl'));
		#$output =& $args[1];
		#$output .= $templateMgr->fetch(self::$plugin->getTemplateResource('badgesform.tpl'));


		// Redirigir de vuelta al formulario
		#$dispatcher = $request->getDispatcher();
		#$dispatcher->handleRedirect();

		#$this->setupTemplate($request);
		#$dispatcher = $request->getDispatcher();
		#$dispatcher->handleRedirect();

		// Redirigir de vuelta al formulario
		$router = $request->getRouter();
		#$url = $router->url($request, null, 'OpenBadgesPlugin', 'addToPublicationForms', null, array('submissionId' => $submissionId));
		#$url = $router->url($request, null, 'workflow', 'index', $submissionId);
		#$url = $router->url($request, null, 'workflow', 'index', null, array('submissionId' => $submissionId , '1'));
		#$dispatcher = $request->getDispatcher();
		#$url = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'index', array($submissionId, '1'));

		#return $request->redirectUrlJson($url);
		$dispatcher = Application::get()->getDispatcher();
		$context = $request->getContext();
		$url = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'index', [$submission->getId(), 5],null, "publication/badgesInWorkflow");
		echo $url;
		// $router = $request->getRouter();
		//     $url = $router->url($request, null, 'authorDashboard', 'submission', $submission->getId());

		// Realizar la redirección completa
		$request->redirectUrl($url);



		return true;
	}
}
