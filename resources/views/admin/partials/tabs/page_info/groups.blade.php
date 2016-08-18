<h4>Groups</h4>

<div class="form-group" id="groupContainer">
    {!! Form::label('page_groups', 'Top Level Group Page', ['class' => 'control-label col-xs-6 col-sm-2']) !!}
    <div class="col-sm-2 col-xs-6">
        <label class="radio-inline">
            {!! Form::radio('page_info_other[group_radio]', 1, $page->group_container ? 1 : 0) !!} Yes
        </label>
        <label class="radio-inline">
            {!! Form::radio('page_info_other[group_radio]', 0, $page->group_container ? 0 : 1) !!} No
        </label>
    </div>
    <div class="col-sm-8 col-xs-12">
        <select name="page_info[group_container]" class="form-control">
            <option value="-1">-- New Group --</option>
            <option value="0">-- Not Top Level Group Page --</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" {{ $page->group_container == $group->id ? 'selected="selected"' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@if (!$groups->isEmpty())
<div class="form-group" id="inGroup">
    {!! Form::label('page_groups', 'In Group', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        @foreach($groups as $group)
            <label class="checkbox-inline">
                {!! Form::checkbox('page_groups['.$group->id.']', 1, in_array($group->id, $page->groupIds())) !!} &nbsp; {!! $group->name !!}
            </label>
        @endforeach
    </div>
</div>
@endif