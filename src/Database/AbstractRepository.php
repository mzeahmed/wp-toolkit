<?php

declare(strict_types=1);

namespace MzeAhmed\WpHelpers\Database;

abstract class AbstractRepository
{
    /**
     * @var \wpdb $wpdb wpdb instance
     */
    protected \wpdb $wpdb;

    /**
     * @var string $dbPrefix Database prefix
     */
    protected string $dbPrefix;

    /**
     * @var string Table name
     */
    protected string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->dbPrefix = $this->wpdb->prefix;

        $this->createChangeLogTable();
    }

    /**
     * Finds a record by its ID.
     *
     * @param int $id The ID of the record.
     * @param array $columns Columns to select. By default, all columns are selected.
     *
     * @return object|null The found object or null.
     */
    public function findById(int $id, array $columns = ['*']): ?object
    {
        $columnsString = implode(',', $columns);

        $query = "SELECT {$columnsString} FROM {$this->tableName} WHERE id = %d LIMIT 1";
        $query = $this->wpdb->prepare($query, $id);

        return $this->wpdb->get_row($query);
    }

    /**
     * Finds a record based on specified criteria with options for joins, selected columns, and table aliases.
     *
     * @param array $where Search criteria as an associative array. Example:
     *      array('user_id' => 1);
     *      Using specific operators:
     *      array(
     *          'user_id' => array('value' => 1, 'operator' => '!='),
     *          'status' => array('value' => 'active', 'operator' => '=')
     *      );
     * @param array $columns Columns to select. By default, all columns are selected.
     * @param array $joinArgs Array of additional arguments for joins. Example:
     *      array(
     *          array(
     *              'selectColumns' => array('users.name as userName', 'posts.title as postTitle'),
     *              'joinType' => 'LEFT',
     *              'joinTable' => 'posts',
     *              'joinTableAlias' => 'posts',
     *              'joinOn' => 'users.id = posts.user_id',
     *          ),
     *          array(
     *              'selectColumns' => array('comments.content as commentContent'),
     *              'joinType' => 'LEFT',
     *              'joinTable' => 'comments',
     *              'joinTableAlias' => 'comments',
     *              'joinOn' => 'users.id = comments.user_id',
     *          )
     *      );
     * @param string $mainTableAlias The alias of the main table. Can be left empty if not used.
     *
     * @return object|null The found object or null if no result.
     */
    public function findOneByCriteria(
        array $where,
        array $columns = ['*'],
        array $joinArgs = [],
        string $mainTableAlias = ''
    ): ?object {
        $query = $this->buildSelectQuery($where, $columns, $joinArgs, [], $mainTableAlias, 1);

        return $this->wpdb->get_row($query);
    }

    /**
     * Retrieves all records from the table.
     *
     * @param array $orderBy Column(s) to sort by, as an associative array where the key is the column name and the
     *     value is the order ('ASC' or 'DESC').
     *
     * @return array List of found objects.
     */
    public function findAll(array $orderBy = []): array
    {
        $orderClause = '';
        if (!empty($orderBy)) {
            $orderColumns = array_map(static function ($col, $dir) {
                return "{$col} {$dir}";
            }, array_keys($orderBy), $orderBy);

            $orderClause = 'ORDER BY ' . implode(', ', $orderColumns);
        }

        return $this->wpdb->get_results("SELECT * FROM {$this->tableName} {$orderClause}");
    }

    /**
     * Retrieves multiple records based on specified criteria with options for joins, selected columns, and table
     * aliases.
     *
     * @param array $where Search criteria.
     *      Simple usage with the default '=' operator:
     *      $where = array('user_id' => 1);
     *      Using specific operators:
     *      $where = array(
     *          'user_id' => array('value' => 1, 'operator' => '!='),
     *          'status' => array('value' => 'active', 'operator' => '=')
     *      );
     * @param array $columns Columns to select. By default, all columns are selected.
     * @param array $extraArgs Array of additional arguments for the query ('orderby', 'limit', 'mainTableAlias').
     *      $extraArgs = array(
     *          'joinArgs => array(
     *              array(
     *                  'selectColumns' => array('users.name as userName', 'posts.title as postTitle'),
     *                  'joinType' => 'LEFT',
     *                  'joinTable' => 'posts',
     *                  'joinTableAlias' => 'posts'
     *                  'joinOn' => 'users.id = posts.user_id',
     *              ),
     *              array(
     *                  'selectColumns' => array('comments.content as commentContent'),
     *                  'joinType' => 'LEFT',
     *                  'joinTable' => 'comments',
     *                  'joinTableAlias' => 'comments'
     *                  'joinOn' => 'users.id = comments.user_id',
     *              )
     *           ),
     *       'orderBy' => array('u.updated_at' => 'DESC'),
     *       'limit' => 10,
     *       'mainTableAlias' => 'u'
     *    );
     *
     * @return array Found objects.
     */
    public function findByCriteria(array $where, array $columns = ['*'], array $extraArgs = []): array
    {
        $allowedKeys = ['joinArgs', 'orderBy', 'limit', 'mainTableAlias'];

        $this->validateAllowedKeys($extraArgs, $allowedKeys);

        $joinArgs = $extraArgs['joinArgs'] ?? [];
        $orderBy = $extraArgs['orderBy'] ?? [];
        $mainTableAlias = $extraArgs['mainTableAlias'] ?? '';
        $limit = $extraArgs['limit'] ?? null;

        $query = $this->buildSelectQuery($where, $columns, $joinArgs, $orderBy, $mainTableAlias, $limit);

        return $this->wpdb->get_results($query);
    }

    /**
     * Creates a new entry in the table.
     *
     * @param array<string|int> $datas Data to insert.
     * @param array|null $formatData Data formats for the insertion.
     *
     * @return int|false The ID of the created entry.
     */
    public function insert(array $datas, array $formatData = null): int|false
    {
        $nbRows = $this->wpdb->insert($this->tableName, $datas, $formatData);

        if (false === $nbRows) {
            return false;
        }

        $this->logChange('insert', $datas);

        return $this->wpdb->insert_id;
    }

    /**
     * Updates an entry.
     *
     * @param array $datas New data.
     * @param array $where WHERE clause for filtering results.
     * @param array|null $formatData Formats of the data to update.
     * @param array|null $formatWhere Formats of the data in the WHERE clause.
     *
     * @return int|false The number of updated rows, or false on error.
     */
    public function update(array $datas, array $where, array $formatData = null, array $formatWhere = null): int|false
    {
        $updated = $this->wpdb->update($this->tableName, $datas, $where, $formatData, $formatWhere);

        if ($updated) {
            $this->logChange('update', $datas);
        }

        return $updated;
    }

    /**
     * Deletes an entry from the database.
     *
     * @param array $where Criteria for deletion.
     *
     * @return int|false The number of deleted rows, or false on error.
     */
    public function delete(array $where, array $whereFormat = null): int|false
    {
        $deleted = $this->wpdb->delete($this->tableName, $where, $whereFormat);

        if ($deleted) {
            $this->logChange('delete');
        }

        return $deleted;
    }

    /**
     * Counts entries in the table based on criteria and a specific column.
     *
     * @param array $args Array of arguments for the query ('column', 'distinct', 'where', 'mainTableAlias',
     *     'joinArgs').
     *         $args = array(
     *              'column' => 'id',
     *              'distinct' => true,
     *              'where' => array('status' => 'active'),
     *              'joinArgs' => array(
     *                  array(
     *                      'joinType' => 'LEFT',
     *                      'joinTable' => 'posts',
     *                      'joinTableAlias' => 'posts'
     *                      'joinOn' => 'users.id = posts.user_id',
     *                  ),
     *                  array(
     *                      'joinType' => 'LEFT',
     *                      'joinTable' => 'comments',
     *                      'joinTableAlias' => 'comments'
     *                      'joinOn' => 'users.id = comments.user_id',
     *                  )
     *              )
     *         );
     *
     * @return int The total number of entries.
     */
    public function count(array $args = []): int
    {
        $allowedKeys = ['column', 'distinct', 'where', 'joinArgs', 'mainTableAlias'];

        $this->validateAllowedKeys($args, $allowedKeys);

        $column = $args['column'] ?? 'id';
        $distinct = $args['distinct'] ?? false;
        $where = $args['where'] ?? [];
        $joinArgs = $args['joinArgs'] ?? [];
        $mainTableAlias = $args['mainTableAlias'] ?? '';

        $column = !empty($mainTableAlias) ? "{$mainTableAlias}.{$column}" : "{$column}";

        $whereString = $this->buildWhereClause($where);

        [$joinClauses, $columns] = $this->processJoinArguments($joinArgs);
        $mainTableWithAlias = !empty($mainTableAlias) ? "{$this->tableName} AS {$mainTableAlias}" : $this->tableName;
        $joinClause = implode(' ', $joinClauses);

        $query = 'SELECT SQL_CACHE COUNT(' . ($distinct ? 'DISTINCT ' : '') . "{$column}) FROM {$mainTableWithAlias} {$joinClause}";

        if (!empty($where)) {
            $query .= " WHERE {$whereString}";
        }

        $var = $this->wpdb->get_var($query);


        return (int)$var;
    }

    /**
     * Performs a text search across multiple columns.
     *
     * @param string $keyword Keyword to search for.
     * @param array $columns Columns to search in.
     *
     * @return array Found objects.
     */
    public function searchByKeyword(string $keyword, array $columns): array
    {
        $searchCriteria = [];
        foreach ($columns as $column) {
            $column = esc_sql($column);
            $searchCriteria[] = "{$column} LIKE %s";
        }

        $searchClause = implode(' OR ', $searchCriteria);

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE {$searchClause}",
            array_fill(0, count($columns), '%' . $this->wpdb->esc_like($keyword) . '%')
        );

        return $this->wpdb->get_results($query);
    }

    /**
     * Retrieves results based on specified criteria with pagination.
     *
     * @param array $args Array of arguments for the query:
     *      // Using the default '=' operator:
     *      $where = ['gm.user_id' => $userId];
     *
     *     // Using a specific operator:
     *     $where = [
     *        'gm.user_id' => ['value' => 'active', 'operator' => '='],
     *        'g.status' => ['value' => [1, 2, 3], 'operator' => 'IN']
     *      ];
     *
     *      $joinArgs = [
     *          [
     *              'selectColumns' => ['g.*', 'gm.user_id'],
     *              'joinType' => 'LEFT',
     *              'joinTable' => $this->groupMembersTableName,
     *              'joinTableAlias' => 'gm',
     *              'joinOn' => 'g.id = gm.group_id',
     *          ],
     *      ];
     *
     *      $args = [
     *          'joinArgs' => $joinArgs,
     *          'orderBy' => ['gm.updated_at' => 'DESC'],
     *          'mainTableAlias' => 'g',
     *          'where' => $where,
     *          'limit' => $limit,
     *          'page' => $page
     *      ];
     *
     * @return array The objects matching the criteria with pagination.
     */
    public function findPaginatedResults(array $args): array
    {
        $where = $args['where'] ?? [];
        $joinArgs = $args['joinArgs'] ?? [];
        $order = $args['orderBy'] ?? [];
        $mainTableAlias = $args['mainTableAlias'] ?? '';
        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? null;

        $offset = ($page - 1) * $limit;

        $query = $this->buildSelectQuery($where, ['*'], $joinArgs, $order, $mainTableAlias);
        $query .= " LIMIT {$limit} OFFSET {$offset}";

        $results = $this->wpdb->get_results($query);

        $countArgs = [
            'where' => $where,
            'joinArgs' => $joinArgs,
            'mainTableAlias' => $mainTableAlias,
        ];

        $total = $this->count($countArgs);
        $totalPages = ($total / $limit);


        return [
            'results' => $results,
            'total' => $total,
            'totalPages' => \is_float($totalPages) ? (int)$totalPages + 1 : $totalPages,
        ];
    }

    /**
     * Inserts multiple entries in a single query.
     *
     * @param array $datas Array of associative arrays containing the data to insert.
     *
     * @return int|bool The insertion status or the number of inserted rows.
     */
    public function bulkInsert(array $datas): int|bool
    {
        if (empty($datas)) {
            return false;
        }

        $bulkData = $this->prepareBulkData($datas);
        $query = "
            INSERT INTO {$this->tableName} 
            (" . implode(',', $bulkData['columns']) . ') 
            VALUES ' . implode(',', $bulkData['placeholders']);
        $preparedQuery = $this->wpdb->prepare($query, $bulkData['values']);

        $insertedRows = $this->wpdb->query($preparedQuery);

        if ($insertedRows) {
            return $insertedRows;
        }


        return false;
    }

    /**
     * Updates multiple entries based on a specific column.
     *
     * @param string $column The name of the column used as the key for the update.
     * @param array $datas Array of associative arrays containing the data to update.
     *
     * @return bool The status of the update.
     * @since 2.2.0
     */
    public function bulkUpdate(string $column, array $datas): bool
    {
        if (empty($datas)) {
            return false;
        }

        foreach ($datas as $row) {
            $updateColumns = array_diff_key($row, [$column => '']);
            $updateColumns = array_map(static fn($col) => "{$col} = %s", array_keys($updateColumns));
            $updateColumns = implode(',', $updateColumns);

            $where = "{$column} = %s";
            $values = array_values($row);
            $query = $this->wpdb->prepare("UPDATE {$this->tableName} SET {$updateColumns} WHERE {$where}", $values);

            $update = $this->wpdb->query($query);

            if ($update) {
                return $update;
            }
        }


        return false;
    }

    /**
     * Deletes multiple entries based on a specific column.
     *
     * @param string $column The name of the column used as the key for deletion.
     * @param array $values The values to delete.
     *
     * @return bool The status of the deletion.
     */
    public function bulkDelete(string $column, array $values): bool
    {
        if (empty($values)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $query = "DELETE FROM {$this->tableName} WHERE {$column} IN ({$placeholders})";

        $delete = $this->wpdb->query($this->wpdb->prepare($query, $values));

        return false !== $delete;
    }

    /**
     * Starts a transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->wpdb->query('START TRANSACTION');
    }

    /**
     * Commits the changes made in the current transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->wpdb->query('COMMIT');
    }

    /**
     * Rolls back the changes made in the current transaction.
     *
     * @return void
     */
    public function rollback(): void
    {
        $this->wpdb->query('ROLLBACK');
    }

    /**
     * Checks if an entry exists based on the specified criteria.
     *
     * @param array $where The search criteria as an associative array.
     *
     * @return bool Returns true if the entry exists, false otherwise.
     */
    public function exists(array $where): bool
    {
        $whereString = $this->buildWhereClause($where);

        $query = $this->wpdb->prepare(
            "SELECT EXISTS(SELECT 1 FROM {$this->tableName} WHERE {$whereString})",
            array_values($where)
        );

        return 1 === (int)$this->wpdb->get_var($query);
    }

    /**
     * Returns the ID of the last inserted entry.
     *
     * @return int|null The ID of the last inserted entry, or null if none.
     */
    public function getLastInsertId(): ?int
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Retrieves a specific value from an entry.
     *
     * @param array $where The search criteria.
     * @param string $column The column to retrieve.
     * @param array $joinArgs An array of additional arguments for joins.
     *
     * @return string|null The retrieved value, or null if not found.
     */
    public function findValueByCriteria(array $where, string $column, array $joinArgs = []): ?string
    {
        $query = $this->buildSelectQuery($where, [$column], $joinArgs);

        return $this->wpdb->get_var($query);
    }

    /**
     * Logs changes made to the table.
     *
     * @param string $action The action performed (e.g., 'insert', 'update', 'delete').
     * @param array $data The data associated with the action.
     *
     * @return bool True on success, false on failure.
     */
    public function logChange(string $action, array $data = []): bool
    {
        $logTable = $this->dbPrefix . 'change_logs';
        $entry = [
            'table_name' => $this->tableName,
            'action' => $action,
            'user_id' => get_current_user_id(),
            'data' => !(empty($data)) ? json_encode($data) : null,
            'created_at' => current_time('mysql'),
        ];

        return (bool)$this->wpdb->insert($logTable, $entry);
    }

    /**
     * Creates an index on the table.
     *
     * @param string $indexName The name of the index.
     * @param array $columns The columns to include in the index.
     *
     * @return bool True on success, false on failure.
     */
    public function createIndex(string $indexName, array $columns): bool
    {
        $columnsList = implode(',', $columns);
        $query = "CREATE INDEX {$indexName} ON {$this->tableName} ({$columnsList})";
        return (bool)$this->wpdb->query($query);
    }

    /**
     * Drops an index from the table.
     *
     * @param string $indexName The name of the index to drop.
     *
     * @return bool True on success, false on failure.
     */
    public function dropIndex(string $indexName): bool
    {
        $query = "DROP INDEX {$indexName} ON {$this->tableName}";
        return (bool)$this->wpdb->query($query);
    }

    /**
     * Creates a log table to track changes made in a table.
     *
     * @return void True on success, false on failure.
     */
    protected function createChangeLogTable(): void
    {
        $charsetCollate = $this->wpdb->get_charset_collate();
        $logTable = $this->dbPrefix . 'change_logs';

        $sql = "CREATE TABLE IF NOT EXISTS {$logTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(255) NOT NULL,
            action VARCHAR(255) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            data LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            index table_name_index (table_name),
            index action_index (action),
            index user_id_index (user_id),
            index created_at_index (created_at)
        ) {$charsetCollate}";

        // $this->wpdb->query($sql);
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Logs a database error into the logs and throws an exception.
     *
     * @param mixed $result The result of the database query.
     * @param string $message The error message to log.
     *
     * @return void
     */
    protected function logDbError(mixed $result, string $message): void
    {
        if (false === $result) {
            $this->writeLog(\sprintf('%s: Raised exception with message=%s', 'Yoostart / Database', $message));
            $this->writeLog(\sprintf('%s: SQL last error=%s', 'Yoostsrt / Database', $this->wpdb->last_error));
            throw new \RuntimeException($message);
        }
    }

    /**
     * Prepares a WHERE clause for SQL queries based on given criteria.
     *
     * This function can handle both simple criteria and criteria with specific operators.
     *
     * @param array $where The search criteria.
     *
     * @return string The generated WHERE clause.
     *
     * @example
     * // Using the default '=' operator:
     * $results = $repository->findBy(['column1' => 'someValue']);
     *
     * // Using specific operators:
     * $where = [
     *     'column1' => ['value' => 'someValue', 'operator' => '!='],
     *     'column2' => ['value' => [1, 2, 3], 'operator' => 'IN'],
     *     'column3' => ['value' => 'someValue', 'operator' => 'LIKE'],
     *     'column4' => ['value' => [1, 10], 'operator' => 'BETWEEN']
     * ];
     * $results = $repository->findBy($where);
     */
    private function buildWhereClause(array $where): string
    {
        $whereClause = [];
        foreach ($where as $column => $data) {
            // Si la donnée est un tableau avec un opérateur et une valeur
            if (isset($data['operator'], $data['value']) && \is_array($data)) {
                switch ($data['operator']) {
                    case 'IN':
                        $inValues = implode(
                            ',',
                            array_map(
                                static function ($value) {
                                    return \is_string($value) ? "'" . esc_sql($value) . "'" : (int)$value;
                                },
                                $data['value']
                            )
                        );
                        $whereClause[] = esc_sql($column) . " IN ({$inValues})";
                        break;
                    case 'LIKE':
                        $whereClause[] = esc_sql($column) .
                            " LIKE '%" .
                            esc_sql($data['value']) .
                            "%'";
                        break;
                    case 'BETWEEN':
                        $whereClause[] = esc_sql($column) .
                            ' BETWEEN ' .
                            esc_sql($data['value'][0]) .
                            ' AND ' .
                            esc_sql($data['value'][1]);
                        break;
                    case 'IS NULL':
                        $whereClause[] = esc_sql($column) . ' IS NULL';
                        break;
                    case 'IS NOT NULL':
                        $whereClause[] = esc_sql($column) . ' IS NOT NULL';
                        break;
                    default:
                        $value = \is_string($data['value']) ? "'" . esc_sql($data['value']) . "'" : (int)$data['value'];
                        $whereClause[] = esc_sql($column) . " {$data['operator']} {$value}";
                        break;
                }
            } elseif (\is_string($data) && 0 === stripos($data, 'EXISTS')) {
                // Si la donnée est une chaîne de caractères et commence par 'EXISTS'
                $whereClause[] = esc_sql($data);
            } else {
                // Si c'est juste une valeur, utiliser l'opérateur par défaut '='
                $data = \is_string($data) ? "'" . esc_sql($data) . "'" : (int)$data;
                $whereClause[] = esc_sql($column) . " = {$data}";
            }
        }

        return implode(' AND ', $whereClause);
    }

    /**
     * Prepares an SQL SELECT query based on the specified criteria and arguments.
     *
     * @param array $where The search criteria to filter results.
     * @param array $columns The columns to select.
     * @param array $joinArgs Array of additional arguments for joins:
     *      $joinArgs = [
     *          [
     *              'selectColumns' => ['users.name AS userName', 'posts.title AS postTitle'],
     *              'joinType' => 'LEFT',
     *              'joinTable' => 'posts',
     *              'joinTableAlias' => 'posts',
     *              'joinOn' => 'users.id = posts.user_id',
     *          ],
     *          [
     *              'selectColumns' => ['comments.content AS commentContent'],
     *              'joinType' => 'LEFT',
     *              'joinTable' => 'comments',
     *              'joinTableAlias' => 'comments',
     *              'joinOn' => 'users.id = comments.user_id',
     *          ]
     *      ];
     * @param array $orderBy The column(s) to use for sorting.
     * @param string $mainTableAlias Alias for the main table in the query.
     * @param int|null $limit The limit of results to retrieve.
     *
     * @return string The prepared SQL SELECT query.
     */
    private function buildSelectQuery(
        array $where = [],
        array $columns = ['*'],
        array $joinArgs = [],
        array $orderBy = [],
        string $mainTableAlias = '',
        int $limit = null
    ): string {
        $whereClause = '';

        if (!empty($where)) {
            $whereClause = $this->buildWhereClause($where);
            $whereClause = "WHERE {$whereClause}";
        }

        $mainTableWithAlias = !empty($mainTableAlias) ? "{$this->tableName} AS {$mainTableAlias}" : $this->tableName;

        [$joinClauses, $columns] = $this->processJoinArguments($joinArgs, $columns);

        $joinClause = implode(' ', $joinClauses);

        $orderClause = '';
        if (!empty($orderBy)) {
            $orderColumns = array_map(static function ($col, $dir) {
                return "{$col} {$dir}";
            }, array_keys($orderBy), $orderBy);

            $orderClause = 'ORDER BY ' . implode(', ', $orderColumns);
        }

        $limitClause = '';
        if (null !== $limit) {
            $limitClause = "LIMIT {$limit}";
        }

        $columnsString = implode(',', $columns);

        return "
            SELECT {$columnsString} 
            FROM {$mainTableWithAlias} 
                {$joinClause} 
            {$whereClause} 
            {$orderClause} 
                {$limitClause}
        ";
    }

    /**
     * Prepares data for bulk operations.
     *
     * This method constructs the columns, placeholders, and values for bulk queries
     * from an array of data.
     *
     * @param array $datas An array of associative arrays containing the data to process.
     *
     * @return array An array containing columns, placeholders, and values:
     *               - 'columns': an array of column names.
     *               - 'placeholders': an array of placeholders for the values.
     *               - 'values': an array of values.
     */
    private function prepareBulkData(array $datas): array
    {
        $firstRow = current($datas);
        $columns = array_keys($firstRow);
        $placeholders = array_fill(
            0,
            count($datas),
            '(' . implode(',', array_fill(0, count($columns), '%s')) . ')'
        );
        $values = [];

        foreach ($datas as $data) {
            foreach ($columns as $column) {
                $values[] = $data[$column];
            }
        }

        return [
            'columns' => $columns,
            'placeholders' => $placeholders,
            'values' => $values,
        ];
    }

    /**
     * Iterates over join arguments to construct the JOIN clause.
     *
     * @param array $joinArgs An array of additional arguments for the query:
     *      Example:
     *      $joinArgs = [
     *          [
     *              'selectColumns' => ['users.name AS userName', 'posts.title AS postTitle'],
     *              'joinType' => 'LEFT',
     *              'joinTable' => 'posts',
     *              'joinTableAlias' => 'posts',
     *              'joinOn' => 'users.id = posts.user_id',
     *          ]
     *      ];
     * @param array $columns The columns to select.
     *
     * @return array An array containing the JOIN clauses and the selected columns.
     */
    private function processJoinArguments(array $joinArgs, array $columns = []): array
    {
        $joinClauses = [];

        foreach ($joinArgs as $join) {
            $joinType = $join['joinType'] ?? 'INNER';
            $joinTable = $join['joinTable'] ?? '';
            $joinTableAlias = $join['joinTableAlias'] ?? '';
            $joinOn = $join['joinOn'] ?? '';
            $joinTableWithAlias = $joinTableAlias ? "{$joinTable} AS {$joinTableAlias}" : $joinTable;

            if (!empty($joinTable) && !empty($joinOn)) {
                $joinClauses[] = "{$joinType} JOIN {$joinTableWithAlias} ON {$joinOn}";
            }

            if (!empty($join['selectColumns'])) {
                $columns = $join['selectColumns'];
            }
        }

        return [$joinClauses, $columns];
    }

    /**
     * Validates that the given array only contains allowed keys.
     *
     * @param array $args The array to validate.
     * @param array $allowedKeys The list of allowed keys.
     *
     * @throws \InvalidArgumentException If an invalid key is found.
     */
    private function validateAllowedKeys(array $args, array $allowedKeys): void
    {
        foreach ($args as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new \InvalidArgumentException(
                    sprintf('The $args array contains an invalid key (%s)', $key)
                );
            }
        }
    }

    /**
     * Validates the presence of one key when another is present.
     *
     * Ensures that if one value is provided, the other must also be provided.
     * Throws an exception if the validation fails.
     *
     * @param mixed $valueOne The first value to check.
     * @param mixed $valueTwo The second value to check.
     * @param array $args An array containing the names of the keys being validated.
     *                    Example: ['key1', 'key2']
     */
    private function validateRequiredKeys(mixed $valueOne, mixed $valueTwo, array $args): void
    {
        if ((empty($valueOne) && !empty($valueTwo)) || (!empty($valueOne) && empty($valueTwo))) {
            throw new \InvalidArgumentException(
                sprintf('The keys %s and %s must be defined together', $args[0], $args[1])
            );
        }
    }

    private function writeLog(mixed $log): void
    {
        if (true === WP_DEBUG) {
            if (\is_array($log) || \is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
