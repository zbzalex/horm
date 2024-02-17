<?php

namespace horm;

/**
 * Data source
 * 
 * @author Sasha Broslavskiy <sasha.broslavskiy@gmail.com>
 */
class DataSource
{
    /**
     * @var Connection Connection
     */
    protected $connection;

    /**
     * Constructor
     * 
     * @param Connection $connection Connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Execute native query
     * 
     * @param string $query SQL query
     * @param array $params Query params
     * @param bool $assoc   If true, then array must be associative
     * 
     * @return \PDOStatement
     */
    public function query($query, array $params = [], $assoc = false)
    {
        return $this->connection->executeNativeQuery($query, $params, $assoc);
    }

    /**
     * Creates query builder for table
     * 
     * @param string $table Table name
     * @param string $alias Table alias
     * 
     * @return QueryBuilder
     */
    public function createQueryBuilder($table = null, $alias = null)
    {
        return new QueryBuilder($this->connection, $table, $alias);
    }

    /**
     * Creates repository by entity class
     * 
     * @param string $entityClass Entity class
     * 
     * @return Repository|null
     */
    public function getRepository($entityClass)
    {
        try {
            $reflector = new \ReflectionClass($entityClass);
            if ($reflector->isSubclassOf(Entity::class)) {

                $config = $reflector->getMethod("getConfig")->invoke(null);

                return new Repository(
                    $this->connection,
                    $entityClass,
                    $config['table'],
                    $config['primaryKey']
                );
            }
        } catch (\ReflectionException $e) {
        }

        return null;
    }
}
