<?php
/* Shorthands for various mysql->query(sqlstring); mysql->fetch() */

if (! function_exists('db') ) {
    /** @return mysqli The (global) connection handler */
    function db() {
        global $db;
        return $db;
    }
}

/**
 * @param string $selectQuery sql-query-string
 * @param int $resultType
 * @return array
 */
function qArrayOne($selectQuery, $resultType = MYSQLI_BOTH) {
    $db = db();
    $res = $db->query($selectQuery);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }
    return false === $res ? [] : mysqli_fetch_array($res, $resultType);
}

/**
 * @param $selectQuery string sql-query-string
 * @return array
 */
function qAssocOne($selectQuery) {
    return qArrayOne($selectQuery, MYSQLI_ASSOC);
}

/**
 * @param string $selectQuery
 * @param mixed $defaultValue
 * @param int $resultType
 * @return mixed (defaultValue or first column of the first row in the result set)
 */
function qVar($selectQuery, $defaultValue = null, $resultType = MYSQLI_NUM) {
    $db = db();
    $res = $db->query($selectQuery);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }
    if ($res && $res->num_rows) {
        $first = $res->fetch_array($resultType);
        assert(is_array($first));
        return reset($first);
    }
    return $defaultValue;
}


/**
 * @param mysqli_result $res
 * @param int $resultType
 * @return array
 */
function qOne(mysqli_result $res, $resultType = MYSQLI_ASSOC) {
    return false === $res ? [] : mysqli_fetch_array($res, $resultType);
}



/**
 * @param string $selectQuery sql-query-string
 * @param int $resultType
 * @return array even when query is incorrect.
 */
function qArrayAll($selectQuery, $resultType = MYSQLI_BOTH) {
    $db = db();
    $result = $db->query($selectQuery);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }
    $rows = mysqli_fetch_all($result, $resultType);
    return $rows ? $rows : [];
}

/**
 * @param string $selectQuery sql-query-string
 * @return array|false
 */
function qAssocAll($selectQuery) {
    return qArrayAll($selectQuery, MYSQLI_ASSOC);
}


/** @return int */
function qInsert($insertQuery) {
    $db = db();
    $db->query($insertQuery);
    if ($db->error) {
        error_log($db->error);
        return 0;
    }
    return $db->insert_id;
}

/** @return int count of affected rows. */
function qUpdate($query) {
    $db = db();
    $res = $db->query($query);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => $query,
            'trace' => getPrettyTrace()
        ], true));
    }
    return $db->affected_rows;
}

/** @return false|mysqli_result */
function qResult($query) {
    $db = db();
    $res = $db->query($query);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => $query,
            'trace' => getPrettyTrace()
        ], true));
    }
    return $res;
}

/** @return int */ function qInsertId() { return db()->insert_id; }
/** @return int */ function qLastId() { return db()->insert_id; }

/** @return array */
function qArrayColumnAll($colName, $selectQuery, $resultType = MYSQLI_BOTH) {
    $records = qArrayAll($selectQuery, $resultType);
    $columns = [];
    $first = reset($records);
    if (empty($first) or (!array_key_exists($colName, $first))) {
        return [];
    }
    foreach ($records as $e) {
        $columns[] = $e[$colName];
    }
    return $columns ? $columns : [];
}

/** @return mysqli_stmt */
function qPrep($query) {
    $db = db();
    $stmt = $db->prepare($query);
    if ($db->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $db->error,
            'query' => 'mysqli_stmt (prepared)',
            'trace' => getPrettyTrace()
        ], true));
    }
    return $stmt;
}

function qExecutePrepared(mysqli_stmt $stmt) {
    $res = $stmt->execute();
    if ($stmt->errno)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $stmt->error,
            'query' => 'mysqli_stmt (prepared)',
            'trace' => getPrettyTrace()
        ], true));
    }
    return $res;
}

/**
 * @param string $query select statement (with several conditions)
 * @return bool true if the query matches one or more rows.
 */
function qExists($query) {
    $db = db();
    $res = $db->query($query);
    return $res and $res->num_rows > 0;
}

function qEscape($query) {
    $db = db();
    return $db->real_escape_string($query);
}

function qError() {
    $db = db();
    return $db->error;
}
