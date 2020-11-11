window.addEventListener("load", () =>
{
    document.getElementById("user-button").addEventListener("click", () =>
    {
        if (document.getElementById("user-menu").style.display === "none")
            document.getElementById("user-menu").style.display = "block";
        else document.getElementById("user-menu").style.display = "none";
    });
});

function logOut()
{
    window.location.href = "resources/routines/logout.php";
}