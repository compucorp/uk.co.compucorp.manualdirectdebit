<table style="border-collapse: collapse;border: 1px solid black; max-width: 600px; width: 100%;">
    <tr style="border: 1px solid black;background: rgb(162,162,162)">
        <th style="padding-left: 10px;text-align: left"><p style="color: black;"><strong>{ts}Membership Type{/ts}<strong></p></th>
        <th style="padding-left: 10px;text-align: left"><p style="color: black;"><strong>{ts}Start Date{/ts}<strong></p></th>
        <th style="padding-left: 10px;text-align: left"><p style="color: black;"><strong>{ts}End Date{/ts}<strong></p></th>
    </tr>

    {foreach from=$activeMemberships item=membership}
        <tr style="border: 1px solid black;">
            <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$membership.name}</strong></p></td>
            <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$membership.startDate|crmDate:$shortDateFormat}</strong></p></td>
            <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$membership.endDate|crmDate:$shortDateFormat}</strong></p></td>
        </tr>
    {/foreach}
</table>
