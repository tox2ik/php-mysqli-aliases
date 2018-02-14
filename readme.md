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

    qArrayAll()              - get array (hydrate: both)
    qAssocAll()              - get array (hydrate: associative)
    qArrayColumnAll()        - get all values of a single column
    qArrayOne()              - get a single record
    qAssocOne()              - get a single record (assoc)
    qOne()                   - get a single record (assoc)
    qEscape()                - escape ' and "
    qExists()                - get a true boolean if a query produces results
    qInsert()                - get the last inserted id after inserting
    qInsertId()              - get the last inserted id without inserting anything
    qLastId()                - get the last inserted id without inserting anything
    qResult()                - query and get a result set
    qPrep()                  - prepare a statement
    qExecutePrepared()       - yep
    qUpdate()                - get the number of affected rows    
    qVar()                   - get a single value
    qError()                 - get last error

## Features


- reduces boilerplate
- supports mysqli and PDO with the same *interface*
- loggs all SQL errors to the `error_log` without `die()`
- returns `array` instead of `false`, empty or not.
- fetch one column as a flat list
- fetch a single value
- fetches in bulk (no while-fetch-one)
- returns the *raw* result object or resource if you need it
