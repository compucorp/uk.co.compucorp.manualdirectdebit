<html xmlns="http://www.w3.org/1999/xhtml">
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<body>

{if isset($recurringContributionData)}
    <!-- Start of 1st section for custom text.  -->
    <div>
        <p>Thank you for making a recurring contribution. Your payment details are:</p>
    </div>
    <!-- End of 1st section for custom text.  -->

    <!-- Start of code block for generating payment plan schedule information. Edit with caution. -->
    <table style="border-collapse: collapse;border: 1px solid black;">
        <tr style="border: 1px solid black;">
            <th><p><strong>Order Summary<strong></p></th>
            <th><p></p></th>
        </tr>
        {foreach from=$recurringContributionData.recurringContributionRows item='item'}
            <tr style="border: 1px solid black;">
                <td style="border: 1px solid black;"><p>{$item.type}</p></td>
                <td style="border: 1px solid black;"><p>{$item.amount}</p></td>
            </tr>
        {/foreach}
        <tr style="border: 1px solid black;">
            <td style="border: 1px solid black;"><p><strong>Total</strong></p></td>
            <td style="border: 1px solid black;"><p><strong>{$recurringContributionData.total}</strong></p></td>
        </tr>
        <tr style="border: 1px solid black;">
            <td><p>To be paid on {$recurringContributionData.installments} installments of {$recurringContributionData.installments_paid} each</p></td>
            <td><p></p></td>
        </tr>
    </table>
    <!-- End of code block for generating payment plan schedule information. -->
{/if}

<!-- if payment method = direct debit. -->
{if isset($mandateData)}

    <!-- Start of 2nd section for custom text.  -->
    <div>
        <p>
            Thank you for choosing Direct Debit. Please check that your Direct Debit details below are correct. If they are not, please contact us. If your Direct Debit details are correct, you need do nothing and your Direct Debit will be collected as stated.
        </p>
    </div>
    <!-- End of 2nd section for custom text.  -->

    <!-- Start of code block for generating mandate information. Edit with caution. -->
    <table>
        <tr>
            <td><p>Bank Name:</p></td>
            <td><p>{$mandateData.bank_name}</p></td>
        </tr>
        <tr>
            <td><p>Bank Street Address:</p></td>
            <td><p>{$mandateData.bank_street_address}</p></td>
        </tr>
        <tr>
            <td><p>City:</p></td>
            <td><p>{$mandateData.bank_city}</p></td>
        </tr>
        <tr>
            <td><p>County:</p></td>
            <td><p>{$mandateData.bank_county}</p></td>
        </tr>
        <tr>
            <td><p>Postcode:</p></td>
            <td><p>{$mandateData.Postcode}</p></td>
        </tr>
        <tr>
            <td><p>Account Holder Name:</p></td>
            <td><p>{$mandateData.account_holder_name}</p></td>
        </tr>
        <tr>
            <td><p>A/C Number:</p></td>
            <td><p>{$mandateData.ac_number}</p></td>
        </tr>
        <tr>
            <td><p>Sort Code:</p></td>
            <td><p>{$mandateData.sort_code}</p></td>
        </tr>
        <tr>
            <td><p>DD Code:</p></td>
            <td><p>{$mandateData.dd_code}</p></td>
        </tr>
        <tr>
            <td><p>DD Ref:</p></td>
            <td><p>{$mandateData.dd_ref}</p></td>
        </tr>
        <tr>
            <td><p>Start Date</p></td>
            <td><p>{$mandateData.start_date}</p></td>
        </tr>
        <tr>
            <td><p>Authorisation Date:</p></td>
            <td><p>{$mandateData.authorisation_date}</p></td>
        </tr>
    </table>
    <!-- End of code block for generating mandate information. -->
{/if}

{if isset($membershipData)}
    <!-- Start of 3rd section for custom text.  -->
    <div>
        <p>
            Your order contains the following membership(s):
        </p>
    </div>
    <!-- End of 3rd section for custom text.  -->

    <!-- Start of code block for generating membership information. Edit with caution. -->
    <div>
        <p>
            {$membershipData.membershipName} at {$membershipData.amountPerUnit} per {$membershipData.durationUnit}.
        </p>
    </div>
    <!-- End of code block for generating membership information. -->


    {if isset($membershipData.nextPayment)}
        <!-- Start of 4th section for custom text.  -->
        <div>
            <p>Please find the details of you next payment below:</p>
        </div>
        <!-- End of 4th section for custom text.  -->

        <!-- Start of code block for generating next contribution information. Edit with caution. -->
        <div>
            <p>Your next of {$membershipData.nextPayment.amount} will be collected on {$membershipData.nextPayment.date|date_format:"%Y%m%d"}</p>
        </div>
        <!-- End of code block for generating next contribution information. -->
    {/if}
{/if}

{if isset($mandateData)}
    <!-- Start of Direct Debit Guarantee.  -->
    <table style="border-collapse: collapse;border: 1px solid black;">
        <tr >
            <th><h3>The Direct Debit Guarantee</h3></th>
            <th></th>
        </tr>
        <tr>
            <td>
                <ul>
                    <li>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s</li>
                    <li>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old.</li>
                    <li>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable.</li>
                    <li>The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested.</li>
                    <li>Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy.</li>
                </ul>
            </td>
            <td></td>
        </tr>
    </table>
    <!-- End of Direct Debit Guarantee.  -->
{/if}

</body>
</html>