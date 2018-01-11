<form id="goto_saderat_bank" class="form-horizontal goto-bank-form" method="POST" action="{!! $endPoint !!}">
    <input type="hidden" name="TOKEN" value="{{$token}}" />
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-success">{{$submitLabel}}</button>
        </div>
    </div>
</form>

@if($autoSubmit === true)
<script type="text/javascript">
	var f=document.getElementById('goto_saderat_bank');
  f.submit();
</script>
@endif