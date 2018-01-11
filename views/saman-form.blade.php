<form id="goto_saman_bank" class="form-horizontal goto-bank-form" method="post" action="{!! $endPoint !!}">
	@if(isset($token) && !empty($token))
    <input type="hidden" name="Token" value="{{$token}}" />
	@else
		<input type="hidden" name="Amount" value="{{$amount}}">
		<input type="hidden" name="MID" value="{{$merchantId}}">
		<input type="hidden" name="ResNum" value="{{$orderId}}">
	@endif
	<input type="hidden" name="RedirectURL" value="{!! $redirectUrl !!}">
  <div class="control-group">
      <div class="controls">
          <button type="submit" class="btn btn-success">{{$submitLabel}}</button>
      </div>
  </div>
</form>

@if($autoSubmit === true)
	<script type="text/javascript">
	var f=document.getElementById('goto_saman_bank');
  f.submit();
</script>
@endif


