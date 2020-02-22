$(window).on("load", () =>
{
    if (getQueryStringValue("id") === null)
    {
        $("#main").prepend(ERROR_HTML);
        return;
    }

    const TEMPLATE = `<div class="item"><a href="node.html?id={0}&session={1}">
        <span>{2}</span><br><span>{3}</span><div class="node-data">{4}</div></a></div>`;
    const REPORT_TEMPLATE = "<div><span>{0}</span><br><span>{1}</span></div>";

    // Load session info
    $.ajax(
    {
        url: "data/get-session-info.php?session=" + this.getQueryStringValue("id"),
        dataType: "json",

        success: (data) =>
        {
            if (data !== false)
            {
                if (data !== null)
                {
                    $("#session-name").html(data["name"]);
                    $("#session-description").html(data["description"]);
                    $("#session-info-group").css("display", "block");
                } else $("#main").prepend(NO_DATA_HTML);
            } else $("#main").prepend(ERROR_HTML);
        },

        error: () =>
        {
            $("#main").prepend(ERROR_HTML);
        }
    });

    // Load currently active nodes
    $.ajax(
    {
        url: "data/get-active-nodes.php?session=" + this.getQueryStringValue("id"),
        dataType: "json",

        success: (data) =>
        {
            if (data !== false)
            {
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
                                report_data += REPORT_TEMPLATE.format("Temp.",
                                    round(data[i]["latest_report_id"]["airt"], 1) + "°C");
                            } else report_data += REPORT_TEMPLATE.format("Temp.", "None");

                            if (data[i]["latest_report_id"]["relh"] !== null)
                            {
                                report_data += REPORT_TEMPLATE.format("Humid.",
                                    round(data[i]["latest_report_id"]["relh"], 1) + "%");
                            } else report_data += REPORT_TEMPLATE.format("Humid.", "None");

                            if (data[i]["latest_report_id"]["batv"] !== null)
                            {
                                report_data += REPORT_TEMPLATE.format("Battery",
                                    round(data[i]["latest_report_id"]["batv"], 2) + "V");
                            } else report_data += REPORT_TEMPLATE.format("Battery", "None");
                        } else report_time = "No Latest Report";
                        
                        html += TEMPLATE.format(data[i]["node_id"], getQueryStringValue("id"),
                            data[i]["location"], report_time, report_data);
                    }

                    $("#active-nodes").append(html);
                } else $("#active-nodes").append(NO_DATA_HTML);
            } else $("#active-nodes").append(ERROR_HTML);

            $("#active-nodes-group").css("display", "block");
        },

        error: () =>
        {
            $("#active-nodes").append(ERROR_HTML);
            $("#active-nodes-group").css("display", "block");
        }
    });

    // Load completed nodes
    $.ajax(
    {
        url: "data/get-completed-nodes.php?session=" + this.getQueryStringValue("id"),
        dataType: "json",
        
        success: (data) =>
        {
            if (data !== false)
            {
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
                                report_data += REPORT_TEMPLATE.format("Temp.",
                                    round(data[i]["latest_report_id"]["airt"], 1) + "°C");
                            } else report_data += REPORT_TEMPLATE.format("Temp.", "None");

                            if (data[i]["latest_report_id"]["relh"] !== null)
                            {
                                report_data += REPORT_TEMPLATE.format("Humid.",
                                    round(data[i]["latest_report_id"]["relh"], 1) + "%");
                            } else report_data += REPORT_TEMPLATE.format("Humid.", "None");

                            if (data[i]["latest_report_id"]["batv"] !== null)
                            {
                                report_data += REPORT_TEMPLATE.format("Battery",
                                    round(data[i]["latest_report_id"]["batv"], 2) + "V");
                            } else report_data += REPORT_TEMPLATE.format("Battery", "None");
                        } else report_time = "No Latest Report";
                        
                        html += TEMPLATE.format(data[i]["node_id"], getQueryStringValue("id"),
                            data[i]["location"], report_time, report_data);
                    }

                    $("#completed-nodes").append(html);
                } else $("#completed-nodes").append(NO_DATA_HTML);
            } else $("#completed-nodes").append(ERROR_HTML);

            $("#completed-nodes-group").css("display", "block");
        },

        error: () =>
        {
            $("#completed-nodes").append(ERROR_HTML);
            $("#completed-nodes-group").css("display", "block");
        }
    });
});