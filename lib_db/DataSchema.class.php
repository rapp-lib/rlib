<?php

//-------------------------------------
// DataSchema
class DataSchema {
	
	
	//-------------------------------------
	// スキーマ比較（DBI::alterSchemaの引数を返す）
	protected function compare_schema ($old, $new = null) {
	
		if (empty($new)) {
			$new =& $this;
		}
		if (is_array($new)) {
			if (isset($new['tables'])) {
				$new = $new['tables'];
			}
		} else {
			$new = $new->tables;
		}

		if (is_array($old)) {
			if (isset($old['tables'])) {
				$old = $old['tables'];
			}
		} else {
			$old = $old->tables;
		}
		$tables = array();
		foreach ($new as $table => $fields) {
			if ($table == 'missing') {
				continue;
			}
			if (!array_key_exists($table, $old)) {
				$tables[$table]['add'] = $fields;
			} else {
				$diff = array_diff_assoc($fields, $old[$table]);
				if (!empty($diff)) {
					$tables[$table]['add'] = $diff;
				}
				$diff = array_diff_assoc($old[$table], $fields);
				if (!empty($diff)) {
					$tables[$table]['drop'] = $diff;
				}
			}

			foreach ($fields as $field => $value) {
				if (isset($old[$table][$field])) {
					$diff = array_diff_assoc($value, $old[$table][$field]);
					if (!empty($diff) && $field !== 'indexes' && $field !== 'tableParameters') {
						$tables[$table]['change'][$field] = array_merge($old[$table][$field], $diff);
					}
				}

				if (isset($add[$table][$field])) {
					$wrapper = array_keys($fields);
					if ($column = array_search($field, $wrapper)) {
						if (isset($wrapper[$column - 1])) {
							$tables[$table]['add'][$field]['after'] = $wrapper[$column - 1];
						}
					}
				}
			}

			if (isset($old[$table]['indexes']) && isset($new[$table]['indexes'])) {
				$diff = $this->_compareIndexes($new[$table]['indexes'], $old[$table]['indexes']);
				if ($diff) {
					if (!isset($tables[$table])) {
						$tables[$table] = array();
					}
					if (isset($diff['drop'])) {
						$tables[$table]['drop']['indexes'] = $diff['drop'];
					}
					if ($diff && isset($diff['add'])) {
						$tables[$table]['add']['indexes'] = $diff['add'];
					}
				}
			}
			if (isset($old[$table]['tableParameters']) && isset($new[$table]['tableParameters'])) {
				$diff = $this->_compareTableParameters($new[$table]['tableParameters'], $old[$table]['tableParameters']);
				if ($diff) {
					$tables[$table]['change']['tableParameters'] = $diff;
				}
			}
		}
		return $tables;
	}
	
	//-------------------------------------
	// TableParameters比較
	protected function _compareTableParameters($new, $old) {
	
		if (!is_array($new) || !is_array($old)) {
			return false;
		}
		$change = array_diff_assoc($new, $old);
		return $change;
	}
	
	//-------------------------------------
	// Indexes比較
	protected function _compareIndexes($new, $old) {
	
		if (!is_array($new) || !is_array($old)) {
			return false;
		}

		$add = $drop = array();

		$diff = array_diff_assoc($new, $old);
		if (!empty($diff)) {
			$add = $diff;
		}

		$diff = array_diff_assoc($old, $new);
		if (!empty($diff)) {
			$drop = $diff;
		}

		foreach ($new as $name => $value) {
			if (isset($old[$name])) {
				$newUnique = isset($value['unique']) ? $value['unique'] : 0;
				$oldUnique = isset($old[$name]['unique']) ? $old[$name]['unique'] : 0;
				$newColumn = $value['column'];
				$oldColumn = $old[$name]['column'];

				$diff = false;

				if ($newUnique != $oldUnique) {
					$diff = true;
				} elseif (is_array($newColumn) && is_array($oldColumn)) {
					$diff = ($newColumn !== $oldColumn);
				} elseif (is_string($newColumn) && is_string($oldColumn)) {
					$diff = ($newColumn != $oldColumn);
				} else {
					$diff = true;
				}
				if ($diff) {
					$drop[$name] = null;
					$add[$name] = $value;
				}
			}
		}
		return array_filter(compact('add', 'drop'));
	}
}
