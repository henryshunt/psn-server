$(window).on("load", () =>
{
    if (getQueryStringValue("id") !== null)
    {
        loadActiveNodes();
        loadCompletedNodes();
    } else $("#main").prepend(ERROR_HTML);
});


function loadSessionInfo(activeNodeCount)
{
    url = "api.php/projects/" + this.getQueryStringValue("id");
    $.getJSON(url, (sessionData) =>
    {
        if (sessionData !== false)
        {
            if (sessionData !== null)
            {
                $("#session-name").html(sessionData["name"]);

                if (sessionData["description"] !== "" && sessionData["description"] !== null)
                    $("#session-description").html(sessionData["description"]);
                else $("#session-description").html("No Description Available");

                // Disable end session button if the session has already ended
                if (activeNodeCount === 0)
                    $("#button-stop").attr("disabled", true);

                $("#session-info-group").css("display", "block");
            }
            else
            {
                $("#main").prepend(ERROR_HTML);
                return false;
            }
        }
        else
        {
            $("#main").prepend(ERROR_HTML);
            return false;
        }

    }).fail(() => 
    {
        $("#main").prepend(ERROR_HTML);
        return false;
    });

    return true;
}

function loadActiveNodes()
{
    var url = "data/get-nodes-active.php?sessionId=" + this.getQueryStringValue("id");
    $.getJSON(url, (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
            {
                const REPORT_TEMPLATE = "<div><span>{0}</span><br><span>{1}</span></div>";
                const TEMPLATE = `
                    <div class="item">
                        <a href="node.php?id={0}&session={1}">
                        <span>{2}</span>
                        <br>
                        <span>{3}</span>
                        <div class="node-data">{4}</div>
                        </a>
                    </div>`;
                                    
                var html = "";
                for (var i = 0; i < data.length; i++)
                {
                    var report_time = "";
                    var report_data = "";

                    // Display data from the latest report for the node
                    if (data[i]["latest_report"] !== null)
                    {
                        report_time = "Latest Report on " + dbTimeToLocal(
                            data[i]["latest_report"]["time"]).format("DD/MM/YYYY [at] HH:mm");

                        if (data[i]["latest_report"]["airt"] !== null)
                        {
                            report_data += REPORT_TEMPLATE.format("Temp.",
                                round(data[i]["latest_report"]["airt"], 1) + "°C");
                        } else report_data += REPORT_TEMPLATE.format("Temp.", "None");

                        if (data[i]["latest_report"]["relh"] !== null)
                        {
                            report_data += REPORT_TEMPLATE.format("Humid.",
                                round(data[i]["latest_report"]["relh"], 1) + "%");
                        } else report_data += REPORT_TEMPLATE.format("Humid.", "None");
                    } else report_time = "No Latest Report";
                    
                    html += TEMPLATE.format(data[i]["node_id"], getQueryStringValue("id"),
                        data[i]["location"], report_time, report_data);
                }

                $("#active-nodes").append(html);
            } else $("#active-nodes").append(NO_DATA_HTML);

            // Load session info now since data gotten in this function informs how session
            // info displays
            loadSessionInfo(data === null ? 0 : data.length);
        }
        else
        {
            $("#active-nodes").append(ERROR_HTML);
            $("#main").prepend(ERROR_HTML);
        }

        $("#active-nodes-group").css("display", "block");

    }).fail(() =>
    {
        $("#active-nodes").append(ERROR_HTML);
        $("#active-nodes-group").css("display", "block");
        $("#main").prepend(ERROR_HTML);
    });
}

function loadCompletedNodes()
{
    var url = "data/get-nodes-completed.php?sessionId=" + this.getQueryStringValue("id");
    $.getJSON(url, (data) => {
        if (data !== false)
        {
            if (data !== null)
            {
                var html = "";
                for (var i = 0; i < data.length; i++)
                {
                    const TEMPLATE = `
                        <div class="item item-thin">
                            <a href="node.php?id={0}&session={1}">
                                <span>{2}</span>
                            </a>
                        </div>`;

                    html += TEMPLATE.format(data[i]["node_id"], getQueryStringValue("id"),
                        data[i]["location"]);
                }

                $("#completed-nodes").append(html);
            } else $("#completed-nodes").append(NO_DATA_HTML);
        } else $("#completed-nodes").append(ERROR_HTML);

        $("#completed-nodes-group").css("display", "block");

    }).fail(() =>
    {
        $("#completed-nodes").append(ERROR_HTML);
        $("#completed-nodes-group").css("display", "block")
    });
}

function downloadDataClick()
{
    window.open("data/get-session-download.php?sessionId=" +
        getQueryStringValue("id"));
}

function deleteSessionClick()
{
    if (confirm("This will delete the session and all reports produced by the nodes. Are you sure?"))
    {
        let url = "data/del-session.php?sessionId=" + this.getQueryStringValue("id");

        $.getJSON(url, (data) =>
        {
            if (data === true)
                window.location.href = "index.php";
            else alert("An error occured while completing the operation.");
        }).fail(() => alert("An error occured while completing the operation."));
    }
}

function stopSessionNow()
{
    if (confirm("This will delete the session and all reports produced by the nodes. Are you sure?"))
    {
        let url = "data/set-session-stop.php?sessionId=" + this.getQueryStringValue("id");

        $.getJSON(url, (data) =>
        {
            if (data === true)
                window.location.reload();
            else alert("An error occured while completing the operation.");
        }).fail(() => alert("An error occured while completing the operation."));
    }
}