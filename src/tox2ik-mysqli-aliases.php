<?php
/* Shorthands for various mysql->query(sqlstring); mysql->fetch() */



if (! function_exists('db') ) {



    /** @return mysqli|PDO The (global) connection handler */
    function db() {
        if (function_exists('db_override')) {
            return db_override();
        }
        global $db;
        return $db;
    }
}


/**
 * @param string|PDOStatement $selectQuery sql-query-string
 * @param int $resultType
 * @return array
 */
function qArrayOne($selectQuery, $resultType = MYSQLI_BOTH) {
    $db = db();
    $record = [];
    if (is_a($selectQuery, 'PDOStatement')) {
        $res = $selectQuery->fetch(_toPdoResultType($resultType));
        $record = false === $res ? [] : $res;
    } else {
        $res = db()->query($selectQuery);
        $record = false === $res ? [] : mysqli_fetch_array($res, $resultType);
    }
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }
    return $record;
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
    /** @var mysqli_result|PDOStatement */
    $count = 0;
    $res = $db->query($selectQuery);
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }

    if ($isPdo = is_a($res, 'PDOStatement')) {
        $count = $res->rowCount();
    } elseif ($res) {
        $count = $res->num_rows;
    }

    if ($res && $count) {
         $first = $isPdo
             ? $res->fetch(_toPdoResultType($resultType))
             : $res->fetch_array($resultType);

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

    /** @var PDOStatement|mysqli_result $result */

    $result = $db->query($selectQuery);
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => $selectQuery,
            'trace' => getPrettyTrace()
        ], true));
    }

    $rows = is_a($result, PDOStatement::class)
        ? $result->fetchAll(_toPdoResultType($resultType))
        : mysqli_fetch_all($result, $resultType);
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
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => $insertQuery,
            'trace' => getPrettyTrace()
        ], true));
        return 0;
    }
    return is_a($db, 'PDO') ? $db->lastInsertId() : $db->insert_id;
}

/** @return int count of affected rows. */
function qUpdate($query) {
    $db = db();
    $res = $db->query($query);
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => $query,
            'trace' => getPrettyTrace()
        ], true));
    }

    return is_a($db, 'PDO') ? $res->rowCount() : $db->affected_rows;;
}

/** @return false|mysqli_result */
function qResult($query) {
    $db = db();
    $res = $db->query($query);
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
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

/** @return mysqli_stmt|PDOStatement */
function qPrep($query) {
    $db = db();
    $stmt = $db->prepare($query);
    if ($error = _qLastError())  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => 'prepared mysqli_stmt/PDOStatement',
            'trace' => getPrettyTrace()
        ], true));
    }
    return $stmt;
}


/**
 * @param $stmt mysqli_stmt|PDOStatement
 * @param array $bindParams values for placeholders in query
 * @return mixed
 */
function qExecutePrepared($stmt, $bindParams=[]) {
    $isPdo = is_a($stmt, 'PDOStatement');
    $res = null;

    if (!$stmt) {
        error_log(sprintf('%s: refusing to executen a non-statement', __FILE__));
        return null;
    }
    if ($isPdo && $stmt) {
        $stmt->execute($bindParams);
    } elseif ($stmt) {
        $res = $stmt->execute();
    }


    $error = _qLastError();
    if ($error)  {
        error_log(print_r([
            'error' => __METHOD__ . ': ' . $error,
            'query' => 'mysqli_stmt (prepared)',
            'trace' => getPrettyTrace()
        ], true));
    }
    return $isPdo ? $stmt : $res;
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

if (! function_exists('getPrettyTrace')) {
    function getPrettyTrace() {
        $trace ='';
        $btrace = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT |
            DEBUG_BACKTRACE_IGNORE_ARGS );

        foreach ($btrace as $element) {
            if ( !isset($element['file'] ))
                $element['file'] = '*nofile*';

            if ( !isset($element['line'] ))
                $element['line'] = -1;

            $trace .= sprintf("%20s/%20s %4d %20s()\n",
                dirname( basename( $element['file'])),
                basename( $element['file']) ,
                $element['line'], $element['function']);
        }
        return $trace;
    }
}


/**
 * @param $mysqliConstant int MYSQLI_{BOTH,NUM,ASSOC}
 * @return mixed
 */
function _toPdoResultType($mysqliConstant) {
    $num = defined('MYSQLI_NUM') ? MYSQLI_NUM : 1;
    $assoc = defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 1;
    $both = defined('MYSQLI_BOTH') ? MYSQLI_BOTH : 1;
    $mi2pdo = [
        /* MYSQLI_NUM   */ 1 => PDO::FETCH_NUM,
        /* MYSQLI_ASSOC */ 2 => PDO::FETCH_ASSOC,
        /* MYSQLI_BOTH  */ 3 => PDO::FETCH_BOTH
    ];
    return $mi2pdo[$mysqliConstant];
}

/**
 * Formatting of the error is subject to change.
 * @return string error message from the db-driver.
 */
function _qLastError() {

    $db = db();
    if (is_a($db, 'PDO')) {
        if ($db->errorCode() === '00000') {
            return null;
        }
        $err = db()->errorInfo();
        $error =  array_filter($err);
        $error = empty($error) ? null : join(' : ', $error);
    } else {
         $error = db()->errno ? db()->error : null;
    }
    return $error;
}
