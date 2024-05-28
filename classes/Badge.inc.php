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
	 * @return string
	 */
	function getBadgeName() {
		return $this->getData('badgeName');
	}

	/**
	 * Set name.
	 * @param $badgeName string
	 */
	function setBadgeName($badgeName) {
		return $this->setData('badgeName', $badgeName);
	}

    /**
	 * Get description.
	 * @return string
	 */
	function getBadgeDescription() {
		return $this->getData('badgeDescription');
	}

	/**
	 * Set description.
	 * @param $badgeDescription string
	 */
	function setBadgeDescription($badgeDescription) {
		return $this->setData('badgeDescription', $badgeDescription);
	}

    /**
	 * Get img.
	 * @return string
	 */
	function getBadgeImage() {
		return $this->getData('badgeImage');
	}

	/**
	 * Set image.
	 * @param $badgeImage string
	 */
	function setBadgeimage($badgeImage) {
		return $this->setData('badgeImage', $badgeImage);
	}

}
