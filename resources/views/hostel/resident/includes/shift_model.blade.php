<!-- Modal -->
<div class="modal fade" id="shiftResident" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        {!! Form::open(['route' => $base_route.'.shift', 'method' => 'POST', 'class' => 'form-horizontal',
                       'id' => 'validation-form', "enctype" => "multipart/form-data"]) !!}

        <div class="modal-content">
            <div class="modal-header">
                {{--<button type="button" class="close top-close" data-dismiss="modal" id="close-button">×</button>
                <h4 class="modal-title title text-center fees_title" id="MasterTitle"><b>Class 6 General:</b> admission-fees</h4>--}}
            </div>
            <div class="modal-body pb0">
                <div class="form-horizontal">
                    <div class="box-body">
                        <input type="hidden" name="residentId" id="residentId" value=""/>
                        <div class="form-group">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Hostel</label>
                                    <div class="col-sm-9">
                                        {!! Form::select('hostel_shift', $data['hostels'], null, ['class' => 'form-control', "onChange" => "loadRooms(this)"]) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Room</label>
                                    <div class="col-sm-3">
                                        <select name="room_shift" class="form-control room_select" onChange="loadBeds(this)">
                                            <option value="0"> Select Room... </option>
                                        </select>
                                    </div>
                                    <label class="col-sm-3 control-label">Bed</label>
                                    <div class="col-sm-3">
                                        <select name="bed_shift" class="form-control bed_select">
                                            <option value="0"> Select Bed... </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="box-body">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn cfees shift_button btn-success" id="shift-btn" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Processing"> <i class="fa fa-exchange" aria-hidden="true"></i> Shift Now </button>
                </div>
            </div>
        </div>
        {!! Form::close() !!}

    </div>
</div>
