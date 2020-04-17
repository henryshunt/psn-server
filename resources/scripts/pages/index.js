let availableNodes = [];
// var datePicker = null;

$(window).on("load", () =>
{
    loadActiveSessions();
    loadCompletedSessions();
});


function loadActiveSessions()
{
    $.getJSON("data/get-sessions-active.php", (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
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
                        dbTimeToLocal(data[i]["start_time"]).format("DD/MM/YYYY");

                    if (data[i]["end_time"] !== null)
                    {
                        dateRange += " to " +
                            dbTimeToLocal(data[i]["end_time"]).format("DD/MM/YYYY");
                    } else dateRange += ", Indefinitely";

                    let nodeCount = "";
                    if (data[i]["node_count"] != 1)
                        nodeCount = data[i]["node_count"] + " Sensor Nodes";
                    else nodeCount = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["session_id"], data[i]["name"],
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
    $.getJSON("data/get-sessions-completed.php", (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
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
                    if (data[i]["start_time"] !== null)
                    {
                        dateRange += "From " +
                            dbTimeToLocal(data[i]["start_time"]).format("DD/MM/YYYY");
                        dateRange += " to " +
                            dbTimeToLocal(data[i]["end_time"]).format("DD/MM/YYYY");
                    }

                    let nodeCount = "";
                    if (data[i]["node_count"] != 1)
                        nodeCount = data[i]["node_count"] + " Sensor Nodes";
                    else nodeCount = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["session_id"], data[i]["name"],
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

    // Get all available nodes for display in sensor node dropdown
    $.getJSON("data/get-nodes-completed-all.php", (data) =>
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

    // Reset form
    $("#new-session-name").val("");
    $("#new-session-description").val("");
    $("#session-node-rows").children().empty();
    $("#session-node-add-button").attr("disabled", true);
}


function newSessionModalAddNode()
{
    let TEMPLATE = `
        <div class="session-node-row" style="display: flex">
            <button type="button" onclick="newSessionModalRemoveNode(this)"><i class="material-icons">delete</i></button>

            <select class="form-control">
                <option>Sensor Node</option>
                {0}
            </select>

            <input type="text" id="session-title" class="form-control" placeholder="Sensor Node Location"/>
            <input type="text" id="session-description" class="form-control" placeholder="End Time"/>

            <span>Interval & Batch Size:</span>
            <select class="form-control" id="node-interval" title="Interval between reports">
                <option value="1">1 Min</option>
                <option value="2">2 Mins</option>
                <option value="5">5 Mins</option>
                <option value="10">10 Mins</option>
                <option value="15">15 Mins</option>
                <option value="20">20 Mins</option>
                <option value="30">30 Mins</option>
            </select>
            <input type="number" min="1" max="127" value="1" class="form-control" title="Transmit reports in batches of"/>
        </div>`;
    let TEMPLATE2 = `<option value="{0}">{1}</option>`;

    let options = "";
    for (let i = 0; i < availableNodes.length; i++)
        options += TEMPLATE2.format(availableNodes[i]["node_id"], availableNodes[i]["mac_address"]);

    let elements = $(TEMPLATE.format(options));

    // Add date picker for end time
    flatpickr($(elements).children().eq(3)[0],
    {
        enableTime: true, time_24hr: true, dateFormat: "d/m/Y H:i"
        // enableTime: true, time_24hr: true, defaultDate: initTime,
        // disableMobile: true,
    });
                
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
    let TEMPLATE2 = `{"nodeId":{0},"location":"{1}","endTime":"{2}","interval":{3},"batchSize":{4}}`;

    let sessionNodes = "";
    for (let i = 0; i < document.getElementById("session-node-rows").children.length; i++)
    {
        let node = document.getElementById("session-node-rows").children[i].children[1].value;
        let location = document.getElementById("session-node-rows").children[i].children[2].value;
        let end = moment(document.getElementById("session-node-rows").children[i].children[3].value,
            "DD/MM/YYYY HH:mm").utc();
        let interval = document.getElementById("session-node-rows").children[i].children[5].value;
        let batch = document.getElementById("session-node-rows").children[i].children[6].value;
        sessionNodes += TEMPLATE2.format(node, location, end.format("YYYY-MM-DD HH:mm:ss"), interval, batch);

        if (node === "Sensor Node" || location === "" || end === "") emptyFields = true;
    }

    if (sessionNodes === "") emptyFields = true;
    if (emptyFields === true)
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