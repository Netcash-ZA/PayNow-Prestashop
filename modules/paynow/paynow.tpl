<div class='sagePayNow'>
<form id='sagePayNow' action="{$data.paynow_url}" method="post">
    <p class="payment_module">

    {foreach $data.info as $k=>$v}
        <input type="hidden" name="{$k}" value="{$v}" />
    {/foreach}

    <a href='#' onclick='document.getElementById("sagePayNow").submit();return false;'>{$data.paynow_text}
      {if $data.paynow_logo=='on'} <img align='{$data.paynow_align}' alt='Pay Now With SagePay' title='Pay Now With SagePay' src="{$base_dir}modules/paynow/logo.png">{/if}</a>
       <noscript><input type="image" src="{$base_dir}modules/paynow/logo.png"></noscript>
    </p>
</form>
</div>
<div class="clear"></div>
