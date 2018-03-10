<?php
/**
 * Of course the easiest solution would be to use KnpLabs/php-github-api or similar library, but if I understood
 * the task correctly, it has to be done using GitHub V3 REST API directly. So here we go
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models;

use Socialite;
use Illuminate\Http\RedirectResponse;

class Git 
{
    /**
     * Storing singleton instance
     * 
     * @var Git
     */
    private static $_instance = null;
    
    private function __construct() 
    {
    
    }
    
    /**
     * Returns a sigleton instance of Git class
     */
    public static function getInstance(): Git
    {
        if (self::$_instance == null) {
            self::$_instance = new Git();
        }
        
        return self::$_instance;
    }
    
    /**
     * Executes OAuth login request
     * 
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }
    
    /**
     * Processes Git API login callback
     * 
     * @return RedirectResponse
     */
    public function loginCallback(): RedirectResponse {
        $user = Socialite::driver('github')->user();
        
        //dd($user);
        
        return redirect()->route('issues');
    }
}