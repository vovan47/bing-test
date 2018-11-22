<?php
namespace BingTest;

class Util
{
    /**
     * @param bool $realUsage False reports used memory, True reports allocated memory
     * @return string
     */
    public static function getMemoryUsage($realUsage = true)
    {
        $size = memory_get_usage($realUsage);
        return static::formatMemoryUsageOutput($size);
    }

    /**
     * @param bool $realUsage False reports used memory, True reports allocated memory
     * @return string
     */
    public static function getPeakMemoryUsage($realUsage = true)
    {
        $size = memory_get_peak_usage($realUsage);
        return static::formatMemoryUsageOutput($size);
    }

    /**
     * @param int $size
     * @return string
     */
    protected static function formatMemoryUsageOutput($size)
    {
        $unit = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = (int)floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * @param string $string
     * @return string
     */
    public static function removeNonDigits($string)
    {
        return preg_replace('/[^0-9]+/', '', $string);
    }
}