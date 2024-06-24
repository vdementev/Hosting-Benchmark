<!DOCTYPE html>
<html>

<head>
    <title>Hosting Benchmark</title>
    <style>
        body {
            font-family: system-ui,
                -apple-system,
                'Segoe UI',
                Roboto,
                'Helvetica Neue',
                'Noto Sans',
                'Liberation Sans',
                Arial,
                sans-serif,
                'Apple Color Emoji',
                'Segoe UI Emoji',
                'Segoe UI Symbol',
                'Noto Color Emoji';
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 10px;
        }

        input[type="submit"]:hover {
            background: #555;
        }

        .results {
            margin-top: 20px;
        }

        .results p {
            background: #ade6ad;
            padding: 5px;
            border-left: 3px solid #00d900;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Server Information</h2>
        <p>PHP Version: <?php echo phpversion(); ?></p>

        <h2>Database</h2>
        <form method="post">
            <label for="dbType">Database Type:</label>
            <select id="dbType" name="dbType">
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
            </select><br>

            <label for="hostname">Hostname:</label>
            <input type="text" id="hostname" name="hostname" value="mysql80"><br>

            <label for="port">Port:</label>
            <input type="text" id="port" name="port" value="3306"><br>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="user"><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="password"><br>

            <label for="databaseName">Database Name:</label>
            <input type="text" id="databaseName" name="databaseName" value="mydb"><br>

            <input type="submit" name="submit" value="Check Connection">
        </form>

        <?php
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        function getMicrotimeDiff($timeStart)
        {
            return number_format(microtime(true) - $timeStart, 6);
        }

        function runTest($testName, &$results, $callback, $count)
        {
            $timeStart = microtime(true);
            $callback($count);
            $results['benchmark'][$testName] = getMicrotimeDiff($timeStart);
        }

        function testMath($count)
        {
            for ($i = 0; $i < $count; $i++) {
                sin($i);
                asin($i);
                cos($i);
                acos($i);
                tan($i);
                atan($i);
                abs($i);
                floor($i);
                exp($i);
                is_finite($i);
                is_nan($i);
                sqrt($i);
                log10($i);
            }
        }

        function testString($count)
        {
            $string = 'Why don\'t scientists trust atoms? Because they make up everything!';
            for ($i = 0; $i < $count; $i++) {
                addslashes($string);
                chunk_split($string);
                metaphone($string);
                strip_tags($string);
                md5($string);
                sha1($string);
                strtoupper($string);
                strtolower($string);
                strrev($string);
                strlen($string);
                soundex($string);
                ord($string);
            }
        }

        function testLoops($count)
        {
            for ($i = 0; $i < $count; ++$i);
            $i = 0;
            while ($i < $count) {
                ++$i;
            }
        }

        function testIfElse($count)
        {
            for ($i = 0; $i < $count; $i++) {
                if ($i == -1) continue;
                if ($i == -2) continue;
                if ($i == -3) continue;
            }
        }

        function testFileIo($count)
        {
            $filename = 'testfile.txt';
            if (file_exists($filename)) {
                unlink($filename);
            }

            try {
                for ($i = 0; $i < $count; $i++) {
                    file_put_contents($filename, str_repeat('a', 1000));
                    $data = file_get_contents($filename);
                }
            } catch (Exception $e) {
                echo "<p>Error: Unable to create or read file. " . htmlspecialchars($e->getMessage()) . "</p>";
            } finally {
                if (file_exists($filename)) {
                    unlink($filename);
                }
            }
        }

        function testSort($count)
        {
            $array = range(1, $count);
            shuffle($array);
            sort($array);
        }

        function fibonacci($n)
        {
            if ($n <= 1) return $n;
            return fibonacci($n - 1) + fibonacci($n - 2);
        }

        function testRecursion($n)
        {
            fibonacci($n);
        }

        function testRegex($count)
        {
            $string = 'What do you call a train carrying bubblegum? A chew-chew train.';
            for ($i = 0; $i < $count; $i++) {
                preg_match('/train/', $string);
                preg_replace('/bubblegum/', 'potato', $string);
            }
        }

        class Foo
        {
            private $bar;
            public function __construct($bar)
            {
                $this->bar = $bar;
            }
            public function getBar()
            {
                return $this->bar;
            }
        }

        function testOop($count)
        {
            for ($i = 0; $i < $count; $i++) {
                $foo = new Foo($i);
                $foo->getBar();
            }
        }

        function connectDatabase($dbType, $hostname, $port, $username, $password, $databaseName)
        {
            try {
                $dsn = ($dbType == "mysql") ? "mysql:host=$hostname;port=$port;dbname=$databaseName" : "pgsql:host=$hostname;port=$port;dbname=$databaseName";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                return new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                return null;
            }
        }

        function runSQLTest($pdo, &$results, $task, $callback)
        {
            $timeStart = microtime(true);
            $callback($pdo);
            $results['benchmark'][$task] = number_format(microtime(true) - $timeStart, 6) . ' sec.';
        }

        function createTable($pdo)
        {
            $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50),
                value INT
            )");
        }

        function dropTable($pdo)
        {
            $pdo->exec("DROP TABLE IF EXISTS test_table");
        }

        function testInsert($pdo)
        {
            createTable($pdo);

            for ($i = 0; $i < 1000; $i++) {
                $pdo->exec("INSERT INTO test_table (name, value) VALUES ('test$i', $i)");
            }
        }

        function testSelect($pdo)
        {

            for ($i = 0; $i < 1000; $i++) {
                $stmt = $pdo->query("SELECT * FROM test_table WHERE name = 'test$i'");
                $stmt->fetch();
            }
        }

        function testUpdate($pdo)
        {

            for ($i = 0; $i < 1000; $i++) {
                $pdo->exec("UPDATE test_table SET value = $i + 1 WHERE name = 'test$i'");
            }
        }

        function testDelete($pdo)
        {


            for ($i = 0; $i < 1000; $i++) {
                $pdo->exec("DELETE FROM test_table WHERE name = 'test$i'");
            }

            dropTable($pdo);
        }

        function testTransactionHandling($pdo)
        {
            createTable($pdo);

            try {
                $pdo->beginTransaction();
                for ($i = 0; $i < 10000; $i++) {
                    $pdo->exec("INSERT INTO test_table (name, value) VALUES ('trans_test$i', $i)");
                }
                $pdo->commit();
                echo "<p>Transaction Commit Test: Passed</p>";
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<p>Transaction Commit Test: Failed</p>";
            }

            try {
                $pdo->beginTransaction();
                for ($i = 0; $i < 10000; $i++) {
                    $pdo->exec("INSERT INTO test_table (name, value) VALUES ('trans_test$i', $i)");
                }
                throw new Exception("Forced rollback");
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<p>Transaction Rollback Test: Passed</p>";
            }

            dropTable($pdo);
        }

        function testIndexing($pdo)
        {
            createTable($pdo);

            for ($i = 0; $i < 1000; $i++) {
                $pdo->exec("INSERT INTO test_table (name, value) VALUES ('index_test$i', $i)");
            }

            // Without index
            $timeStart = microtime(true);
            for ($i = 0; $i < 1000; $i++) {
                $stmt = $pdo->query("SELECT * FROM test_table WHERE value = $i");
                $stmt->fetch();
            }
            $timeWithoutIndex = number_format(microtime(true) - $timeStart, 6);

            // Create index
            $pdo->exec("CREATE INDEX value_index ON test_table (value)");

            // With index
            $timeStart = microtime(true);
            for ($i = 0; $i < 1000; $i++) {
                $stmt = $pdo->query("SELECT * FROM test_table WHERE value = $i");
                $stmt->fetch();
            }
            $timeWithIndex = number_format(microtime(true) - $timeStart, 6);

            echo "<p>Without Index: $timeWithoutIndex sec, With Index: $timeWithIndex sec</p>";

            dropTable($pdo);
        }

        function testComplexQueries($pdo)
        {
            // Create and populate tables for join and subquery tests
            $pdo->exec("CREATE TABLE IF NOT EXISTS table1 (id SERIAL PRIMARY KEY, value INT)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS table2 (id SERIAL PRIMARY KEY, table1_id INT, value INT)");

            for ($i = 0; $i < 1000; $i++) {
                $pdo->exec("INSERT INTO table1 (value) VALUES ($i)");
                $pdo->exec("INSERT INTO table2 (table1_id, value) VALUES ($i, $i * 1000)");
            }

            // Join
            $timeStart = microtime(true);
            $stmt = $pdo->query("SELECT t1.value, t2.value FROM table1 t1 JOIN table2 t2 ON t1.id = t2.table1_id");
            $stmt->fetchAll();
            $joinTime = number_format(microtime(true) - $timeStart, 6);

            // Subquery
            $timeStart = microtime(true);
            $stmt = $pdo->query("SELECT * FROM table1 WHERE value IN (SELECT value FROM table2)");
            $stmt->fetchAll();
            $subqueryTime = number_format(microtime(true) - $timeStart, 6);

            // Aggregation
            $timeStart = microtime(true);
            $stmt = $pdo->query("SELECT AVG(value) FROM table2");
            $stmt->fetch();
            $aggregationTime = number_format(microtime(true) - $timeStart, 6);

            echo "<p>Join Time: $joinTime sec, Subquery Time: $subqueryTime sec, Aggregation Time: $aggregationTime sec</p>";

            $pdo->exec("DROP TABLE IF EXISTS table2");
            $pdo->exec("DROP TABLE IF EXISTS table1");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['runPHPTests'])) {
                $results = [];

                runTest('math', $results, 'testMath', 5000000);
                runTest('string', $results, 'testString', 2000000);
                runTest('loops', $results, 'testLoops', 400000000);
                runTest('ifelse', $results, 'testIfElse', 200000000);
                runTest('fileIO', $results, 'testFileIo', 50000);
                runTest('sort', $results, 'testSort', 10000000);
                runTest('recursion', $results, 'testRecursion', 39);
                runTest('regex', $results, 'testRegex', 20000000);
                runTest('oop', $results, 'testOop', 50000000);

                $totalTime = array_reduce($results['benchmark'], function ($carry, $item) {
                    return $carry + floatval($item);
                }, 0);

                echo "<div class='results'><h3>PHP Test Results</h3>";
                foreach ($results['benchmark'] as $test => $time) {
                    echo "<p>" . ucfirst($test) . " Test: $time sec</p>";
                }
                echo "<p><strong>Total Time: " . number_format($totalTime, 6) . " sec</strong></p></div>";
            }

            if (isset($_POST['submit'])) {
                $dbType = $_POST['dbType'];
                $hostname = $_POST['hostname'];
                $port = $_POST['port'];
                $username = $_POST['username'];
                $password = $_POST['password'];
                $databaseName = $_POST['databaseName'];

                $pdo = connectDatabase($dbType, $hostname, $port, $username, $password, $databaseName);
                if ($pdo) {
                    echo "<p>Connection OK</p>";
                    echo '<form method="post">';
                    echo '<input type="hidden" name="dbType" value="' . htmlspecialchars($dbType) . '">';
                    echo '<input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '">';
                    echo '<input type="hidden" name="port" value="' . htmlspecialchars($port) . '">';
                    echo '<input type="hidden" name="username" value="' . htmlspecialchars($username) . '">';
                    echo '<input type="hidden" name="password" value="' . htmlspecialchars($password) . '">';
                    echo '<input type="hidden" name="databaseName" value="' . htmlspecialchars($databaseName) . '">';
                    echo '<input type="submit" name="runSQLTests" value="Run SQL Tests">';
                    echo '</form>';
                }
            }

            if (isset($_POST['runSQLTests'])) {
                $dbType = $_POST['dbType'];
                $hostname = $_POST['hostname'];
                $port = $_POST['port'];
                $username = $_POST['username'];
                $password = $_POST['password'];
                $databaseName = $_POST['databaseName'];

                $pdo = connectDatabase($dbType, $hostname, $port, $username, $password, $databaseName);
                if ($pdo) {
                    $results = [];

                    runSQLTest($pdo, $results, 'Insert Operations', 'testInsert');
                    runSQLTest($pdo, $results, 'Select Operations', 'testSelect');
                    runSQLTest($pdo, $results, 'Update Operations', 'testUpdate');
                    runSQLTest($pdo, $results, 'Delete Operations', 'testDelete');
                    runSQLTest($pdo, $results, 'Transaction Handling', 'testTransactionHandling');
                    runSQLTest($pdo, $results, 'Indexing', 'testIndexing');
                    runSQLTest($pdo, $results, 'Complex Queries', 'testComplexQueries');

                    echo "<div class='results'><h3>SQL Test Results</h3>";
                    foreach ($results['benchmark'] as $task => $time) {
                        echo "<p>$task: $time</p>";
                    }
                    echo "</div>";
                }
            }
        }
        ?>

        <h2>PHP tests</h2>
        <form method="post">
            <input type="submit" name="runPHPTests" value="Run PHP Tests">
        </form>

        <h2>Database tests</h2>
    </div>
</body>

</html>