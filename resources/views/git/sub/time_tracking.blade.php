 @if($logs)
    <br/>
    <br/>

    <div class="row justify-content-center">
        <div class="col-md-6 col-md-offset-3">
            <ul>
                <li><strong>Total API calls time: {{ $logs['totalTime'] }} seconds</strong></li>

                @forelse($logs['queries'] as $log)
                    <li>{!! $log !!}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
