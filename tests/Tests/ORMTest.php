<?php

namespace Tests;

use horm\Connection;
use horm\DataSource;

/**
 * Module test class
 */
class ORMTest extends \PHPUnit\Framework\TestCase
{
    public function testORM()
    {
        $connection = new Connection(
            new \PDO("mysql:host=localhost;dbname=test", "root", "123")
        );

        $databSource = new DataSource($connection);

        $result = $databSource
            ->createQueryBuilder("users")
            ->setFindOptions([
                'where' => [
                    [
                        'id' => 1,
                        'username'  => 'admin',
                        // also
                        'username'  => ['ne', 'user1'], // not equal
                    ],
                    // or
                    [
                        'id' => 2,
                    ]
                ],
            ])
            ->getOne();

        if ($result !== null) {
            echo sprintf("Hello, %s!", $result['username']);
        }
    }
}
