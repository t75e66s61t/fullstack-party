<?php
/**
 * Of course the easiest solution would be to use KnpLabs/php-github-api or similar library, but if I understood
 * the task correctly, it has to be done using GitHub V3 REST API directly. So here we go
 * 
 * @todo add x-rate-limit processing to caching system
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models\Git;

use Auth;
use App\User;
use Socialite;
use App\Models\Git\GitCache;
use App\Models\Git\GitTimeTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Socialite\Two\User as SocialiteUser;

class Git 
{    
    /**
     * Url of GitHub API
     * 
     * @var string
     */
    private $_apiUrl = "";
    
    /**
     * @var GitCache
     */
    private $_cache = null;
    
    
    /**
     * Headers array to use with GitHub API
     * 
     * @var array|null
     */
    private $_headers = "";
    
    
    /**
     * Title of current repository
     * 
     * @var string
     */
    private $_repo = "";
    
    
    /**
     * @var GitTimeTracker
     */
    private $_timeTracker = null;
    
    
    /**
     * Storing user data for future use
     * 
     * @var User
     * @var bool $track_time if set to true, all API calls times will be recorded
     */
    private $_user = null;
    
    /**
     * Should be called from GitApiProvider
     * 
     * @param User $user and instance of authenticated User
     * @param bool $track_time if set to true - all API call times will be recorded
     */
    public function __construct($user=null, $trackTime=false) 
    {
        $this->_user = $user;
        
        if ($this->_user) {
            //setup headers
            $this->_headers = [
                "Accept: application/vnd.github.v3+json,application/vnd.github.symmetra-preview+json",
                "User-Agent: Awesome-Octocat-App",
                "Authorization: token {$this->_user->token}",
            ];
                
            //get url of API
            $this->_apiUrl = config('github.url');
            
            
            //setup caching helper classes
            $this->_cache = new GitCache(['issues'], $this->_headers);
            
            //setup time tracking helper class
            if ($trackTime) {
                $this->_timeTracker = new GitTimeTracker();
            }
        }
        
        //load default repo config
        $this->_repo = config('github.default_repo');
    }

    
    /**
     * Gets list of issues assigned to currently logged in user
     * 
     * @param int $page
     * @param int $limit
     * 
     * @return array
     */
    public function getIssues(int $page, int $limit, string $repo): array
    {
        //initializing issues cache group
        $this->_initCaching("issues");
        
        
        //getting total amount of issues
        $this->_totalIssues = $this->_getTotalIssues();

        
        //getting issues
        $cache_key = "issues_{$this->_user->username}_{$this->_repo}_{$page}_{$limit}";
        $issues = $this->_cache->get("issues", $cache_key, []);
        if (empty($issues)) {
            $issues = (array)$this->_api("repos/{$this->_user->username}/{$this->_repo}/issues?page={$page}&per_page={$limit}", true, "issues");
            
            $this->_cache->set($cache_key, $issues);
        }

        
        //in case of error return an empty result
        if (isset($issues['message'])) {
          $issues = [];
        } else {
          //pagination
          $this->_links = new LengthAwarePaginator($issues, $this->_totalIssues, $limit, $page, [
              'path' => request()->url(), 
              'query' => request()->query(),
          ]);
        }
        
        return $issues;
    }
    
    
    /**
     * Returns an array with collected time tracking data
     * 
     * @return array
     */
    public function getLogs(): array
    {
        if ($this->_timeTracker) {
            return $this->_timeTracker->getTimes();
        }
        
        return [];
    }
    
    
    /**
     * Returns pagination elements
     * 
     * @return LengthAwarePaginator
     */
    public function links(): LengthAwarePaginator 
    {
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
        //preparing request url
        $url = $this->_apiUrl . $request;
        
        //tracking execution time
        if ($this->_timeTracker) {    
            $this->_timeTracker->start();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        
        //tracking execution time
        if ($this->_timeTracker) {
            $this->_timeTracker->stop($url);
        }
        
        return json_decode($content);
    }
    
    
    private function _initCaching(string $group): void
    {
        if ($this->_timeTracker) {
            $this->_timeTracker->start();
        }
        
        $this->_cache->init($group, "{$this->_apiUrl}repos/{$this->_user->username}/{$this->_repo}/issues?page=1&per_page=1");
        
        if ($this->_timeTracker) {
            $this->_timeTracker->stop("ETag verification ({$group})");
        }
    }
    
    
    /**
     * Gets total amount of issues available in current repository for given user 
     * 
     * @return int
     */
    private function _getTotalIssues(): int 
    {
        //try to load results from cache
        $res = $this->_cache->get("issues", "total_issues", -1);
        
        //if cached value vas not found
        if ($res == -1) {
            //unfortrunately this is a workaround :( couldn't find an "official" way of getting total records amount
            //in order to minimize request size (that means response time too), set per_page=1 & page=1000 (maximum allowed)
            //this way we will get 0 elements in most cases (maximum 1)
            $res = (int)($this->_api("search/issues?q=user:{$this->_user->username}+repo:{$this->_repo}&per_page=1&page=1000")->total_count);
            
            //cache new value
            $this->_cache->set("total_issues", $res);
        }

        return $res;
    }
    
    
    /**
     * Get existing or creates new User using GitHub API user data
     * 
     * @param SocialiteUser $suser
     * 
     * @return User
     */
    private function _getUser(SocialiteUser $suser): User 
    {
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