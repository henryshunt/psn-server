var nodes = [];
var newSessionNodeCount = 0;

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
                        <a href="session.html?id={0}">
                            <span>{1}</span>
                            <br>
                            <span>{2}</span>
                            <span>{3}</span>
                        </a>
                    </div>`;

                var html = "";
                for (var i = 0; i < data.length; i++)
                {
                    var date_range = "From " + 
                        dbTimeToLocal(data[i]["start_time"]).format("DD/MM/YYYY");

                    if (data[i]["end_time"] !== null)
                    {
                        date_range += " to " +
                            dbTimeToLocal(data[i]["end_time"]).format("DD/MM/YYYY");
                    } else date_range += ", Indefinitely";

                    if (data[i]["node_count"] != 1)
                        var node_count = data[i]["node_count"] + " Sensor Nodes";
                    else var node_count = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["session_id"], data[i]["name"],
                        date_range, node_count);
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
                        <a href="session.html?id={0}">
                            <span>{1}</span>
                            <br>
                            <span>{2}</span>
                            <span>{3}</span>
                        </a>
                    </div>`;

                var html = "";
                for (var i = 0; i < data.length; i++)
                {
                    var date_range = "";
                    if (data[i]["start_time"] !== null)
                    {
                        date_range += "From " +
                            dbTimeToLocal(data[i]["start_time"]).format("DD/MM/YYYY");
                        date_range += " to " +
                            dbTimeToLocal(data[i]["end_time"]).format("DD/MM/YYYY");
                    }

                    if (data[i]["node_count"] != 1)
                        var node_count = data[i]["node_count"] + " Sensor Nodes";
                    else var node_count = "1 Sensor Node";

                    html += TEMPLATE.format(data[i]["session_id"], data[i]["name"],
                        date_range, node_count);
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
    $("#modal_shade").css("display", "block");
    // $("body").css("overflow", "hidden");
    $("#new_session_modal").css("display", "block");

    $.getJSON("data/get-nodes-completed-all.php", (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
            {
                nodes = data;
            }
        }

        $("#completed-sessions-group").css("display", "block");

    }).fail(() =>
    {
    });
}

function newSessionModalClose()
{
    $("#modal_shade").css("display", "none");
    // $("body").css("overflow", "auto");
    $("#new_session_modal").css("display", "none");
}

function newSessionModalAddNode()
{
    var TEMPLATE = `
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

    let n = "";
    for (let i = 0; i < nodes.length; i++)
    {
        n += "<option value=\"" + nodes[i]["node_id"] + "\">" + nodes[i]["mac_address"] + "</option>";
    }

    $("#session-node-rows").append(TEMPLATE.format(n));
}

function newSessionModalRemoveNode(element)
{
    $(element).parent().remove();
}

function newSessionModalSave()
{
    // var json = `{
    //         "name": "Session name",
    //         "description": "Session description",

    //         "nodes":
    //         [
    //             { "node": 2, "location": "Node location", "startTime": "2020-02-04 14:58:00", "endTime": "2020-03-04 14:58:00", "interval": 2, "batchSize": 10 }
    //         ]
    //     }`;

    var json = `{
        "name": "{0}",
        "description": "{1}",

        "nodes":
        [
            {2}
        ]
    }`;

    var x = "";
    for (let i = 0; i < document.getElementById("session-node-rows").children.length; i++)
    {
        let node = document.getElementById("session-node-rows").children[i].children[1].value;
        let location = document.getElementById("session-node-rows").children[i].children[2].value;
        let end = document.getElementById("session-node-rows").children[i].children[3].value;
        let interval = document.getElementById("session-node-rows").children[i].children[5].value;
        let batch = document.getElementById("session-node-rows").children[i].children[6].value;

        x += "{ \"node\": {0}, \"location\": \"{1}\", \"endTime\": \"{2}\", \"interval\": {3}, \"batchSize\": {4} }".format(
            node, location, end, interval, batch);
    }

    console.log(json.format($("#new_session_name").val(), $("#new_session_description").val(), x));

    $.post({
        url: "data/add-session.php",
        // ContentType: "application/json",
        data: { "data": json },

        success: function(data)
        {
            console.log(data);
        },

        error: (e) => {
            console.log("error");
            console.log(e.responseText);
        }
    });

    newSessionModalClose();
}

function newSessionModalCancel()
{
    newSessionModalClose();
}