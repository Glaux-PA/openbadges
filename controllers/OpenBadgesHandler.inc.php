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

class OpenBadgesHandler extends Handler{
	static $plugin;

	/** @var boolean */
	var $_readOnly;


	/**
	 * Constructor
	 */
	function __construct(){
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('updateBadges')
		);
	}

	//
	// Getters/Setters
	//
	/**
	 * Set the Badge plugin.
	 * @param $plugin OpenBadgesPlugin
	 */
	static function setPlugin($plugin){
		self::$plugin = $plugin;
	}

	/**
	 * Get the submission associated
	 * @return Submission
	 */
	function getSubmission(){
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments){
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
	
	/**
	 * Update a submision badges
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateBadges($args, $request){

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

		$templateMgr = TemplateManager::getManager($request);
		$router = $request->getRouter();
		$dispatcher = Application::get()->getDispatcher();
		$context = $request->getContext();
		$url = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'index', [$submission->getId(), 5],null, "publication/badgesInWorkflow");

		// Make complete redirection
		$request->redirectUrl($url);

		return true;
	}

	/**
	 * Determines if there should be add/edit actions on this grid.
	 * @param $user User
	 * @return boolean
	 */
	function canAdminister($user) {
		$submission = $this->getSubmission();

		// Incomplete submissions can be edited. (Presumably author.)
		if ($submission->getDateSubmitted() == null) return true;
		// Managers should always have access.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (array_intersect(array(ROLE_ID_MANAGER), $userRoles)) return true;
		// Sub editors and assistants need to be assigned to the current stage.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), $submission->getStageId(), null, $user->getId());
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		while ($stageAssignment = $stageAssignments->next()) {
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
			if (in_array($userGroup->getRoleId(), array(ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT))) return true;
		}
		// Default: Read-only.
		return false;
	}

}
