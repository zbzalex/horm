<?php

namespace horm;

/**
 * Repository class
 * 
 * @author Sasha Broslavskiy <sasha.broslavskiy@gmail.com>
 */
class Repository
{
    /**
     * @var Connection $connection Connection
     */
    protected $connection;

    /**
     * @var string $entityClass Entity class
     */
    protected $entityClass;

    /**
     * @var string $table Table name
     */
    protected $table;

    /**
     * @var string $primaryKey Table sequence name
     */
    protected $primaryKey = "id";

    /**
     * Constructor
     * 
     * @param Connection $connection Data source
     * @param string $entityClass Entity class
     * @param string $table Table name
     * @param string $primaryKey Table sequence name
     */
    public function __construct(Connection $connection, $entityClass, $table, $primaryKey)
    {
        $this->connection = $connection;
        $this->entityClass = $entityClass;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Creates a new query builder
     * 
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        return new QueryBuilder($this->connection, $this->table, $alias);
    }

    /**
     * Returns results entities
     * 
     * @param array $findOptions
     * 
     * @return Entity[]
     */
    public function find(array $findOptions = [])
    {
        $results = [];

        $rows = $this->createQueryBuilder()
            ->setFindOptions($findOptions)
            ->getMany();

        foreach ($rows as $row) {
            try {
                $reflector = new \ReflectionClass($this->entityClass);

                $results[] = $reflector->newInstance($row, false);
            } catch (\ReflectionException $e) {
            }
        }

        return $results;
    }

    /**
     * Find entity by where condition
     * 
     * @param string $condition Where condition
     * @param string $bindings Binding params
     * 
     * @return Entity[]
     */
    public function findBy($condition, array $bindings = [])
    {
        return $this->find([
            'where' => [
                $condition,
                $bindings
            ],
        ]);
    }

    /**
     * Returns single result entity
     * 
     * @param array $findOptions find options
     * 
     * @return Entity found entity
     */
    public function findOne(array $findOptions = [])
    {
        $row = $this->createQueryBuilder()
            ->setFindOptions($findOptions)
            ->getOne();

        if ($row === null)
            return null;

        try {
            $reflector = new \ReflectionClass($this->entityClass);

            return $reflector->newInstance($row, false);
        } catch (\ReflectionException $e) {
        }

        return null;
    }

    /**
     * Found entity by condition
     * 
     * @param array $params find options
     * 
     * @return Entity found entity
     */
    public function findOneBy($condition, array $bindings = [])
    {
        return $this->findOne([
            'where' => [
                $condition,
                $bindings
            ],
        ]);
    }

    /**
     * Save entity
     * 
     * @param Entity $entity
     */
    public function save(Entity $entity)
    {
        $queryBuilder = $this->createQueryBuilder();

        if ($entity->isModified()) {
            if ($entity->isNew()) {
                $entity->set(
                    $this->primaryKey,
                    $queryBuilder->insert($entity->getModified())
                );
            } else {
                $queryBuilder
                    ->eq($this->primaryKey, $entity->get($this->primaryKey) !== null
                        ? $entity->get($this->primaryKey)
                        : 0)
                    ->update($entity->getModified());
            }

            $entity->setModified();
        }
    }

    /**
     * Delete entity
     * 
     * @param Entity $entity
     */
    public function delete(Entity $entity)
    {
        $queryBuilder = $this->createQueryBuilder();

        if ($entity !== null && !$entity->isNew()) {
            $queryBuilder
                ->where(sprintf("%s = :seq", $this->primaryKey))
                ->eq($this->primaryKey, $entity->get($this->primaryKey) !== null
                    ? $entity->get($this->primaryKey)
                    : 0)
                ->delete();
        }
    }
}
