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

// Детальная информация о запросах
print_r($pdo->stats()->getQueries());

```

OR

```php


$pdo->query("TRUNCATE TABLE test");

$sth = $pdo->prepare("/* insert data */ INSERT INTO test (property, value) VALUES (:s, :v)");

for ($i=1; $i<100000; $i++){
   $sth->execute([
       's' =>  'option_' . $i,
       'v' =>  mt_rand(100, 10000)
   ]);
}

var_dump( $pdo->query("/* select query */ SELECT * FROM test ORDER BY RAND() LIMIT 1")->fetch() );

// Получаем статистику
echo "Всего запросов: " . $pdo->stats()->getQueryCount() . "\n";
echo "Всего подготовленных запросов: " . $pdo->stats()->getPreparedQueryCount() . "\n";
echo "Общее время запросов: " . $pdo->stats()->getTotalQueryTime() . " сек\n";

```