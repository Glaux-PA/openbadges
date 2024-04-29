<?php

import('lib.pkp.classes.plugins.GenericPlugin');


class OpenBadgesPlugin extends GenericPlugin
{
    
    public function register($category, $path, $mainContextId = NULL)
    {
        // Register the plugin even when it is not enabled
        $success = parent::register($category, $path, $mainContextId);


        if ($success && $this->getEnabled($mainContextId)) {

            // Use a hook to extend the context entity's schema
            #HookRegistry::register('Schema::get::context', array($this, 'addToSchema'));

            HookRegistry::register('LoadComponentHandler', array($this, 'setupBadgesHandler'));


            HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));


        }

        return $success;
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


        // Asigna la función como una variable Smarty
        #$smarty->assign('changeHandler', 'changeHandler');

        $badges = $this->obtenerBadges();
        $smarty->assign('badges', $badges);

        /*
		$output .= sprintf(
			'<tab id="badgesInWorkflow" label="%s">%s</tab>',
			__('plugins.generic.openbadges.tabName'),
			$smarty->fetch($this->getTemplateResource('badgesform.tpl'))
		);
        */
        $output .= sprintf(
			'<tab id="badgesInWorkflow" label="%s">%s</tab>',
			'ola',
			$smarty->fetch($this->getTemplateResource('badgesform.tpl'))
		);

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


    function obtenerBadges() {

        $pluginUrl = \Plugin::getPluginPath('generic', 'openbadges');

        return [
            [   
                'id' => 1,
                'title' => __('plugins.generic.openbadges.badge1name'),
                'description' => __('plugins.generic.openbadges.badge1description'),
                'image' => $pluginUrl . '/images/data_large_color.png',
                'url' => 'https://ejemplo.com/badge2',
                'isActive' => false
            ],
            [
                'id' => 2,
                'title' => __('plugins.generic.openbadges.badge2name'),
                'description' => __('plugins.generic.openbadges.badge2description'),
                'image' => $pluginUrl . '/images/materials_large_color.png',
                'url' => 'https://ejemplo.com/badge2',
                'isActive' => false
            ],
            [
                'id' => 3,
                'title' => __('plugins.generic.openbadges.badge3name'),
                'description' => __('plugins.generic.openbadges.badge3description'),
                'image' => $pluginUrl . '/images/preregistered_large_color.png',
                'url' => 'https://ejemplo.com/badge3',
                'isActive' => false
            ],
            [
                'id' => 4,
                'title' => __('plugins.generic.openbadges.badge4name'),
                'description' => __('plugins.generic.openbadges.badge4description'),
                'image' => $pluginUrl . '/images/data_large_color_pa.png',
                'url' => 'https://ejemplo.com/badge3',
                'isActive' => false
            ],
        ];
    }







}