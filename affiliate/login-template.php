<!-- affilate/login-template.php -->
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sas_login'])) {
    sas_process_affiliate_login();
}
?>

<div class="sas-login">
    <h2>Affiliate Login</h2>
    <form method="post" action="<?php echo esc_url(home_url('/affiliate/login')); ?>">
        <p>
            <label for="sas_username">Username</label>
            <input type="text" name="sas_username" required />
        </p>
        <p>
            <label for="sas_password">Password</label>
            <input type="password" name="sas_password" required />
        </p>
        <p>
            <input type="submit" name="sas_login" value="Login" />
        </p>
    </form>
</div>
