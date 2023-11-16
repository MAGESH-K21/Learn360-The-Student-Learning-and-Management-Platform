{{--<h4 class="header large lighter blue"><i class="fa fa-list" aria-hidden="true"></i>&nbsp;{{ $panel }} List</h4>--}}
<div class="clearfix">
    <span class="pull-right tableTools-container"></span>
</div>
{{--<div class="table-header">
    {{ $panel }}  Record list on table. Filter {{ $panel }} using the filter.
</div>--}}
<!-- div.table-responsive -->
<div class="table-responsive">
    <table id="dynamic-table" class="table table-striped table-bordered table-hover">
        <thead>
        <tr>
            <th class="center">
                <label class="pos-rel">
                    <input type="checkbox" class="ace" />
                    <span class="lbl"></span>
                </label>
            </th>
            <th>S.N.</th>
            {{--<th>SEM/SEC</th>--}}
            <th>Subject</th>
            <th>Question</th>
            <th>Created By</th>
            <th>Date</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data['assignment']) && $data['assignment']->count() > 0)
            @php($i=1)
            @foreach($data['assignment'] as $assignment)
                <tr>
                    <td class="center first-child">
                        <label>
                            <input type="checkbox" name="chkIds[]" value="{{ $assignment->id }}" class="ace" />
                            <span class="lbl"></span>
                        </label>
                    </td>
                    <td>{{ $i }}</td>
                    {{--<td>{{ isset($assignment->semesters_id)?ViewHelper::getSemesterById($assignment->semesters_id):'' }}</td>--}}
                    <td>{{ isset($assignment->subjects_id)?ViewHelper::getSubjectById($assignment->subjects_id):'' }}</td>
                    <td>
                        <a href="{{ route('assignment.view', ['id' => encrypt($assignment->id)]) }}">
                            {{ $assignment->title }}
                        </a>
                    </td>
                    <td> {{$assignment->created_by_name}} </td>

                    <td>
                        {{ \Carbon\Carbon::parse($assignment->publish_date)->format('M d, Y')}} TO
                        {{ \Carbon\Carbon::parse($assignment->end_date)->format('M d, Y')}}
                    </td>
                    <td>
                        <div class="btn-group">
                            @php($approveStatus = $assignment->answers()->where('students_id',$data['student']->id)->first())
                            @if(isset($approveStatus))
                                @if( $approveStatus->approve_status == 1)
                                    <button class="btn btn-success btn-minier dropdown-toggle" >
                                        Approve
                                    </button>
                                @elseif( $approveStatus->approve_status == 2)
                                    <button class="btn btn-danger btn-minier dropdown-toggle" >
                                        Rejected
                                    </button>
                                @else
                                    <button class="btn btn-info btn-minier dropdown-toggle" >
                                        Pending
                                    </button>
                                @endif
                            @else
                                <button class="btn btn-warning btn-minier dropdown-toggle" >
                                    Not Submited
                                </button>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="hidden-sm hidden-xs action-buttons">
                            @if(!isset($approveStatus))
                                <a href="{{ route('user-student.assignment.answer.add', ['id' => $assignment->id]) }}" class="btn btn-primary btn-minier">
                                    <i class="ace-icon fa fa-reply bigger-130"></i> Submit Answer
                                </a>
                            @endif

                            @if(isset($approveStatus))
                                @if( $approveStatus->approve_status != 1)
                                    <a href="{{ route('user-student.assignment.answer.edit', ['id' => $assignment->id]) }}" class="btn btn-success btn-minier">
                                        <i class="ace-icon fa fa-pencil bigger-130"></i> Edit
                                    </a>
                                @endif
                                <a href="{{ route('user-student.assignment.answer.view', ['id' => $assignment->id, 'answer'=> $approveStatus->id]) }}" class="btn btn-primary btn-minier">
                                    <i class="ace-icon fa fa-eye bigger-130"></i> View
                                </a>
                            @endif


                        </div>
                    </td>

                </tr>
                @php($i++)
            @endforeach
        @else
            <tr>
                <td colspan="12">Assignment not found. </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
</div>