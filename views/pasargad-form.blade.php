<form id="goto_pasargad_bank" class="form-horizontal goto-bank-form" method="POST" target="_self" action="{!! $url !!}">
    <input type="hidden" name="invoiceNumber" value="{{$invoiceNumber}}" />
    <input type="hidden" name="invoiceDate" value="{!! $invoiceDate !!}" />
    <input type="hidden" name="amount" value="{{$amount}}" />
    <input type="hidden" name="terminalCode" value="{{$terminalCode}}" />
    <input type="hidden" name="merchantCode" value="{{$merchantCode}}" />
    <input type="hidden" name="timeStamp" value="{!! $timeStamp !!}" />
    <input type="hidden" name="action" value="{{$action}}" />
    <input type="hidden" name="sign" value="{!! $sign !!}" />
    <input type="hidden" name="redirectAddress" value="{!! $redirectUrl !!}" />
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-success">{{$submitLabel}}</button>
        </div>
    </div>
</form>

@if($autoSubmit === true)
    <script type="text/javascript">
	var f=document.getElementById('goto_pasargad_bank');
    f.submit();
</script>
@endif
