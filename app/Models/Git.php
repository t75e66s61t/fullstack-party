<?php
/**
 * Of course the easiest solution would be to use KnpLabs/php-github-api or similar library, but if I understood
 * the task correctly, it has to be done using GitHub V3 REST API directly. So here we go
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models;

use Auth;
use Socialite;
use App\User;
use Laravel\Socialite\Two\User as SocialiteUser;
use Illuminate\Http\RedirectResponse;

class Git 
{
    /**
     * Storing singleton instance
     * 
     * @var Git
     */
    private static $_instance = null;
    
    /**
     * Storing user data for future use
     * 
     * @var User
     */
    private $_user = null;
    
    private function __construct() 
    {
        if ($this->_user == null) {
            $this->_setUser();
        }
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
    public function loginCallback(): RedirectResponse 
    {
        $user = Socialite::driver('github')->user();
        
        if ($user) {
            $user = $this->_getUser($user);
            
            //Using Laravel authentication in order to stay logged in and avoid using OAuth on every call
            Auth::loginUsingId($user->id);

            //refresh our user data
            $this->_user = Auth::user();
            self::$_instance = $this;
        } else {
            return redirect('/');
        }
        
        return redirect()->route('issues');
    }
    
    
    private function _getUser(SocialiteUser $suser): User {
        //trying to get user with given github user id
        $user = User::where('github_id', $suser->id)->first();
        
        //if it does not exist - let's create it
        if (!$user) {
            $user = User::create([
                'username'=>$suser->nickname,
                'email'=>$suser->email,
                'github_id'=>$suser->id,
                'token'=>$suser->token,
            ])->first();
        }

        return $user;
    }
    
    /**
     * Stores User object inside $this->_user
     */
    private function _setUser(): void 
    {
        $this->_user = Auth::user();
    }
}