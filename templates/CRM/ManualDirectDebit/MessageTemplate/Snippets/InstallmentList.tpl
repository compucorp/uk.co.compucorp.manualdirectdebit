<table>
    <tr>
        <th style="padding-left: 10px;"><strong>Installment No.</strong></th>
        {if !empty($totalTax)}
          <th style="padding-left: 10px;"><strong>Value</strong></th>
          <th style="padding-left: 10px;"><strong>Tax</strong></th>
        {/if}
        <th style="padding-left: 10px;"><strong>Total Amount</strong></th>
        <th style="padding-left: 10px;"><strong>Due Date</strong></th>
    </tr>
    {foreach from=$installments item=recurringPlanRow}
        <tr>
            <td style="padding-left: 10px;">
                {$recurringPlanRow.index}
            </td>
            {if !empty($totalTax)}
              <td style="padding-left: 10px;">
                £{$recurringPlanRow.sub_total}
              </td>
              <td style="padding-left: 10px;">
                £{$recurringPlanRow.tax}
              </td>
            {/if}
            <td style="padding-left: 10px;">
                £{$recurringPlanRow.amount}
            </td>
            <td style="padding-left: 10px;">
                {$recurringPlanRow.due_date|crmDate:$shortDateFormat}
            </td>
        </tr>
    {/foreach}
</table>
