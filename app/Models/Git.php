<?php
/**
 * Of course the easiest solution would be to use KnpLabs/php-github-api or similar library, but if I understood
 * the task correctly, it has to be done using GitHub V3 REST API directly. So here we go
 * 
 * @todo add some debug info
 * @todo add caching
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models;

use Auth;
use Socialite;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Socialite\Two\User as SocialiteUser;

class Git 
{
    /**
     * Storing singleton instance
     * 
     * @var Git
     */
    private static $_instance = null;
    
    /**
     * Url of GitHub API
     * 
     * @var string
     */
    private $_apiUrl = "https://api.github.com/";
    
    
    private $_repo = "test5645";
    
    /**
     * Storing user data for future use
     * 
     * @var User
     */
    private $_user = null;
    
    private function __construct($user) 
    {
        $this->_user = $user;
        
        $this->_apiUrl = config('github.url');
        $this->_repo = config('github.default_repo');
    }
    
    /**
     * Returns a sigleton instance of Git class
     */
    public static function getInstance($user=null): Git
    {
        if (self::$_instance == null) {
            self::$_instance = new Git($user);
        }
        
        return self::$_instance;
    }
    
    /**
     * Gets list of issues assigned to currently logged in user
     * 
     * @param int $page
     * @param int $limit
     * 
     * @return array
     */
    public function getIssues(int $page, int $limit, string $repo=""): array
    {
        if (!empty($repo)) {
            $this->_repo = $repo;
        }
        
        //getting total amount of issues
        $this->_totalIssues = $this->_getTotalIssues();

        $issues = (array)$this->_api("repos/{$this->_user->username}/{$this->_repo}/issues?page={$page}&per_page={$limit}");

        //in case of error return an empty result
        if (isset($issues['message'])) {
          $issues = [];
        } else {
          $this->_links = new LengthAwarePaginator($issues, $this->_totalIssues, $limit, $page, [
              'path' => request()->url(), 
              'query' => request()->query(),
          ]);
        }
        
        return $issues;
    }
    
    /**
     * Gets total amount of issues available in current repository for given user 
     * 
     * @return int
     */
    private function _getTotalIssues(): int {
        //unfortrunately this is a workaround :( couldn't find an "official" way of getting total records amount
        //in order to minimize request size (that means response time too), set per_page=1 & page=1000 (maximum allowed)
        //this way we will get 0 elements in most cases (maximum 1)
        return (int)($this->_api("search/issues?q=user:{$this->_user->username}+repo:{$this->_repo}&per_page=1&page=1000")->total_count);
    }
    
    /**
     * Returns pagination elements
     * 
     * @return LengthAwarePaginator
     */
    public function links(): LengthAwarePaginator {
      return $this->_links;
    }
    
    /**
     * Executes OAuth login request
     * 
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        return Socialite::driver('github')->scopes(['public_repo'])->redirect();
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
        } else {
            return redirect('/');
        }
        
        return redirect()->route('issues', ['repo' => $this->_repo]);
    }
    
    /**
     * Executes API call
     * 
     * @param string $request
     * 
     * @return mixed
     */
    private function _api(string $request)
    {
        $headers = [
                "Accept: application/vnd.github.v3+json,application/vnd.github.symmetra-preview+json",
                "User-Agent: Awesome-Octocat-App",
                "Authorization: token " . ($this->_user?$this->_user->token:"NULL"),
        ];
          
        //preparing request url
        $url = $this->_apiUrl . $request;
        //var_dump($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        
        return json_decode($content);
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
}