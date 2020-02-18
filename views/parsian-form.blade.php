<form id="goto_parsian_bank" class="form-horizontal goto-bank-form" method="get" action="{!! $endPoint !!}">
    <input type="hidden" name="token" value="{{$refId}}" />
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-success">{{$submitLabel}}</button>
        </div>
    </div>
</form>

@if($autoSubmit === true)
	<script type="text/javascript">
	var f=document.getElementById('goto_parsian_bank');
  f.submit();
</script>
@endif


