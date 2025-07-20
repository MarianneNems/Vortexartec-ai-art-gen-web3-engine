<?php
/**
 * Rate Limiter for AI Agent API calls
 *
 * @package VortexAIEngine
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VortexAIEngine_RateLimiter')) {
class VortexAIEngine_RateLimiter {
    private static $instance = null;
    
    /** @var array Rate limit configurations */
    private $limits = [
        'per_user_per_minute' => 10,
        'per_user_per_hour' => 100,
        'per_agent_per_minute' => 20,
        'global_per_minute' => 500,
        'cost_per_user_per_hour' => 5.00 // Dollar limit per user per hour
    ];
    
    /** @var string Cache prefix for rate limiting */
    private $cache_prefix = 'vortex_rate_limit_';

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize rate limits from options
        $this->load_rate_limits();
    }

    /**
     * Load rate limits from WordPress options
     */
    private function load_rate_limits() {
        $saved_limits = get_option( 'vortex_rate_limits', [] );
        $this->limits = array_merge( $this->limits, $saved_limits );
    }

    /**
     * Check if request is allowed for user
     */
    public function isAllowed( $user_id, $agent_id = null, $cost = 0.0 ) {
        $user_id = $user_id ?: 'anonymous';
        $current_time = time();
        
        // Check user rate limits
        if ( ! $this->checkUserRateLimit( $user_id, $current_time ) ) {
            return [
                'allowed' => false,
                'reason' => 'User rate limit exceeded',
                'retry_after' => $this->getRetryAfter( $user_id, 'user' )
            ];
        }
        
        // Check cost limits
        if ( $cost > 0 && ! $this->checkCostLimit( $user_id, $cost, $current_time ) ) {
            return [
                'allowed' => false,
                'reason' => 'User cost limit exceeded',
                'retry_after' => 3600 // 1 hour
            ];
        }
        
        // Check agent-specific limits
        if ( $agent_id && ! $this->checkAgentRateLimit( $agent_id, $current_time ) ) {
            return [
                'allowed' => false,
                'reason' => "Agent {$agent_id} rate limit exceeded",
                'retry_after' => $this->getRetryAfter( $agent_id, 'agent' )
            ];
        }
        
        // Check global limits
        if ( ! $this->checkGlobalRateLimit( $current_time ) ) {
            return [
                'allowed' => false,
                'reason' => 'Global rate limit exceeded',
                'retry_after' => 60 // 1 minute
            ];
        }
        
        return ['allowed' => true];
    }

    /**
     * Record successful request
     */
    public function recordRequest( $user_id, $agent_id = null, $cost = 0.0 ) {
        $user_id = $user_id ?: 'anonymous';
        $current_time = time();
        
        // Record user request
        $this->incrementCounter( "user_{$user_id}_minute", 60, $current_time );
        $this->incrementCounter( "user_{$user_id}_hour", 3600, $current_time );
        
        // Record cost
        if ( $cost > 0 ) {
            $this->incrementCost( "user_{$user_id}_cost_hour", $cost, 3600, $current_time );
        }
        
        // Record agent request
        if ( $agent_id ) {
            $this->incrementCounter( "agent_{$agent_id}_minute", 60, $current_time );
        }
        
        // Record global request
        $this->incrementCounter( "global_minute", 60, $current_time );
    }

    /**
     * Check user rate limit
     */
    private function checkUserRateLimit( $user_id, $current_time ) {
        $minute_count = $this->getCounter( "user_{$user_id}_minute", $current_time );
        $hour_count = $this->getCounter( "user_{$user_id}_hour", $current_time );
        
        return $minute_count < $this->limits['per_user_per_minute'] && 
               $hour_count < $this->limits['per_user_per_hour'];
    }

    /**
     * Check cost limit for user
     */
    private function checkCostLimit( $user_id, $cost, $current_time ) {
        $current_cost = $this->getCost( "user_{$user_id}_cost_hour", $current_time );
        return ( $current_cost + $cost ) <= $this->limits['cost_per_user_per_hour'];
    }

    /**
     * Check agent rate limit
     */
    private function checkAgentRateLimit( $agent_id, $current_time ) {
        $count = $this->getCounter( "agent_{$agent_id}_minute", $current_time );
        return $count < $this->limits['per_agent_per_minute'];
    }

    /**
     * Check global rate limit
     */
    private function checkGlobalRateLimit( $current_time ) {
        $count = $this->getCounter( "global_minute", $current_time );
        return $count < $this->limits['global_per_minute'];
    }

    /**
     * Get counter value
     */
    private function getCounter( $key, $current_time ) {
        $cache_key = $this->cache_prefix . $key;
        $data = wp_cache_get( $cache_key );
        
        if ( $data === false ) {
            // Try transient as fallback
            $data = get_transient( $cache_key );
        }
        
        if ( ! $data || ! is_array( $data ) ) {
            return 0;
        }
        
        // Clean old entries
        $window = $this->getWindowSize( $key );
        $data = array_filter( $data, function( $timestamp ) use ( $current_time, $window ) {
            return ( $current_time - $timestamp ) < $window;
        } );
        
        return count( $data );
    }

    /**
     * Get cost value
     */
    private function getCost( $key, $current_time ) {
        $cache_key = $this->cache_prefix . $key;
        $data = wp_cache_get( $cache_key );
        
        if ( $data === false ) {
            $data = get_transient( $cache_key );
        }
        
        if ( ! $data || ! is_array( $data ) ) {
            return 0.0;
        }
        
        // Clean old entries and sum costs
        $window = 3600; // 1 hour
        $total_cost = 0.0;
        $current_data = [];
        
        foreach ( $data as $timestamp => $cost ) {
            if ( ( $current_time - $timestamp ) < $window ) {
                $current_data[$timestamp] = $cost;
                $total_cost += $cost;
            }
        }
        
        // Update cache with cleaned data
        wp_cache_set( $cache_key, $current_data, '', $window );
        set_transient( $cache_key, $current_data, $window );
        
        return $total_cost;
    }

    /**
     * Increment counter
     */
    private function incrementCounter( $key, $window, $current_time ) {
        $cache_key = $this->cache_prefix . $key;
        $data = wp_cache_get( $cache_key );
        
        if ( $data === false ) {
            $data = get_transient( $cache_key );
        }
        
        if ( ! $data || ! is_array( $data ) ) {
            $data = [];
        }
        
        // Add current request
        $data[] = $current_time;
        
        // Clean old entries
        $data = array_filter( $data, function( $timestamp ) use ( $current_time, $window ) {
            return ( $current_time - $timestamp ) < $window;
        } );
        
        // Store updated data
        wp_cache_set( $cache_key, $data, '', $window );
        set_transient( $cache_key, $data, $window );
    }

    /**
     * Increment cost
     */
    private function incrementCost( $key, $cost, $window, $current_time ) {
        $cache_key = $this->cache_prefix . $key;
        $data = wp_cache_get( $cache_key );
        
        if ( $data === false ) {
            $data = get_transient( $cache_key );
        }
        
        if ( ! $data || ! is_array( $data ) ) {
            $data = [];
        }
        
        // Add current cost
        $data[$current_time] = $cost;
        
        // Clean old entries
        $current_data = [];
        foreach ( $data as $timestamp => $stored_cost ) {
            if ( ( $current_time - $timestamp ) < $window ) {
                $current_data[$timestamp] = $stored_cost;
            }
        }
        
        // Store updated data
        wp_cache_set( $cache_key, $current_data, '', $window );
        set_transient( $cache_key, $current_data, $window );
    }

    /**
     * Get window size for rate limit key
     */
    private function getWindowSize( $key ) {
        if ( strpos( $key, '_minute' ) !== false ) {
            return 60;
        } elseif ( strpos( $key, '_hour' ) !== false ) {
            return 3600;
        }
        return 60; // default
    }

    /**
     * Get retry after seconds
     */
    private function getRetryAfter( $identifier, $type ) {
        // For simplicity, return window size
        if ( $type === 'user' ) {
            return 60; // 1 minute
        } elseif ( $type === 'agent' ) {
            return 60; // 1 minute
        }
        return 60;
    }

    /**
     * Get current usage stats for user
     */
    public function getUserUsage( $user_id ) {
        $user_id = $user_id ?: 'anonymous';
        $current_time = time();
        
        return [
            'requests_per_minute' => $this->getCounter( "user_{$user_id}_minute", $current_time ),
            'requests_per_hour' => $this->getCounter( "user_{$user_id}_hour", $current_time ),
            'cost_per_hour' => $this->getCost( "user_{$user_id}_cost_hour", $current_time ),
            'limits' => [
                'requests_per_minute' => $this->limits['per_user_per_minute'],
                'requests_per_hour' => $this->limits['per_user_per_hour'],
                'cost_per_hour' => $this->limits['cost_per_user_per_hour']
            ]
        ];
    }

    /**
     * Update rate limits
     */
    public function updateLimits( $new_limits ) {
        $this->limits = array_merge( $this->limits, $new_limits );
        update_option( 'vortex_rate_limits', $this->limits );
    }

    /**
     * Clear rate limit data for user (admin function)
     */
    public function clearUserLimits( $user_id ) {
        $keys = [
            "user_{$user_id}_minute",
            "user_{$user_id}_hour",
            "user_{$user_id}_cost_hour"
        ];
        
        foreach ( $keys as $key ) {
            $cache_key = $this->cache_prefix . $key;
            wp_cache_delete( $cache_key );
            delete_transient( $cache_key );
        }
        
        error_log( "[VortexAI] Cleared rate limits for user {$user_id}" );
    }
}
} 