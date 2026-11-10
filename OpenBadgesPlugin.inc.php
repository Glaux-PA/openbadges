<?php

/**
 * @file plugins/generic/openbadges/OpenBadgesPlugin.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class OpenBadgesPlugin
 * @ingroup plugins_generic_openbadges

 * @brief Add openbadge data to the submission metadata,
 * and display them on the submission view page.
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

//require_once('BadgesSchemaMigration.inc.php');

class OpenBadgesPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = NULL) {

        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                //TODO change this call and adapt to plugin lifecicle
                $this->install();
                import('plugins.generic.openbadges.classes.BadgeDAO');
                $badgeDao = new BadgeDAO();
                DAORegistry::registerDAO('BadgeDAO', $badgeDao);
    
                import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
                $badgeSubmissionDao = new BadgeSubmissionDAO();
                DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);


                $this->initBadges();
                //Update badge settings
                $this->checkAndAddSmallImageSetting();
                #$this->getInstallMigration();
                $request = $this->getRequest();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->addStyleSheet(
                    'badgeStyles',
                    $this->getCssUrl($request).'/styles.css',
                    array('contexts' => 'backend')
                );
                $templateMgr->addStyleSheet(
                    'badgeStyles',
                    $this->getCssUrl($request).'/styles-front.css',
                    array('contexts' => 'frontend')
                );

                // Use a hook to extend the context entity's schema
                HookRegistry::register('LoadComponentHandler', array($this, 'setupBadgesHandler'));
                //Submission/publication form
                HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));
                // Frontend
                HookRegistry::register('Templates::Article::Details', array($this, 'addSubmissionDisplay'));
                // Frontend - Badge Headers
                HookRegistry::register('ArticleHandler::view', array(&$this, 'addSubmissionDisplayHeaders'));
                // Frontend - Articles list - Add small badge icons
                HookRegistry::register('Templates::Issue::Issue::Article', array(&$this, 'addArticleBadges'));


            } else {
                //TODO change this call and adapt to plugin lifecicle
                // or make a alert windows that allow user to confirm the removal of badges data
                #$this->uninstall();
            }
            return true;
        }
        return false;
    }

	/**
	 * Insert badgeds in articles list
	 */
    function addArticleBadges($hookName, $params) {
        $output =& $params[2];
        $smarty = $params[1];

        $article = $smarty->getTemplateVars('article');
        $assetsUrl = $this->getRequest()->getBaseUrl() . '/' . $this->getPluginPath();

        $badgeHtml = '';
        foreach ($this->getBadges($article->getId()) as $badge) {
            if ($badge['isActive']) {
                $badgeHtml .= '<a href="' . htmlspecialchars($badge['url']) . '" target="_blank" rel="noopener">';
                $badgeHtml .= '<img src="' . htmlspecialchars($assetsUrl . $badge['badgeSmallImage']) . '" alt="' . htmlspecialchars(__($badge['title'])) . '" />';
                $badgeHtml .= '</a>';
            }
        }
        if ($badgeHtml !== '') {
            $output .= '<div class="article-badges">' . $badgeHtml . '</div>';
        }

        return false;
    }

	/**
	 * Permit requests to the Badges handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupBadgesHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.openbadges.controllers.OpenBadgesHandler') {
			import($component);
			OpenBadgesHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Insert badgeds in the publication tabs
	 */
	function addToPublicationForms($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
		$submission = $smarty->get_template_vars('submission');
		$smarty->assign([
			'submissionId' => $submission->getId(),
		]);
        //Retrieve badges for this publication
        $badges = $this->getBadges($submission->getId());
        $smarty->assign('badges', $badges);
        $smarty->assign('pluginurl', $pluginUrl);
        $smarty->assign('pluginBaseUrl', $this->getRequest()->getBaseUrl());
		$output .= sprintf(
			'<tab id="badgesInWorkflow" label="%s">%s</tab>',
			__('plugins.generic.openbadges.tabName'),
			$smarty->fetch($this->getTemplateResource('badgesform.tpl'))
		);
		return false;
	}

	/**
	* Hook to ArticleHandler::view add badge metadata
	* @param $hookName string
	* @param $params array
	*/
    function addSubmissionDisplayHeaders($hookName, $params) {
        $request = $params[0];    
        $templateMgr = TemplateManager::getManager($request);
        $submission = $templateMgr->get_template_vars('article');
        $badges = $this->getBadges($submission->getId());
        foreach($badges as $badge){
            if($badge['isActive']){
                $badgemetadata = $badge['badgeMetadata']; 
                $templateMgr->addHeader('openbadge'.$badge['id'], '<meta name="open-badge" content="' 
                . htmlspecialchars($badgemetadata) . '"/> ');
            }   
        }
    }


	/**
	* Hook to Templates::Article::Details and Templates::Catalog::Book::Details and list badge information
	* @param $hookName string
	* @param $params array
	*/
	function addSubmissionDisplay($hookName, $params) {
        $request = $params[0];
        $templateMgr = $params[1];
		$output =& $params[2];
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        $submission = $templateMgr->get_template_vars('article');
        //Retrieve badges for this publication
        $badges = $this->getBadges($submission->getId());
        $hasActive = false;
        foreach ($badges as $badge) {
            if ($badge['isActive']) {
                $hasActive = true;
                break;
            }
        }
        if (!$hasActive) {
            return false;
        }
        $templateMgr->assign('badges', $badges);
        $templateMgr->assign('pluginurl', $pluginUrl);
        $templateMgr->assign('pluginBaseUrl', $this->getRequest()->getBaseUrl());
        $output .= $templateMgr->fetch($this->getTemplateResource('block.tpl'));

		return false;
	}

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
    {
        return __('plugins.generic.openbadges.displayName');
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
    {
        return __('plugins.generic.openbadges.description');
    }

    /**
	 * Get plugin CSS URL
	 *
	 * @return string Public plugin CSS URL
	 */
	function getCssUrl($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css';
	}

    function getBadges($submissionID) {
        import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
        $badgeSubmissionDao = new BadgeSubmissionDAO();
        DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
        import('plugins.generic.openbadges.classes.BadgeSubmission');
        $badgeDAO = new BadgeDAO();
        $badges = $badgeDAO->getAll();
        $result = [];
        while ($badge = $badges->next()) {
            $badgeSubmission = $badgeSubmissionDao->getBySubmissionAndBadgeId($submissionID, $badge->getId());
            if (!$badgeSubmission) {
                $badgeSubmission = new BadgeSubmission();
                $badgeSubmission->setBadgeId($badge->getId());
                $badgeSubmission->setSubmissionId($submissionID);
                $badgeSubmission->setBadgeUrl("#");
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

    //TODO 2 options for this
    // 1-Load initial data from xml or json
    // 2-Add settings section for manage that data
    function initBadges(){
        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        import('plugins.generic.openbadges.classes.BadgeDAO');
        $badgeDao = new BadgeDAO();
        DAORegistry::registerDAO('BadgeDAO', $badgeDao);
        import('plugins.generic.openbadges.classes.Badge');

        //TODO refactor this method to manage dinamically this conditions
        #Open Data
        if(!$badgeDao->existBadges()){
            if($badgeDao->getByName('plugins.generic.openbadges.badge1name') === null){
                $badge = new Badge();
                $badge->setBadgeName('plugins.generic.openbadges.badge1name', null);
                $badge->setBadgeDescription('plugins.generic.openbadges.badge1description', null);
                $badge->setBadgeimage('plugins.generic.openbadges.badge1image', null);
                $metadataValue="open-data";
                $badge->setBadgeMetadata($metadataValue);
                $badgeDao->insertObject($badge);                
            }

            #Open Materials
            if($badgeDao->getByName('plugins.generic.openbadges.badge2name') === null){
                $badge = new Badge();
                $badge->setBadgeName('plugins.generic.openbadges.badge2name', null);
                $badge->setBadgeDescription('plugins.generic.openbadges.badge2description', null);
                $badge->setBadgeimage('plugins.generic.openbadges.badge2image', null);
                $metadataValue="open-materials";
                $badge->setBadgeMetadata($metadataValue);
                $badgeDao->insertObject($badge);
            }

            #Preregistered
            if($badgeDao->getByName('plugins.generic.openbadges.badge3name') === null){
                $badge = new Badge();
                $badge->setBadgeName('plugins.generic.openbadges.badge3name', null);
                $badge->setBadgeDescription('plugins.generic.openbadges.badge3description', null);
                $badge->setBadgeimage('plugins.generic.openbadges.badge3image', null);
                $metadataValue="preregistered";
                $badge->setBadgeMetadata($metadataValue);                
                $badgeDao->insertObject($badge);
            }

            #Open Revision
            if($badgeDao->getByName('plugins.generic.openbadges.badge4name') === null){
                $badge = new Badge();
                $badge->setBadgeName('plugins.generic.openbadges.badge4name', null);
                $badge->setBadgeDescription('plugins.generic.openbadges.badge4description', null);
                $badge->setBadgeimage('plugins.generic.openbadges.badge4image', null);
                $metadataValue="open-revision";
                $badge->setBadgeMetadata($metadataValue);                
                $badgeDao->insertObject($badge);
            }
        }
    }

    /*
    * Method to add small image to badges
    * /TODO refactor for dinamic manage updates in badge/submission settings
    */
    function checkAndAddSmallImageSetting() {
        $badgeDao = DAORegistry::getDAO('BadgeDAO');
        $badges = $badgeDao->getAll();
        while ($badge = $badges->next()) {
            $smallImage = $badge->getBadgeSmallImage();

            if (empty($smallImage)) {
                $badgeId = $badge->getId();
                $defaultSmallImage = "plugins.generic.openbadges.badge{$badgeId}simage";
                $badge->setBadgeSmallImage(__($defaultSmallImage));
                $badgeDao->updateLocaleFields($badge);
            }
        }
    }


    function install() {
        $this->getInstallMigration()->up();
    }

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration() {
		$this->import('BadgesSchemaMigration');
		return new BadgesSchemaMigration();
	}

    public function uninstall() {
        $this->getInstallMigration()->down();
    }
}