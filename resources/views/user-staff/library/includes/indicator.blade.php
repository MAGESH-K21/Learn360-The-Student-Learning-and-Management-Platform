{{--'issue_limit_days','',
        'fine_per_day',--}}
@if($data['lib_member'])
<div class="row">
    <div class="col-md-3">
        <div class="card card-blue text-xs-center">
            <div class="card-block">
                @if($data['circulation']->issue_limit_books)
                    <h4 class="card-title">
                        {{ $data['circulation']->issue_limit_books }}
                    </h4>
                    <p class="card-text">Maximum Allowed</p>
                    @else
                    <p class="card-text">Please, Setup Circulation Setting</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-yellow text-xs-center">
            <div class="card-block">
                <h4 class="card-title">{{ $data['books_taken']->count() }}</h4>
                <p class="card-text">Issued</p>
            </div>

        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-green text-xs-center">
            <div class="card-block">
                <h4 class="card-title">{{ $data['circulation']->issue_limit_books - $data['books_taken']->count() }}</h4>
                <p class="card-text">Eligible</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-red text-xs-center">
            <div class="card-block">
                <h4 class="card-title">{{ $data['books_history']->count()  }}</h4>
                <p class="card-text">Transactions</p>
            </div>

        </div>
    </div>
</div>
<hr class="hr-8">
@endif