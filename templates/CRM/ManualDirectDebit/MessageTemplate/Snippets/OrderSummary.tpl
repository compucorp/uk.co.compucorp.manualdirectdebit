<table style="border-collapse: collapse;border: 1px solid black; max-width: 600px; width: 100%;">
    <tr style="border: 1px solid black;background: rgb(162,162,162)">
        <th style="padding-left: 10px;text-align: left"><p style="color: black;"><strong>{ts}Order Summary{/ts}<strong></p></th>
        <th><p></p></th>
    </tr>

    {foreach from=$orderLineItems item=lineItem}
        <tr style="border: 1px solid black;">
            <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$lineItem.label}</strong></p></td>
            <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$currency}{$lineItem.price}{if !empty($lineItem.tax)} (+{$currency}{$lineItem.tax} tax){/if}</strong></p></td>
        </tr>
    {/foreach}

    <tr style="border: 1px solid black;">
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{ts}Total{/ts}</strong></p></td>
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$currency}{$recurringContributionData.total}{if !empty($recurringContributionData.tax_total)} (+{$currency}{$recurringContributionData.tax_total} tax){/if}</strong></p></td>
    </tr>
    <tr style="border: 1px solid black;">
        <td style="padding-left: 10px;">
          <p style="color: black">
            {ts 1=$recurringContributionData.installments 2=$recurringContributionData.installments_paid 3=$currency }To be paid in %1 installments of %3%2 each{/ts}
            {if !empty($recurringContributionData.tax_total)}
              (includes {$currency}{$recurringContributionData.tax_total/$recurringContributionData.installments} tax)
            {/if}
          </p>
        </td>
        <td style="padding-left: 10px;"><p></p></td>
    </tr>
</table>
