<?php
/**
 * https://github.com/SubZtep/modell
 */

class Modell
{
	const MEMCACHE_PREFIX = 'modell_';

	public static $pdo = null;
	public static $memcache = null;

	private $schema = array();  // Table schema (index is column name)
	private $values = array();  // Field values

	public $table;  // Table name
	public $fieldCreatedAt = 'created_at'; // Created datetime column or false
	public $fieldUpdatedAt = 'updated_at'; // Updated datetime column or false

	public $modified = false; // Any object property has been updated (for save)

	/**
	 * [__construct description]
	 * @param [type]  $id         [description]
	 * @param boolean $forceCache [description]
	 */
	public function __construct($id = null, $forceCache = false) {
		if (is_null(self::$pdo))
			return false;

		$this->table = $this->getTableName();
		$this->cacheSchema($forceCache);

		if (! is_null($id))
			$this->load($id);

		$this->modified = false;
	}

	/**
	 * Set field value
	 * @param string $name Field name
	 * @param [type] $value
	 */
	public function __set($name, $value) {
		if (!$this->modified && (!isset($this->values[$name]) || $this->values[$name] !== $value))
			$this->modified = true;
		$this->values[$name] = $value;
	}

	/**
	 * Get field value
	 * @param  [type] $name
	 * @return [type]
	 */
	public function __get($name) {
		if (isset($this->values[$name]))
			return $this->values[$name];
		return null;
	}

	/**
	 * Load object from database (delete current values)
	 * If load is unsuccessful object loose all properties
	 * @param  int $id Object id
	 * @return boolean Successful?
	 */
	public function load($id) {
		$query = self::$pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
		$query->bindParam(':id', $id, \PDO::PARAM_INT);
		$query->execute();
		if (!$query->rowCount()) {
			$this->values = array();
			return false;
		}
		$this->values = $query->fetch(\PDO::FETCH_ASSOC);
		return true;
	}

	/**
	 * Insert or update current object to database
	 * (depends on id set or not)
	 * @return boolean Successful?
	 */
	public function save() {
		if (empty($this->values))
			return false;

		if ($this->fieldUpdatedAt && isset($this->schema[$this->fieldUpdatedAt]))
			$this->values[$this->fieldUpdatedAt] = date('Y-m-d H:i:s');
		else if (!$this->modified)
			return true;

		// Prepare sql
		if (is_null($this->id)) {
			// Insert
			if ($this->fieldCreatedAt && isset($this->schema[$this->fieldCreatedAt]))
				$this->values[$this->fieldCreatedAt] = date('Y-m-d H:i:s');

			$sql = "INSERT INTO `{$this->table}` ("
				. implode(',', array_map(function($item) {
						return "`$item`";
					}, array_keys($this->values)))
				. ') VALUES ('
				. implode(',', array_map(function($item) {
						return ":$item";
					}, array_keys($this->values)))
				. ')';
		} else {
			// Update
			$id = $this->values['id'];
			unset($this->values['id']);
			$sql = "UPDATE `{$this->table}` SET "
				. implode(',', array_map(function($item) {
						return "`$item`=:$item";
					}, array_keys($this->values)))
				. ' WHERE `id`=:id';
			$this->values['id'] = $id;
		}

		// Bind params
		$query = self::$pdo->prepare($sql);
		foreach ($this->values as $key => $value) {
			$query->bindValue(":$key", $value);
		}

		if ($query->execute()) {
			if (is_null($this->id))
				$this->id = self::$pdo->lastInsertId();
			$this->modified = false;
			return true;
		}
		return false;
	}

	/**
	 * Get tablestructure and cache it
	 * @param  boolean $forceCache if true always update currenct schema in cache
	 */
	public function cacheSchema($forceCache = false) {
		if (!$forceCache && !is_null(self::$memcache)) {
			$schema = self::$memcache->get(self::MEMCACHE_PREFIX . $this->table);
			if ($schema) {
				$this->schema = json_decode($schema, true);
				return;
			}
		}

		$query = self::$pdo->prepare('DESCRIBE ' . $this->table);
		$query->execute();

		while ($row = $query->fetch(\PDO::FETCH_ASSOC))
			$this->schema[$row['Field']] = array(
				'type' => $row['Type'],
				'null' => $row['Null'] == 'YES'
			);

		if (!is_null(self::$memcache))
			$x = self::$memcache->set(
				self::MEMCACHE_PREFIX . $this->table,
				json_encode($this->schema)
			);
	}

	/**
	 * Get model's table name
	 * (if this guess is wrong needs to be overwritten or set table)
	 * @return string Table name
	 */
	public function getTableName() {
		$table = strtolower(get_called_class());
		if ($table == 'person')
			return 'people';
		switch (substr($table, -1)) {
			case 'y': return substr($table, 0, -1) . 'ies';
			case 's': return $table . 'es';
		}
		return $table . 's';
	}
}
?>