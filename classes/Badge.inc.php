<?php

/**
 * @file plugins/generic/openbadges/classes/Badge.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class Badge
 * @ingroup plugins_generic_openbadges
 *
 * Data object representing a badge.
 */

class Badge extends DataObject {

    /**
	 * Get name.
	 * 
     * @param string $locale
	 * 
	 * @return string
	 */
	function getBadgeName($locale) {
		return $this->getData('badgeName', $locale);
	}

	/**
	 * Set name.
	 * @param $badgeName string
     * @param string $locale
	 */
	function setBadgeName($badgeName, $locale) {
		return $this->setData('badgeName', $badgeName, $locale);
	}

    /**
     * Get Localized badge name
     *
     * @return string
     */
    public function getLocalizedBadgeName()
    {
        return $this->getLocalizedData('badgeName');
    }	

    /**
	 * Get description.
	 * 
     * @param string $locale
	 * 
	 * @return string
	 */
	function getBadgeDescription($locale) {
		return $this->getData('badgeDescription', $locale);
	}

	/**
	 * Set description.
	 * @param $badgeDescription string
     * @param string $locale
	 */
	function setBadgeDescription($badgeDescription, $locale) {
		return $this->setData('badgeDescription', $badgeDescription, $locale);
	}

    /**
     * Get Localized badge description
     *
     * @return string
     */
    public function getLocalizedBadgeDescription()
    {
        return $this->getLocalizedData('badgeDescription');
    }		

    /**
	 * Get img.
	 * 
     * @param string $locale
	 * 
	 * @return string
	 */
	function getBadgeImage($locale) {
		return $this->getData('badgeImage', $locale);
	}

	/**
	 * Set image.
	 * @param $badgeImage string
     * @param string $locale
	 */
	function setBadgeimage($badgeImage, $locale) {
		return $this->setData('badgeImage', $badgeImage, $locale);
	}

    /**
     * Get Localized badge image
     *
     * @return string
     */
    public function getLocalizedBadgeImage()
    {
        return $this->getLocalizedData('badgeImage');
    }
	
    /**
     * Get Localized badge metadata
     *
     * @return string
     */	
	function getBadgeMetadata() {
        return $this->getData('badge_metadata');
    }

	/**
	 * Set metadata.
	 * @param $metadata string
	 */
    function setBadgeMetadata($metadata) {
        return $this->setData('badge_metadata', $metadata);
    }

    /**
     * Get Localized badge small image
     *
     * @return string
     */	
	function getBadgeSmallImage() {
        return $this->getData('badge_smallimage');
    }

	/**
	 * Set badge small image.
	 * @param $smallimage string
	 */
    function setBadgeSmallImage($smallimage) {
        return $this->setData('badge_smallimage', $smallimage);
    }

    /**
     * Get Localized badge fields
     */
    public static function getLocaleFieldNames() {
        return ['badge_metadata'];
    }

    /**
     * Get Localized additional badge fields
     */
    public static function getAdditionalFieldNames() {
        return ['badge_metadata'];
    }	

}
