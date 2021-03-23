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


\Kws3\ApiCore\Loader::set('TEST_DB_CONFIG', $config['db']);
?>