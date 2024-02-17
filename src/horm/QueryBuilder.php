<?php

namespace horm;

/**
 * Query builder
 * 
 * @author Sasha Broslavskiy <sasha.broslavskiy@gmail.com>
 */
class QueryBuilder
{
    /**
     * @var string[] $select Select fields
     */
    protected $select = [];

    /**
     * @var string[] $joins Joins
     */
    protected $joins = [];

    /**
     * @var string[] $where Where conditions
     */
    protected $where = [];

    /**
     * @var string[] $orderBy Order by expressions
     */
    protected $orderBy = [];

    /**
     * @var string[] $groupBy Group by expressions
     */
    protected $groupBy = [];

    /**
     * @var string[] $having Having conditions
     */
    protected $having = [];

    private $operators = [
        'eq'        => '=',
        'ne'        => '!=',
        'gt'        => '>',
        'ge'        => '>=',
        'lt'        => '<',
        'le'        => '<=',
        'notnull'   => 'not null',
        'isnull'    => 'is null',
    ];

    protected $expressions  = [];

    /**
     * @var int $offset Offset
     */
    protected $offset = 0;

    /**
     * @var int $take Limit
     */
    protected $take = 0;

    /**
     * @var array $bindings Binding params
     */
    protected $bindings = [];

    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var string $table Table name
     */
    protected $table;

    /**
     * @var string $alias Table alias
     */
    protected $alias;

    /**
     * Constructor
     * 
     * @param Connection $connection Connection
     * @param string|null $table Table name
     * @param string|null $alias Table alias
     */
    public function __construct(Connection $connection, $table = null, $alias = null)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->alias = $alias;
    }

    /**
     * Select fields
     * 
     * @param string[] $columns Select fields
     * 
     * @return QueryBuilder
     */
    public function select(array $columns)
    {
        $this->select = $columns;

        return $this;
    }

    /**
     * Select field
     * 
     * @param string $column Select field
     * 
     * @return QueryBuilder
     */
    public function addSelect($column)
    {
        $this->select[] = $column;

        return $this;
    }

    /**
     * Join table
     * 
     * Example:
     * 
     * $result = $dataSource
     *  ->createQueryBuilder("users_friends", "uf")
     *  ->select([
     *      'uf.id as friend_id',
     *      'u.*',
     *  ])
     *  ->join("users", "u", "u.id = uf.friend_id", "left")
     *  ->getOne();
     * 
     * if ($result !== null) {
     *      echo sprintf("Hello, %s!", $result['username']);
     * }
     * 
     * @param string $table Table name
     * @param string $alias Table alias
     * @param string $condition Join condition
     * @param string $type Join type (default: left)
     * 
     * @return QueryBuilder
     */
    public function join($table, $alias, $condition, $type = "left")
    {
        $this->joins[] = $alias !== null
            ? sprintf("%s join `%s` as `%s` on %s", $type, $table, $alias, $condition)
            : sprintf("%s join `%s` on %s", $type, $table, $condition);
    }

    /**
     * Left join
     * 
     * @see QueryBuilder::join()
     * 
     * @param string $table Table name
     * @param string $alias Table alias
     * @param string $condition Join condition
     * 
     * @return QueryBuilder
     */
    public function leftJoin($table, $alias, $condition)
    {
        $this->join($table, $alias, $condition, "left");

        return $this;
    }

    public function addExpression($expression)
    {
        $this->expressions[] = $expression;
        return $this;
    }

    /**
     * Calls operator
     * 
     * Example:
     * 
     * $result = $dataSource
     *  ->createQueryBuilder("users")
     *  ->select([ 'username' ])
     *  ->eq("id", 1)
     *  ->wrap()
     *  ->getOne();
     * 
     * if ($result !== null) {
     *      echo sprintf("Hello, %s!", $result['username']);
     * }
     * 
     * @return QueryBuilder
     */
    public function __call($name, array $args)
    {
        if (array_key_exists($name, $this->operators)) {
            if (count($args) > 0) {

                $field = $args[0];

                $value = count($args) > 1 ? $args[1] : null;

                if (in_array($name, [
                    'notnull',
                    'isnull'
                ])) {
                    $this->expressions[] = sprintf(
                        "`%s` %s",
                        $field,
                        $this->operators[$name],
                    );
                } else {
                    if (is_callable($value)) {
                        $this->expressions[] = sprintf(
                            "`%s` %s %s",
                            $field,
                            $this->operators[$name],
                            call_user_func($value)
                        );
                    } else if ($value !== null) {
                        $placeholder = sprintf(
                            "placeholder%d",
                            count($this->bindings)
                        );

                        $this->expressions[] = sprintf(
                            "`%s` %s :%s",
                            $field,
                            $this->operators[$name],
                            $placeholder
                        );

                        $this->bindings[$placeholder] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Where condition
     * 
     * @param string $condition Condition
     * 
     * @return QueryBuilder
     */
    public function where($condition)
    {
        $this->where[] = $condition;

        return $this;
    }

    public function andWhere($condition)
    {
        $this->where[] = sprintf("and %s", $condition);

        return $this;
    }

    public function orWhere($condition)
    {
        $this->where[] = sprintf("or %s", $condition);

        return $this;
    }

    public function wrap()
    {
        $this->where[] = sprintf("   (%s)", implode(" and ", $this->expressions));
        $this->expressions = [];

        return $this;
    }

    public function wrapOr()
    {
        $this->where[] = sprintf("or (%s)", implode(" and ", $this->expressions));
        $this->expressions = [];

        return $this;
    }

    /**
     * Order by expression
     * 
     * @param string $field Field
     * @param string $order Order (desc or asc)
     * 
     * @return QueryBuilder
     */
    public function orderBy($field, $order = "desc")
    {
        $this->orderBy[] = sprintf("%s %s", $field, $order);

        return $this;
    }

    public function groupBy($expression)
    {
        $this->groupBy[] = $expression;

        return $this;
    }

    /**
     * Having condition
     * 
     * @param string $condition Having condition
     * 
     * @return QueryBuilder
     */
    public function having($condition)
    {
        $this->having[] = $condition;

        return $this;
    }

    public function andHaving($condition)
    {
        $this->having[] = sprintf("and %s", $condition);

        return $this;
    }

    public function orHaving($condition)
    {
        $this->having[] = sprintf("or  %s", $condition);

        return $this;
    }

    /**
     * Set offset
     * 
     * @param int $offset
     * 
     * @return QueryBuilder
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set limit
     * 
     * @param int $take
     * 
     * @return QueryBuilder
     */
    public function take($take)
    {
        $this->take = $take;

        return $this;
    }

    /**
     * Bind params
     * 
     * @param array $bindings Binding params
     * 
     * @return QueryBuilder
     */
    public function bind(array $bindings = [])
    {
        $this->bindings = $bindings;

        return $this;
    }

    /**
     * Sets table name and alias (optional)
     * 
     * @param string $table Table name
     * @param string $alias Table alias
     * 
     * @return QueryBuilder
     */
    public function table($table, $alias = null)
    {
        $this->table = $table;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Sets find options
     * 
     * Example:
     * 
     * $result = $dataSource
     *  ->createQueryBuilder("users")
     *  ->setFindOptions([
     *      'select' => [
     *          'username',
     *      ],
     *      'where'  => [
     *          [
     *              'id' => 1,
     *          ],
     *          // or
     *          [
     *              'username' => 'admin',
     *          ],
     *          // or
     *          [
     *              'access_level' => ['ge', 9],
     *          ]
     *      ],
     *      'orderBy'   => [
     *          'id'    => 'desc',
     *      ],
     *      'offset' => 0,
     *  ])
     *  ->getOne();
     * 
     * if ($result !== null) {
     *      echo sprintf("Hello, %s!", $result['username']);
     * }
     * 
     * @param array $options Find options
     * 
     * @return QueryBuilder
     */
    public function setFindOptions(array $options = [])
    {
        $this->select   = isset($options['select'])
            ? $options['select']
            : [];

        if (isset($options['where']) && is_array($options['where'])) {

            $i = 0;

            foreach ($options['where'] as $condition) {
                if (!is_array($condition)) continue;

                foreach ($condition as $field => $expression) {

                    if (is_array($expression)) {
                        $op     = $expression[0];
                        $value  = isset($expression[1])
                            ? $expression[1]
                            : null;

                        call_user_func([$this, $op], $field, $value);
                    } else {

                        // call default operator
                        call_user_func([$this, 'eq'], $field, $expression);
                    }
                }

                if ($i == 0) {
                    $this->wrap();
                } else {
                    $this->wrapOr();
                }

                $i++;
            }
        }

        if (
            isset($options['orderBy'])
            && is_array($options['orderBy'])
        ) {
            foreach ($options['orderBy'] as $field => $order) {
                $this->orderBy[] = sprintf("%s %s", $field, $order);
            }
        }

        $this->groupBy  = isset($options['groupBy'])
            ? $options['groupBy']
            : [];

        if (isset($options['having'])) {
            if (is_array($options['having'])) {
                if (count($options['having']) > 0) {
                    // having condition
                    $this->having[] = $options['having'][0];

                    if (
                        count($options['having']) > 1
                        && is_array($options['having'][1])
                    ) {
                        foreach ($options['having'][1] as $k => $v) {
                            $this->bindings[$k] = $v;
                        }
                    }
                }
            } else {
                $this->having[] = $options['having'];
            }
        }

        $this->take     = isset($options['take'])
            ? $options['take']
            : 0;
        $this->offset   = isset($options['offset'])
            ? $options['offset']
            : 0;

        return $this;
    }

    /**
     * Prepare select query
     * 
     * @return array Prepared query
     */
    protected function prepareQuery()
    {
        $chunks = [];
        $chunks[] = "select";

        // select columns
        $chunks[] = count($this->select) > 0
            ? implode(", ", $this->select)
            : "*";

        $chunks[] = "from";
        $chunks[] = $this->alias !== null
            ? sprintf("`%s` as %s", $this->table, $this->alias)
            : sprintf("`%s`", $this->table);

        // joins
        $chunks[] = implode(" ", $this->joins);

        // where conditions
        if (count($this->where) != 0) {
            $chunks[] = "where";
            $chunks[] = implode(" ", $this->where);
        }

        if (count($this->orderBy) > 0) {
            $chunks[] = "order by";
            $chunks[] = implode(",", $this->orderBy);
        }

        if (count($this->groupBy) > 0) {
            $chunks[] = "group by";
            $chunks[] = implode(",", $this->groupBy);

            // having conditions
            if (count($this->having) > 0) {
                $chunks[] = "having";
                $chunks[] = implode(" ", $this->having);
            }
        }

        if ($this->take > 0) {
            $chunks[] = "limit";
            $chunks[] = sprintf("%d, %d", $this->offset, $this->take);
        } else if ($this->offset > 0) {
            $chunks[] = "offset";
            $chunks[] = $this->offset;
        }

        $chunks[] = ";";

        return implode("\n", $chunks);
    }

    /**
     * Returns debug info
     * 
     * @return array
     */
    public function debug()
    {
        return [
            $this->prepareQuery(),
            $this->bindings,
        ];
    }

    /**
     * Returns list of results
     * 
     * Example:
     * 
     * $results = $dataSource
     *  ->createQueryBuilder("users")
     *  ->ge("access_level", 9)
     *  ->getMany();
     * 
     * if (count($results) > 0) {
     *      // 
     * }
     * 
     * @return array
     */
    public function getMany()
    {
        $st = $this->connection->executeNativeQuery(
            $this->prepareQuery(),
            $this->bindings,
            true
        );

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Returns single result
     * 
     * Example:
     * 
     * $result = $dataSource
     *  ->createQueryBuilder("users")
     *  ->select([
     *      'username'
     *  ])
     *  ->eq("id", 1)
     *  ->wrap()
     *  ->getOne();
     *
     * if ($result !== null) {
     *      echo sprintf("Hello, %s!", $result['username']);
     * }
     * 
     * @return array|null
     */
    public function getOne()
    {
        $st = $this->connection->executeNativeQuery(
            $this->prepareQuery(),
            $this->bindings,
            true
        );

        return $st->rowCount() >= 1 ? $st->fetch(\PDO::FETCH_ASSOC) : null;
    }

    /**
     * Returns results count
     * 
     * Example:
     * 
     * $count = $dataSource
     *  ->createQueryBuilder("users")
     *  ->count();
     * 
     * echo sprintf("users count: %d", $count);
     * 
     * @return int
     */
    public function count()
    {
        $st = $this->connection->executeNativeQuery(
            $this->prepareQuery(),
            $this->bindings
        );

        return $st->rowCount();
    }

    /**
     * Update data
     * 
     * Example:
     * 
     * $dataSource
     *  ->createQueryBuilder()
     *  ->eq("id", 1)
     *  ->wrap()
     *  ->update([
     *      'access_level' => 9,
     *  ]);
     * 
     * @param array $data Update data
     */
    public function update(array $data)
    {
        $chunks = [];
        $chunks[] = "update";
        $chunks[] = sprintf("`%s`", $this->table);

        $chunks[] = "set";

        $str = [];

        foreach (array_keys($data) as $k) {
            if (is_callable($data[$k])) {
                $str[] = sprintf("`%s` = %s", $k, $data[$k]());
            } else {
                $str[] = sprintf("`%s` = %s", $k, sprintf(":%s", $k));
                $this->bindings[$k] = $data[$k];
            }
        }

        $chunks[] = implode(", ", $str);

        // where conditions
        if (count($this->where) > 0) {
            $chunks[] = "where";
            $chunks[] = implode(" ", $this->where);
        }

        $chunks[] = ";";
        $query = implode("\n", $chunks);

        $this->connection->executeNativeQuery($query, $this->bindings, true);
    }

    /**
     * Insert data and returns insert id
     * 
     * Example:
     * 
     * $id = $dataSource
     *  ->createQueryBuilder("users")
     *  ->insert([
     *      'username'  => 'admin',
     *  ]);
     * 
     * echo sprintf("new user id: %d", $id);
     * 
     * @param array $data Insert data
     * 
     * @return int id
     */
    public function insert(array $data)
    {
        $chunks = [];
        $values = [];

        $chunks[] = "insert into";
        $chunks[] = sprintf("`%s`", $this->table);
        $chunks[] = "(";

        $chunks[] = implode(", ", array_map(function ($k) {
            return '`' . $k . '`';
        }, array_keys($data)));

        $chunks[] = ")";
        $chunks[] = "values";
        $chunks[] = "(";

        $str = [];
        foreach (array_keys($data) as $k) {
            if (is_callable($data[$k])) {
                $str[] = call_user_func($data[$k]);
            } else {
                $str[] = "?";
                $values[] = $data[$k];
            }
        }

        $chunks[] = implode(", ", $str);

        $chunks[] = ")";
        $chunks[] = ";";

        $query = implode(" ", $chunks);

        $this->connection->executeNativeQuery($query, $values);

        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * Delete entity
     * 
     * Example:
     * 
     * $dataSource
     *  ->createQueryBuilder("users")
     *  ->eq("id", 1)
     *  ->wrap()
     *  ->delete();
     * 
     * @return QueryBuilder
     */
    public function delete()
    {
        $chunks = [];

        $chunks[] = "delete from";
        $chunks[] = sprintf("`%s`", $this->table);

        // where conditions
        if (count($this->where) > 0) {
            $chunks[] = "where";
            $chunks[] = implode(" ", $this->where);
        }

        $chunks[] = ";";

        $query = implode(" ", $chunks);

        $this->connection->executeNativeQuery($query, $this->bindings, true);

        return $this;
    }
}
