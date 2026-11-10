<?php

/**
 * @file plugins/generic/openbadges/OpenbadgesPlugin.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class OpenbadgesPlugin
 * @ingroup plugins_generic_openbadges
 *
 * @brief Add openbadge data to the submission metadata,
 * and display them on the submission view page.
 */

namespace APP\plugins\generic\openbadges;

use APP\core\Application;
use APP\plugins\generic\openbadges\classes\Badge;
use APP\plugins\generic\openbadges\classes\BadgeDAO;
use APP\plugins\generic\openbadges\classes\BadgeSubmission;
use APP\plugins\generic\openbadges\classes\BadgeSubmissionDAO;
use APP\plugins\generic\openbadges\controllers\OpenBadgesHandler;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\Schema;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class OpenbadgesPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (!$this->getEnabled($mainContextId)) {
            return true;
        }

        if (!Schema::hasTable('badge')) {
            try {
                $this->getInstallMigration()->up();
            } catch (\Throwable $e) {
                error_log('OpenbadgesPlugin: could not create the database schema: ' . $e->getMessage());
                return false;
            }
        }

        DAORegistry::registerDAO('BadgeDAO', new BadgeDAO());
        DAORegistry::registerDAO('BadgeSubmissionDAO', new BadgeSubmissionDAO());

        try {
            $this->initBadges();
            $this->checkAndAddSmallImageSetting();
        } catch (\Throwable $e) {
            error_log('OpenbadgesPlugin: could not initialize badges: ' . $e->getMessage());
            return false;
        }

        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->addStyleSheet(
            'badgeStyles',
            $this->getCssUrl($request) . '/styles.css',
            ['contexts' => 'backend']
        );
        $templateMgr->addStyleSheet(
            'badgeFrontStyles',
            $this->getCssUrl($request) . '/styles-front.css',
            ['contexts' => 'frontend']
        );

        Hook::add('LoadComponentHandler', [$this, 'setupBadgesHandler']);
        Hook::add('Template::Workflow::Publication', [$this, 'addToPublicationForms']);
        Hook::add('Templates::Article::Details', [$this, 'addSubmissionDisplay']);
        Hook::add('ArticleHandler::view', [$this, 'addSubmissionDisplayHeaders']);
        Hook::add('Templates::Issue::Issue::Article', [$this, 'addArticleBadges']);

        return true;
    }

    /**
     * Insert badges in articles list
     */
    public function addArticleBadges($hookName, $params)
    {
        $smarty = $params[1];
        $output = & $params[2];

        $article = $smarty->getTemplateVars('article');
        if (!$article) {
            return false;
        }

        $request = Application::get()->getRequest();
        $assetsUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

        $badgeHtml = '';
        foreach ($this->getBadges($article->getId()) as $badge) {
            if ($badge['isActive']) {
                $badgeHtml .= '<a href="' . htmlspecialchars($badge['url']) . '" target="_blank" rel="noopener">'
                    . '<img src="' . htmlspecialchars($assetsUrl . $badge['badgeSmallImage']) . '" alt="' . htmlspecialchars(__($badge['title'])) . '" />'
                    . '</a>';
            }
        }
        if ($badgeHtml !== '') {
            $output .= '<div class="article-badges">' . $badgeHtml . '</div>';
        }

        return false;
    }

    /**
     * Permit requests to the Badges handler
     */
    public function setupBadgesHandler($hookName, $params)
    {
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component === 'plugins.generic.openbadges.controllers.OpenBadgesHandler') {
            OpenBadgesHandler::setPlugin($this);
            $componentInstance = new OpenBadgesHandler();
            return true;
        }
        return false;
    }

    /**
     * Insert badges in the publication tabs
     */
    public function addToPublicationForms($hookName, $params)
    {
        $smarty = & $params[1];
        $output = & $params[2];
        $request = Application::get()->getRequest();
        $submission = $smarty->getTemplateVars('submission');

        $smarty->assign([
            'submissionId' => $submission->getId(),
            'badges' => $this->getBadges($submission->getId()),
            'pluginurl' => $this->getPluginPath(),
            'pluginBaseUrl' => $request->getBaseUrl(),
        ]);
        $output .= sprintf(
            '<tab id="badgesInWorkflow" label="%s">%s</tab>',
            __('plugins.generic.openbadges.tabName'),
            $smarty->fetch($this->getTemplateResource('badgesform.tpl'))
        );
        return false;
    }

    /**
     * Hook to ArticleHandler::view: add badge metadata to the page head
     */
    public function addSubmissionDisplayHeaders($hookName, $params)
    {
        $request = $params[0];
        $article = $params[2];
        $templateMgr = TemplateManager::getManager($request);
        foreach ($this->getBadges($article->getId()) as $badge) {
            if ($badge['isActive']) {
                $templateMgr->addHeader(
                    'openbadge' . $badge['id'],
                    '<meta name="open-badge" content="' . htmlspecialchars($badge['badgeMetadata']) . '"/>'
                );
            }
        }
        return false;
    }

    public function addSubmissionDisplay($hookName, $params)
    {
        $templateMgr = $params[1];
        $output = & $params[2];
        $request = Application::get()->getRequest();

        $article = $templateMgr->getTemplateVars('article');
        if (!$article) {
            return false;
        }

        $badges = $this->getBadges($article->getId());
        if (!array_filter($badges, fn ($badge) => $badge['isActive'])) {
            return false;
        }

        $templateMgr->assign([
            'badges' => $badges,
            'pluginurl' => $this->getPluginPath(),
            'pluginBaseUrl' => $request->getBaseUrl(),
        ]);
        $output .= $templateMgr->fetch($this->getTemplateResource('block.tpl'));

        return false;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.openbadges.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
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

    public function getBadges($submissionID): array
    {
        $badgeSubmissionDao = DAORegistry::getDAO('BadgeSubmissionDAO');
        $badgeDAO = DAORegistry::getDAO('BadgeDAO');

        $result = [];
        $badges = $badgeDAO->getAll();
        while ($badge = $badges->next()) {
            $badgeSubmission = $badgeSubmissionDao->getBySubmissionAndBadgeId($submissionID, $badge->getId());
            if (!$badgeSubmission) {
                $badgeSubmission = new BadgeSubmission();
                $badgeSubmission->setBadgeId($badge->getId());
                $badgeSubmission->setSubmissionId($submissionID);
                $badgeSubmission->setBadgeUrl('#');
                $badgeSubmission->setStatus(false);
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
    public function initBadges(): void
    {
        $badgeDao = DAORegistry::getDAO('BadgeDAO');
        if ($badgeDao->existBadges()) {
            return;
        }

        $badges = [
            ['open-data', 1],
            ['open-materials', 2],
            ['preregistered', 3],
            ['open-revision', 4],
        ];

        foreach ($badges as [$metadata, $n]) {
            $name = "plugins.generic.openbadges.badge{$n}name";
            if ($badgeDao->getByName($name) === null) {
                $badge = new Badge();
                $badge->setBadgeName($name, null);
                $badge->setBadgeDescription("plugins.generic.openbadges.badge{$n}description", null);
                $badge->setBadgeimage("plugins.generic.openbadges.badge{$n}image", null);
                $badge->setBadgeMetadata($metadata);
                $badge->setBadgeSmallImage(__("plugins.generic.openbadges.badge{$n}simage"));
                $badgeDao->insertObject($badge);
            }
        }
    }

    /**
     * Add small image to badges
     */
    public function checkAndAddSmallImageSetting(): void
    {
        $badgeDao = DAORegistry::getDAO('BadgeDAO');
        $badges = $badgeDao->getAll();
        while ($badge = $badges->next()) {
            $smallImage = $badge->getBadgeSmallImage();
            if (empty($smallImage) || str_starts_with($smallImage, 'plugins.generic.openbadges.')) {
                $key = empty($smallImage) ? "plugins.generic.openbadges.badge{$badge->getId()}simage" : $smallImage;
                $badge->setBadgeSmallImage(__($key));
                $badgeDao->updateLocaleFields($badge);
            }
        }
    }

    /**
     * @copydoc Plugin::getInstallMigration()
     */
    public function getInstallMigration()
    {
        return new BadgesSchemaMigration();
    }
}
