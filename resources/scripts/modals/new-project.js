window.addEventListener("load", () =>
{
    document.getElementById(
        "m-newproj-form").addEventListener("submit", newProjectModal._onFormSubmit);
    document.getElementById(
        "m-newproj-cancel").addEventListener("click", newProjectModal._onCancelClick);
});
    
var newProjectModal = (function ()
{
    function open()
    {
        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("newproj-modal").classList.remove("modal--hidden");

        document.getElementById("m-newproj-name").value = "";
        document.getElementById("m-newproj-desc").value = "";
        document.getElementById("m-newproj-status").innerText = "";
        document.getElementById("m-newproj-name").focus();
    }

    function _onFormSubmit(event)
    {
        // Prevent form submission from reloading the page
        event.preventDefault();

        if (document.getElementById("m-newproj-name").value.length === 0)
        {
            document.getElementById("m-newproj-status").innerText =
                "Name cannot be empty";
            return;
        }

        document.getElementById("m-newproj-name").disabled = true;
        document.getElementById("m-newproj-desc").disabled = true;
        document.getElementById("m-newproj-submit").disabled = true;
        document.getElementById("m-newproj-cancel").disabled = true;
        document.getElementById("m-newproj-status").innerText = "";

        const reEnableForm = () => 
        {
            document.getElementById("m-newproj-name").disabled = false;
            document.getElementById("m-newproj-desc").disabled = false;
            document.getElementById("m-newproj-submit").disabled = false;
            document.getElementById("m-newproj-cancel").disabled = false;
        }


        let project = { name: document.getElementById("m-newproj-name").value };

        if (document.getElementById("m-newproj-desc").value.length > 0)
            project.description = document.getElementById("m-newproj-desc").value;

        postJson("api.php/projects", JSON.stringify(project))
            .then((data) => window.location.href = "project.php?id=" + data["projectId"])

            .catch((data) =>
            {
                reEnableForm();

                if (data !== null && "error" in data &&
                    data["error"] === "name is not unique within user")
                {
                    document.getElementById("m-newproj-status").innerText =
                        "A project with that name already exists";
                    document.getElementById("m-newproj-name").focus();
                }
                else
                {
                    document.getElementById("m-newproj-status").innerText =
                        "Error creating project";
                }
            });
    }

    function _onCancelClick()
    {
        document.getElementById("modal-shade").classList.add("modal__shade--hidden");
        document.getElementById("newproj-modal").classList.add("modal--hidden");
    }

    return {
        open: open,
        onFormSubmit: _onFormSubmit,
        onCancelClick: _onCancelClick
    };
})();