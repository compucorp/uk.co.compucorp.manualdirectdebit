<html xmlns="http://www.w3.org/1999/xhtml">
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<body>
<div style="color: black;">

    {if isset($recurringContributionData) and $recurringContributionData}

        <div>
            <p style="color: black;">{ts}Thank you for making a recurring contribution. Your payment details are:{/ts}</p>
        </div>

        {$orderSummaryTable nofilter}
    {/if}

    {if isset($mandateData) and $mandateData}

        <div style="max-width: 600px; width: 100%;" >
            <p style="color: black;">
                {ts}Thank you for choosing Direct Debit. Please check that your Direct Debit details below are correct. If they are not, please contact us. If your Direct Debit details are correct, you need do nothing and your Direct Debit will be collected as stated.{/ts}
            </p>
        </div>

        <table>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Bank Name:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.bank_name}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Bank Street Address:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.bank_street_address}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}City:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.bank_city}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}County:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.bank_county}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Postcode:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.bank_postcode}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Account Holder Name:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.account_holder_name}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}A/C Number:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.ac_number}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Sort Code:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.sort_code}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}DD Code:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.dd_code}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}DD Ref:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.dd_ref}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Start Date{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.start_date|crmDate:$shortDateFormat}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Authorisation Date:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.authorisation_date|crmDate:$shortDateFormat}</span></td>
            </tr>
        </table>
    {/if}

    {if isset($paymentPlanMemberships) and $paymentPlanMemberships}
        <div>
            <p style="color: black;">
                {ts}Your order contains the following membership(s):{/ts}
            </p>
        </div>

        {foreach from=$paymentPlanMemberships item=membership}
            <div>
                <p style="color: black;">
                    {if !empty($membership.tax)}
                        {ts 1=$membership.price 2=$membership.durationUnit 3=$currency 4=$membership.tax}{$membership.label} at %3%1 (+%3%4 tax) per %2{/ts}.
                    {else}
                        {ts 1=$membership.price 2=$membership.durationUnit 3=$currency }{$membership.label} at %3%1 per %2{/ts}.
                    {/if}
                </p>
            </div>
        {/foreach}

        {if $recurringContributionData.installments gt 0}
            <p>You can find your installment schedule below:</p>
            {$recurringContributionData.recurringContributionRows.recurringInstallmentsTable nofilter}
        {/if}

        {if isset($nextMembershipPayment) and $nextMembershipPayment}
            <div>
                <p style="color: black;">{ts}Please find the details of you next payment below:{/ts}</p>
            </div>

            <div>
                <p style="color: black;">
                  {ts 1=$nextMembershipPayment.amount 2=$currency}Your next payment of %2%1 will be collected on{/ts}
                  {$nextMembershipPayment.date|crmDate:$shortDateFormat}
                </p>
            </div>
        {/if}
    {/if}

    {if isset($activeMemberships) and $activeMemberships}
        <div>
            <p style="color: black;">{ts}You have the following active membership(s):{/ts}</p>
        </div>

        {$activeMembershipsTable nofilter}
    {/if}

    {if isset($mandateData) and $mandateData}
        <div style="padding-top: 30px">
            <table style="border-collapse: collapse;border: 1px solid black;max-width: 600px;width: 100%;">
                <tr >
                    <th style="text-align: left; padding-left: 40px;">
                        <h3 style="color: black;">{ts}The Direct Debit Guarantee{/ts}</h3>
                    </th>
                    <th>
                        <div style="margin-right: 10px;">
                            <img src="{$directDebitImageSrc}" style="width:90px;height: auto;" alt="Direct Debit"/>
                        </div>
                    </th>
                </tr>
                <tr>
                    <td>
                        <ul>
                          <li style="color: black;">{ts}The Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits.{/ts}</li>
                          <li style="color: black;">{ts}If there are any changes to the amount, date or frequency of your Direct Debit (Insert Your Organisation name) will notify you (Insert number of days) working days in advance of your account being debited or as otherwise agreed. If you request (Insert Your Organisation name)  to collect a payment, confirmation of the amount and date will be given to you at the time of the request.{/ts}</li>
                          <li style="color: black;">{ts}If an error is made in the payment of your Direct Debit, by (Insert Your Organisation name) or your bank or building society, you are entitled to a full and immediate refund of the amount paid from your bank or building society - If you receive a refund you are not entitled to, you must pay it back when (Insert Your Organisation name) asks you to.{/ts}</li>
                          <li style="color: black;">{ts}You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written confirmation may be required. Please also notify us.{/ts}</li>
                        </ul>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>
    {/if}
</div>
</body>
</html>
