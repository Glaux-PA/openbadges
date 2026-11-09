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

		$submission = $this->getSubmission();
		$badges = $request->getUserVar('badges');
		import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
		$badgeSubmissionDao = new BadgeSubmissionDAO();
		DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
		import('plugins.generic.openbadges.classes.BadgeSubmission');

		foreach ((array) $badges as $badge) {
			$badgeSubmission = $badgeSubmissionDao->getById((int) (isset($badge['badgeSubmissionID']) ? $badge['badgeSubmissionID'] : 0));
			if (!$badgeSubmission || (int) $badgeSubmission->getSubmissionId() !== (int) $submission->getId()) {
				continue;
			}
			$url = trim((string) (isset($badge['url']) ? $badge['url'] : ''));
			if ($url !== '#' && !preg_match('#^https?://\S+$#i', $url)) {
				$url = '#';
			}
			$badgeSubmission->setBadgeUrl($url);
			$badgeSubmission->setStatus(!empty($badge['isActive']));
			$badgeSubmissionDao->updateObject($badgeSubmission);
		}

		$userRoles = (array) $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$isEditorial = (bool) array_intersect(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT), $userRoles);
		$dispatcher = Application::get()->getDispatcher();
		$context = $request->getContext();
		if ($isEditorial) {
			$url = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', 'index', [$submission->getId(), 5], null, "publication/badgesInWorkflow");
		} else {
			$url = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'authorDashboard', 'submission', [$submission->getId()], null, "publication/badgesInWorkflow");
		}

		// Make complete redirection
		$request->redirectUrl($url);

		return true;
	}

}
