<!-- affiliate/registration-template.php -->
<?php
get_header();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sas_register'])) {
    sas_process_affiliate_registration();
}

?>

<div class="sas-registration">
    <h2>Affiliate Registration</h2>
    <form method="post">
        <p>
            <label for="sas_username">Username <strong>*</strong></label>
            <input type="text" name="sas_username" required />
        </p>
        <p>
            <label for="sas_email">Email <strong>*</strong></label>
            <input type="email" name="sas_email" required />
        </p>
        <p>
            <label for="sas_password">Password <strong>*</strong></label>
            <input type="password" name="sas_password" required />
        </p>
        <p>
            <input type="submit" name="sas_register" value="Register" />
        </p>
    </form>
</div>

<?php
get_footer();
?>
