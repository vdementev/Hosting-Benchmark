<?php
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_NOTICE);

const HttpOK = 200;
const HttpBadResponse = 400;
const HttpServerError = 500;

/**
 * Timer Helper
 */
class Timer
{
  private $timeStart;

  public function __construct()
  {
    $this->timeStart = microtime(true);
  }

  public static function make()
  {
    return new self();
  }

  public function start()
  {
    $this->timeStart = microtime(true);
    return $this;
  }

  public function stop()
  {
    return (float)number_format(microtime(true) - $this->timeStart, 6);
  }
}

/**
 * DB Helper
 */
class Db
{
  private $host;
  private $port;
  private $db;
  private $user;
  private $pass;
  private $charset;
  private $error;
  private $pdo;

  public function __construct($host, $port, $db, $user, $pass, $charset = 'utf8mb4')
  {
    $this->host = $host;
    $this->port = $port;
    $this->db = $db;
    $this->user = $user;
    $this->pass = $pass;
    $this->charset = $charset;
  }

  public function connect()
  {
    $dsn = "mysql:host=$this->host:$this->port;dbname=$this->db;charset=$this->charset";
    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
      $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
      return true;
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function getPDO()
  {
    return $this->pdo;
  }

  public function getError()
  {
    return $this->error;
  }
}

/**
 * Benchmark cases
 */
class LogicBenchmarkCases
{

  public static function test_Math($count)
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

  public static function test_String($n)
  {
    $string = 'Why don\'t scientists trust atoms? Because they make up everything!';
    for ($i = 0; $i < $n; $i++) {
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

  public static function test_Loops($n)
  {
    for ($i = 0; $i < $n; ++$i);
    $i = 0;
    while ($i < $n) {
      ++$i;
    }
  }

  public static function test_IfElse($n)
  {
    for ($i = 0; $i < $n; $i++) {
      if ($i == -1) continue;
      if ($i == -2) continue;
      if ($i == -3) continue;
    }
  }

  public static function test_Sort($n)
  {
    $array = range(1, $n);
    shuffle($array);
    sort($array);
  }

  public static function test_Recursion($n)
  {
    function fibonacci($n)
    {
      if ($n <= 1) return $n;
      return fibonacci($n - 1) + fibonacci($n - 2);
    }

    fibonacci($n);
  }

  function test_Regex($count)
  {
    $string = 'What do you call a train carrying bubblegum? A chew-chew train.';
    for ($i = 0; $i < $count; $i++) {
      preg_match('/train/', $string);
      preg_replace('/bubblegum/', 'potato', $string);
    }
  }
}

class RWBenchmarkCases
{
  public static function test_FilePutContents($count)
  {
    $filename = 'benchmark.temp';
    if (file_exists($filename)) {
      unlink($filename);
    }

    try {
      for ($i = 0; $i < $count; $i++) {
        file_put_contents($filename, str_repeat('a', 1000));
        file_get_contents($filename);
      }
    } catch (Exception $e) {
    } finally {
      if (file_exists($filename)) {
        unlink($filename);
      }
    }
  }
}

class DbBenchmarkCases
{
  private static function createTable($pdo)
  {
    $pdo->exec("CREATE TABLE IF NOT EXISTS benchmark_temp (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50),
            val INT
        )");
  }

  private static function dropTable($pdo)
  {
    $pdo->exec("DROP TABLE IF EXISTS benchmark_temp");
  }

  public static function test_DropTable($pdo, $n)
  {
    self::dropTable($pdo);
  }

  public static function test_Insert($pdo, $n)
  {
    self::createTable($pdo);

    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("INSERT INTO benchmark_temp (name, val) VALUES ('temp$i', $i)");
    }
    return array(
      array('name' => 'Insert', 'exec_time' => $t->stop())
    );
  }

  public static function test_Select($pdo, $n)
  {
    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $stmt = $pdo->query("SELECT * FROM benchmark_temp WHERE name = 'temp$i'");
      $stmt->fetch();
    }
    return array(
      array('name' => 'Select', 'exec_time' => $t->stop())
    );
  }

  public static function test_Update($pdo, $n)
  {
    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("UPDATE benchmark_temp SET val = $i + 1 WHERE name = 'temp$i'");
    }
    return array(
      array('name' => 'Update', 'exec_time' => $t->stop())
    );
  }

  public static function test_Delete($pdo, $n)
  {
    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("DELETE FROM benchmark_temp WHERE name = 'test$i'");
    }
    $res = array(
      array('name' => 'Delete', 'exec_time' => $t->stop())
    );

    self::dropTable($pdo);

    return $res;
  }

  public static function test_TransactionHandling($pdo, $n)
  {
    self::createTable($pdo);

    $t = Timer::make()->start();
    try {
      $pdo->beginTransaction();
      for ($i = 0; $i < $n; $i++) {
        $pdo->exec("INSERT INTO benchmark_temp (name, val) VALUES ('trans_test$i', $i)");
      }
      $pdo->commit();
      $res = array(
        array('name' => 'Transaction', 'exec_time' => $t->stop())
      );
    } catch (Exception $e) {
      $pdo->rollBack();
      $res = array(
        array('name' => 'Transaction', 'exec_time' => 'N/A')
      );
    }

    self::dropTable($pdo);

    return $res;
  }

  public static function test_IndexingA($pdo, $n)
  {
    self::createTable($pdo);

    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("INSERT INTO benchmark_temp (name, val) VALUES ('index_test$i', $i)");
    }

    for ($i = 0; $i < $n; $i++) {
      $stmt = $pdo->query("SELECT * FROM benchmark_temp WHERE val = $i");
      $stmt->fetch();
    }
    $res = array(
      array('name' => 'IndexingA', 'exec_time' => $t->stop())
    );

    self::dropTable($pdo);

    return $res;
  }

  public static function test_IndexingB($pdo, $n)
  {
    self::createTable($pdo);

    $t = Timer::make()->start();
    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("INSERT INTO benchmark_temp (name, val) VALUES ('index_test$i', $i)");
    }

    $pdo->exec("CREATE INDEX value_index ON benchmark_temp (val)");
    for ($i = 0; $i < $n; $i++) {
      $stmt = $pdo->query("SELECT * FROM benchmark_temp WHERE val = $i");
      $stmt->fetch();
    }
    $res = array(
      array('name' => 'IndexingA', 'exec_time' => $t->stop())
    );

    self::dropTable($pdo);

    return $res;
  }

  public static function test_ComplexQueries($pdo, $n)
  {
    $res = array();
    $t = Timer::make();

    $pdo->exec("CREATE TABLE IF NOT EXISTS benchmark_tempA (id SERIAL PRIMARY KEY, val INT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS benchmark_tempB (id SERIAL PRIMARY KEY, benchmark_tempA_id INT, val INT)");

    for ($i = 0; $i < $n; $i++) {
      $pdo->exec("INSERT INTO benchmark_tempA (val) VALUES ($i)");
      $pdo->exec("INSERT INTO benchmark_tempB (benchmark_tempA_id, val) VALUES ($i, $i * 1000)");
    }

    $t->start();
    $stmt = $pdo->query("SELECT t1.val, t2.val FROM benchmark_tempA t1 JOIN benchmark_tempB t2 ON t1.id = t2.benchmark_tempA_id");
    $stmt->fetchAll();
    $res[] = array('name' => 'Join', 'exec_time' => $t->stop());

    $t->start();
    $stmt = $pdo->query("SELECT * FROM benchmark_tempA WHERE val IN (SELECT val FROM benchmark_tempB)");
    $stmt->fetchAll();
    $res[] = array('name' => 'Subquery', 'exec_time' => $t->stop());

    $t->start();
    $stmt = $pdo->query("SELECT AVG(val) FROM benchmark_tempB");
    $stmt->fetch();
    $res[] = array('name' => 'Aggregation', 'exec_time' => $t->stop());

    $pdo->exec("DROP TABLE IF EXISTS benchmark_tempA");
    $pdo->exec("DROP TABLE IF EXISTS benchmark_tempB");

    return $res;
  }
}

/**
 * Application
 */
class App
{

  public function run()
  {
    $this->processAPI();
  }

  private function processAPI()
  {
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
      $action = isset($_GET['action']) ? $_GET['action'] : '';
      switch ($action) {
        case 'info':
          $this->ajaxInfo();
          break;
        case 'check_connect':
          $this->ajaxCheckConnect();
          break;
          /* Test Cases */
        case 'case':
          $this->ajaxCase();
          break;
      }
    }
  }

  /* AJAX Controllers */

  private function ajaxInfo()
  {
    $this->apiResponse(
      $this->getServerInfo()
    );
  }

  private function ajaxCheckConnect()
  {
    if ($this->validateConnectionRequest()) {
      $db = $this->dbFromRequest();
      if ($db !== false) {
        if ($db->connect()) {
          $this->apiResponse(array('status' => 'success', 'message' => 'Connection successful'));
        } else {
          $this->apiResponse(array('status' => 'error', 'message' => 'Connection failed: ' . $db->getError()), HttpServerError);
        }
      } else {
        $this->apiResponse(array('status' => 'error', 'message' => 'Unhandled error'), HttpServerError);
      }
    } else {
      $this->apiResponse(array('status' => 'error', 'message' => 'Missing connection parameters'), HttpBadResponse);
    }
  }

  private function ajaxCase()
  {
    $pass = false;
    $time = 'N/A';

    if (isset($_GET['test'])) {
      $caseType = isset($_GET['type']) ? $_GET['type'] : '';
      $caseMethod = 'test_' . $_GET['test'];
      $caseIter = isset($_GET['n']) ? (int)$_GET['n'] : 1000;

      // Available test cases types

      // Just void functions
      if ($caseType === 'logic' || $caseType === 'rw') {
        if ($caseMethod === 'test_Recursion' && $caseIter > 35) {
          $caseIter = 35;
        }

        $className = '';
        switch ($caseType) {
          case 'logic':
            $className = 'LogicBenchmarkCases';
            break;
          case 'rw':
            $className = 'RWBenchmarkCases';
            break;
        }

        if (method_exists($className, $caseMethod)) {
          $pass = true;
          $ticker = Timer::make()->start();
          call_user_func([$className, $caseMethod], $caseIter);
          $time = $ticker->stop();
        }

        $this->apiResponse(array('exec_time' => $time));
        return;
      }

      // Return array of test and tickers array(array('name' => 'insert', 'exec_time'))
      if ($caseType === 'db') {
        if (method_exists('DbBenchmarkCases', $caseMethod)) {
          if ($this->validateConnectionRequest()) {
            $db = $this->dbFromRequest();
            if ($db->connect()) {
              $result = call_user_func(['DbBenchmarkCases', $caseMethod], $db->getPDO(), $caseIter);
              $this->apiResponse($result);
              return;
            }
          }
        }
      }
    }

    $this->apiResponse(array('pass' => $pass, 'exec_time' => $time));
  }

  /* Helper functions */

  private function dbFromRequest()
  {
    if ($this->validateConnectionRequest()) {
      $host = $_GET['host'];
      $port = $_GET['port'];
      $db = $_GET['db'];
      $user = $_GET['user'];
      $pass = $_GET['pass'];

      return new DB($host, $port, $db, $user, $pass);
    }

    return false;
  }

  private function validateConnectionRequest()
  {
    if (isset($_GET['host']) && isset($_GET['port']) && isset($_GET['db']) && isset($_GET['user']) && isset($_GET['pass'])) {
      return true;
    }
    return false;
  }

  private function apiResponse($data, $status = HttpOK)
  {
    http_response_code($status);
    header('Content-Type', 'application/json');
    echo json_encode($data);
    die();
  }

  private function getServerInfo()
  {
    $serverInfo = array();

    $serverInfo['server_name'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'N/A';
    $serverInfo['server_software'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A';
    $serverInfo['server_protocol'] = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'N/A';
    $serverInfo['server_addr'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'N/A';
    $serverInfo['server_port'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 'N/A';

    $serverInfo['php_version'] = phpversion();
    $serverInfo['max_execution_time'] = ini_get('max_execution_time');
    $serverInfo['upload_max_filesize'] = ini_get('upload_max_filesize');

    $serverInfo['os'] = php_uname();

    if (function_exists('sys_getloadavg')) {
      $serverInfo['load_average'] = sys_getloadavg();
    }

    $serverInfo['memory_usage'] = memory_get_usage();
    $serverInfo['memory_peak_usage'] = memory_get_peak_usage();

    return $serverInfo;
  }
}

/**
 * Execute
 */

$url = strtok($_SERVER["REQUEST_URI"], '?');
$version = '1.0.0a';

$app = new App();
$app->run();
?>

<html>

<head>
  <title>PHP Hosting Bench</title>
  <style>
    * {
      box-sizing: border-box;
    }

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
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      color: #000;
      background: #fff;
      margin: 0;
      padding: 0;
    }

    h1 {
      font-size: 150%;
      margin: 0;
      padding: .8em;
      border-bottom: 1px solid #999;
      font-weight: normal;
      color: #777;
      background: #eee;
    }

    .version {
      color: #777;
      font-size: 67%;
    }

    .menu {
      position: absolute;
      margin: 10px 0 0;
      padding: 0 0 30px 0;
      top: 5em;
      left: 1em;
      width: 19em;
    }

    h2 {
      font-size: 120%;
      margin: 0;
      padding-bottom: 1em;
      font-weight: normal;
      color: #555;
    }

    .menu label {
      display: block;
      margin-bottom: .5em;
    }

    .menu .config-opt-db {
      margin-bottom: .5em;
    }

    .menu table {
      width: 100%;
    }

    .menu td input {
      width: 100%;
    }

    .menu th {
      color: #555;
      font-size: 90%;
      font-weight: normal;
      width: 4em;
      text-align: left;
      padding-left: 1.7em;
      padding-right: 1em;
    }

    .test-connection-info {
      margin-left: 2.7em;
      margin-top: .5em;
      font-size: 70%;
    }

    .test-connection-info.success {
      color: green;
    }

    .test-connection-info.error {
      color: red;
    }

    #test-connection {
      margin-top: .5em;
      margin-left: 1.9em;
    }

    .benchflow {
      padding-top: 1.3em;
      padding-right: 1.3em;
      margin-left: 21em;
      ;
    }

    .benchflow p {
      margin-top: 0;
    }

    .benchflow table {
      width: 100%;
      margin-top: 1em;
    }

    .benchflow table th {
      text-align: left;
      font-size: 80%;
      background-color: #eee;
      padding: .5em;
    }

    .benchflow table td {
      text-align: left;
      font-size: 80%;
      background-color: #f6f6f6;
      padding: .5em;
    }
  </style>
</head>

<body>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <h1>PHP Hosting Bench <span class="version"><?php echo $version; ?></span></h1>

  <div class="menu" id="menu">
    <h2>Test Cases:</h2>
    <label>
      <input type="checkbox" id="case_php_info" name="case_php_info" checked>
      Show PHP Info
    </label>
    <label>
      <input type="checkbox" id="case_logic" name="case_logic">
      PHP Test
    </label>
    <label>
      <input type="checkbox" id="case_rw" name="case_network">
      Read/Write Test
    </label>
    <label>
      <input type="checkbox" id="case_db" name="case_db">
      Database Test
    </label>
    <div class="config-opt-db" id="case_db_env" style="display: none;">
      <table>
        <tr>
          <th>Server</th>
          <td><input type="text" name="db_host" value="127.0.0.1"></td>
        </tr>
        <tr>
          <th>Port</th>
          <td><input type="text" name="db_port" value="3306"></td>
        </tr>
        <tr>
          <th>Database</th>
          <td><input type="text" name="db_name"></td>
        </tr>
        <tr>
          <th>Username</th>
          <td><input type="text" name="db_username" value="root"></td>
        </tr>
        <tr>
          <th>Password</th>
          <td><input type="password" name="db_password"></td>
        </tr>
      </table>
      <button id="test-connection">Test Connection</button>
      <div id="test-connection-info" class="test-connection-info" style="display: none"></div>
    </div>
  </div>

  <div class="benchflow" id="benchflow">
    <h2>Benchmark flow:</h2>
    <p>Please select Test Cases and click "Start" button. You can test your database connection by "Test Connection"
      button. Enjoy it.</p>
    <div>
      <button id="start">Start</button>
    </div>
  </div>

  <script>
    $(document).ready(() => {
      const ApiUrl = '<?php echo $url; ?>';

      // Cases

      const casesLogic = [{
          name: 'Math',
          iter: 5,
          step: 500000
        },
        {
          name: 'String',
          iter: 5,
          step: 500000
        },
        {
          name: 'Loop',
          iter: 5,
          step: 500000
        },
        {
          name: 'IfElse',
          iter: 5,
          step: 500000
        },
        {
          name: 'Sort',
          iter: 5,
          step: 500000
        },
        {
          name: 'Recursion',
          iter: 7,
          step: 5
        }
      ];

      const casesDB = [{
          name: 'Insert',
          iter: 2,
          step: 5000
        },
        {
          name: 'Select',
          iter: 2,
          step: 1000
        },
        {
          name: 'Update',
          iter: 2,
          step: 5000
        },
        {
          name: 'Delete',
          iter: 1,
          step: 5000
        },
        {
          name: 'TransactionHandling',
          iter: 2,
          step: 25000
        },
        {
          name: 'IndexingA',
          iter: 2,
          step: 5000
        },
        {
          name: 'IndexingB',
          iter: 2,
          step: 5000
        },
        {
          name: 'ComplexQueries',
          iter: 2,
          step: 5000
        },
        {
          name: 'DropTable',
          iter: 1,
          step: 0
        },
      ];

      const casesRW = [{
        name: 'FilePutContents',
        iter: 3,
        step: 10000
      }, ];

      const callCase = (typeGroup, caseObj) => {
        return new Promise(async (resolve) => {
          for (let i = 0; i < caseObj.iter; i++) {
            let n = (i + 1) * caseObj.step;
            try {
              let data = {
                ajax: 1,
                action: 'case',
                type: typeGroup,
                test: caseObj.name,
                n: n,
              }

              if (typeGroup === 'db') {
                data = {
                  ...data,
                  ...{
                    host: $envHost.val(),
                    port: $envPort.val(),
                    db: $envName.val(),
                    user: $envUsername.val(),
                    pass: $envPassword.val(),
                  }
                }
              }

              await $.ajax({
                url: ApiUrl,
                type: 'get',
                dataType: 'json',
                data: data,
                success: function(resp) {
                  if (typeGroup === 'db' && Array.isArray(resp)) {
                    resp.forEach((v, i) => {
                      $('#scores').append(`
                    <tr>
                      <td ${n === 0 ? 'colspan="2"' : ''}>${caseObj.name}</td>
                      ${n !== 0 ? '<td>'+n+'</td>' : ''}
                      <td>${v.exec_time}</td>
                    </tr>
                `)
                    })
                  } else {
                    $('#scores').append(`
                  <tr>
                    <td ${n === 0 ? 'colspan="2"' : ''}>${caseObj.name}</td>
                    ${n !== 0 ? '<td>'+n+'</td>' : ''}
                    <td>${resp.exec_time}</td>
                  </tr>
                `)
                  }
                },
                error: function(error) {
                  $('#scores').append(`
                <tr>
                  <td ${n === 0 ? 'colspan="2"' : ''}>${caseObj.name}</td>
                  ${n !== 0 ? '<td>'+n+'</td>' : ''}
                  <td>N/A</td>
                </tr>
              `)
                }
              });
            } catch (e) {
              $('#scores').append(`
            <tr>
              <td>${caseObj.desc}</td>
              <td>${n}</td>
              <td>N/A</td>
            </tr>
          `)
            }
          }
          resolve();
        });
      }

      const executeCasesSequentially = async (typeGroup, cases) => {
        for (let i = 0; i < cases.length; i++) {
          try {
            await callCase(typeGroup, cases[i]);
          } catch (e) {}
        }
      }

      // App

      $casePhpInfo = $('#case_php_info');
      $caseLogic = $('#case_logic');
      $caseRW = $('#case_rw');
      $caseDb = $('#case_db');

      $envHost = $('[name=db_host]');
      $envPort = $('[name=db_port]');
      $envName = $('[name=db_name]');
      $envUsername = $('[name=db_username]');
      $envPassword = $('[name=db_password]');

      $btnStart = $('#start');
      $btnTestConnection = $('#test-connection');

      $benchflow = $('#benchflow');

      $caseDb.on('change', () => {
        if ($caseDb.is(':checked')) {
          $('#case_db_env').show();
        } else {
          $('#case_db_env').hide();
        }
      });

      $btnTestConnection.on('click', () => {
        $btnTestConnection.attr('disabled', 'disabled');
        $('#test-connection-info').hide();
        $('#test-connection-info').removeClass(['success', 'error']);

        $.ajax({
          url: ApiUrl,
          type: 'get',
          dataType: 'json',
          data: {
            ajax: 1,
            action: 'check_connect',
            host: $envHost.val(),
            port: $envPort.val(),
            db: $envName.val(),
            user: $envUsername.val(),
            pass: $envPassword.val(),
          }
        }).done((r) => {
          if (r.status === 'success') {
            $('#test-connection-info').addClass('success');
            $('#test-connection-info').show();
            $('#test-connection-info').html('Connection successful');
          }
        }).fail((e) => {
          $('#test-connection-info').addClass('error');
          $('#test-connection-info').show();
          $('#test-connection-info').html('Connection error: ' + e.responseJSON.message);
        }).always(() => {
          $btnTestConnection.removeAttr('disabled');
        })
      });

      $btnStart.on('click', async () => {
        $btnStart.html('Calculating scores...')
        $('button').attr('disabled', 'disable');
        $('input').attr('disabled', 'disable');

        $('#scores').remove();
        $benchflow.append(`
      <table id="scores">
        <tr>
          <th>Case name</th>
          <th>Iter</th>
          <th>Result</th>
        </tr>
      </table>
    `);

        if ($casePhpInfo.is(':checked')) {
          $('#scores').append(`
        <tr>
          <th colspan="3">PHP Base Info</th>
        </tr>
      `)

          await $.ajax({
            url: ApiUrl,
            type: 'get',
            dataType: 'json',
            data: {
              ajax: 1,
              action: 'info'
            },
            success: function(resp) {
              Object.keys(resp).forEach(k => {
                $('#scores').append(`
                <tr>
                  <td colspan="3">${k}: ${resp[k]}</td>
                </tr>
              `)
              })
            },
            error: function() {
              $('#scores').append(`
            <tr>
              <td colspan="3">N/A</td>
            </tr>
          `)
            }
          });
        }

        if ($caseLogic.is(':checked')) {
          $('#scores').append(`
      <tr>
        <th colspan="3">Logical operation cases</th>
      </tr>
      `)
          await executeCasesSequentially('logic', casesLogic);
        }

        if ($caseRW.is(':checked')) {
          $('#scores').append(`
      <tr>
        <th colspan="3">Write operation cases</th>
      </tr>
      `)
          await executeCasesSequentially('rw', casesRW);
        }

        if ($caseDb.is(':checked')) {
          $('#scores').append(`
      <tr>
        <th colspan="3">Database operation cases</th>
      </tr>
    `)
          await executeCasesSequentially('db', casesDB);
        }

        $btnStart.html('Start')
        $('button').removeAttr('disabled')
        $('input').removeAttr('disabled')
      });
    })
  </script>
</body>

</html>