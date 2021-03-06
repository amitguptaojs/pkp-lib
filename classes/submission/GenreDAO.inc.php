<?php

/**
 * @file classes/submission/GenreDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenreDAO
 * @ingroup submission
 * @see Genre
 *
 * @brief Operations for retrieving and modifying Genre objects.
 */

import('lib.pkp.classes.submission.Genre');
import('lib.pkp.classes.context.DefaultSettingDAO');

class GenreDAO extends DefaultSettingDAO {
	/**
	 * Constructor
	 */
	function GenreDAO() {
		parent::DefaultSettingDAO();
	}

	/**
	 * @see DefaultSettingsDAO::getPrimaryKeyColumnName()
	 */
	function getPrimaryKeyColumnName() {
		return 'genre_id';
	}

	/**
	 * Retrieve a genre by type id.
	 * @param $genreId int
	 * @return Genre
	 */
	function getById($genreId, $contextId = null){
		$params = array((int) $genreId);
		if ($contextId) {
			$params[] = (int) $contextId;
		}

		$result = $this->retrieve(
			'SELECT * FROM genres WHERE genre_id = ?' .
			($contextId ? ' AND context_id = ?' : '') .
			' ORDER BY seq',
			$params
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a set of Genres by category.
	 * @param int $category
	 * @param int $contextId
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getByCategory($category, $contextId = null, $rangeInfo = null) {
		$params = array((int) $category, 1);
		if ($contextId) {
			$params[] = (int) $contextId;
		}

		$result = $this->retrieveRange(
			'SELECT * FROM genres
			WHERE	category = ?
				AND enabled = ?' .
				($contextId ? ' AND context_id = ?' : '') . '
			ORDER BY seq',
			$params,
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieve a genre by type.
	 * @param string $type e.g. 'STYLE'
	 * @param int $contextId Context ID
	 * @return Genre
	 */
	function getByType($type, $contextId = null) {
		$params = array($type, 1);
		if ($contextId) {
			$params[] = (int) $contextId;
		}

		$result = $this->retrieve(
			'SELECT * FROM genres
			WHERE	entry_key = ?
				AND enabled = ?' .
				($contextId ? ' AND context_id = ?' : '') . '
			ORDER BY seq',
			$params
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all genres
	 * @param $contextId int
	 * @param $enabledOnly boolean optional
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getEnabledByContextId($contextId, $rangeInfo = null) {
		$params = array(1, (int) $contextId);

		$result = $this->retrieveRange(
			'SELECT * FROM genres
			WHERE	enabled = ? AND context_id = ?
			ORDER BY seq',
			$params, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieve genres based on whether they are dependent or not.
	 * @param $dependentFilesOnly boolean
	 * @param $contextId int
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getByDependenceAndContextId($dependentFilesOnly, $contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM genres
			WHERE enabled = ? AND context_id = ? AND dependent = ?
			ORDER BY seq',
			array(1, (int) $contextId, (int) $dependentFilesOnly),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Retrieve all genres
	 * @param $contextId int
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching genres
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM genres WHERE context_id = ? ORDER BY seq',
			array((int) $contextId),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', array('id'));
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * Get a list of field names for which non-localized settings are stored
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('designation');
	}

	/**
	 * Update the settings for this object
	 * @param $genre object
	 */
	function updateLocaleFields(&$genre) {
		$this->updateDataObjectSettings('genre_settings', $genre, array(
			'genre_id' => $genre->getId()
		));
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Genre
	 */
	function newDataObject() {
		return new Genre();
	}

	/**
	 * Internal function to return a Genre object from a row.
	 * @param $row array
	 * @return Genre
	 */
	function _fromRow($row) {
		$genre = $this->newDataObject();
		$genre->setId($row['genre_id']);
		$genre->setContextId($row['context_id']);
		$genre->setSortable($row['sortable']);
		$genre->setCategory($row['category']);
		$genre->setDependent($row['dependent']);
		$genre->setSequence($row['seq']);

		$this->getDataObjectSettings('genre_settings', 'genre_id', $row['genre_id'], $genre);

		HookRegistry::call('GenreDAO::_fromRow', array(&$genre, &$row));

		return $genre;
	}

	/**
	 * Insert a new genre.
	 * @param $genre Genre
	 */
	function insertObject(&$genre) {
		$this->update(
			'INSERT INTO genres
				(seq, sortable, context_id, category, dependent)
			VALUES
				(?, ?, ?, ?, ?)',
			array(
				(float) $genre->getSequence(),
				$genre->getSortable() ? 1 : 0,
				(int) $genre->getContextId(),
				(int) $genre->getCategory(),
				$genre->getDependent() ? 1 : 0
			)
		);

		$genre->setId($this->getInsertId());
		$this->updateLocaleFields($genre);
		return $genre->getId();
	}

	/**
	 * Update an existing genre.
	 * @param $genre Genre
	 */
	function updateObject($genre) {
		$this->update(
			'UPDATE genres
			SET 	seq = ?,
				sortable = ?,
				dependent = ?
			WHERE	genre_id = ?',
			array(
				(float) $genre->getSequence(),
				$genre->getSortable() ? 1 : 0,
				$genre->getDependent() ? 1 : 0,
				(int) $genre->getId(),
			)
		);
		$this->updateLocaleFields($genre);
	}

	/**
	 * Delete a genre by id.
	 * @param $genre Genre
	 */
	function deleteObject($genre) {
		return $this->deleteById($genre->getId());
	}

	/**
	 * Soft delete a genre by id.
	 * @param $genreId int
	 */
	function deleteById($genreId) {
		return $this->update(
			'UPDATE genres SET enabled = ? WHERE genre_id = ?',
			array(0, (int) $genreId)
		);
	}

	/**
	 * delete the genre entries associated with a context.
	 * Called when deleting a Context in ContextDAO.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		$genres = $this->getByContextId($contextId);
		while ($genre = $genres->next()) {
			$this->update('DELETE FROM genre_settings WHERE genre_id = ?', (int) $genre->getId());
		}
		return $this->update(
			'DELETE FROM genres WHERE context_id = ?', (int) $contextId
		);
	}

	/**
	 * Get the ID of the last inserted genre.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('genres', 'genre_id');
	}

	/**
	 * Get the name of the settings table.
	 * @return string
	 */
	function getSettingsTableName() {
		return 'genre_settings';
	}

	/**
	 * Get the name of the main table for this setting group.
	 * @return string
	 */
	function getTableName() {
		return 'genres';
	}

	/**
	 * Get the default type constant.
	 * @return int
	 */
	function getDefaultType() {
		return DEFAULT_SETTING_GENRES;
	}

	/**
	 * Get the path of the setting data file.
	 * @return string
	 */
	function getDefaultBaseFilename() {
		return 'registry/genres.xml';
	}

	/**
	 * Install genres from an XML file.
	 * @param $contextId int
	 * @return boolean
	 */
	function installDefaultBase($contextId) {
		$xmlDao = new XMLDAO();

		$data = $xmlDao->parseStruct($this->getDefaultBaseFilename(), array('genre'));
		if (!isset($data['genre'])) return false;
		$seq = 0;

		foreach ($data['genre'] as $entry) {
			$attrs = $entry['attributes'];
			$this->update(
				'INSERT INTO genres
				(seq, entry_key, sortable, context_id, category, dependent)
				VALUES
				(?, ?, ?, ?, ?, ?)',
				array(
					$seq++,
					$attrs['key'],
					$attrs['sortable'] ? 1 : 0,
					(int) $contextId,
					$attrs['category'],
					$attrs['dependent'] ? 1 : 0
				)
			);
		}
		return true;
	}

	/**
	 * Get setting names and values.
	 * @param $node XMLNode
	 * @param $locale string
	 * @return array
	 */
	function &getSettingAttributes($node = null, $locale = null) {

		if ($node == null) {
			$settings = array('name', 'designation');
		} else {
			$localeKey = $node->getAttribute('localeKey');
			$sortable = $node->getAttribute('sortable');

			$designation = $sortable ? GENRE_SORTABLE_DESIGNATION : __($localeKey.'.designation', array(), $locale);

			$settings = array(
				'name' => __($localeKey, array(), $locale),
				'designation' => $designation
			);
		}
		return $settings;
	}
}

?>
