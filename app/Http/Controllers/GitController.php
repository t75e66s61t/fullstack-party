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
    public function comments(Git $git, string $repo, int $number)
    {
        //get issue data
        $issue = $git->getIssue($repo, $number);
        
        //get comments data
        $comments = $git->getComments($repo, $number);
        
        //get time tracking logs
        $logs = $git->getLogs();
        
        return view('git.comments', compact(['repo', 'issue', 'comments', 'logs']));
    }
    
    public function issues(Git $git, string $repo) 
    {
        $status = request()->get('status', "open");
        $page = (int)request()->get('page', 0);
        $limit = 2;
        
        //get issues assigned to given repository
        $issues = $git->getIssues($status, $page, $limit, $repo);
        
        //create paging elements
        $links = $git->links();
        
        //get time tracking logs
        $logs = $git->getLogs();
        
        //get totals
        $totals = $git->getTotals();
        
        
        return view('git.issues', compact(['issues', 'links', 'logs', 'totals', 'repo']));
    }
}
