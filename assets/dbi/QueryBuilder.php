<?php
namespace R\Lib\Table;
/**
 *
 */
class QueryBuilder
{
/**
 * index definition, standard cake, primary, index, unique
 */
    public $index = array('PRI' => 'primary', 'MUL' => 'index', 'UNI' => 'unique');
/**
 * Database keyword used to assign aliases to identifiers.
 */
    public $alias = 'AS ';
/**
 * Caches result from query parsing operations. Cached results for both DboSource::name() and
 * DboSource::conditions() will be stored here. Method caching uses `md5()`. If you have
 * problems with collisions, set DboSource::$cacheMethods to false.
 */
    public static $methodCache = array();
/**
 * Whether or not to cache the results of DboSource::name() and DboSource::conditions()
 * into the memory cache. Set to false to disable the use of the memory cache.
 */
    public $cacheMethods = true;
    // public $useNestedTransactions = false;
    // public $fullDebug = false;
/**
 * String to hold how many rows were affected by the last SQL operation.
 */
    // public $affected = null;
/**
 * Number of rows in current resultset
 */
    // public $numRows = null;
/**
 * Time the last query took
 */
    // public $took = null;
/**
 * Result
 */
    // public $_result = null;
/**
 * Queries count.
 */
    // protected $_queriesCnt = 0;
/**
 * Total duration of all queries.
 */
    // protected $_queriesTime = null;
/**
 * Log of queries executed by this DataSource
 */
    // protected $_queriesLog = array();
/**
 * Maximum number of items in query log
 */
    // protected $_queriesLogMax = 200;
/**
 * Caches serialized results of executed queries
 */
    // protected $_queryCache = array();
/**
 * A reference to the physical connection of this DataSource
 */
    // protected $_connection = null;
/**
 * The DataSource configuration key name
 */
    // public $configKeyName = null;
/**
 * The starting character that this DataSource uses for quoted identifiers.
 */
    public $startQuote = null;
/**
 * The ending character that this DataSource uses for quoted identifiers.
 */
    public $endQuote = null;
/**
 * The set of valid SQL operations usable in a WHERE statement
 */
    protected $_sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');
/**
 * Indicates the level of nested transactions
 */
    // protected $_transactionNesting = 0;
/**
 * Default fields that are used by the DBO
 */
    protected $_queryDefaults = array(
        'conditions' => array(),
        'fields' => null,
        'table' => null,
        'alias' => null,
        'order' => null,
        'limit' => null,
        'joins' => array(),
        'group' => null,
        'offset' => null
    );
/**
 * Separator string for virtualField composition
 */
    // public $virtualFieldSeparator = '__';
/**
 * List of table engine specific parameters used on table creating
 */
    // public $tableParameters = array();
/**
 * List of engine specific additional field parameters used on table creating
 */
    // public $fieldParameters = array();
/**
 * Indicates whether there was a change on the cached results on the methods of this class
 * This will be used for storing in a more persistent cache
 */
    protected $_methodCacheChange = false;
/**
 * Constructor
 */
    // public function __construct($config = null, $autoConnect = true) {
    //     if (!isset($config['prefix'])) {
    //         $config['prefix'] = '';
    //     }
    //     parent::__construct($config);
    //     $this->fullDebug = Configure::read('debug') > 1;
    //     if (!$this->enabled()) {
    //         throw new MissingConnectionException(array(
    //             'class' => get_class($this),
    //             'message' => __d('cake_dev', 'Selected driver is not enabled'),
    //             'enabled' => false
    //         ));
    //     }
    //     if ($autoConnect) {
    //         $this->connect();
    //     }
    // }
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 */
    public function value($data, $column = null) {
        if (is_array($data) && !empty($data)) {
            return array_map(
                array(&$this, 'value'),
                $data, array_fill(0, count($data), $column)
            );
        } elseif (is_object($data) && isset($data->type, $data->value)) {
            if ($data->type === 'identifier') {
                return $this->name($data->value);
            } elseif ($data->type === 'expression') {
                return $data->value;
            }
        } elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
            return $data;
        }
        if ($data === null || (is_array($data) && empty($data))) {
            return 'NULL';
        }
        if (empty($column)) {
            $column = $this->introspectType($data);
        }
        switch ($column) {
            case 'binary':
                return $this->_connection->quote($data, PDO::PARAM_LOB);
            case 'boolean':
                return $this->_connection->quote($this->boolean($data, true), PDO::PARAM_BOOL);
            case 'string':
            case 'text':
                return $this->_connection->quote($data, PDO::PARAM_STR);
            default:
                if ($data === '') {
                    return 'NULL';
                }
                if (is_float($data)) {
                    return str_replace(',', '.', strval($data));
                }
                if ((is_int($data) || $data === '0') || (
                    is_numeric($data) && strpos($data, ',') === false &&
                    $data[0] != '0' && strpos($data, 'e') === false)
                ) {
                    return $data;
                }
                return $this->_connection->quote($data);
        }
    }
/**
 * Returns an object to represent a database identifier in a query. Expression objects
 * are not sanitized or escaped.
 */
    // public function identifier($identifier) {
    //     $obj = new stdClass();
    //     $obj->type = 'identifier';
    //     $obj->value = $identifier;
    //     return $obj;
    // }
/**
 * Returns an object to represent a database expression in a query. Expression objects
 * are not sanitized or escaped.
 */
    // public function expression($expression) {
    //     $obj = new stdClass();
    //     $obj->type = 'expression';
    //     $obj->value = $expression;
    //     return $obj;
    // }
/**
 * Empties the method caches.
 * These caches are used by DboSource::name() and DboSource::conditions()
 */
    public function flushMethodCache() {
        $this->_methodCacheChange = true;
        self::$methodCache = array();
    }
/**
 * Cache a value into the methodCaches. Will respect the value of DboSource::$cacheMethods.
 * Will retrieve a value from the cache if $value is null.
 *
 * If caching is disabled and a write is attempted, the $value will be returned.
 * A read will either return the value or null.
 */
    public function cacheMethod($method, $key, $value = null) {
        if ($this->cacheMethods === false) {
            return $value;
        }
        if (!$this->_methodCacheChange && empty(self::$methodCache)) {
            self::$methodCache = (array)Cache::read('method_cache', '_cake_core_');
        }
        if ($value === null) {
            return (isset(self::$methodCache[$method][$key])) ? self::$methodCache[$method][$key] : null;
        }
        $this->_methodCacheChange = true;
        return self::$methodCache[$method][$key] = $value;
    }
/**
 * Returns a quoted name of $data for use in an SQL statement.
 * Strips fields out of SQL functions before quoting.
 */
    public function name($data) {
        if (is_object($data) && isset($data->type)) {
            return $data->value;
        }
        if ($data === '*') {
            return '*';
        }
        if (is_array($data)) {
            foreach ($data as $i => $dataItem) {
                $data[$i] = $this->name($dataItem);
            }
            return $data;
        }
        $cacheKey = md5($this->startQuote . $data . $this->endQuote);
        if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
            return $return;
        }
        $data = trim($data);
        if (preg_match('/^[\w-]+(?:\.[^ \*]*)*$/', $data)) { // string, string.string
            if (strpos($data, '.') === false) { // string
                return $this->cacheMethod(__FUNCTION__, $cacheKey, $this->startQuote . $data . $this->endQuote);
            }
            $items = explode('.', $data);
            return $this->cacheMethod(__FUNCTION__, $cacheKey,
                $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote
            );
        }
        if (preg_match('/^[\w-]+\.\*$/', $data)) { // string.*
            return $this->cacheMethod(__FUNCTION__, $cacheKey,
                $this->startQuote . str_replace('.*', $this->endQuote . '.*', $data)
            );
        }
        if (preg_match('/^([\w-]+)\((.*)\)$/', $data, $matches)) { // Functions
            return $this->cacheMethod(__FUNCTION__, $cacheKey,
                $matches[1] . '(' . $this->name($matches[2]) . ')'
            );
        }
        if (preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+' . preg_quote($this->alias) . '\s*([\w-]+)$/i', $data, $matches)) {
            return $this->cacheMethod(
                __FUNCTION__, $cacheKey,
                preg_replace(
                    '/\s{2,}/', ' ', $this->name($matches[1]) . ' ' . $this->alias . ' ' . $this->name($matches[3])
                )
            );
        }
        if (preg_match('/^[\w-_\s]*[\w-_]+/', $data)) {
            return $this->cacheMethod(__FUNCTION__, $cacheKey, $this->startQuote . $data . $this->endQuote);
        }
        return $this->cacheMethod(__FUNCTION__, $cacheKey, $data);
    }
/**
 * Gets full table name including prefix
 */
    public function fullTableName($model, $quote = true, $schema = true) {
        if (is_object($model)) {
            $schemaName = $model->schemaName;
            $table = $model->tablePrefix . $model->table;
        } elseif (!empty($this->config['prefix']) && strpos($model, $this->config['prefix']) !== 0) {
            $table = $this->config['prefix'] . strval($model);
        } else {
            $table = strval($model);
        }
        if ($schema && !isset($schemaName)) {
            $schemaName = $this->getSchemaName();
        }
        if ($quote) {
            if ($schema && !empty($schemaName)) {
                if (strstr($table, '.') === false) {
                    return $this->name($schemaName) . '.' . $this->name($table);
                }
            }
            return $this->name($table);
        }
        if ($schema && !empty($schemaName)) {
            if (strstr($table, '.') === false) {
                return $schemaName . '.' . $table;
            }
        }
        return $table;
    }
/**
 * Builds and generates a JOIN condition from an array. Handles final clean-up before conversion.
 *
 * @param array $join An array defining a JOIN condition in a query.
 * @return string An SQL JOIN condition to be used in a query.
 * @see DboSource::renderJoinStatement()
 * @see DboSource::buildStatement()
 */
    public function buildJoinStatement($join) {
        $data = array_merge(array(
            'type' => null,
            'alias' => null,
            'table' => 'join_table',
            'conditions' => '',
        ), $join);
        if (!empty($data['alias'])) {
            $data['alias'] = $this->alias . $this->name($data['alias']);
        }
        if (!empty($data['conditions'])) {
            $data['conditions'] = trim($this->conditions($data['conditions'], true, false));
        }
        if (!empty($data['table']) && (!is_string($data['table']) || strpos($data['table'], '(') !== 0)) {
            $data['table'] = $this->fullTableName($data['table']);
        }
        return $this->renderJoinStatement($data);
    }
/**
 * Builds and generates an SQL statement from an array. Handles final clean-up before conversion.
 *
 * @param array $query An array defining an SQL query.
 * @param Model $Model The model object which initiated the query.
 * @return string An executable SQL statement.
 * @see DboSource::renderStatement()
 */
    public function buildStatement($query, Model $Model) {
        $query = array_merge($this->_queryDefaults, $query);
        if (!empty($query['joins'])) {
            $count = count($query['joins']);
            for ($i = 0; $i < $count; $i++) {
                if (is_array($query['joins'][$i])) {
                    $query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
                }
            }
        }
        return $this->renderStatement('select', array(
            'conditions' => $this->conditions($query['conditions'], true, true, $Model),
            'fields' => implode(', ', $query['fields']),
            'table' => $query['table'],
            'alias' => $this->alias . $this->name($query['alias']),
            'order' => $this->order($query['order'], 'ASC', $Model),
            'limit' => $this->limit($query['limit'], $query['offset']),
            'joins' => implode(' ', $query['joins']),
            'group' => $this->group($query['group'], $Model)
        ));
    }
/**
 * Renders a final SQL JOIN statement
 *
 * @param array $data The data to generate a join statement for.
 * @return string
 */
    public function renderJoinStatement($data) {
        if (strtoupper($data['type']) === 'CROSS' || empty($data['conditions'])) {
            return "{$data['type']} JOIN {$data['table']} {$data['alias']}";
        }
        return trim("{$data['type']} JOIN {$data['table']} {$data['alias']} ON ({$data['conditions']})");
    }
/**
 * Renders a final SQL statement by putting together the component parts in the correct order
 *
 * @param string $type type of query being run. e.g select, create, update, delete, schema, alter.
 * @param array $data Array of data to insert into the query.
 * @return string Rendered SQL expression to be run.
 */
    public function renderStatement($type, $data) {
        extract($data);
        $aliases = null;
        switch (strtolower($type)) {
            case 'select':
                return trim("SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}");
            case 'create':
                return "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
            case 'update':
                if (!empty($alias)) {
                    $aliases = "{$this->alias}{$alias} {$joins} ";
                }
                return trim("UPDATE {$table} {$aliases}SET {$fields} {$conditions}");
            case 'delete':
                if (!empty($alias)) {
                    $aliases = "{$this->alias}{$alias} {$joins} ";
                }
                return trim("DELETE {$alias} FROM {$table} {$aliases}{$conditions}");
            case 'schema':
                foreach (array('columns', 'indexes', 'tableParameters') as $var) {
                    if (is_array(${$var})) {
                        ${$var} = "\t" . implode(",\n\t", array_filter(${$var}));
                    } else {
                        ${$var} = '';
                    }
                }
                if (trim($indexes) !== '') {
                    $columns .= ',';
                }
                return "CREATE TABLE {$table} (\n{$columns}{$indexes}) {$tableParameters};";
            case 'alter':
                return;
        }
    }
/**
 * Merges a mixed set of string/array conditions.
 *
 * @param mixed $query The query to merge conditions for.
 * @param mixed $assoc The association names.
 * @return array
 */
    protected function _mergeConditions($query, $assoc) {
        if (empty($assoc)) {
            return $query;
        }
        if (is_array($query)) {
            return array_merge((array)$assoc, $query);
        }
        if (!empty($query)) {
            $query = array($query);
            if (is_array($assoc)) {
                $query = array_merge($query, $assoc);
            } else {
                $query[] = $assoc;
            }
            return $query;
        }
        return $assoc;
    }
/**
 * Quotes and prepares fields and values for an SQL UPDATE statement
 *
 * @param Model $Model The model to prepare fields for.
 * @param array $fields The fields to update.
 * @param bool $quoteValues If values should be quoted, or treated as SQL snippets
 * @param bool $alias Include the model alias in the field name
 * @return array Fields and values, quoted and prepared
 */
    protected function _prepareUpdateFields(Model $Model, $fields, $quoteValues = true, $alias = false) {
        $quotedAlias = $this->startQuote . $Model->alias . $this->endQuote;
        $updates = array();
        foreach ($fields as $field => $value) {
            if ($alias && strpos($field, '.') === false) {
                $quoted = $Model->escapeField($field);
            } elseif (!$alias && strpos($field, '.') !== false) {
                $quoted = $this->name(str_replace($quotedAlias . '.', '', str_replace(
                    $Model->alias . '.', '', $field
                )));
            } else {
                $quoted = $this->name($field);
            }
            if ($value === null) {
                $updates[] = $quoted . ' = NULL';
                continue;
            }
            $update = $quoted . ' = ';
            if ($quoteValues) {
                $update .= $this->value($value, $Model->getColumnType($field));
            } elseif ($Model->getColumnType($field) === 'boolean' && (is_int($value) || is_bool($value))) {
                $update .= $this->boolean($value, true);
            } elseif (!$alias) {
                $update .= str_replace($quotedAlias . '.', '', str_replace(
                    $Model->alias . '.', '', $value
                ));
            } else {
                $update .= $value;
            }
            $updates[] = $update;
        }
        return $updates;
    }
/**
 * Returns an SQL calculation, i.e. COUNT() or MAX()
 */
    public function calculate(Model $Model, $func, $params = array()) {
        $params = (array)$params;
        switch (strtolower($func)) {
            case 'count':
                if (!isset($params[0])) {
                    $params[0] = '*';
                }
                if (!isset($params[1])) {
                    $params[1] = 'count';
                }
                if ($Model->isVirtualField($params[0])) {
                    $arg = $this->_quoteFields($Model->getVirtualField($params[0]));
                } else {
                    $arg = $this->name($params[0]);
                }
                return 'COUNT(' . $arg . ') AS ' . $this->name($params[1]);
            case 'max':
            case 'min':
                if (!isset($params[1])) {
                    $params[1] = $params[0];
                }
                if ($Model->isVirtualField($params[0])) {
                    $arg = $this->_quoteFields($Model->getVirtualField($params[0]));
                } else {
                    $arg = $this->name($params[0]);
                }
                return strtoupper($func) . '(' . $arg . ') AS ' . $this->name($params[1]);
        }
    }
/**
 * Creates a default set of conditions from the model if $conditions is null/empty.
 * If conditions are supplied then they will be returned. If a model doesn't exist and no conditions
 * were provided either null or false will be returned based on what was input.
 */
    public function defaultConditions(Model $Model, $conditions, $useAlias = true) {
        if (!empty($conditions)) {
            return $conditions;
        }
        $exists = $Model->exists();
        if (!$exists && ($conditions !== null || !empty($Model->__safeUpdateMode))) {
            return false;
        } elseif (!$exists) {
            return null;
        }
        $alias = $Model->alias;
        if (!$useAlias) {
            $alias = $this->fullTableName($Model, false);
        }
        return array("{$alias}.{$Model->primaryKey}" => $Model->getID());
    }
/**
 * Returns a key formatted like a string Model.fieldname(i.e. Post.title, or Country.name)
 */
    public function resolveKey(Model $Model, $key, $assoc = null) {
        if (strpos('.', $key) !== false) {
            return $this->name($Model->alias) . '.' . $this->name($key);
        }
        return $key;
    }
/**
 * Private helper method to remove query metadata in given data array.
 */
    protected function _scrubQueryData($data) {
        static $base = null;
        if ($base === null) {
            $base = array_fill_keys(array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'), array());
            $base['callbacks'] = null;
        }
        return (array)$data + $base;
    }
/**
 * Generates the fields list of an SQL query.
 */
    public function fields(Model $Model, $alias = null, $fields = array(), $quote = true) {
        if (empty($alias)) {
            $alias = $Model->alias;
        }
        $virtualFields = $Model->getVirtualField();
        $cacheKey = array(
            $alias,
            get_class($Model),
            $Model->alias,
            $virtualFields,
            $fields,
            $quote,
            ConnectionManager::getSourceName($this),
            $Model->schemaName,
            $Model->table
        );
        $cacheKey = md5(serialize($cacheKey));
        if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
            return $return;
        }
        $allFields = empty($fields);
        if ($allFields) {
            $fields = array_keys($Model->schema());
        } elseif (!is_array($fields)) {
            $fields = String::tokenize($fields);
        }
        $fields = array_values(array_filter($fields));
        $allFields = $allFields || in_array('*', $fields) || in_array($Model->alias . '.*', $fields);
        $virtual = array();
        if (!empty($virtualFields)) {
            $virtualKeys = array_keys($virtualFields);
            foreach ($virtualKeys as $field) {
                $virtualKeys[] = $Model->alias . '.' . $field;
            }
            $virtual = ($allFields) ? $virtualKeys : array_intersect($virtualKeys, $fields);
            foreach ($virtual as $i => $field) {
                if (strpos($field, '.') !== false) {
                    $virtual[$i] = str_replace($Model->alias . '.', '', $field);
                }
                $fields = array_diff($fields, array($field));
            }
            $fields = array_values($fields);
        }
        if (!$quote) {
            if (!empty($virtual)) {
                $fields = array_merge($fields, $this->_constructVirtualFields($Model, $alias, $virtual));
            }
            return $fields;
        }
        $count = count($fields);
        if ($count >= 1 && !in_array($fields[0], array('*', 'COUNT(*)'))) {
            for ($i = 0; $i < $count; $i++) {
                if (is_string($fields[$i]) && in_array($fields[$i], $virtual)) {
                    unset($fields[$i]);
                    continue;
                }
                if (is_object($fields[$i]) && isset($fields[$i]->type) && $fields[$i]->type === 'expression') {
                    $fields[$i] = $fields[$i]->value;
                } elseif (preg_match('/^\(.*\)\s' . $this->alias . '.*/i', $fields[$i])) {
                    continue;
                } elseif (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
                    $prepend = '';
                    if (strpos($fields[$i], 'DISTINCT') !== false) {
                        $prepend = 'DISTINCT ';
                        $fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
                    }
                    $dot = strpos($fields[$i], '.');
                    if ($dot === false) {
                        $prefix = !(
                            strpos($fields[$i], ' ') !== false ||
                            strpos($fields[$i], '(') !== false
                        );
                        $fields[$i] = $this->name(($prefix ? $alias . '.' : '') . $fields[$i]);
                    } else {
                        if (strpos($fields[$i], ',') === false) {
                            $build = explode('.', $fields[$i]);
                            if (!Hash::numeric($build)) {
                                $fields[$i] = $this->name(implode('.', $build));
                            }
                        }
                    }
                    $fields[$i] = $prepend . $fields[$i];
                } elseif (preg_match('/\(([\.\w]+)\)/', $fields[$i], $field)) {
                    if (isset($field[1])) {
                        if (strpos($field[1], '.') === false) {
                            $field[1] = $this->name($alias . '.' . $field[1]);
                        } else {
                            $field[0] = explode('.', $field[1]);
                            if (!Hash::numeric($field[0])) {
                                $field[0] = implode('.', array_map(array(&$this, 'name'), $field[0]));
                                $fields[$i] = preg_replace('/\(' . $field[1] . '\)/', '(' . $field[0] . ')', $fields[$i], 1);
                            }
                        }
                    }
                }
            }
        }
        if (!empty($virtual)) {
            $fields = array_merge($fields, $this->_constructVirtualFields($Model, $alias, $virtual));
        }
        return $this->cacheMethod(__FUNCTION__, $cacheKey, array_unique($fields));
    }
/**
 * Creates a WHERE clause by parsing given conditions data. If an array or string
 * conditions are provided those conditions will be parsed and quoted. If a boolean
 * is given it will be integer cast as condition. Null will return 1 = 1.
 *
 * Results of this method are stored in a memory cache. This improves performance, but
 * because the method uses a hashing algorithm it can have collisions.
 * Setting DboSource::$cacheMethods to false will disable the memory cache.
 *
 * @param mixed $conditions Array or string of conditions, or any value.
 * @param bool $quoteValues If true, values should be quoted
 * @param bool $where If true, "WHERE " will be prepended to the return value
 * @param Model $Model A reference to the Model instance making the query
 * @return string SQL fragment
 */
    public function conditions($conditions, $quoteValues = true, $where = true, Model $Model = null) {
        $clause = $out = '';
        if ($where) {
            $clause = ' WHERE ';
        }
        if (is_array($conditions) && !empty($conditions)) {
            $out = $this->conditionKeysToString($conditions, $quoteValues, $Model);
            if (empty($out)) {
                return $clause . ' 1 = 1';
            }
            return $clause . implode(' AND ', $out);
        }
        if (is_bool($conditions)) {
            return $clause . (int)$conditions . ' = 1';
        }
        if (empty($conditions) || trim($conditions) === '') {
            return $clause . '1 = 1';
        }
        $clauses = '/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i';
        if (preg_match($clauses, $conditions)) {
            $clause = '';
        }
        $conditions = $this->_quoteFields($conditions);
        return $clause . $conditions;
    }
/**
 * Creates a WHERE clause by parsing given conditions array. Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @param bool $quoteValues If true, values should be quoted
 * @param Model $Model A reference to the Model instance making the query
 * @return string SQL fragment
 */
    public function conditionKeysToString($conditions, $quoteValues = true, Model $Model = null) {
        $out = array();
        $data = $columnType = null;
        $bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');
        foreach ($conditions as $key => $value) {
            $join = ' AND ';
            $not = null;
            if (is_array($value)) {
                $valueInsert = (
                    !empty($value) &&
                    (substr_count($key, '?') === count($value) || substr_count($key, ':') === count($value))
                );
            }
            if (is_numeric($key) && empty($value)) {
                continue;
            } elseif (is_numeric($key) && is_string($value)) {
                $out[] = $this->_quoteFields($value);
            } elseif ((is_numeric($key) && is_array($value)) || in_array(strtolower(trim($key)), $bool)) {
                if (in_array(strtolower(trim($key)), $bool)) {
                    $join = ' ' . strtoupper($key) . ' ';
                } else {
                    $key = $join;
                }
                $value = $this->conditionKeysToString($value, $quoteValues, $Model);
                if (strpos($join, 'NOT') !== false) {
                    if (strtoupper(trim($key)) === 'NOT') {
                        $key = 'AND ' . trim($key);
                    }
                    $not = 'NOT ';
                }
                if (empty($value)) {
                    continue;
                }
                if (empty($value[1])) {
                    if ($not) {
                        $out[] = $not . '(' . $value[0] . ')';
                    } else {
                        $out[] = $value[0];
                    }
                } else {
                    $out[] = '(' . $not . '(' . implode(') ' . strtoupper($key) . ' (', $value) . '))';
                }
            } else {
                if (is_object($value) && isset($value->type)) {
                    if ($value->type === 'identifier') {
                        $data .= $this->name($key) . ' = ' . $this->name($value->value);
                    } elseif ($value->type === 'expression') {
                        if (is_numeric($key)) {
                            $data .= $value->value;
                        } else {
                            $data .= $this->name($key) . ' = ' . $value->value;
                        }
                    }
                } elseif (is_array($value) && !empty($value) && !$valueInsert) {
                    $keys = array_keys($value);
                    if ($keys === array_values($keys)) {
                        $count = count($value);
                        if ($count === 1 && !preg_match('/\s+(?:NOT|\!=)$/', $key)) {
                            $data = $this->_quoteFields($key) . ' = (';
                            if ($quoteValues) {
                                if ($Model !== null) {
                                    $columnType = $Model->getColumnType($key);
                                }
                                $data .= implode(', ', $this->value($value, $columnType));
                            }
                            $data .= ')';
                        } else {
                            $data = $this->_parseKey($key, $value, $Model);
                        }
                    } else {
                        $ret = $this->conditionKeysToString($value, $quoteValues, $Model);
                        if (count($ret) > 1) {
                            $data = '(' . implode(') AND (', $ret) . ')';
                        } elseif (isset($ret[0])) {
                            $data = $ret[0];
                        }
                    }
                } elseif (is_numeric($key) && !empty($value)) {
                    $data = $this->_quoteFields($value);
                } else {
                    $data = $this->_parseKey(trim($key), $value, $Model);
                }
                if ($data) {
                    $out[] = $data;
                    $data = null;
                }
            }
        }
        return $out;
    }
/**
 * Extracts a Model.field identifier and an SQL condition operator from a string, formats
 * and inserts values, and composes them into an SQL snippet.
 *
 * @param string $key An SQL key snippet containing a field and optional SQL operator
 * @param mixed $value The value(s) to be inserted in the string
 * @param Model $Model Model object initiating the query
 * @return string
 */
    protected function _parseKey($key, $value, Model $Model = null) {
        $operatorMatch = '/^(((' . implode(')|(', $this->_sqlOps);
        $operatorMatch .= ')\\x20?)|<[>=]?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)/is';
        $bound = (strpos($key, '?') !== false || (is_array($value) && strpos($key, ':') !== false));
        $key = trim($key);
        if (strpos($key, ' ') === false) {
            $operator = '=';
        } else {
            list($key, $operator) = explode(' ', $key, 2);
            if (!preg_match($operatorMatch, trim($operator)) && strpos($operator, ' ') !== false) {
                $key = $key . ' ' . $operator;
                $split = strrpos($key, ' ');
                $operator = substr($key, $split);
                $key = substr($key, 0, $split);
            }
        }
        $virtual = false;
        $type = null;
        if ($Model !== null) {
            if ($Model->isVirtualField($key)) {
                $key = $this->_quoteFields($Model->getVirtualField($key));
                $virtual = true;
            }
            $type = $Model->getColumnType($key);
        }
        $null = $value === null || (is_array($value) && empty($value));
        if (strtolower($operator) === 'not') {
            $data = $this->conditionKeysToString(
                array($operator => array($key => $value)), true, $Model
            );
            return $data[0];
        }
        $value = $this->value($value, $type);
        if (!$virtual && $key !== '?') {
            $isKey = (
                strpos($key, '(') !== false ||
                strpos($key, ')') !== false ||
                strpos($key, '|') !== false
            );
            $key = $isKey ? $this->_quoteFields($key) : $this->name($key);
        }
        if ($bound) {
            return String::insert($key . ' ' . trim($operator), $value);
        }
        if (!preg_match($operatorMatch, trim($operator))) {
            $operator .= is_array($value) ? ' IN' : ' =';
        }
        $operator = trim($operator);
        if (is_array($value)) {
            $value = implode(', ', $value);
            switch ($operator) {
                case '=':
                    $operator = 'IN';
                    break;
                case '!=':
                case '<>':
                    $operator = 'NOT IN';
                    break;
            }
            $value = "({$value})";
        } elseif ($null || $value === 'NULL') {
            switch ($operator) {
                case '=':
                    $operator = 'IS';
                    break;
                case '!=':
                case '<>':
                    $operator = 'IS NOT';
                    break;
            }
        }
        if ($virtual) {
            return "({$key}) {$operator} {$value}";
        }
        return "{$key} {$operator} {$value}";
    }
/**
 * Quotes Model.fields
 *
 * @param string $conditions The conditions to quote.
 * @return string or false if no match
 */
    protected function _quoteFields($conditions) {
        $start = $end = null;
        $original = $conditions;
        if (!empty($this->startQuote)) {
            $start = preg_quote($this->startQuote);
        }
        if (!empty($this->endQuote)) {
            $end = preg_quote($this->endQuote);
        }
        $conditions = str_replace(array($start, $end), '', $conditions);
        $conditions = preg_replace_callback(
            '/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9_][a-z0-9\\-_]*\\.[a-z0-9_][a-z0-9_\\-]*)/i',
            array(&$this, '_quoteMatchedField'),
            $conditions
        );
        if ($conditions !== null) {
            return $conditions;
        }
        return $original;
    }
/**
 * Auxiliary function to quote matches `Model.fields` from a preg_replace_callback call
 *
 * @param string $match matched string
 * @return string quoted string
 */
    protected function _quoteMatchedField($match) {
        if (is_numeric($match[0])) {
            return $match[0];
        }
        return $this->name($match[0]);
    }
/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
    public function limit($limit, $offset = null) {
        if ($limit) {
            $rt = ' LIMIT';
            if ($offset) {
                $rt .= sprintf(' %u,', $offset);
            }
            $rt .= sprintf(' %u', $limit);
            return $rt;
        }
        return null;
    }
/**
 * Returns an ORDER BY clause as a string.
 *
 * @param array|string $keys Field reference, as a key (i.e. Post.title)
 * @param string $direction Direction (ASC or DESC)
 * @param Model $Model Model reference (used to look for virtual field)
 * @return string ORDER BY clause
 */
    public function order($keys, $direction = 'ASC', Model $Model = null) {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $keys = array_filter($keys);
        $result = array();
        while (!empty($keys)) {
            list($key, $dir) = each($keys);
            array_shift($keys);
            if (is_numeric($key)) {
                $key = $dir;
                $dir = $direction;
            }
            if (is_string($key) && strpos($key, ',') !== false && !preg_match('/\(.+\,.+\)/', $key)) {
                $key = array_map('trim', explode(',', $key));
            }
            if (is_array($key)) {
                //Flatten the array
                $key = array_reverse($key, true);
                foreach ($key as $k => $v) {
                    if (is_numeric($k)) {
                        array_unshift($keys, $v);
                    } else {
                        $keys = array($k => $v) + $keys;
                    }
                }
                continue;
            } elseif (is_object($key) && isset($key->type) && $key->type === 'expression') {
                $result[] = $key->value;
                continue;
            }
            if (preg_match('/\\x20(ASC|DESC).*/i', $key, $_dir)) {
                $dir = $_dir[0];
                $key = preg_replace('/\\x20(ASC|DESC).*/i', '', $key);
            }
            $key = trim($key);
            if ($Model !== null) {
                if ($Model->isVirtualField($key)) {
                    $key = '(' . $this->_quoteFields($Model->getVirtualField($key)) . ')';
                }
                list($alias) = pluginSplit($key);
                if ($alias !== $Model->alias && is_object($Model->{$alias}) && $Model->{$alias}->isVirtualField($key)) {
                    $key = '(' . $this->_quoteFields($Model->{$alias}->getVirtualField($key)) . ')';
                }
            }
            if (strpos($key, '.')) {
                $key = preg_replace_callback('/([a-zA-Z0-9_-]{1,})\\.([a-zA-Z0-9_-]{1,})/', array(&$this, '_quoteMatchedField'), $key);
            }
            if (!preg_match('/\s/', $key) && strpos($key, '.') === false) {
                $key = $this->name($key);
            }
            $key .= ' ' . trim($dir);
            $result[] = $key;
        }
        if (!empty($result)) {
            return ' ORDER BY ' . implode(', ', $result);
        }
        return '';
    }
/**
 * Create a GROUP BY SQL clause.
 *
 * @param string|array $fields Group By fields
 * @param Model $Model The model to get group by fields for.
 * @return string Group By clause or null.
 */
    public function group($fields, Model $Model = null) {
        if (empty($fields)) {
            return null;
        }
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if ($Model !== null) {
            foreach ($fields as $index => $key) {
                if ($Model->isVirtualField($key)) {
                    $fields[$index] = '(' . $Model->getVirtualField($key) . ')';
                }
            }
        }
        $fields = implode(', ', $fields);
        return ' GROUP BY ' . $this->_quoteFields($fields);
    }
/**
 * Gets the length of a database-native column description, or null if no length
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return mixed An integer or string representing the length of the column, or null for unknown length.
 */
    public function length($real) {
        if (!preg_match_all('/([\w\s]+)(?:\((\d+)(?:,(\d+))?\))?(\sunsigned)?(\szerofill)?/', $real, $result)) {
            $col = str_replace(array(')', 'unsigned'), '', $real);
            $limit = null;
            if (strpos($col, '(') !== false) {
                list($col, $limit) = explode('(', $col);
            }
            if ($limit !== null) {
                return (int)$limit;
            }
            return null;
        }
        $types = array(
            'int' => 1, 'tinyint' => 1, 'smallint' => 1, 'mediumint' => 1, 'integer' => 1, 'bigint' => 1
        );
        list($real, $type, $length, $offset, $sign) = $result;
        $typeArr = $type;
        $type = $type[0];
        $length = $length[0];
        $offset = $offset[0];
        $isFloat = in_array($type, array('dec', 'decimal', 'float', 'numeric', 'double'));
        if ($isFloat && $offset) {
            return $length . ',' . $offset;
        }
        if (($real[0] == $type) && (count($real) === 1)) {
            return null;
        }
        if (isset($types[$type])) {
            $length += $types[$type];
            if (!empty($sign)) {
                $length--;
            }
        } elseif (in_array($type, array('enum', 'set'))) {
            $length = 0;
            foreach ($typeArr as $key => $enumValue) {
                if ($key === 0) {
                    continue;
                }
                $tmpLength = strlen($enumValue);
                if ($tmpLength > $length) {
                    $length = $tmpLength;
                }
            }
        }
        return (int)$length;
    }
/**
 * Translates between PHP boolean values and Database (faked) boolean values
 *
 * @param mixed $data Value to be translated
 * @param bool $quote Whether or not the field should be cast to a string.
 * @return string|bool Converted boolean value
 */
    public function boolean($data, $quote = false) {
        if ($quote) {
            return !empty($data) ? '1' : '0';
        }
        return !empty($data);
    }
/**
 * Guesses the data type of an array
 *
 * @param string $value The value to introspect for type data.
 * @return string
 */
    public function introspectType($value) {
        if (!is_array($value)) {
            if (is_bool($value)) {
                return 'boolean';
            }
            if (is_float($value) && (float)$value === $value) {
                return 'float';
            }
            if (is_int($value) && (int)$value === $value) {
                return 'integer';
            }
            if (is_string($value) && strlen($value) > 255) {
                return 'text';
            }
            return 'string';
        }
        $isAllFloat = $isAllInt = true;
        $containsInt = $containsString = false;
        foreach ($value as $valElement) {
            $valElement = trim($valElement);
            if (!is_float($valElement) && !preg_match('/^[\d]+\.[\d]+$/', $valElement)) {
                $isAllFloat = false;
            } else {
                continue;
            }
            if (!is_int($valElement) && !preg_match('/^[\d]+$/', $valElement)) {
                $isAllInt = false;
            } else {
                $containsInt = true;
                continue;
            }
            $containsString = true;
        }
        if ($isAllFloat) {
            return 'float';
        }
        if ($isAllInt) {
            return 'integer';
        }
        if ($containsInt && !$containsString) {
            return 'integer';
        }
        return 'string';
    }
}
