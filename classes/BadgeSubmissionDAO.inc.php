

<?php

/**
 * @file plugins/generic/openbadges/classes/BadgeSubmissionDAO.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class Badge
 * @ingroup plugins_generic_openbadges
 *
 * Operations for retrieving and modifying badge submission objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.openbadges.classes.BadgeSubmission');
class BadgeSubmissionDAO extends DAO {


	/**
	 * Get a badge_submission by ID
	 * @param $badge_submission_id int BadgeSubmission ID
	 */
	function getById($badgeSubmissionID) {

		$result = $this->retrieve(
			'SELECT * FROM badge_submission WHERE badge_submission_id = ?',
			[$badgeSubmissionID]
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get badges by submission ID.
	 * @param $submissionId int Submission ID
	 * @return BadgeSubmission
	 */
	function getBySubmissionId($submissionId) {

		$result = $this->retrieve(
			'SELECT * FROM badge_submission WHERE submission_id = ?',
			$submissionId
		);
	
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
		#return new DAOResultFactory($rows, $this, '_fromRow');

	}

	/**
	 * Get if badge_submission exist by submission ID and badgeID.
	 * @param $submissionId int Submission ID
	 * @param $badgeId int Badge ID 
	 * @return exist
	 */
	function existSubmisionBadge($submissionId, $badgeId) {

		$result = $this->retrieve(
			'SELECT EXISTS(SELECT 1 FROM badge_submission WHERE submission_id = ? AND badge_id = ?) AS register_exist',
			[(int) $submissionId, (int) $badgeId]
		);
	
		$row = $result->current();
		$exist = $row->register_exist;
		return $exist;

	}
	


	/**
	 * Get badges by submission ID and badgeID
	 * @param $submissionId int Submission ID
	 * @param $badgeId int Badge ID 
	 * @return Submission
	 */
	function getBySubmissionAndBadgeId($submissionId, $badgeId) {

		$result = $this->retrieve(
			'SELECT * FROM badge_submission WHERE submission_id = ? AND badge_id = ?',
			[(int) $submissionId, (int) $badgeId]
		);		

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;

	}	

	/**
	 * Insert a BadgeSubmission.
	 * @param $badgeSubmission BadgeSubmission
	 * @return int Inserted BadgeSubmission ID
	 */
	function insertObject($badgeSubmission) {
		$this->update(
			'INSERT INTO badge_submission (badge_id, submission_id, badge_url, badge_status) VALUES (?, ?, ?, ?)',
			array(
				$badgeSubmission->getBadgeId(),
				$badgeSubmission->getSubmissionId(),
				$badgeSubmission->getBadgeUrl(),
				$badgeSubmission->getStatus()
			)
		);
		$badgeSubmission->setId($this->getInsertId());

		$this->updateLocaleFields($badgeSubmission);

		return $badgeSubmission->getId();
	}

		/**
	 * Update the database with a BadgeSubmission object
	 * @param $badgeSubmission BadgeSubmission
	 */
	function updateObject($badgeSubmission) {
		$this->update(
			'UPDATE	badge_submission
			SET	badge_id = ?,
				submission_id = ?,
                badge_url = ?,
				badge_status = ?
			WHERE badge_submission_id = ?',
			array(
				$badgeSubmission->getBadgeId(),
				$badgeSubmission->getSubmissionId(),
                $badgeSubmission->getBadgeUrl(),
				$badgeSubmission->getStatus(),
				(int) $badgeSubmission->getId()
			)
		);
		$this->updateLocaleFields($badgeSubmission);
	}


	/**
	 * Delete BadgeSubmission by ID.
	 * @param $badgeSubmission int
	 */
	function deleteById($badgeSubmission) {
		$this->update(
			'DELETE FROM badge_submission WHERE badge_submission_id = ?',
			[(int) $badgeSubmission]
		);
		$this->update(
			'DELETE FROM badge_submission_settings WHERE badge_submission_id = ?',
			[(int) $badgeSubmission]
		);
	}    

	/**
	 * Generate a new BadgeSubmission object.
	 * @return badgeSubmission
	 */
	function newDataObject() {
		return new BadgeSubmission();
	}

	/**
	 * Return a new badge_submission object from a given row.
	 * @return BadgeSubmission
	 */
	function _fromRow($row) {
		$badgeSubmission = $this->newDataObject();
		$badgeSubmission->setId($row['badge_submission_id']);
		$badgeSubmission->setBadgeId($row['badge_id']);
		$badgeSubmission->setSubmissionId($row['submission_id']);
		$badgeSubmission->setBadgeUrl($row['badge_url']);
		$badgeSubmission->setStatus($row['badge_status']);

		$this->getDataObjectSettings('badge_submission_settings', 'badge_submission_id', $row['badge_submission_id'], $badgeSubmission);

		return $badgeSubmission;
	}


	/**
	 * Get the insert ID for the last inserted badge_submission.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('badge_submission', 'badge_submission_id');
	}

	/**
	 * Update the settings for this object
	 * @param $badge_submission object
	 */
	function updateLocaleFields($badgeSubmission) {
		$this->updateDataObjectSettings('badge_submission_settings', $badgeSubmission, array('badge_submission_id' => (int) $badgeSubmission->getId()));
	}

}