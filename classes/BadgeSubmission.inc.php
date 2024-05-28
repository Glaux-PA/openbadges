<?php

/**
 * @file plugins/generic/openbadges/classes/BadgeSubmision.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class Badge
 * @ingroup plugins_generic_openbadges
 *
 * Data object representing a badgeSubmission.
 */

class BadgeSubmission extends DataObject {

    /**
	 * Get badge ID.
	 * @return int
	 */
	function getBadgeId(){
		return $this->getData('badgeId');
	}

	/**
	 * Set badge ID.
	 * @param $badgeId int
	 */
	function setBadgeId($badgeId) {
		return $this->setData('badgeId', $badgeId);
	}

    /**
	 * Get submission ID.
	 * @return int
	 */
	function getSubmissionId(){
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

    /**
	 * Get url.
	 * @return string
	 */
	function getBadgeUrl() {
		return $this->getData('badgeUrl');
	}

	/**
	 * Set url.
	 * @param $badgeUrl string
	 */
	function setBadgeUrl($badgeUrl) {
		return $this->setData('badgeUrl', $badgeUrl);
	}	

    /**
	 * Get badge status.
	 * @return boolean
	 */
	function getStatus(){
		return $this->getData('badgeStatus');
	}

	/**
	 * Set badge status.
	 * @param $enabled boolean
	 */
	function setStatus($enabled) {
		return $this->setData('badgeStatus', $enabled);
	}


}