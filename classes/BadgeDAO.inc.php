<?php

/**
 * @file plugins/generic/openbadges/classes/BadgeDAO.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class BadgeDAO
 * @ingroup plugins_generic_openbadges
 *
 * Operations for retrieving and modifying badge objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.openbadges.classes.Badge');

class BadgeDAO extends DAO {

	/**
	 * Get a badge by ID
	 * @param $badgeId int Badge ID
	 */
	function getById($badgeId) {
		$params = [(int) $badgeId];

		$result = $this->retrieve(
			'SELECT * FROM badge WHERE badge_id = ?',
			$params
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get a badge by Name
	 * @param $badge object
	 */
	function getByName($badgeName) {
		$params = [(string) $badgeName];

		$result = $this->retrieve(
			'SELECT * FROM badge WHERE badge_name = ?',
			$params
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get all badges
	 */	
    function getAll() {

		$result = $this->retrieve(
			'SELECT * FROM badge'
		);
		
		return new DAOResultFactory($result, $this, '_fromRow');
		
    }

	/**
	 * Get if existe any badge in bbdd
	 * @return exist
	 */
	function existBadges() {

		$result = $this->retrieve(
			'SELECT EXISTS(SELECT 1 FROM badge) AS register_exist'
		);
	
		$row = $result->current();
		$exist = $row->register_exist;
		return $exist;

	}

	/**
	 * Insert a badge.
	 * @param $badge Badge
	 * @return int Inserted badge ID
	 */
	function insertObject($badge) {
		$this->update(
			'INSERT INTO badge (badge_name, badge_description, badge_image) VALUES (?, ?, ?)',
			array(
				$badge->getBadgeName(null),
				$badge->getBadgeDescription(null),
                $badge->getBadgeImage(null)
			)
		);
		$badge->setId($this->getInsertId());
		$this->updateLocaleFields($badge);
		return $badge->getId();
	}

	/**
	 * Update the database with a badge object
	 * @param $badge Badge
	 */
	function updateObject($badge) {
		$this->update(
			'UPDATE	badge
			SET	badge_name = ?,
                badge_description = ?,
                badge_image = ?
			WHERE badge_id = ?',
			array(
				$badge->getBadgeName(null),
				$badge->getBadgeDescription(null),
                $badge->getBadgeImage(null),
				(int) $badge->getId()
			)
		);
		$this->updateLocaleFields($badge);
	}

	/**
	 * Delete a badge by ID.
	 * @param $badgeId int
	 */
	function deleteById($badgeId) {

		$this->update(
			'DELETE FROM badge WHERE badge_id = ?',
			[(int) $badgeId]
		);

		$this->update(
			'DELETE FROM badge_settings WHERE badge_id = ?',
			[(int) $badgeId]
		);

		$badgeSubmissionDAO = DAORegistry::getDAO('BadgeSubmissionDAO');
		$badgeSubmissionDAO->deleteByBadgeId($badgeId);

	}

	/**
	 * Delete a badge object.
	 * @param $badge Badge
	 */
	function deleteObject($badge) {
		$this->deleteById($badge->getId());
	}

	/**
	 * Generate a new badge object.
	 * @return Badge
	 */
	function newDataObject() {
		return new Badge();
	}

	/**
	 * Return a new badge object from a given row.
	 * @return Badge
	 */
	function _fromRow($row) {
		$badge = $this->newDataObject();
		$badge->setId($row['badge_id']);
		$badge->setBadgeName($row['badge_name'], null);
		$badge->setBadgeDescription($row['badge_description'], null);
		$badge->setBadgeimage($row['badge_image'], null);

		$this->getDataObjectSettings('badge_settings', 'badge_id', $row['badge_id'], $badge);

		return $badge;
	}

	/**
	 * Get the insert ID for the last inserted funder.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('badge', 'badge_id');
	}

	/**
	 * Update the settings for this object
	 * @param $badge object
	 */
	function updateLocaleFields($badge) {
		$this->updateDataObjectSettings('badge_settings', $badge, array('badge_id' => (int) $badge->getId()));
	}

}