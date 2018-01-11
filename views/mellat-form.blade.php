<form id="goto_mellat_bank" class="form-horizontal goto-bank-form" method="POST" action="{!! $endPoint !!}">
    <input type="hidden" name="RefId" value="{{$refId}}" />
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-success">{{$submitLabel}}</button>
        </div>
    </div>
</form>

@if($autoSubmit === true)
<script type="text/javascript">
	var f=document.getElementById('goto_mellat_bank');
  f.submit();
</script>
@endif