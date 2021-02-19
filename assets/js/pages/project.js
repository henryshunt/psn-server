window.addEventListener("load", () =>
{
    document.getElementById("edit-project-btn").addEventListener("click",
        () => editProjectModal.open());
    document.getElementById("m-editprj-form")
        .addEventListener("submit", editProjectModal.onFormSubmit);
    document.getElementById("m-editprj-cancel")
        .addEventListener("click", editProjectModal.onCancelClick);
    
    flatpickr(document.getElementById("m-addnode-end"),
        { enableTime: true, time_24hr: true, dateFormat: "Y/m/d H:i" });

    document.getElementById("download-data-btn").addEventListener("click", onDownloadDataClick);
    document.getElementById("stop-project-btn").addEventListener("click", onStopProjectClick);
    document.getElementById("delete-project-btn").addEventListener("click", onDeleteProjectClick);

    document.getElementById("add-node-btn").addEventListener("click",
        () => addNodeModal.open());
    document.getElementById("m-addnode-form")
        .addEventListener("submit", addNodeModal.onFormSubmit);
    document.getElementById("m-addnode-cancel")
        .addEventListener("click", addNodeModal.onCancelClick);
});


function onDownloadDataClick()
{
    // window.open("data/get-session-download.php?sessionId=" +
    //     getQueryStringValue("id"));
}

function onStopProjectClick()
{
    if (confirm("Are you sure you want to stop the project?"))
    {
        patchReq("api.php/projects/{0}?stop=true".format(getQueryStringValue("id")))
            .then(() => window.location.reload())
            .catch(() => alert("An error occured while stopping the project."));
    }
}

function onDeleteProjectClick()
{
    if (confirm("Are you sure you want to delete the project? " +
        "This will also delete all reports produced by the sensor nodes inside it."))
    {
        deleteReq("")
            .then(() => window.location.href = "../projects")
            .catch(() => alert("An error occured while deleting the project."));
    }
}


var editProjectModal = (function ()
{
    function open()
    {
        document.getElementById("m-editprj-name").value = projectName;
        document.getElementById("m-editprj-desc").value = projectDescription;
        document.getElementById("m-editprj-status").innerText = "";
        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("editprj-modal").classList.remove("modal--hidden");
        document.getElementById("m-editprj-name").focus();
    }

    function onFormSubmit(event)
    {
        // Prevent form submission from reloading the page
        event.preventDefault();

        document.getElementById("m-editprj-status").innerText = "";

        if (document.getElementById("m-editprj-name").value.length === 0)
        {
            document.getElementById("m-editprj-status").innerText =
                "Name cannot be empty";
            document.getElementById("m-editprj-name").focus();
            return;
        }

        let project = { name: document.getElementById("m-editprj-name").value };
        if (document.getElementById("m-editprj-desc").value.length > 0)
            project.description = document.getElementById("m-editprj-desc").value;
        else project.description = null;

        _makeTheRequest(project);
    }

    function _makeTheRequest(data)
    {
        _disableModal();
        
        patchRequest("", JSON.stringify(data))
            .then(() => window.location.reload())

            .catch((response) =>
            {
                _enableModal();

                if (response.status === 401)
                    window.location.href = "../../auth/login";
                else if (response.json !== null && "error" in response.json &&
                    response.json["error"] === "'name' is not unique within user")
                {
                    document.getElementById("m-editprj-status").innerText =
                        "A project with that name already exists";
                    document.getElementById("m-editprj-name").focus();
                }
                else document.getElementById("m-editprj-status").innerText = "Error";
            });
    }

    function onCancelClick()
    {
        document.getElementById("editprj-modal").classList.add("modal--hidden");
        document.getElementById("modal-shade").classList.add("modal__shade--hidden");
    }

    function _enableModal()
    {
        document.getElementById("m-editprj-name").disabled = false;
        document.getElementById("m-editprj-desc").disabled = false;
        document.getElementById("m-editprj-submit").disabled = false;
        document.getElementById("m-editprj-cancel").disabled = false;
    }

    function _disableModal()
    {
        document.getElementById("m-editprj-name").disabled = true;
        document.getElementById("m-editprj-desc").disabled = true;
        document.getElementById("m-editprj-submit").disabled = true;
        document.getElementById("m-editprj-cancel").disabled = true;
    }

    return {
        open: open,
        onFormSubmit: onFormSubmit,
        onCancelClick: onCancelClick
    };
})();

var addNodeModal = (function ()
{
    function open()
    {
        _disableModal();
        _clearModal();

        document.getElementById("m-addnode-status").innerText =
            "Loading available sensor nodes...";
        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("addnode-modal").classList.remove("modal--hidden");

        // Get all available nodes for display in sensor node dropdown
        getJson("/nodes/inactive")
            .then((data) =>
            {
                for (const node of data)
                {
                    const option = document.createElement("option");
                    option.innerText = node["macAddress"];
                    
                    if (node["name"] !== null)
                        option.innerText += " ({0})".format(node["name"]);

                    option.value = node["nodeId"];
                    document.getElementById("m-addnode-node").append(option);
                }

                _enableModal();
                document.getElementById("m-addnode-status").innerText = "";
                document.getElementById("m-addnode-node").focus();
            })
            .catch(() =>
            {
                document.getElementById("m-addnode-status").innerText =
                    "Error getting available sensor nodes";
            });
    }

    function onFormSubmit(event)
    {
        // Prevent form submission from reloading the page
        event.preventDefault();

        if (document.getElementById("m-addnode-node").selectedIndex === 0)
        {
            document.getElementById("m-addnode-status").innerText =
                "You must select a sensor node";
            return;
        }

        if (document.getElementById("m-addnode-location").value.length === 0)
        {
            document.getElementById("m-addnode-status").innerText =
                "Location cannot be empty";
            return;
        }

        document.getElementById("m-addnode-node").disabled = true;
        document.getElementById("m-addnode-location").disabled = true;
        document.getElementById("m-addnode-interval").disabled = true;
        document.getElementById("m-addnode-end").disabled = true;
        document.getElementById("m-addnode-batch").disabled = true;
        document.getElementById("m-addnode-submit").disabled = true;
        document.getElementById("m-addnode-cancel").disabled = true;
        document.getElementById("m-addnode-status").innerText = "";

        const reEnableForm = () =>
        {
            document.getElementById("m-addnode-node").disabled = false;
            document.getElementById("m-addnode-location").disabled = false;
            document.getElementById("m-addnode-interval").disabled = false;
            document.getElementById("m-addnode-end").disabled = false;
            document.getElementById("m-addnode-batch").disabled = false;
            document.getElementById("m-addnode-submit").disabled = false;
            document.getElementById("m-addnode-cancel").disabled = false;
        };

        if (document.getElementById("m-addnode-end").value.length === 0)
            var endAt = null;
        else var endAt = document.getElementById("m-addnode-end").value.replaceAll("/", "-") + ":00";

        let projectNode =
        {
            nodeId: parseInt(document.getElementById("m-addnode-node").value),
            interval: parseInt(document.getElementById("m-addnode-interval").value),
            endAt: endAt,
            batchSize: parseInt(document.getElementById("m-addnode-batch").value)
        };

        if (document.getElementById("m-addnode-location").value.length > 0)
            projectNode.location = document.getElementById("m-addnode-location").value;


        postJson("/nodes", JSON.stringify(projectNode))
            .then(() => window.location.reload())

            .catch((data) =>
            {
                reEnableForm();

                if (data !== null && "error" in data &&
                    data["error"] === "location is not unique within project")
                {
                    document.getElementById("m-addnode-status").innerText =
                        "A node with that location already exists in this project";
                    document.getElementById("m-addnode-location").focus();
                }
                else if (data !== null && "error" in data &&
                    data["error"] === "Node is already active")
                {
                    document.getElementById("m-addnode-status").innerText =
                        "That sensor node is already in use";
                }
                else
                {
                    document.getElementById("m-addnode-status").innerText =
                        "Error creating project";
                }
            });
    }

    function onCancelClick()
    {
        document.getElementById("modal-shade").classList.add("modal__shade--hidden");
        document.getElementById("addnode-modal").classList.add("modal--hidden");
    }

    function _enableModal()
    {
        document.getElementById("m-addnode-node").disabled = false;
        document.getElementById("m-addnode-location").disabled = false;
        document.getElementById("m-addnode-interval").disabled = false;
        document.getElementById("m-addnode-end").disabled = false;
        document.getElementById("m-addnode-batch").disabled = false;
        document.getElementById("m-addnode-submit").disabled = false;
        document.getElementById("m-addnode-cancel").disabled = false;
    }

    function _disableModal()
    {
        document.getElementById("m-addnode-node").disabled = true;
        document.getElementById("m-addnode-location").disabled = true;
        document.getElementById("m-addnode-interval").disabled = true;
        document.getElementById("m-addnode-end").disabled = true;
        document.getElementById("m-addnode-batch").disabled = true;
        document.getElementById("m-addnode-submit").disabled = true;
        document.getElementById("m-addnode-cancel").disabled = true;
    }

    function _clearModal()
    {
        document.getElementById("m-addnode-node").innerHTML = "";
        document.getElementById("m-addnode-location").value = "";
        document.getElementById("m-addnode-interval").value = 0;
        document.getElementById("m-addnode-end").value = "";
        document.getElementById("m-addnode-batch").value = 1;
    }

    return {
        open: open,
        onFormSubmit: onFormSubmit,
        onCancelClick: onCancelClick
    };
})();