<?php

namespace App\Http\Controllers;

class GitController extends Controller
{
    public function issues() 
    {
        $issues = "Issues";
        
        return view('git.issues', compact('issues'));
    }
}
