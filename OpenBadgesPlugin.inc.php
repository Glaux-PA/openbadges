<?php

import('lib.pkp.classes.plugins.GenericPlugin');

require_once('BadgesSchemaMigration.inc.php');

class OpenBadgesPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = NULL) {

        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                //TODO change this call and adapt to plugin lifecicle
                $this->install();
                $this->inicializarBadges();
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
                #HookRegistry::register('Schema::get::context', array($this, 'addToSchema'));

                HookRegistry::register('LoadComponentHandler', array($this, 'setupBadgesHandler'));


                //Submission/publication form
                HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));
                // Frontend
                HookRegistry::register('Templates::Article::Details', array($this, 'addSubmissionDisplay'));


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
		$submission = $smarty->get_template_vars('submission');
		$smarty->assign([
			'submissionId' => $submission->getId(),
		]);

        //Recuperamos las insignias para esta publicacion
        $badges = $this->obtenerBadges($submission->getId());
        $smarty->assign('badges', $badges);
        // Registrar el archivo CSS para que se pueda cargar en el template
        #$templateMgr = TemplateManager::getManager($request);
        #$templateMgr->addStylesheet('badgeStyles', $this->getPluginPath() . '/css/styles.css');






		$output .= sprintf(
			'<tab id="badgesInWorkflow" label="%s">%s</tab>',
			__('plugins.generic.openbadges.tabName'),
			$smarty->fetch($this->getTemplateResource('badgesform.tpl'))
		);

		return false;
	}


	/**
	* Hook to Templates::Article::Details and Templates::Catalog::Book::Details and list funder information
	* @param $hookName string
	* @param $params array
	*/
	function addSubmissionDisplay($hookName, $params) {

        $templateMgr = $params[1];
		$output =& $params[2];

        $submission = $templateMgr->get_template_vars('article');

        //Recuperamos las insignias para esta publicacion
        $badges = $this->obtenerBadges($submission->getId());
        $templateMgr->assign('badges', $badges);

        #$templateMgr->addStylesheet('badgeStyles', $this->getPluginPath() . '/css/styles.css');

        #$templateMgr->addStyleSheet(
        #    'badgeStyles', 'css/styles.css',
        #    array('contexts' => 'backend')
        #);

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


    function obtenerBadges($submissionID) {

        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');

        import('plugins.generic.openbadges.classes.BadgeSubmissionDAO');
        $badgeSubmissionDao = new BadgeSubmissionDAO();
        DAORegistry::registerDAO('BadgeSubmissionDAO', $badgeSubmissionDao);
        import('plugins.generic.openbadges.classes.BadgeSubmission');

        $badgeDAO = new BadgeDAO();
        $badges = $badgeDAO->getAll();

        $result = [];
        while ($badge = $badges->next()) {
            #error_log("");

            // Procesar cada badge
            $badgeSubmission = new BadgeSubmission();
            if($badgeSubmissionDao->existSubmisionBadge($submissionID, $badge->getId()) == false){

                $badgeSubmission->setBadgeId($badge->getId());
                $badgeSubmission->setSubmissionId($submissionID);
                $badgeSubmission->setBadgeUrl("#");
                $badgeSubmission->setStatus(false);
                $badgeSubmissionDao->insertObject($badgeSubmission);
            }else{
                $badgeSubmission = $badgeSubmissionDao->getBySubmissionAndBadgeId($submissionID, $badge->getId());
                #error_log("badgeSubmission->getStatus(): ".$badgeSubmission->getStatus());
            }
            $result[] = [
                'id' => $badge->getId(),
                'title' => $badge->getBadgeName(),
                'description' => $badge->getBadgeDescription(),
                'image' => $badge->getBadgeImage(),
                'url' => $badgeSubmission->getBadgeUrl(),
                'isActive' => $badgeSubmission->getStatus(),
                'submissionID' => $badgeSubmission->getSubmissionId(),
                'badgeSubmissionID' => $badgeSubmission->getId()
            ];

        }

        return $result;
    }

    //TODO 2 options for this
    // 1-Load initial data from xml or json
    // 2-Add settings section for manage that data
    function inicializarBadges(){

        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');
        import('plugins.generic.openbadges.classes.BadgeDAO');
        $badgeDao = new BadgeDAO();
        DAORegistry::registerDAO('BadgeDAO', $badgeDao);
        import('plugins.generic.openbadges.classes.Badge');

        //TODO refactor this method to manage dinamically this conditions
        #Datos Abiertos
        if(!$badgeDao->existBadges()){
            if($badgeDao->getByName(__('plugins.generic.openbadges.badge1name')) === null){
                $badge = new Badge();
                $badge->setBadgeName(__('plugins.generic.openbadges.badge1name'));
                $badge->setBadgeDescription(__('plugins.generic.openbadges.badge1description'));
                $badge->setBadgeimage($pluginUrl . '/images/data_large_color.png');
                $badgeDao->insertObject($badge);
            }

            #Materiales Abiertos
            if($badgeDao->getByName(__('plugins.generic.openbadges.badge2name')) === null){
                $badge = new Badge();
                $badge->setBadgeName( __('plugins.generic.openbadges.badge2name'));
                $badge->setBadgeDescription(__('plugins.generic.openbadges.badge2description'));
                $badge->setBadgeimage($pluginUrl . '/images/materials_large_color.png');
                $badgeDao->insertObject($badge);
            }

            #Registro
            if($badgeDao->getByName(__('plugins.generic.openbadges.badge3name')) === null){
                $badge = new Badge();
                $badge->setBadgeName( __('plugins.generic.openbadges.badge3name'));
                $badge->setBadgeDescription(__('plugins.generic.openbadges.badge3description'));
                $badge->setBadgeimage($pluginUrl . '/images/preregistered_large_color.png');
                $badgeDao->insertObject($badge);
            }

            #Revisión Transparente
            if($badgeDao->getByName(__('plugins.generic.openbadges.badge4name')) === null){
                $badge = new Badge();
                $badge->setBadgeName( __('plugins.generic.openbadges.badge4name'));
                $badge->setBadgeDescription(__('plugins.generic.openbadges.badge4description'));
                $badge->setBadgeimage($pluginUrl . '/images/open_revision_badge.png');
                $badgeDao->insertObject($badge);
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
		#$this->import('BadgesSchemaMigration');
		return new BadgesSchemaMigration();
	}

    /*
    function updateSchema($hookName, $args) {
        $this->getInstallMigration()->up();
    }
    */

    public function uninstall() {
        $this->getInstallMigration()->down();
    }



}