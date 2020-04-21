$(window).on("load", () =>
{
    $("#account-button").on("click", () => toggleAccountMenu());
});

function toggleAccountMenu()
{
    if ($("#account-menu").css("display") === "none")
        $("#account-menu").css("display", "block");
    else $("#account-menu").css("display", "none");
}

function logOut()
{
    window.location.href = "resources/routines/logout.php";
}