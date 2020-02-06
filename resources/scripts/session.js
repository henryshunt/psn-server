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
        url: "data/get-active-nodes.php?session=" + this.getQueryStringValue("id"),
        dataType: "json",

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
                        var report_time = "";
                        var report_data = "";

                        // Display the data from the latest report from the node
                        if (data[i]["latest_report_id"] !== null)
                        {
                            report_time = "Latest Report on " + dbTimeToLocal(
                                data[i]["latest_report_id"]["time"]).format("DD/MM/YYYY [at] HH:mm");

                            if (data[i]["latest_report_id"]["airt"] !== null)
                            {
                                report_data += report_format.format("Temp.",
                                    round(data[i]["latest_report_id"]["airt"], 1) + "°C");
                            } else report_data += report_format.format("Temp.", "None");

                            if (data[i]["latest_report_id"]["relh"] !== null)
                            {
                                report_data += report_format.format("Humid.",
                                    round(data[i]["latest_report_id"]["relh"], 1) + "%");
                            } else report_data += report_format.format("Humid.", "None");

                            if (data[i]["latest_report_id"]["batv"] !== null)
                            {
                                report_data += report_format.format("Battery",
                                    round(data[i]["latest_report_id"]["batv"], 2) + "V");
                            } else report_data += report_format.format("Battery", "None");
                        } else report_time = "No Latest Report";
                        
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
    $.ajax({
        url: "data/get-completed-nodes.php?session=" + this.getQueryStringValue("id"),
        dataType: "json",
        
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
                        var report_time = "";
                        var report_data = "";

                        // Display the data from the latest report from the node
                        if (data[i]["latest_report_id"] !== null)
                        {
                            report_time = "Latest Report on " + dbTimeToLocal(
                                data[i]["latest_report_id"]["time"]).format("DD/MM/YYYY [at] HH:mm");

                            if (data[i]["latest_report_id"]["airt"] !== null)
                            {
                                report_data += report_format.format("Temp.",
                                    round(data[i]["latest_report_id"]["airt"], 1) + "°C");
                            } else report_data += report_format.format("Temp.", "None");

                            if (data[i]["latest_report_id"]["relh"] !== null)
                            {
                                report_data += report_format.format("Humid.",
                                    round(data[i]["latest_report_id"]["relh"], 1) + "%");
                            } else report_data += report_format.format("Humid.", "None");

                            if (data[i]["latest_report_id"]["batv"] !== null)
                            {
                                report_data += report_format.format("Battery",
                                    round(data[i]["latest_report_id"]["batv"], 2) + "V");
                            } else report_data += report_format.format("Battery", "None");
                        } else report_time = "No Latest Report";
                        
                        html += format.format(data[i]["node_id"], getQueryStringValue("id"),
                            data[i]["location"], report_time, report_data);
                    }

                    $("#completed_nodes").append(html);
                } else $("#completed_nodes").append(NO_DATA_HTML);
            } else $("#completed_nodes").append(ERROR_HTML);

            $("#completed_nodes_group").css("display", "block");
        },

        error: requestError = () => {
            $("#completed_sessions").append(ERROR_HTML);
            $("#completed_sessions_group").css("display", "block");
        }
    });
};