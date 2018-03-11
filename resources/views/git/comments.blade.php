@extends('layouts.app')

@section('content')
    <div class="container justify-content-center">
        <div class="col-md-12 col-md-offset-3">
            <a href="{{ route('issues', ['repo'=>$repo]) }}">&laquo; Back to issues</a>
            
            <div class="card card-default mb20">
                <div class="card-header">
                    <h1>{{ $issue->title }} <span style="color: gray;" class="mr50">#{{ $issue->id }}</span></h1>
                    <br/>
                    @if($issue->state == "open")
                        <button class="btn btn-sm btn-success mr50">OPEN</button>
                    @else
                        <button class="btn btn-sm btn-danger mr50">CLOSED</button>
                    @endif
                    
                    <a href="{{ $issue->user->html_url }}" target="_blank" class="mr50">{{ $issue->user->login }}</a>
                    
                    
                    <small style="color: gray;">
                        @if($issue->comments == 1)
                            1 comment
                        @elseif($issue->comments > 1)
                            {{ $issue->comments }} comments
                        @endif
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-11" style="float: right;">
            @foreach($comments as $comment)
                <div class="card card-default mb20">
                    <div class="card-body">
                        <img src="{{ $comment->user->avatar_url }}" class="avatar mr20" />
                        <a href="{{ $comment->user->html_url }}" target="_blank" class="mr50">{{ $comment->user->login }}</a>
                        <hr>
                        
                        <p>{{ $comment->body }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="clear: both;"><!-- --></div>

    @include('git.sub.time_tracking')
@endsection