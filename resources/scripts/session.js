var ERROR_HTML = "<div class='message_item'><span>Error Getting Data</span></div>";
var NO_DATA_HTML = "<div class='message_item'><span>Nothing Here</span></div>";

window.onload = function()
{
    if (getQueryStringValue("id") === null)
    {
        $("#main").prepend(ERROR_HTML);
        return;
    }

    // Load currently active nodes
    $.ajax({
        dataType: "json", url: "data/get-active-nodes.php?id=" + this.getQueryStringValue("id"),
        success: function(data)
        {
            if (data !== false)
            {
                var format = `<div class='group_item'><a href='node.html?id={0}&session={1}'>
                    <span>{2}</span><br><span>{3}</span><div class='node_data'>{4}</div></a></div>`;
                var report_format = `<div><span>{0}</span><br><span>{1}</span></div>`;

                if (data !== null)
                {
                    var html = "";
                    for (var i = 0; i < data.length; i++)
                    {
                        var report_time = "Latest Report: ";
                        var report_data = "";

                        if (data[i]["latest_report"] !== null)
                        {
                            var time = moment.utc(data[i]["latest_report"]["time"], "YYYY-MM-DD HH:mm:ss");
                            report_time += time.tz("Europe/London").format("DD/MM/YYYY [at] HH:mm");

                            if (data[i]["latest_report"]["airt"] !== null)
                            {
                                report_data += report_format.format(
                                    "Temp.", round(data[i]["latest_report"]["airt"], 1) + "°C");
                            } else report_data += report_format.format("Temp.", "None");

                            if (data[i]["latest_report"]["relh"] !== null)
                            {
                                report_data += report_format.format(
                                    "Humid.", round(data[i]["latest_report"]["relh"], 1) + "%");
                            } else report_data += report_format.format("Humid.", "None");

                            if (data[i]["latest_report"]["batv"] !== null)
                            {
                                report_data += report_format.format(
                                    "Battery", round(data[i]["latest_report"]["batv"], 2) + "V");
                            } else report_data += report_format.format("Battery", "None");
                        }
                        
                        html += format.format(data[i]["node_id"], getQueryStringValue("id"),
                            data[i]["location"], report_time, report_data);
                    }

                    $("#active_nodes").append(html);
                } else $("#active_nodes").append(NO_DATA_HTML);
            } else $("#active_nodes").append(ERROR_HTML);

            $("#active_nodes_group").css("display", "block");
        },

        error: requestError = () => {
            $("#active_nodes").append(ERROR_HTML);
            $("#active_nodes_group").css("display", "block");
        }
    });

    // Load completed nodes
    // $.ajax({
    //     dataType: "json", url: "data/get-completed-sessions.php",
    //     success: function(data)
    //     {
    //         if (data !== false)
    //         {
    //             var format = `<div class='group_item'><a href='session.php?id={0}'>
    //                 <span>{1}</span><br><span>{2}</span><span>{3}</span></a></div>`;

    //             if (data !== null)
    //             {
    //                 var html = "";
    //                 for (var i = 0; i < data.length; i++)
    //                 {
    //                     var session_dates = "";
    //                     if (data[i]["start_time"] !== null)
    //                     {
    //                         session_dates += "From ";
    //                         var start_time = moment.utc(data[i]["start_time"], "YYYY-MM-DD HH:mm:ss");
    //                         session_dates += start_time.tz("Europe/London").format("DD/MM/YYYY");

    //                         var end_time = moment.utc(data[i]["end_time"], "YYYY-MM-DD HH:mm:ss");
    //                         session_dates += " to ";
    //                         session_dates += end_time.tz("Europe/London").format("DD/MM/YYYY");
    //                     }

    //                     if (data[i]["node_count"] != 1)
    //                         var node_count = data[i]["node_count"] + " Sensor Nodes";
    //                     else var node_count = "1 Sensor Node";

    //                     html += format.format(data[i]["session_id"], data[i]["name"],
    //                         session_dates, node_count);
    //                 }

    //                 $("#completed_sessions").append(html);
    //             } else $("#completed_sessions").append(NO_DATA_HTML);
    //         } else $("#completed_sessions").append(ERROR_HTML);

    //         $("#completed_sessions_group").css("display", "block");
    //     },

    //     error: requestError = () => {
    //         $("#completed_sessions").append(ERROR_HTML);
    //         $("#completed_sessions_group").css("display", "block");
    //     }
    // });
};