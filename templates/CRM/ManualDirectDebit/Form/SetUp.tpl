{crmStyle ext=uk.co.compucorp.manualdirectdebit file=css/setUp.css}
<div class="crm-block crm-direct-debit-set-up-form-block">
  {if $errorMessage}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {$errorMessage} <a href="/civicrm">{ts}Return to dashboard{/ts}</a>
    </div>
  {else}
    <div class="crm-container crm-public">
      <div class="crm-block crm-manual-direct-debit-form-block">
        <div class="crm-public-form-item crm-section crm-manual-direct-debit-form-payment-information">
          <fieldset class="crm-profile">
            <legend>{ts}Payment Information{/ts}</legend>
            <div class="crm-section form-item crm-manual-direct-debit-form-invoice-number">
              <div class="label"><label> {ts}Invoice number:{/ts}</label></div>
              <div class="content">{$invoiceNumber}</div>
              <div class="clear"></div>
            </div>
            <div class="crm-section form-item crm-manual-direct-debit-form-amount">
              <div class="label"><label> {ts}Amount:{/ts}</label></div>
              <div class="content">{$amount|crmMoney:$currency}</div>
              <div class="clear"></div>
            </div>
            {if $taxAmount}
              <div class="crm-section form-item crm-manual-direct-debit-form-vat">
                <div class="label"><label>{ts}VAT:{/ts}</label></div>
                <div class="content">{$taxAmount|crmMoney:$currency}</div>
                <div class="clear"></div>
              </div>
            {/if}
            <div class="crm-section form-item crm-manual-direct-debit-form-total-amount">
              <div class="label"><label>{ts}Total Amount:{/ts}</label></div>
              <div class="content">{$totalAmount|crmMoney:$currency}</div>
              <div class="clear"></div>
            </div>
          </fieldset>
        </div>
        <div class="crm-public-form-item crm-section crm-manual-direct-debit-form-bank-details">
          <fieldset class="crm-profile">
            <legend>{ts}Direct Debit Details{/ts}</legend>
            <div class="crm-section form-item crm-manual-direct-debit-form-bank-name">
              <div class="label">{$form.bank_name.label}</div>
              <div class="content">{$form.bank_name.html}</div>
              <div class="clear"></div>
            </div>
            <div class="crm-section form-item crm-manual-direct-debit-form-bank-account-holder">
              <div class="label">{$form.bank_account_holder.label}</div>
              <div class="content">{$form.bank_account_holder.html}</div>
              <div class="clear"></div>
            </div>
            <div class="crm-section form-item crm-manual-direct-debit-form-bank-account-number">
              <div class="label">{$form.bank_account_number.label}</div>
              <div class="content">{$form.bank_account_number.html}</div>
              <div class="clear"></div>
            </div>
            <div class="crm-section form-item crm-manual-direct-debit-form-bank-account-sort-code">
              <div class="label">{$form.bank_sort_code.label}</div>
              <div class="content">{$form.bank_sort_code.html}</div>
              <div class="clear"></div>
            </div>
          </fieldset>
        </div>
        <div class="crm-manual-direct-debit-form-hidden-elements">
          {$form.contribution_id.html}
          {$form.contact_id.html}
        </div>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
        <div class="clear"></div>
        <div class="crm-block crm-direct-debit-set-up-guarantee">
          <div class="dd-guarantee">
            <h5><b>{ts}Direct Debit Guarantee{/ts}</b></h5>
            <img class="dd-logo" src="{crmResURL ext=uk.co.compucorp.manualdirectdebit file=Images/debit.png}"
                 alt="Direct Debit"/>
          </div>
          <div class="clear"></div>
          <ul>
            <li>
              {ts}The Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits{/ts}
            </li>
            <li>
              {ts}If there are any changes to the amount, date or frequency of your Direct Debit the organisation will notify you
                (normally 10 working days) in advance of your account being debited or as otherwise agreed.
                If you request the organisation to collect a payment,
                confirmation of the amount and date will be given to you at the time of the request{/ts}
            </li>
            <li>
              {ts}If an error is made in the payment of your Direct Debit, by the organisation or your bank or building society,
                you are entitled to a full and immediate refund of the amount paid from your bank or building society
                You can cancel a Direct Debit at any time by simply contacting your bank or building society -
                If you receive a refund you are not entitled to, you must pay it back when the organisation asks you to{/ts}
            <li>
              {ts}You can cancel a Direct Debit at any time by simply contacting your bank or building society.
                Written confirmation may be required. Please also notify the organisation{/ts}
            </li>
          </ul>
        </div>
      </div>
    </div>
  {/if}
</div>
