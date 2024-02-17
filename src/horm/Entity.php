<?php

namespace horm;

/**
 * Data source
 * 
 * @author Sasha Broslavskiy <sasha.broslavskiy@gmail.com>
 */
abstract class Entity
{
    /**
     * @var bool $isNew Indicates is a new entity
     */
    protected $new_;

    /**
     * @var array $columns List of columns
     */
    protected $columns = [];

    /**
     * @var array $data Data
     */
    protected $data = [];

    /**
     * @var array $modified Modified data
     */
    protected $modified = [];

    /**
     * Constructor
     * 
     * @param array $data
     * @param bool $isNewRecord
     */
    public function __construct(array $data = [], $isNew = true)
    {
        $this->new_ = $isNew;
        $this->setData($data);
    }

    /**
     * Ex:
     * [
     *  'table'     => 'users',
     * ]
     * 
     * @return array Entity config
     */
    abstract public static function getConfig();

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->new_;
    }

    /**
     * Fill data
     * 
     * @param array $data
     */
    private function setData(array $data)
    {
        foreach ($this->columns as $column) {
            if (!isset($data[$column])) continue;

            $this->data[$column] = $data[$column];
        }
    }

    /**
     * Set data field
     * 
     * @param string $key   Field
     * @param string $value Value
     */
    public function set($key, $value)
    {
        if (in_array($key, $this->columns)) {
            $this->data[$key] = $value;
            $this->modified[$key] = $value;
        }
    }

    /**
     * Get data field
     * 
     * @param string $key Field
     * @param mixed $default
     * 
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key])
            ? $this->data[$key]
            : $default;
    }

    /**
     * Set data field
     * 
     * @param string $key   Field
     * @param string $value Value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Get data field
     * 
     * @param string $key Field
     * 
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Get modified state
     * 
     * @return bool
     */
    public function isModified()
    {
        return count(array_keys($this->modified)) != 0;
    }

    /**
     * Returns modified data
     * 
     * @return array Modified data
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set modified data
     * 
     * @param array $modified
     */
    public function setModified(array $modified = [])
    {
        $this->modified = $modified;
    }
}
