$(window).on("load", () =>
{
    // Load currently active sessions
    $.ajax(
    {
        url: "data/get-active-sessions.php",
        dataType: "json",

        success: (data) =>
        {
            if (data !== false)
            {
                const TEMPLATE = `<div class="item"><a href="session.html?id={0}">
                    <span>{1}</span><br><span>{2}</span><span>{3}</span></a></div>`;

                if (data !== null)
                {
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
        },

        error: () =>
        {
            $("#active-sessions").append(ERROR_HTML);
            $("#active-sessions-group").css("display", "block");
        }
    });

    // Load completed sessions
    $.ajax(
    {
        url: "data/get-completed-sessions.php",
        dataType: "json",
        
        success: (data) =>
        {
            if (data !== false)
            {
                const TEMPLATE = `<div class="item item-thin"><a href="session.html?id={0}">
                    <span>{1}</span><br><span>{2}</span><span>{3}</span></a></div>`;

                if (data !== null)
                {
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
        },

        error: () =>
        {
            $("#completed-sessions").append(ERROR_HTML);
            $("#completed-sessions-group").css("display", "block");
        }
    });
});


function newSessionModalOpen()
{
    $("#modal_shade").css("display", "block");
    // $("body").css("overflow", "hidden");
    $("#new_session_modal").css("display", "block");


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
        <button><i class="material-icons">delete</i></button>

        <select class="form-control">
            <option>Sensor Node</option>
            <option>XX:XX:XX:XX:XX:XX</option>
        </select>

        <input type="text" class="form-control" placeholder="Sensor Node Location"/>
        <input type="text" class="form-control" placeholder="Start Time"/>
        <input type="text" class="form-control" placeholder="End Time"/>

        <span>Interval & Batch Size:</span>
        <select class="form-control">
            <option>1 Min</option>
            <option>2 Mins</option>
            <option>5 Mins</option>
            <option>10 Mins</option>
            <option>15 Mins</option>
            <option>20 Mins</option>
            <option>30 Mins</option>
        </select>
        <input type="number" min="1" max="127" value="1" class="form-control"/>
    </div>`;

    $("#session-node-rows").append(TEMPLATE);
}

function newSessionModalSave()
{
    var json = `{
            "name": "Session name",
            "description": "Session description",

            "nodes":
            [
                { "node": 2, "location": "Node location", "startTime": "2020-02-04 14:58:00", "endTime": "2020-03-04 14:58:00", "interval": 2, "batchSize": 10 }
            ]
        }`;

    $.post({
        url: "data/add-session.php",
        ContentType: "application/json",
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