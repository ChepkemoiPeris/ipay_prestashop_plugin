{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='ipaymodule'}">{l s='Checkout' mod='ipaymodule'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='iPay payment' mod='ipaymodule'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='ipaymodule'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='ipaymodule'}</p>
{else}
<img src="https://resources.elipa.co/iPay_10_payment_channels.png" alt="{l s='iPay' mod='ipaymodule'}" width="auto" height="60" />
<br />
<br />
<h3>
	{l s='iPay - Mobile/Card Online' mod='ipaymodule'}
</h3>
<form action="{$link->getModuleLink('ipaymodule', 'validation', [], true)|escape:'html'}" method="post">
<p>
	{l s='You have chosen to pay by iPay.' mod='ipaymodule'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='ipaymodule'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='ipaymodule'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='ipaymodule'}
    {/if}
</p>
<p>
	-
	{if $currencies|@count > 1}
		{l s='We allow several currencies to be sent via iPay.' mod='ipaymodule'}
		<br /><br />
		{l s='Choose one of the following:' mod='ipaymodule'}
		<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
			{foreach from=$currencies item=currency}
				<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
			{/foreach}
		</select>
	{else}
		{l s='We allow the following currency to be sent via IPay:' mod='ipaymodule'}&nbsp;<b>{$currencies.0.name}</b>
		<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
	{/if}
</p>
<p>
	{l s='iPay account information will be displayed on the next page.' mod='ipaymodule'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order".' mod='ipaymodule'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='ipaymodule'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='ipaymodule'}</a>
</p>
</form>
{/if}
