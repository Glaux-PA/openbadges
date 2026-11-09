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
import('lib.pkp.classes.core.JSONMessage');

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
		if (!$request->isPost() || !$request->checkCSRF()) {
			return new JSONMessage(false, __('form.csrfInvalid'));
		}

		$submission = $this->getSubmission();
		$badges = (array) $request->getUserVar('badges');
		import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
		$badgeSubmissionDao = new BadgeSubmissionDAO();
		DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
		import('plugins.generic.openbadges.classes.BadgeSubmission');
		$badgeDao = DAORegistry::getDAO('BadgeDAO');

		$catalog = $badgeDao->getAll();
		while ($badge = $catalog->next()) {
			if (!isset($badges[$badge->getId()]) || !is_array($badges[$badge->getId()])) {
				continue;
			}
			$data = $badges[$badge->getId()];
			$badgeSubmission = $badgeSubmissionDao->getBySubmissionAndBadgeId($submission->getId(), $badge->getId());

			if (empty($data['isActive'])) {
				if ($badgeSubmission) {
					$badgeSubmissionDao->deleteById($badgeSubmission->getId());
				}
				continue;
			}

			$url = trim((string) (isset($data['url']) ? $data['url'] : ''));
			if ($url !== '#' && !preg_match('#^https?://\S+$#i', $url)) {
				$url = '#';
			}

			if (!$badgeSubmission) {
				$badgeSubmission = new BadgeSubmission();
				$badgeSubmission->setBadgeId($badge->getId());
				$badgeSubmission->setSubmissionId($submission->getId());
			}
			$badgeSubmission->setBadgeUrl($url);
			$badgeSubmission->setStatus(true);

			if ($badgeSubmission->getId()) {
				$badgeSubmissionDao->updateObject($badgeSubmission);
			} else {
				$badgeSubmissionDao->insertObject($badgeSubmission);
			}
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
