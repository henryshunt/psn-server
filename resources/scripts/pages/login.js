function oauthLogin()
{
    $("#login-form").attr("action", "resources/routines/login.php?type=oauth");
    $("#login-form").submit();
}

function adminLogin()
{
    if ($("#admin-password").val() !== "")
    {
        $("#login-form").attr("action", "resources/routines/login.php?type=admin");
        $("#login-form").submit();
    }
}

function guestLogin()
{
    if ($("#guest-password").val() !== "")
    {
        $("#login-form").attr("action", "resources/routines/login.php?type=guest");
        $("#login-form").submit();
    }
}