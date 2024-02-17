<?php

namespace horm;

/**
 * Connection class
 * 
 * @author Sasha Broslavskiy <sasha.broslavskiy@gmail.com>
 */
class Connection
{
    /**
     * @var \PDO $pdo PDO
     */
    private $pdo;

    /**
     * Constructor
     * 
     * @param \PDO $pdo PDO
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns PDO object
     * 
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Execute native query
     * 
     * @param string $query SQL Query
     * @param array $params Query params
     * @param bool $assoc   Associative array flag
     * 
     * @return \PDOStatement
     */
    public function executeNativeQuery($query, array $params = [], $assoc = false)
    {
        $st = $this->pdo->prepare($query);
        if ($assoc) {
            foreach ($params as $param => &$value) {
                $st->bindParam(sprintf(":%s", $param), $value, is_numeric($value) || is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }

            $st->execute();
        } else {
            $st->execute($params);
        }

        return $st;
    }
}
