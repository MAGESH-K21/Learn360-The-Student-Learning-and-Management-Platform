<h4 class="header large lighter blue"><i class="fa fa-search" aria-hidden="true"></i>&nbsp;Create {{ $panel }}</h4>
<!-- PAGE CONTENT BEGINS -->
{!! Form::open(['route' => $base_route.'.store', 'method' => 'POST', 'class' => 'form-horizontal',
'id' => 'validation-form', "enctype" => "multipart/form-data"]) !!}
    @include($view_path.'.includes.form')
    <div class="clearfix form-actions">
        <div class="align-right">
            <button class="btn btn-info" type="submit" id="filter-btn">
                <i class="fa fa-filter bigger-110"></i>
                Create
            </button>
        </div>
    </div>
    <div class="hr hr-24"></div>
{!! Form::close() !!}
<div class="hr hr-18 dotted hr-double"></div>