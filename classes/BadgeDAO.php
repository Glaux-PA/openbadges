<?php

/**
 * @file plugins/generic/openbadges/classes/BadgeDAO.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class BadgeDAO
 * @ingroup plugins_generic_openbadges
 *
 * Operations for retrieving and modifying badge objects.
 */

namespace APP\plugins\generic\openbadges\classes;

use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;

class BadgeDAO extends DAO
{
    /**
     * Get a badge by ID
     *
     * @param int $badgeId
     */
    public function getById($badgeId): ?Badge
    {
        $result = $this->retrieve(
            'SELECT * FROM badge WHERE badge_id = ?',
            [(int) $badgeId]
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Get a badge by name
     *
     * @param string $badgeName
     */
    public function getByName($badgeName): ?Badge
    {
        $result = $this->retrieve(
            'SELECT * FROM badge WHERE badge_name = ?',
            [(string) $badgeName]
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Get all badges
     */
    public function getAll(): DAOResultFactory
    {
        $result = $this->retrieve('SELECT * FROM badge ORDER BY badge_id');
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Check whether any badge exists in the database
     */
    public function existBadges(): bool
    {
        $result = $this->retrieve('SELECT EXISTS(SELECT 1 FROM badge) AS register_exist');
        $row = $result->current();
        return (bool) $row->register_exist;
    }

    /**
     * Insert a badge
     *
     * @return int Inserted badge ID
     */
    public function insertObject($badge): int
    {
        $this->update(
            'INSERT INTO badge (badge_name, badge_description, badge_image) VALUES (?, ?, ?)',
            [
                $badge->getBadgeName(null),
                $badge->getBadgeDescription(null),
                $badge->getBadgeImage(null)
            ]
        );
        $badge->setId($this->getInsertId());
        $this->updateLocaleFields($badge);
        return $badge->getId();
    }

    /**
     * Update the database with a badge object
     */
    public function updateObject($badge): void
    {
        $this->update(
            'UPDATE badge
			SET badge_name = ?,
				badge_description = ?,
				badge_image = ?
			WHERE badge_id = ?',
            [
                $badge->getBadgeName(null),
                $badge->getBadgeDescription(null),
                $badge->getBadgeImage(null),
                (int) $badge->getId()
            ]
        );
        $this->updateLocaleFields($badge);
    }

    /**
     * Delete a badge by ID.
     *
     * @param int $badgeId
     */
    public function deleteById($badgeId): void
    {
        $this->update('DELETE FROM badge WHERE badge_id = ?', [(int) $badgeId]);
        $this->update('DELETE FROM badge_settings WHERE badge_id = ?', [(int) $badgeId]);

        $badgeSubmissionDAO = DAORegistry::getDAO('BadgeSubmissionDAO');
        $badgeSubmissionDAO->deleteByBadgeId($badgeId);
    }

    /**
     * Delete a badge object.
     */
    public function deleteObject($badge): void
    {
        $this->deleteById($badge->getId());
    }

    /**
     * Generate a new badge object.
     */
    public function newDataObject(): Badge
    {
        return new Badge();
    }

    /**
     * Return a new badge object from a given row.
     */
    public function _fromRow($row): Badge
    {
        $badge = $this->newDataObject();
        $badge->setId($row['badge_id']);
        $badge->setBadgeName($row['badge_name'], null);
        $badge->setBadgeDescription($row['badge_description'], null);
        $badge->setBadgeimage($row['badge_image'], null);

        $this->getDataObjectSettings('badge_settings', 'badge_id', $row['badge_id'], $badge);

        return $badge;
    }

    /**
     * Get the additional (non-localized) setting names.
     */
    public function getAdditionalFieldNames()
    {
        return ['badge_metadata', 'badge_smallimage'];
    }

    /**
     * Update the settings for this object
     */
    public function updateLocaleFields($badge): void
    {
        $this->updateDataObjectSettings('badge_settings', $badge, ['badge_id' => (int) $badge->getId()]);
    }
}
