<html xmlns="http://www.w3.org/1999/xhtml">
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<body>
<div style="color: black;">

  {if isset($recurringContributionData) and $recurringContributionData}
    <!-- Start of 1st section for custom text.  -->
    <div>
      <p style="color: black;">{ts}Thank you for making a recurring contribution. Your payment details are:{/ts}</p>
    </div>
    <!-- End of 1st section for custom text.  -->

    <!-- Start of code block for generating payment plan schedule information. Edit with caution. -->
    <table style="border-collapse: collapse;border: 1px solid black; max-width: 600px; width: 100%;">
      <tr style="border: 1px solid black;background: rgb(162,162,162)">
        <th style="padding-left: 10px;text-align: left"><p style="color: black;"><strong>{ts}Order Summary{/ts}<strong></p></th>
        <th><p></p></th>
      </tr>
      <tr style="border: 1px solid black;">
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$membershipData.membershipName}</strong></p></td>
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$currency}{$recurringContributionData.total}</strong></p></td>
      </tr>
      <tr style="border: 1px solid black;">
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{ts}Total{/ts}</strong></p></td>
        <td style="border: 1px solid black;padding-left: 10px;"><p style="color: black;"><strong>{$currency}{$recurringContributionData.total}</strong></p></td>
      </tr>
      <tr style="border: 1px solid black;">
        <td style="padding-left: 10px;"><p style="color: black">{ts 1=$recurringContributionData.installments 2=$recurringContributionData.installments_paid 3=$currency }To be paid on %1 installments of %3%2 each{/ts}</p></td>
        <td style="padding-left: 10px;"><p></p></td>
      </tr>
    </table>
    <!-- End of code block for generating payment plan schedule information. -->
  {/if}

  <!-- if payment method = direct debit. -->
  {if isset($mandateData) and $mandateData}

    <!-- Start of 2nd section for custom text.  -->
    <div style="max-width: 600px; width: 100%;" >
      <p style="color: black;">
        {ts}Thank you for choosing Direct Debit. Please check that your Direct Debit details below are correct. If they are not, please contact us. If your Direct Debit details are correct, you need do nothing and your Direct Debit will be collected as stated.{/ts}
      </p>
    </div>
    <!-- End of 2nd section for custom text.  -->

    <!-- Start of code block for generating mandate information. Edit with caution. -->
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
        <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.start_date|date_format:"%d/%m/%Y"}</span></td>
      </tr>
      <tr>
        <td style="padding-left: 10px;"><span style="color: black;">{ts}Authorisation Date:{/ts}</span></td>
        <td style="padding-left: 10px;"><span style="color: black;">{$mandateData.authorisation_date|date_format:"%d/%m/%Y"}</span></td>
      </tr>
    </table>
    <!-- End of code block for generating mandate information. -->
  {/if}

  {if isset($membershipData) and $membershipData}
    <!-- Start of 3rd section for custom text.  -->
    <div>
      <p style="color: black;">
        {ts}Your order contains the following membership(s):{/ts}
      </p>
    </div>
    <!-- End of 3rd section for custom text.  -->

    <!-- Start of code block for generating membership information. Edit with caution. -->
    <div>
      <p style="color: black;">
        {ts 1=$membershipData.amountPerUnit 2=$membershipData.durationUnit 3=$currency }{$membershipData.membershipName} at %3%1 per %2.{/ts}
      </p>
    </div>
    <!-- End of code block for generating membership information. -->

    {if isset($nextMembershipPayment) and $nextMembershipPayment}
      <!-- Start of 4th section for custom text.  -->
      <div>
        <p style="color: black;">{ts}Please find the details of you next payment below:{/ts}</p>
      </div>
      <!-- End of 4th section for custom text.  -->

      <!-- Start of code block for generating next contribution information. Edit with caution. -->
      <div>
        <p style="color: black;">{ts 1=$nextMembershipPayment.amount 2=$nextMembershipPayment.date 3=$currency }Your next payment of %3%1 will be collected on %2{/ts}</p>
      </div>
      <!-- End of code block for generating next contribution information. -->
    {/if}
  {/if}

  {if isset($mandateData) and $mandateData}
    <!-- Start of Direct Debit Guarantee.  -->
    <div style="padding-top: 60px">
      <table style="border-collapse: collapse;border: 1px solid black;max-width: 600px;width: 100%;">
        <tr >
          <th style="text-align: left; padding-left: 40px;">
            <h3 style="color: black;">{ts}The Direct Debit Guarantee{/ts}</h3>
          </th>
          <th>
            <div style="margin-right: 20px">
              <img src="{$directDebitImageSrc}" style="width: 100%;height: auto;" alt="Direct Debit" />
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
    <!-- End of Direct Debit Guarantee.  -->
  {/if}
</div>
</body>
</html>
