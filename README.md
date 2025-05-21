# Как использовать

```php
require_once __DIR__ . '/vendor/autoload.php';

$config =
    (new \Arris\Database\LazyPDOConfig())
    ->setUsername('arris')
    ->setPassword('password')
    ->setDatabase('test');
    
$pdo = $config->connect();
// or
$pdo = new \Arris\Database\LazyPDO($config);

$pdo->query('SELECT * FROM phpauth_users');
$pdo->exec('UPDATE phpauth_users SET dt = NOW() WHERE id = 1');

$sth = $pdo->prepare('SELECT * FROM phpauth_users WHERE id = ?');
$sth->exec([ 1 ]);

// Получаем статистику
echo "Всего запросов: " . $pdo->stats()->getQueryCount() . "\n";
echo "Общее время запросов: " . $pdo->stats()->getTotalQueryTime() . " сек\n";
echo "Среднее время запроса: " . $pdo->stats()->getAverageQueryTime() . " сек\n";

// Детальная информация о запросах
print_r($pdo->stats()->getQueries());

```


Legacy:
```php
// exposes normal 

$sth = $dbh->prepare("/* insert data */ INSERT INTO test (property, value) VALUES (:s, :v)");
$sth->execute([
    's' =>  'option',
    'v' =>  mt_rand(100, 10000)
]);
// var_dump($dbh->getLastState());

var_dump( $dbh->query("/* select query */ SELECT * FROM test ORDER BY RAND() LIMIT 1")->fetchAll() );
// var_dump($dbh->getLastState());


$sth = $dbh->query('SELECT id, title FROM articles ORDER BY id DESC LIMIT 10000');
var_dump($sth->fetchAll());
// var_dump($dbh->getLastState());

// var_dump($dbh->getStats());



```