<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Traits;

/**
 * This trait implements the Singleton design pattern, which ensures that a class can only have one instance
 * by prohibiting multiple instantiations, cloning, and deserialization.
 */
trait Singleton
{
    private static ?self $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * The constructor is private to enforce the use of the `getInstance()` method
     * to obtain the single instance of the class.
     */
    private function __construct()
    {
    }

    /**
     * Prevents cloning of the instance.
     *
     * This method is marked as final to prohibit creating clones of the single instance.
     *
     * @return void
     */
    final public function __clone()
    {
    }

    /**
     * Prevents deserialization of the instance.
     *
     * This method is marked as final to prohibit deserialization of the single instance.
     *
     * @return void
     */
    final public function __wakeup()
    {
    }

    /**
     * Returns the single instance of the class.
     *
     * This method creates an instance of the class if it does not already exist,
     * otherwise it returns the existing instance.
     *
     * @return self The single instance of the class.
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
