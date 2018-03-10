<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Git;

class GitController extends Controller
{
    /**
     * @var Git
     */
    private $_git = null;
    
    public function __construct() 
    {   
        $this->middleware(function ($request, $next) {
            $this->_git = Git::getInstance(Auth::user());
        
            return $next($request);
        });
    }
    
    public function issues(string $repo) 
    {
        $page = (int)request()->get('page', 0);
        $limit = 2;
        
        //get issues assigned to given repository
        $issues = $this->_git->getIssues($page, $limit, $repo);
        
        //create paging elements
        $links = $this->_git->links();
        
        //dd($issues);
        return view('git.issues', compact(['issues', 'links']));
    }
}
