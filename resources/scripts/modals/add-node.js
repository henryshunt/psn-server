window.addEventListener("load", () =>
{
    document.getElementById(
        "m-addnode-form").addEventListener("submit", addNodeModal._onFormSubmit);
    document.getElementById(
        "m-addnode-cancel").addEventListener("click", addNodeModal._onCancelClick);
});

var addNodeModal = (function ()
{
    let _modalData = null;

    function open(data)
    {
        _modalData = data;

        document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
        document.getElementById("addnode-modal").classList.remove("modal--hidden");

        document.getElementById("m-addnode-location").value = "";
        document.getElementById("m-addnode-status").innerText =
            "Loading available sensor nodes...";

        // Get all available nodes for display in sensor node dropdown
        getJson("api.php/nodes?inactive=true")
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

                    document.getElementById("m-addnode-node").disabled = false;
                    document.getElementById("m-addnode-location").disabled = false;
                    document.getElementById("m-addnode-interval").disabled = false;
                    document.getElementById("m-addnode-end").disabled = false;
                    document.getElementById("m-addnode-batch").disabled = false;
                    document.getElementById("m-addnode-submit").disabled = false;
                    document.getElementById("m-addnode-cancel").disabled = false;
                    document.getElementById("m-addnode-status").innerText = "";
                    document.getElementById("m-addnode-node").focus();

                    flatpickr(document.getElementById("m-addnode-end"),
                        { enableTime: true, time_24hr: true, dateFormat: "Y/m/d H:i" });
                }
            )
            .catch(() =>
                {
                    document.getElementById("m-addnode-status").innerText =
                        "Error getting available sensor nodes";
                }
            );
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


        const url = "api.php/projects/{0}/nodes";

        postJson(url.format(_modalData.projectId), JSON.stringify(projectNode))
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

    return {
        open: open,
        _onFormSubmit: onFormSubmit,
        _onCancelClick: onCancelClick
    };
})();