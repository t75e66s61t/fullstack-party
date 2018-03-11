<?php
/**
 * Displaying GIT API output
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Http\Controllers;

use Git;

class GitController extends Controller
{
    public function comments(Git $git, string $repo, string $id)
    {
        return view('git.comments', compact(['repo']));
    }
    
    public function issues(Git $git, string $repo) 
    {
        $page = (int)request()->get('page', 0);
        $limit = 2;
        
        //get issues assigned to given repository
        $issues = $git->getIssues($page, $limit, $repo);
        
        //create paging elements
        $links = $git->links();
        
        //get time tracking logs
        $logs = $git->getLogs();
        
        
        return view('git.issues', compact(['issues', 'links', 'logs']));
    }
}
