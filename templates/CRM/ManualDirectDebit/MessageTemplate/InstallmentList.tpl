<table>
    <tr>
        <th style="padding-left: 10px;"><strong>Installment No.</strong></th>
        <th style="padding-left: 10px;"><strong>Amount</strong></th>
        <th style="padding-left: 10px;"><strong>Due Date</strong></th>
    </tr>
    {foreach from=$installments item=recurringPlanRow}
        <tr>
            <td style="padding-left: 10px;">
                {$recurringPlanRow.index}
            </td>
            <td style="padding-left: 10px;">
                {$recurringPlanRow.amount}
            </td>
            <td style="padding-left: 10px;">
                {$recurringPlanRow.due_date}
            </td>
        </tr>
    {/foreach}
</table>
