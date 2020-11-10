let availableNodes = [];

window.addEventListener("load", () =>
{
    loadActiveProjects();
    loadCompletedProjects();
});


function loadActiveProjects()
{
    $.getJSON("api.php/projects?mode=active", (data) =>
    {
        if (data !== false)
        {
            if (data.length > 0)
            {
                const TEMPLATE = `
                    <div class="item">
                        <a href="session.php?id={0}">
                            <span>{1}</span>
                            <br>
                            <span>{2}</span>
                            <span>{3}</span>
                        </a>
                    </div>`;

                let html = "";
                for (let i = 0; i < data.length; i++)
                {
                    let dateRange = "From " + 
                        dbTimeToLocal(data[i]["startAt"]).format("DD/MM/YYYY");

                    if (data[i]["endAt"] !== null)
                    {
                        dateRange += " to " +
                            dbTimeToLocal(data[i]["endAt"]).format("DD/MM/YYYY");
                    } else dateRange += ", Indefinitely";

                    let nodeCount = "";
                    if (data[i]["nodeCount"] != 1)
                        nodeCount = data[i]["nodeCount"] + " Sensor Nodes";
                    else nodeCount = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["projectId"], data[i]["name"],
                        dateRange, nodeCount);
                }

                $("#active-sessions").append(html);
            } else $("#active-sessions").append(NO_DATA_HTML);
        } else $("#active-sessions").append(ERROR_HTML);

        $("#active-sessions-group").css("display", "block");

    }).fail(() =>
    {
        $("#active-sessions").append(ERROR_HTML);
        $("#active-sessions-group").css("display", "block");
    });
}

function loadCompletedProjects()
{
    $.getJSON("api.php/projects?mode=completed", (data) =>
    {
        if (data !== false)
        {
            if (data.length > 0)
            {
                const TEMPLATE = `
                    <div class="item item-thin">
                        <a href="session.php?id={0}">
                            <span>{1}</span>
                            <br>
                            <span>{2}</span>
                            <span>{3}</span>
                        </a>
                    </div>`;

                let html = "";
                for (let i = 0; i < data.length; i++)
                {
                    let dateRange = "";
                    if (data[i]["startAt"] !== null)
                    {
                        dateRange += "From " +
                            dbTimeToLocal(data[i]["startAt"]).format("DD/MM/YYYY");
                        dateRange += " to " +
                            dbTimeToLocal(data[i]["endAt"]).format("DD/MM/YYYY");
                    }

                    let nodeCount = "";
                    if (data[i]["nodeCount"] != 1)
                        nodeCount = data[i]["nodeCount"] + " Sensor Nodes";
                    else nodeCount = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["projectId"], data[i]["name"],
                        dateRange, nodeCount);
                }

                $("#completed-sessions").append(html);
            } else $("#completed-sessions").append(NO_DATA_HTML);
        } else $("#completed-sessions").append(ERROR_HTML);

        $("#completed-sessions-group").css("display", "block");

    }).fail(() =>
    {
        $("#completed-sessions").append(ERROR_HTML);
        $("#completed-sessions-group").css("display", "block");
    });
}


function newSessionModalOpen()
{
    $("#modal-shade").css("display", "block");
    $("#new-session-modal").css("display", "block");

    // Reset form
    $("#new-session-name").val("");
    $("#new-session-description").val("");
    $("#session-node-rows").children().empty();
    $("#session-node-add-button").attr("disabled", true);

    // Get all available nodes for display in sensor node dropdown
    $.getJSON("api.php/nodes?inactive=true", (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
                availableNodes = data;

            $("#session-node-add-button").attr("disabled", false);
        }
        else
        {
            alert("Error while getting the available sensor nodes.");
            newSessionModalClose();
        }

    }).fail(() =>
    {
        alert("Error while getting the available sensor nodes.");
        newSessionModalClose();
    });
}

function newSessionModalClose()
{
    $("#modal-shade").css("display", "none");
    $("#new-session-modal").css("display", "none");
}

function newSessionModalSave()
{
    let emptyFields = false;
    if ($("#new-session-name").val() === "") emptyFields = true;

    if (emptyFields === true)
    {
        alert("Cannot submit, one or more fields are empty or you have not added any sensor nodes.");
        return;
    }

    let session =
    {
        name: $("#new-session-name").val(),
        //description: $("#new-session-description").val()
    };

    $.post({
        url: "api.php/projects",
        data: JSON.stringify(session),
        ContentType: "application/json",

        success: (data) =>
        {
            window.location.href = "session.php?id=" + data["projectId"];
        },
        error: () => alert("Error while creating the session")
    });
}


function newNodeModalOpen()
{
    $("#modal-shade").css("display", "block");
    $("#new-node-modal").css("display", "block");

    // Reset form
    $("#new-node-address").val("");
}

function newNodeModalClose()
{
    $("#modal-shade").css("display", "none");
    $("#new-node-modal").css("display", "none");
}

function newNodeModalSave()
{
    if ($("#new-node-address").val() === "")
    {
        alert("You must enter a MAC address.");
        return;
    }

    $.post({
        url: "api.php/nodes?inactive=true",
        data: { "mac_address": $("#new-node-address").val() },
        ContentType: "application/json",

        success: () => newNodeModalClose(),
        error: () => alert("Error while adding the sensor node. Are you sure a node with this address does not already exist?")
    });
}