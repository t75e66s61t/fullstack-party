@extends('layouts.app')

@section('content')
    <div class="container justify-content-center">
        <div class="col-md-12 col-md-offset-3">
            <a href="{{ route('issues', ['repo'=>$repo]) }}">&laquo; Back to issue</a>
        </div>
    </div>
@endsection