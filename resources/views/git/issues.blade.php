@extends('layouts.app')

@section('content')
    <div class="container justify-content-center">
        <div class="col-md-12 col-md-offset-3">
            <div class="card card-default mb20">
                <div class="card-header">
                    <center>
                        <a href="{{ route('issues', ['repo'=>$repo]) }}" class="btn btn-success mr50">{{ $totals['total_opened'] }} open</a>
                        
                        <a href="{{ route('issues', ['repo'=>$repo]) }}?status=closed" class="btn btn-warning">{{ $totals['total_closed'] }} closed</a>
                    </center>
                </div>
            </div>
            
            @forelse($issues as $issue)
              <div class="card card-default mb20">
                  <div class="card-body">
                    <span>
                      {{ $issue->title }}

                      @if($issue->labels)
                        <span style="padding-left: 20px;">
                          @foreach($issue->labels as $label)
                            <button class="btn btn-xxs" style="background-color: #{{ $label->color }}; color: #fff;" value="">{{ $label->description }}</button>
                          @endforeach
                        </span>
                      @endif
                    </span>

                    <span class="float-right">
                      <a href="{{ Request::url() . "/" . $issue->id . "/comments" }}">
                        @if($issue->comments > 0)
                          @if($issue->comments == 1)
                            1 comment
                          @else
                            {{ $issue->comments }} comments
                          @endif
                        @endif
                      </a>
                    </span>

                    <br/>
                    <br/>

                    <span>
                      #{{ $issue->id }} opened {{ Carbon\Carbon::parse($issue->created_at)->diffForHumans() }} 
                      by <a href="{{ $issue->user->html_url }}" target="_blank">{{ $issue->user->login }}</a>
                    </span>
                  </div>
                  <!--div class="card-body">
                    <p>{{ $issue->body }}</p>
                  </div-->
              </div>
            @empty
              No items found
            @endforelse
            
            {{-- broken css... will not center as it is 100% of width --}}
            {{ $links }}
        </div>
    </div>


    @include('git.sub.time_tracking')
@endsection