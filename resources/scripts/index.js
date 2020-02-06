var ERROR_HTML = "<div class='message_item'><span>Error Getting Data</span></div>";
var NO_DATA_HTML = "<div class='message_item'><span>Nothing Here</span></div>";

window.onload = function()
{
    // Load currently active sessions
    $.ajax({
        url: "data/get-active-sessions.php",
        dataType: "json",

        success: function(data)
        {
            if (data !== false)
            {
                var format = `<div class='group_item'><a href='session.html?id={0}'>
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

                        html += format.format(data[i]["session_id"], data[i]["name"],
                            date_range, node_count);
                    }

                    $("#active_sessions").append(html);
                } else $("#active_sessions").append(NO_DATA_HTML);
            } else $("#active_sessions").append(ERROR_HTML);

            $("#active_sessions_group").css("display", "block");
        },

        error: () => {
            $("#active_sessions").append(ERROR_HTML);
            $("#active_sessions_group").css("display", "block");
        }
    });

    // Load completed sessions
    $.ajax({
        url: "data/get-completed-sessions.php",
        dataType: "json",
        
        success: function(data)
        {
            if (data !== false)
            {
                var format = `<div class='group_item'><a href='session.html?id={0}'>
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

                        html += format.format(data[i]["session_id"], data[i]["name"],
                        date_range, node_count);
                    }

                    $("#completed_sessions").append(html);
                } else $("#completed_sessions").append(NO_DATA_HTML);
            } else $("#completed_sessions").append(ERROR_HTML);

            $("#completed_sessions_group").css("display", "block");
        },

        error: () => {
            $("#completed_sessions").append(ERROR_HTML);
            $("#completed_sessions_group").css("display", "block");
        }
    });
};


function new_session_overlay_open()
{
    $("#overlay_shade").css("display", "block");
    $("#new_session_overlay").css("display", "block");
}

function new_session_overlay_close()
{
    $("#overlay_shade").css("display", "none");
    $("#new_session_overlay").css("display", "none");
}

function new_session_overlay_save()
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

    new_session_overlay_close();
}

function new_session_overlay_cancel()
{
    new_session_overlay_close();
}