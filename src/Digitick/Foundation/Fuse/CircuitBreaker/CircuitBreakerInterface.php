<?php


namespace Digitick\Foundation\Fuse\CircuitBreaker;


interface CircuitBreakerInterface
{
    /**
     * Check if a service as been reported available
     * @param string $key Name of the service to test availibility
     * @return bool True if the service is available, false otherwise
     */
    public function isAvailable($key);

    /**
     * Report that a service is available
     *
     * @param string $key Name of the service to report availibility
     */
    public function reportSuccess($key);

    /**
     * Report that a service is unavailable
     *
     * @param string $key Name of the service to report unavailibility
     */
    public function reportFailure($key);
}