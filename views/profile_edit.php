<form action="<?php print $url; ?>/register" method="post" target="_blank" style="display: inline">
    <input type="hidden" name="auth_key" value="<?php print $auth_key; ?>">
    <input type="hidden" name="auth_token" value="<?php print $token; ?>">
    <input type="hidden" name="success_url" value="<?php print $edit_account_url; ?>">
    <input type="hidden" name="failure_url" value="<?php print get_site_url(); ?>">
    <input type="hidden" name="parameters[user_id]" value="<?php print $user->ID; ?>">
    <input type="submit" value="Create a TradeSafe Account">
</form>
<br />
<br />
<form action="<?php print $url; ?>/authorize" method="post" target="_blank" style="display: inline">
    <input type="hidden" name="auth_key" value="<?php print $auth_key; ?>">
    <input type="hidden" name="auth_token" value="<?php print $token; ?>">
    <input type="hidden" name="success_url" value="<?php print $edit_account_url; ?>">
    <input type="hidden" name="failure_url" value="<?php print get_site_url(); ?>">
    <input type="hidden" name="parameters[user_id]" value="<?php print $user->ID; ?>">
    <input type="submit" value="Link Your TradeSafe Account">
</form>