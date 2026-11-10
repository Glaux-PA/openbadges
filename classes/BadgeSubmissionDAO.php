<?php

/**
 * @file plugins/generic/openbadges/classes/BadgeSubmissionDAO.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class BadgeSubmissionDAO
 * @ingroup plugins_generic_openbadges
 *
 * Operations for retrieving and modifying badge submission objects.
 */

namespace APP\plugins\generic\openbadges\classes;

use PKP\db\DAO;

class BadgeSubmissionDAO extends DAO
{
    /**
     * Get a badge_submission by ID
     *
     * @param int $badgeSubmissionID
     */
    public function getById($badgeSubmissionID): ?BadgeSubmission
    {
        $result = $this->retrieve(
            'SELECT * FROM badge_submission WHERE badge_submission_id = ?',
            [(int) $badgeSubmissionID]
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Check whether a badge_submission exists for a submission and badge.
     */
    public function existSubmisionBadge($submissionId, $badgeId): bool
    {
        $result = $this->retrieve(
            'SELECT EXISTS(SELECT 1 FROM badge_submission WHERE submission_id = ? AND badge_id = ?) AS register_exist',
            [(int) $submissionId, (int) $badgeId]
        );
        $row = $result->current();
        return (bool) $row->register_exist;
    }

    /**
     * Get a badge_submission by submission ID and badge ID
     */
    public function getBySubmissionAndBadgeId($submissionId, $badgeId): ?BadgeSubmission
    {
        $result = $this->retrieve(
            'SELECT * FROM badge_submission WHERE submission_id = ? AND badge_id = ?',
            [(int) $submissionId, (int) $badgeId]
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Insert a BadgeSubmission.
     *
     * @return int Inserted BadgeSubmission ID
     */
    public function insertObject($badgeSubmission): int
    {
        $this->update(
            'INSERT INTO badge_submission (badge_id, submission_id, badge_url, badge_status) VALUES (?, ?, ?, ?)',
            [
                (int) $badgeSubmission->getBadgeId(),
                (int) $badgeSubmission->getSubmissionId(),
                $badgeSubmission->getBadgeUrl(),
                (int) $badgeSubmission->getStatus()
            ]
        );
        $badgeSubmission->setId($this->getInsertId());
        $this->updateLocaleFields($badgeSubmission);
        return $badgeSubmission->getId();
    }

    /**
     * Update the database with a BadgeSubmission object
     */
    public function updateObject($badgeSubmission): void
    {
        $this->update(
            'UPDATE badge_submission
			SET badge_id = ?,
				submission_id = ?,
				badge_url = ?,
				badge_status = ?
			WHERE badge_submission_id = ?',
            [
                (int) $badgeSubmission->getBadgeId(),
                (int) $badgeSubmission->getSubmissionId(),
                $badgeSubmission->getBadgeUrl(),
                (int) $badgeSubmission->getStatus(),
                (int) $badgeSubmission->getId()
            ]
        );
        $this->updateLocaleFields($badgeSubmission);
    }

    /**
     * Delete BadgeSubmission by ID.
     *
     * @param int $badgeSubmissionId
     */
    public function deleteById($badgeSubmissionId): void
    {
        $this->update('DELETE FROM badge_submission WHERE badge_submission_id = ?', [(int) $badgeSubmissionId]);
        $this->update('DELETE FROM badge_submission_settings WHERE badge_submission_id = ?', [(int) $badgeSubmissionId]);
    }

    /**
     * Delete all BadgeSubmissions of a badge.
     *
     * @param int $badgeId
     */
    public function deleteByBadgeId($badgeId): void
    {
        $this->update(
            'DELETE FROM badge_submission_settings WHERE badge_submission_id IN (SELECT badge_submission_id FROM badge_submission WHERE badge_id = ?)',
            [(int) $badgeId]
        );
        $this->update('DELETE FROM badge_submission WHERE badge_id = ?', [(int) $badgeId]);
    }

    /**
     * Generate a new BadgeSubmission object.
     */
    public function newDataObject(): BadgeSubmission
    {
        return new BadgeSubmission();
    }

    /**
     * Return a new badge_submission object from a given row.
     */
    public function _fromRow($row): BadgeSubmission
    {
        $badgeSubmission = $this->newDataObject();
        $badgeSubmission->setId($row['badge_submission_id']);
        $badgeSubmission->setBadgeId($row['badge_id']);
        $badgeSubmission->setSubmissionId($row['submission_id']);
        $badgeSubmission->setBadgeUrl($row['badge_url']);
        $badgeSubmission->setStatus((bool) $row['badge_status']);
        $this->getDataObjectSettings('badge_submission_settings', 'badge_submission_id', $row['badge_submission_id'], $badgeSubmission);
        return $badgeSubmission;
    }

    /**
     * Update the settings for this object
     */
    public function updateLocaleFields($badgeSubmission): void
    {
        $this->updateDataObjectSettings('badge_submission_settings', $badgeSubmission, ['badge_submission_id' => (int) $badgeSubmission->getId()]);
    }
}
