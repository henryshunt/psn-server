var ERROR_HTML = "<div class='message_item'><span>Error Getting Data</span></div>";
var NO_DATA_HTML = "<div class='message_item'><span>Nothing Here</span></div>";

window.onload = function()
{
    // Load currently active sessions
    $.ajax({
        dataType: "json", url: "data/get-active-sessions.php",
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
                        var session_dates = "From ";
                        var start_time = moment.utc(data[i]["start_time"], "YYYY-MM-DD HH:mm:ss");
                        session_dates += start_time.tz("Europe/London").format("DD/MM/YYYY");

                        if (data[i]["end_time"] !== null)
                        {
                            var end_time = moment.utc(data[i]["end_time"], "YYYY-MM-DD HH:mm:ss");
                            session_dates += " to ";
                            session_dates += end_time.tz("Europe/London").format("DD/MM/YYYY");
                        } else session_dates += ", Indefinitely";

                        if (data[i]["node_count"] != 1)
                            var node_count = data[i]["node_count"] + " Sensor Nodes";
                        else var node_count = "1 Sensor Node";

                        html += format.format(data[i]["session_id"],data[i]["name"],
                            session_dates, node_count);
                    }

                    $("#active_sessions").append(html);
                } else $("#active_sessions").append(NO_DATA_HTML);
            } else $("#active_sessions").append(ERROR_HTML);

            $("#active_sessions_group").css("display", "block");
        },

        error: requestError = () => {
            $("#active_sessions").append(ERROR_HTML);
            $("#active_sessions_group").css("display", "block");
        }
    });

    // Load completed sessions
    $.ajax({
        dataType: "json", url: "data/get-completed-sessions.php",
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
                        var session_dates = "";
                        if (data[i]["start_time"] !== null)
                        {
                            session_dates += "From ";
                            var start_time = moment.utc(data[i]["start_time"], "YYYY-MM-DD HH:mm:ss");
                            session_dates += start_time.tz("Europe/London").format("DD/MM/YYYY");

                            var end_time = moment.utc(data[i]["end_time"], "YYYY-MM-DD HH:mm:ss");
                            session_dates += " to ";
                            session_dates += end_time.tz("Europe/London").format("DD/MM/YYYY");
                        }

                        if (data[i]["node_count"] != 1)
                            var node_count = data[i]["node_count"] + " Sensor Nodes";
                        else var node_count = "1 Sensor Node";

                        html += format.format(data[i]["session_id"], data[i]["name"],
                            session_dates, node_count);
                    }

                    $("#completed_sessions").append(html);
                } else $("#completed_sessions").append(NO_DATA_HTML);
            } else $("#completed_sessions").append(ERROR_HTML);

            $("#completed_sessions_group").css("display", "block");
        },

        error: requestError = () => {
            $("#completed_sessions").append(ERROR_HTML);
            $("#completed_sessions_group").css("display", "block");
        }
    });
};