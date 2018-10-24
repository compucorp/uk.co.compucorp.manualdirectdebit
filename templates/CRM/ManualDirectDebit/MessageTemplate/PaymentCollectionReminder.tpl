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
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.start_date}</span></td>
            </tr>
            <tr>
                <td style="padding-left: 10px;"><span style="color: black;">{ts}Authorisation Date:{/ts}</span></td>
                <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.authorisation_date}</span></td>
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
                    {ts 1=$membership.price 2=$membership.durationUnit 3=$currency }{$membership.label} at %3%1 per %2.{/ts}
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
                <p style="color: black;">{ts 1=$nextMembershipPayment.amount 2=$nextMembershipPayment.date 3=$currency }Your next payment of %3%1 will be collected on %2{/ts}</p>
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
        <div style="padding-top: 60px">
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
                            <li style="color: black;">{ts}Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s{/ts}</li>
                            <li style="color: black;">{ts}Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.{/ts}</li>
                            <li style="color: black;">{ts}There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable.{/ts}</li>
                            <li style="color: black;">{ts}The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested.{/ts}</li>
                            <li style="color: black;">{ts}Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy.{/ts}</li>
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
