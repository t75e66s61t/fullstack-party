<?php
/**
 * Tracking time of API calls
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models\Git;

class GitTimeTracker {
    /**
     * An array with time log data
     * 
     * @var array
     */
    private $_timeLogs = [];
    
    
    /**
     * Total time needed for all operations
     * 
     * @var array
     */
    private $_timeTotal = 0.0;
    
    /**
     * An exact time when operation started
     * 
     * @var float
     */
    private $_timeStart = 0.0;
    
    public function __construct()
    {
        
    }
    
    
    /**
     * Returns all recorded information
     */
    public function getTimes(): array
    {
        return [
            'queries' => $this->_timeLogs,
            'totalTime' => $this->_timeTotal,
        ];
    }
    
    
    /**
     * Starts time tracking
     */
    public function start(): void
    {
        $this->_timeStart = microtime(true); 
    }
    
    
    /**
     * Stops time tracking and adds it to records list
     * 
     * @param string $url executed API call
     */
    public function stop(string $url): void
    {
        $timeEnd = microtime(true);
        
        $time = number_format($timeEnd - $this->_timeStart, 2);
        $this->_timeLogs[] = "[<small>" . date("Y-m-d H:i:s") . "</small>] (<strong>{$time} seconds</strong>) 
                              <small>URL: {$url}</small>";
        $this->_timeTotal += (float)$time;
    }
}
