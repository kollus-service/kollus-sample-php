<?php

namespace Kollus\Component\Container;

class Category extends AbstractContainer
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $key;

    /**
     * @var int|bool
     */
    private $parent_id;

    /**
     * @var int
     */
    private $count_of_media_contents;

    /**
     * @var int
     */
    private $level;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return bool|int
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @param bool|int $parent_id
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;
    }

    /**
     * @return int
     */
    public function getCountOfMediaContents()
    {
        return $this->count_of_media_contents;
    }

    /**
     * @param int $count_of_media_contents
     */
    public function setCountOfMediaContents($count_of_media_contents)
    {
        $this->count_of_media_contents = $count_of_media_contents;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
