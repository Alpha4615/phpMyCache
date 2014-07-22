<?php

class phpMyCacheOptions
{

    protected $cacheDirectory;
    protected $defaultExpiry;

    protected $cacheFilePrefix;
    protected $cacheFileSuffix;
    //This is true by default. Turn it off if you don't want/need it.
    protected $throwExceptionOnInvalidOption = TRUE;

    /**
     * @var array List of required options. validate(); will fail if any of these options are not set
     */
    private $__requiredOptions = array('cacheDirectory',
                                       'defaultExpiry');

    /**
     * @param bool $throwExceptionsOnInvalidOption
     */
    public function phpCacheOptions($throwExceptionsOnInvalidOption = TRUE)
    {
        $this->throwExceptionOnInvalidOption = (bool)$throwExceptionsOnInvalidOption;
    }

    /**
     * Performs a batch set() operations using an array where the keys are the option property name
     *
     * @param array $config An array where the key is the keys are the properties to be changed
     * @return $this|bool
     * @throws InvalidArgumentException
     */
    public function import(array $config)
    {
        if (is_array($config) === FALSE) {
            if ($this->throwExceptionOnInvalidOption == TRUE) {
                throw new InvalidArgumentException('You must provide an array to import.');
            } else {
                return FALSE;
            }
        }
        foreach ($config as $propName => $propValue) {
            $this->set($propName, $propValue);
        }

        return $this;
    }

    /**
     * Retrieves a specified option value
     *
     * @param string $propertyName The name of the property sought. This is case sensitive
     * @return mixed Returns the current value of the specified property name
     * @throws ReservedOptionException
     * @throws InvalidOptionException
     */
    public function get($propertyName)
    {
        if (property_exists($this, $propertyName)) {
            if (substr($propertyName, 0, 2) != '__') {
                return $this->$propertyName;
            } elseif ($this->throwExceptionOnInvalidOption == TRUE) {
                throw new ReservedOptionException($propertyName);
            } else {
                return NULL;
            }
        } elseif ($this->throwExceptionOnInvalidOption == TRUE) {
            throw new InvalidOptionException($propertyName);
        } else {
            return NULL;
        }

    }

    /**
     * Sets a specified option value
     *
     * @param   string $propertyName The name of the property to be changed
     * @param   mixed  $value        The value to set
     * @return $this
     * @throws ReservedOptionException
     * @throws InvalidOptionException
     */
    public function set($propertyName, $value)
    {
        if (property_exists($this, $propertyName)) {
            if (substr($propertyName, 0, 2) != '__') {
                $this->$propertyName = $value;

                return $this;
            } elseif ($this->throwExceptionOnInvalidOption == TRUE) {
                throw new ReservedOptionException($propertyName);
            } else {
                return $this;
            }
        } elseif ($this->throwExceptionOnInvalidOption == TRUE) {
            throw new InvalidOptionException($propertyName);
        } else {
            return $this;
        }

    }

    /**
     * Validates the provided configuration settings. It checks for required values and also validates the configuration for proper data types and such.
     * @throws InvalidArgumentException
     * @throws MissingOptionException
     */
    public function validate()
    {
        // Make sure we have all of the required functions (basically, are they not null?)
        $missing = array();
        foreach ($this->__requiredOptions as $propName) {
            if ($this->get($propName) === NULL) {
                $missing[] = $propName;
            }
        }
        if (count($missing) > 0) {
            throw new MissingOptionException('Missing values for required option(s): ' . implode(', ', $missing));
        }
        if (is_numeric($this->get('defaultExpiry')) == FALSE) {
            throw new InvalidArgumentException ("defaultExpiry configuration must be an integer");
        }

    }

} 