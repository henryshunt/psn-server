window.addEventListener("load", () =>
{
    document.getElementById("log-out-btn").addEventListener("click",
        () => window.location.href = "auth/log-out.php"
    );

    document.getElementById("user-menu-btn").addEventListener("click",
        () => document.getElementById("user-menu").classList.toggle("user__menu--hidden")
    );
});