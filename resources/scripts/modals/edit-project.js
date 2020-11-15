window.addEventListener("load", () =>
{
    document.getElementById(
        "m-editprj-form").addEventListener("submit", editProjectModal._onFormSubmit);
    document.getElementById(
        "m-editprj-cancel").addEventListener("click", editProjectModal._onCancelClick);
});
    
var editProjectModal = (function ()
{
    let _modalData = null;

    function open(data)
    {
        _modalData = data;

        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("editprj-modal").classList.remove("modal--hidden");

        document.getElementById("m-editprj-name").value = data.name;
        document.getElementById("m-editprj-desc").value = data.description;
        document.getElementById("m-editprj-status").innerText = "";
        document.getElementById("m-editprj-name").focus();
    }

    function _onFormSubmit(event)
    {
        // Prevent form submission from reloading the page
        event.preventDefault();

        if (document.getElementById("m-editprj-name").value.length === 0)
        {
            document.getElementById("m-editprj-status").innerText =
                "Name cannot be empty";
            return;
        }

        document.getElementById("m-editprj-name").disabled = true;
        document.getElementById("m-editprj-desc").disabled = true;
        document.getElementById("m-editprj-submit").disabled = true;
        document.getElementById("m-editprj-cancel").disabled = true;
        document.getElementById("m-editprj-status").innerText = "";

        const reEnableForm = () => 
        {
            document.getElementById("m-editprj-name").disabled = false;
            document.getElementById("m-editprj-desc").disabled = false;
            document.getElementById("m-editprj-submit").disabled = false;
            document.getElementById("m-editprj-cancel").disabled = false;
        }


        let project = { name: document.getElementById("m-editprj-name").value };

        if (document.getElementById("m-editprj-desc").value.length > 0)
            project.description = document.getElementById("m-editprj-desc").value;
        else project.description = null;

        patchReq("api.php/projects/" + _modalData.projectId, JSON.stringify(project))
            .then(() => window.location.reload())

            .catch((data) =>
            {
                reEnableForm();

                if (data !== null && "error" in data &&
                    data["error"] === "name is not unique within user")
                {
                    document.getElementById("m-editprj-status").innerText =
                        "A project with that name already exists";
                    document.getElementById("m-editprj-name").focus();
                }
                else
                {
                    document.getElementById("m-editprj-status").innerText =
                        "Error creating project";
                }
            });
    }

    function _onCancelClick()
    {
        document.getElementById("modal-shade").classList.add("modal__shade--hidden");
        document.getElementById("editprj-modal").classList.add("modal--hidden");
    }

    return {
        open: open,
        onFormSubmit: _onFormSubmit,
        onCancelClick: _onCancelClick
    };
})();