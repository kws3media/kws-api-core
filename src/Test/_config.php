<?php
$config = [
    'db' => [
        'adapter'   => 'mysql',
        'host'      => 'localhost',
        'username'  => 'username',
        'password'  => '',
        'dbname'    => 'test_database',
    ]
];


$app->set('TEST_DB_CONFIG', $config['db']);
?>