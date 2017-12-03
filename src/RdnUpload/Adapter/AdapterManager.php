<?php

namespace RdnUpload\Adapter;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;

class AdapterManager extends AbstractPluginManager
{

    protected $instanceOf = \RdnUpload\Adapter\AdapterInterface::class;

    /**
     * Validate the plugin is of the expected type (v3).
     *
     * Validates against `$instanceOf`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validate($instance)
    {
        if (!$instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                    '%s can only create instances of %s; %s is invalid', get_class($this), $this->instanceOf, (is_object($instance) ? get_class($instance) : gettype($instance))
            ));
        }
    }

    /**
     * Validate the plugin
     *
     * Checks that the plugin loaded is an instance of AdapterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($instance);
        } catch (InvalidServiceException $e) {
            throw new ComponentSpecificException($e->getMessage(), $e->getCode(), $e);
        }
    }

}
