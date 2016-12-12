<?php

namespace MarcoTisi\SoftEnable;

trait SoftEnable
{
    /**
     * Boot the soft enabling trait for a model.
     *
     * @return void
     */
    public static function bootSoftEnable()
    {
        static::addGlobalScope(new SoftEnablingScope);
    }

    /**
     * Enable a soft-enabled model instance.
     *
     * @return bool|null
     */
    public function enable()
    {
        // If the enabling event does not return false, we will proceed with this
        // enable operation. Otherwise, we bail out so the developer will stop
        // the enable totally. We will set enabled to true and save.
        if ($this->fireModelEvent('enabling') === false) {
            return false;
        }

        $this->{$this->getEnabledColumn()} = true;

        // Once we have saved the model, we will fire the "enabled" event so this
        // developer will do anything they need to after an enable operation is
        // totally finished. Then we will return the result of the save call.
        $result = $this->save();

        $this->fireModelEvent('enabled');

        return $result;
    }

    /**
     * Determine if the model instance is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->{$this->getEnabledColumn()} === true;
    }

    /**
     * Register a enabling model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function enabling($callback)
    {
        static::registerModelEvent('enabling', $callback);
    }

    /**
     * Register a enabled model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function enabled($callback)
    {
        static::registerModelEvent('enabled', $callback);
    }

    /**
     * Disable a soft-enabled model instance.
     *
     * @return bool|null
     */
    public function disable()
    {
        // If the disabling event does not return false, we will proceed with this
        // enable operation. Otherwise, we bail out so the developer will stop
        // the enable totally. We will set enabled to false and save.
        if ($this->fireModelEvent('disabling') === false) {
            return false;
        }

        $this->{$this->getEnabledColumn()} = false;

        // Once we have saved the model, we will fire the "disabled" event so this
        // developer will do anything they need to after a disable operation is
        // totally finished. Then we will return the result of the save call.
        $result = $this->save();

        $this->fireModelEvent('disabled');

        return $result;
    }

    /**
     * Determine if the model instance is disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return is_null($this->{$this->getEnabledColumn()}) || $this->{$this->getEnabledColumn()} === false;
    }

    /**
     * Register a disabling model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function disabling($callback)
    {
        static::registerModelEvent('disabling', $callback);
    }

    /**
     * Register a disabled model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function disabled($callback)
    {
        static::registerModelEvent('disabled', $callback);
    }

    /**
     * Get the name of the "enabled" column.
     *
     * @return string
     */
    public function getEnabledColumn()
    {
        return defined('static::ENABLED') ? static::ENABLED : 'enabled';
    }

    /**
     * Get the fully qualified "enabled" column.
     *
     * @return string
     */
    public function getQualifiedEnabledColumn()
    {
        return $this->getTable().'.'.$this->getEnabledColumn();
    }
}
