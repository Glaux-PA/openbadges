<?php

/**
 * @file plugins/generic/openbadges/OpenBadgesPlugin.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class OpenBadgesPlugin
 * @ingroup plugins_generic_openbadges
 *
 * @brief Add openbadge data to the submission metadata,
 * and display them on the submission view page.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
use PKP\plugins\Hook;
class OpenBadgesPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        error_log('OpenBadgesPlugin: Registering plugin for context ID: ' . ($mainContextId ?? 'null'));
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                // Check if migration has already been run
                $migrationKey = 'openbadges_migration_run';
                if (!$this->getSetting($mainContextId, $migrationKey)) {
                    try {
                        // Log database configuration for debugging
                        $configFile = dirname(__DIR__, 3) . '/config.inc.php';
                        if (file_exists($configFile)) {
                            $config = parse_ini_file($configFile, true);
                            $dbConfig = $config['database'] ?? [];
                            error_log('OpenBadgesPlugin: Database config loaded: ' . print_r($dbConfig, true));
                        } else {
                            error_log('OpenBadgesPlugin: config.inc.php not found at ' . $configFile);
                        }

                        $this->install();
                        $this->updateSetting($mainContextId, $migrationKey, true);
                        error_log('OpenBadgesPlugin: Migration executed during registration for context ID: ' . ($mainContextId ?? 'null'));
                    } catch (Exception $e) {
                        error_log('OpenBadgesPlugin: Failed to run migration during registration: ' . $e->getMessage());
                        return false;
                    }
                }

                import('plugins.generic.openbadges.classes.BadgeDAO');
                $badgeDao = new BadgeDAO();
                DAORegistry::registerDAO('BadgeDAO', $badgeDao);

                import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
                $badgeSubmissionDao = new BadgeSubmissionDAO();
                DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);

                try {
                    $this->initBadges();
                    error_log('OpenBadgesPlugin: Badges initialized successfully.');
                    $this->checkAndAddSmallImageSetting();
                    #$this->getInstallMigration();
                    error_log('OpenBadgesPlugin: Small image settings checked successfully.');
                } catch (Exception $e) {
                    error_log('OpenBadgesPlugin: Failed to initialize badges or settings: ' . $e->getMessage());
                    return false;
                }

                $request = $this->getRequest();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->addStyleSheet(
                    'badgeStyles',
                    $this->getCssUrl($request) . '/styles.css',
                    ['contexts' => 'backend']
                );
                $templateMgr->addStyleSheet(
                    'badgeStyles',
                    $this->getCssUrl($request) . '/styles-front.css',
                    ['contexts' => 'frontend']
                );

                // Use a hook to extend the context entity's schema
                HookRegistry::register('LoadComponentHandler', array($this, 'setupBadgesHandler'));
                //Submission/publication form
                HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));
                // Frontend
                HookRegistry::register('Templates::Catalog::Book::Details', array($this, 'addSubmissionDisplay'));
                // Frontend - Badge Headers
                HookRegistry::register('ArticleHandler::view', array(&$this, 'addSubmissionDisplayHeaders'));
                // Frontend - Articles list - Add small badge icons
                HookRegistry::register('Templates::Issue::Issue::Article', array(&$this, 'addArticleBadges'));

            } else {
                // Optionally handle uninstallation logic
                // $this->uninstall();
            }
            error_log('OpenBadgesPlugin: Registration completed successfully.');
            return true;
        }
        error_log('OpenBadgesPlugin: Registration failed.');
        return false;
    }

    /**
     * Insert badges in articles list
     */
    public function addArticleBadges($hookName, $params)
    {
        $output =& $params[2];
        $smarty = $params[1];

        $article = $smarty->getTemplateVars('article');
        $submissionId = $article->getId();

        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        $badges = $this->getBadges($article->getId());
        if (!empty($badges)) {
            $badgeHtml = '<div class="article-badges">';
            foreach ($badges as $badge) {
                if ($badge['isActive']) {
                    $badgeHtml .= '<a href="' . htmlspecialchars($badge['url']) . '" target="_blank">';
                    $badgeHtml .= '<img src="/' . $pluginUrl . $badge['badgeSmallImage'] . '" alt="' . htmlspecialchars(__($badge['title'])) . '" />';
                    $badgeHtml .= '</a>';
                }
            }
            $badgeHtml .= '</div>';
            $output .= $badgeHtml;
        }

        return false;
    }

    /**
     * Permit requests to the Badges handler
     */
    public function setupBadgesHandler($hookName, $params)
    {
        $component =& $params[0];
        if ($component == 'plugins.generic.openbadges.controllers.OpenBadgesHandler') {
            import($component);
            OpenBadgesHandler::setPlugin($this);
            return true;
        }
        return false;
    }

    /**
     * Insert badges in the publication tabs
     */
    public function addToPublicationForms($hookName, $params)
    {
        $smarty =& $params[1];
        $output =& $params[2];
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        $submission = $smarty->getTemplateVars('submission');

        $smarty->assign([
            'submissionId' => $submission->getId(),
        ]);
        $badges = $this->getBadges($submission->getId());
        $smarty->assign('badges', $badges);
        $smarty->assign('pluginurl', $pluginUrl);
        $output .= sprintf(
            '<tab id="badgesInWorkflow" label="%s">%s</tab>',
            __('plugins.generic.openbadges.tabName'),
            $smarty->fetch($this->getTemplateResource('badgesform.tpl'))
        );
        return false;
    }

    /**
     * Hook to ArticleHandler::view add badge metadata
     */
    public function addSubmissionDisplayHeaders($hookName, $params)
    {
        $request = $params[0];
        $templateMgr = TemplateManager::getManager($request);
        $submission = $templateMgr->getTemplateVars('article');
        $badges = $this->getBadges($submission->getId());
        foreach ($badges as $badge) {
            if ($badge['isActive']) {
                $badgemetadata = $badge['badgeMetadata'];
                $templateMgr->addHeader('openbadge' . $badge['id'], '<meta name="open-badge" content="' . htmlspecialchars($badgemetadata) . '"/> ');
            }
        }
    }

    /**
     * Hook to Templates::Article::Details and Templates::Catalog::Book::Details and list badge information
     */
    public function addSubmissionDisplay($hookName, $params)
    {
        $request = $params[0];
        $templateMgr = $params[1];
        $output =& $params[2];

        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        $submission = $templateMgr->getTemplateVars('article');
        $badges = $this->getBadges($submission->getId());
        $templateMgr->assign('badges', $badges);
        $templateMgr->assign('pluginurl', $pluginUrl);
        $hasActive = false;
        foreach ($badges as $badge) {
            if ($badge['isActive']) {
                $hasActive = true;
                break;
            }
        }
        $output .= $templateMgr->fetch($this->getTemplateResource('block.tpl'));

        return false;
    }

    /**
     * Provide a name for this plugin
     */
    public function getDisplayName()
    {
        return __('plugins.generic.openbadges.displayName');
    }

    /**
     * Provide a description for this plugin
     */
    public function getDescription()
    {
        return __('plugins.generic.openbadges.description');
    }

    /**
     * Get plugin CSS URL
     */
    public function getCssUrl($request)
    {
        return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css';
    }

    /**
     * Get badges for a submission
     */
    public function getBadges($submissionID)
    {
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
        $badgeSubmissionDao = new BadgeSubmissionDAO();
        DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
        import('plugins.generic.openbadges.classes.BadgeSubmission');
        $badgeDAO = new BadgeDAO();
        $badges = $badgeDAO->getAll();
        $result = [];
        while ($badge = $badges->next()) {
            $badgeSubmission = new BadgeSubmission();
            if ($badgeSubmissionDao->existSubmisionBadge($submissionID, $badge->getId()) == false) {
                $badgeSubmission->setBadgeId($badge->getId());
                $badgeSubmission->setSubmissionId($submissionID);
                $badgeSubmission->setBadgeUrl("#");
                $badgeSubmission->setStatus(false);
                $badgeSubmissionDao->insertObject($badgeSubmission);
            } else {
                $badgeSubmission = $badgeSubmissionDao->getBySubmissionAndBadgeId($submissionID, $badge->getId());
            }

            $result[] = [
                'id' => $badge->getId(),
                'title' => $badge->getBadgeName(null),
                'description' => $badge->getBadgeDescription(null),
                'image' => $badge->getBadgeImage(null),
                'url' => $badgeSubmission->getBadgeUrl(),
                'isActive' => $badgeSubmission->getStatus(),
                'submissionID' => $badgeSubmission->getSubmissionId(),
                'badgeSubmissionID' => $badgeSubmission->getId(),
                'badgeMetadata' => $badge->getBadgeMetadata(),
                'badgeSmallImage' => $badge->getBadgeSmallImage()
            ];
        }
        return $result;
    }

    /**
     * Initialize default badges
     */
    public function initBadges()
    {
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        import('plugins.generic.openbadges.classes.BadgeDAO');
        $badgeDao = new BadgeDAO();
        DAORegistry::registerDAO('BadgeDAO', $badgeDao);
        import('plugins.generic.openbadges.classes.Badge');

        if (!$badgeDao->existBadges()) {
            $badges = [
                [
                    'name' => 'plugins.generic.openbadges.badge1name',
                    'description' => 'plugins.generic.openbadges.badge1description',
                    'image' => 'plugins.generic.openbadges.badge1image',
                    'metadata' => 'open-data',
                    'smallImage' => 'plugins.generic.openbadges.badge1simage'
                ],
                [
                    'name' => 'plugins.generic.openbadges.badge2name',
                    'description' => 'plugins.generic.openbadges.badge2description',
                    'image' => 'plugins.generic.openbadges.badge2image',
                    'metadata' => 'open-materials',
                    'smallImage' => 'plugins.generic.openbadges.badge2simage'
                ],
                [
                    'name' => 'plugins.generic.openbadges.badge3name',
                    'description' => 'plugins.generic.openbadges.badge3description',
                    'image' => 'plugins.generic.openbadges.badge3image',
                    'metadata' => 'preregistered',
                    'smallImage' => 'plugins.generic.openbadges.badge3simage'
                ],
                [
                    'name' => 'plugins.generic.openbadges.badge4name',
                    'description' => 'plugins.generic.openbadges.badge4description',
                    'image' => 'plugins.generic.openbadges.badge4image',
                    'metadata' => 'open-revision',
                    'smallImage' => 'plugins.generic.openbadges.badge4simage'
                ]
            ];

            foreach ($badges as $badgeData) {
                if ($badgeDao->getByName($badgeData['name']) === null) {
                    $badge = new Badge();
                    $badge->setBadgeName($badgeData['name'], null);
                    $badge->setBadgeDescription($badgeData['description'], null);
                    $badge->setBadgeimage($badgeData['image'], null);
                    $badge->setBadgeMetadata($badgeData['metadata']);
                    $badge->setBadgeSmallImage($badgeData['smallImage']);
                    $badgeDao->insertObject($badge);
                    error_log('Badge ' . $badgeData['name'] . ' inserted successfully.');
                }
            }
        }
    }

    /**
     * Add small image to badges
     */
    public function checkAndAddSmallImageSetting()
    {
        $badgeDao = DAORegistry::getDAO('BadgeDAO');
        $badges = $badgeDao->getAll();
        while ($badge = $badges->next()) {
            $smallImage = $badge->getBadgeSmallImage();
            if (empty($smallImage)) {
                $badgeId = $badge->getId();
                $defaultSmallImage = "plugins.generic.openbadges.badge{$badgeId}simage";
                $badge->setBadgeSmallImage(__($defaultSmallImage));
                $badgeDao->updateLocaleFields($badge);
                error_log('Small image set for badge ID: ' . $badgeId);
            }
        }
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        try {
            $this->getInstallMigration()->up();
            error_log('OpenBadgesPlugin: Migration completed successfully.');
        } catch (Exception $e) {
            error_log('OpenBadgesPlugin: Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @copydoc Plugin::getInstallMigration()
     */
    public function getInstallMigration()
    {
        $this->import('BadgesSchemaMigration');
        return new BadgesSchemaMigration();
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        try {
            $this->getInstallMigration()->down();
            error_log('OpenBadgesPlugin: Uninstallation completed successfully.');
        } catch (Exception $e) {
            error_log('OpenBadgesPlugin: Uninstallation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}