window.addEventListener("load", () =>
{
    document.getElementById("new-project-btn").addEventListener("click",
        newProjectModal.open);
    document.getElementById("m-newproj-form")
        .addEventListener("submit", newProjectModal.onFormSubmit);
    document.getElementById("m-newproj-cancel")
        .addEventListener("click", newProjectModal.onCancelClick);
});


var newProjectModal = (function ()
{
    function open()
    {
        document.getElementById("m-newproj-name").value = "";
        document.getElementById("m-newproj-desc").value = "";
        document.getElementById("m-newproj-status").innerText = "";
        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("newproj-modal").classList.remove("modal--hidden");
        document.getElementById("m-newproj-name").focus();
    }

    function onFormSubmit(event)
    {
        // Prevent form submission from reloading the page
        event.preventDefault();

        document.getElementById("m-newproj-status").innerText = "";

        if (document.getElementById("m-newproj-name").value.length === 0)
        {
            document.getElementById("m-newproj-status").innerText =
                "Name cannot be empty";
            document.getElementById("m-newproj-name").focus();
            return;
        }

        let project = { name: document.getElementById("m-newproj-name").value };
        if (document.getElementById("m-newproj-desc").value.length > 0)
            project.description = document.getElementById("m-newproj-desc").value;

        _makeTheRequest(project);
    }

    function _makeTheRequest(data)
    {
        _disableModal();
        
        postRequest("", JSON.stringify(data))
            .then((response) => window.location.href += "/" + response.json["projectId"])

            .catch((response) =>
            {
                _enableModal();

                if (response.status === 401)
                    window.location.href = "../auth/login";
                else if (response.json !== null && "error" in response.json &&
                    response.json["error"] === "'name' is not unique within user")
                {
                    document.getElementById("m-newproj-status").innerText =
                        "A project with that name already exists";
                    document.getElementById("m-newproj-name").focus();
                }
                else document.getElementById("m-newproj-status").innerText = "Error";
            });
    }

    function onCancelClick()
    {
        document.getElementById("newproj-modal").classList.add("modal--hidden");
        document.getElementById("modal-shade").classList.add("modal__shade--hidden");
    }

    function _enableModal()
    {
        document.getElementById("m-newproj-name").disabled = false;
        document.getElementById("m-newproj-desc").disabled = false;
        document.getElementById("m-newproj-submit").disabled = false;
        document.getElementById("m-newproj-cancel").disabled = false;
    }

    function _disableModal()
    {
        document.getElementById("m-newproj-name").disabled = true;
        document.getElementById("m-newproj-desc").disabled = true;
        document.getElementById("m-newproj-submit").disabled = true;
        document.getElementById("m-newproj-cancel").disabled = true;
    }

    return {
        open: open,
        onFormSubmit: onFormSubmit,
        onCancelClick: onCancelClick
    };
})();