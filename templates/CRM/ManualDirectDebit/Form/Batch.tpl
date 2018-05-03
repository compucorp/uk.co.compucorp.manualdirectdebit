<div class="crm-section">
    <div class="clear">
        <div class="label">{ts}Batch ID{/ts}:</div>
        <div class="content">{$batch_id}</div>
    </div>
    <div class="clear">
        <div class="label">{$form.title.label}</div>
        <div class="content">{$form.title.html}</div>
    </div>
    <div class="clear">
        <div class="label">{ts}Batch Type{/ts}</div>
        <div class="content">{$batch_type}</div>
    </div>
    <div class="clear">
        <div class="label">{$form.originator_number.label}</div>
        <div class="content">{$form.originator_number.html}</div>
    </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
