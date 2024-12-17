<?php

namespace Nullform\Telegraphus;

abstract class AbstractType implements \JsonSerializable
{
    /**
     * @param array|object|string|null $data Associative array, object or JSON string.
     */
    public function __construct($data = null)
    {
        if (!empty($data)) {
            $this->map($data);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $reflection = new \ReflectionObject($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $array = [];

        foreach ($properties as $property) {
            $array[$property->getName()] = $property->getValue($this);
        }

        return $array;
    }

    /**
     * Assigns values from $data to the Type's properties.
     *
     * @param array|object|string $data Associative array, object or JSON string.
     * @return $this
     */
    public function map($data)
    {
        if (\is_string($data)) {
            $data = \json_decode($data, true);
        }

        $data = (array)$data;
        $reflection = new \ReflectionObject($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if (\array_key_exists($property->getName(), $data)) {
                $property->setValue($this, $data[$property->getName()]);
            }
        }

        return $this;
    }
}
