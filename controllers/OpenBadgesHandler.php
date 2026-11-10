<?php

/**
 * @file plugins/generic/openbadges/controllers/OpenBadgesHandler.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class OpenBadgesHandler
 * @ingroup plugins_generic_openbadges
 *
 * @brief Handle Badge form requests.
 */

namespace APP\plugins\generic\openbadges\controllers;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\openbadges\classes\BadgeDAO;
use APP\plugins\generic\openbadges\classes\BadgeSubmission;
use APP\plugins\generic\openbadges\classes\BadgeSubmissionDAO;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

class OpenBadgesHandler extends Handler
{
    public static $plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_AUTHOR],
            ['getBadges', 'updateBadges']
        );
    }

    /**
     * Set the Badge plugin.
     *
     * @param \APP\plugins\generic\openbadges\OpenbadgesPlugin $plugin
     */
    public static function setPlugin($plugin)
    {
        self::$plugin = $plugin;
    }

    /**
     * Get the authorized submission
     *
     * @return \APP\submission\Submission
     */
    public function getSubmission()
    {
        return $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Get the badges of a submission
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function getBadges($args, $request)
    {
        return new JSONMessage(true, ['badges' => self::$plugin->getBadgesForWorkflow($this->getSubmission()->getId())]);
    }

    /**
     * Update the badges of a submission
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function updateBadges($args, $request)
    {
        if (!$request->isPost() || !$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $submission = $this->getSubmission();
        $badges = (array) $request->getUserVar('badges');

        $badgeDao = DAORegistry::getDAO('BadgeDAO');
        $badgeSubmissionDao = DAORegistry::getDAO('BadgeSubmissionDAO');

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

            $url = trim((string) ($data['url'] ?? ''));
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

        return new JSONMessage(true, ['badges' => self::$plugin->getBadgesForWorkflow($submission->getId())]);
    }
}
