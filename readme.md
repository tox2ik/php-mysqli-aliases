# php-mysqli-aliases

Not sure what the original authors were thinking in 2004, but I definetly don't want to write
something like this just to fill a hash with records.

    if ($db->query("INSERT INTO studends ... ;")) {
        if ($res = mysqli_insert_id($db)) {
            if ($qRes = $db->query("SELECT * FROM exam_results WHERE studint_id ...")) {
                while ($rec = mysqli_fetch_assoc($qRes)) {
                    $rows[] = $rec
                }
            }
        }
    }

This is acceptable:

    if (qInsert('insert into students ...;')) {
        $rows = qAssocAll("SELECT * FROM exam_results WHERE studint_id ...");
    }


## Helpers

### Arbitrary queries

    qInsert()                - get the last inserted id after inserting
    qResult()                - query and get a result set
    qUpdate()                - get the number of affected rows

    qPrep()                  - prepare a statement
    qExecutePrepared()       - yep

### Select queries

    qArrayAll()              - get array (hydrate: both)
    qAssocAll()              - get array (hydrate: associative)
    qArrayColumnAll()        - get a column from several rows
    qPrepFetch()             - prepare, execute and fetch

    qVar()                   - get a single value
    qArrayOne()              - get a single record
    qAssocOne()              - get a single record (assoc)
    qOne()                   - get a single record (assoc)

    qExists()                - get a true boolean if a query produces results

### Meta queries

    qLastId()                - get the last inserted id
    qInsertId()              - get the last inserted id
    qError()                 - get last error
    qEscape()                - escape ' and "
    qPlaceHolders()          - print placeholders for prepared statement ?, ?, ?


## Features

- reduces boilerplate
- supports mysqli and PDO with the same *interface*
- loggs all SQL errors to the `error_log` without `die()`
- returns `array` instead of `false`, empty or not.
- fetch one column as a flat list
- fetch a single value
- fetches in bulk (no while-fetch-one)
- returns the *raw* result object or resource if you need it
