let availableNodes = [];

$(window).on("load", () =>
{
    loadActiveSessions();
    loadCompletedSessions();
});


function loadActiveSessions()
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

                    html += TEMPLATE.format(data[i]["sessionId"], data[i]["name"],
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

function loadCompletedSessions()
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

                    html += TEMPLATE.format(data[i]["sessionId"], data[i]["name"],
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
    $.getJSON("api.php/nodes?completed-only=true", (data) =>
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

function newSessionModalAddNode()
{
    let TEMPLATE = `
        <div class="session-node-row">
            <button type="button" onclick="newSessionModalRemoveNode(this)">
                <i class="material-icons">delete</i>
            </button>

            <div>
                <span>SENSOR NODE</span>
                <select class="form-control">{0}</select>
            </div>

            <div>
                <span>LOCATION DESCRIPTION</span>
                <input type="text" id="session-title" class="form-control">
            </div>

            <div>
                <span>END TIME</span>
                <input type="text" id="session-description" class="form-control">
            </div>

            <div>
                <span>REPORTING INTERVAL</span>
                <select class="form-control" id="node-interval">
                    <option value="1">1 Minute</option>
                    <option value="2">2 Minutes</option>
                    <option value="5">5 Minutes</option>
                    <option value="10">10 Minutes</option>
                    <option value="15">15 Minutes</option>
                    <option value="20">20 Minutes</option>
                    <option value="30">30 Minutes</option>
                </select>
            </div>

            <div>
                <span title="Transmit reports in batches of X to save battery power">BATCH TRANSMIT</span>
                <input type="number" min="1" max="127" value="1" class="form-control">
            </div>
        </div>`;
    let TEMPLATE2 = `<option value="{0}">{1}</option>`;

    let options = "";
    for (let i = 0; i < availableNodes.length; i++)
        options += TEMPLATE2.format(availableNodes[i]["nodeId"], availableNodes[i]["macAddress"]);

    let elements = $(TEMPLATE.format(options));

    // Add date picker for end time
    flatpickr($(elements).children().eq(3).children().eq(1)[0],
    { enableTime: true, time_24hr: true, dateFormat: "d/m/Y H:i" });
                
    $("#session-node-rows").append(elements);
}

function newSessionModalRemoveNode(element)
{
    $(element).parent().remove();
}

function newSessionModalSave()
{
    let emptyFields = false;
    if ($("#new-session-name").val() === "") emptyFields = true;

    let TEMPLATE = `{"name":"{0}","description":"{1}","nodes":[{2}]}`;
    let TEMPLATE2 = `{"nodeId":{0},"location":"{1}","endTime":{2},"interval":{3},"batchSize":{4}}`;

    let sessionNodes = "";

    // Convert each added sensor node to a JSON string
    for (let i = 1; i <= document.getElementById("session-node-rows").children.length; i++)
    {
        let selector = "#session-node-rows > :nth-child(" + i + ") ";
        let node = $(selector + "> :nth-child(2) > :last-child").val();
        let location = $(selector + "> :nth-child(3) > :last-child").val();

        let end = null;
        let endValue = $(selector + "> :nth-child(4) > :last-child").val();
        if (endValue !== "") end = moment(endValue, "DD/MM/YYYY HH:mm").utc();
        let interval = $(selector + "> :nth-child(5) > :last-child").val();
        let batch = $(selector + "> :nth-child(6) > :last-child").val();

        if (end === null)
            sessionNodes += TEMPLATE2.format(node, location, "null", interval, batch);
        else
        {
            sessionNodes += TEMPLATE2.format(node, location,
                "\"" + end.format("YYYY-MM-DD HH:mm:ss") + "\"", interval, batch);
        }

        if (location === "") emptyFields = true;
    }

    if (emptyFields === true || sessionNodes === "")
    {
        alert("Cannot submit, one or more fields are empty or you have not added any sensor nodes.");
        return;
    }

    let session = TEMPLATE.format(
        $("#new-session-name").val(), $("#new-session-description").val(), sessionNodes);

    $.post({
        url: "data/add-session.php",
        data: { "data": session },
        ContentType: "application/json",

        success: () => window.location.reload(),
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