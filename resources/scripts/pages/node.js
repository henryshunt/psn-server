let loadingCount = 0;
let sessionIsActive = false;
let sessionEndTime = null;
let dataTime = null;
let temperatureGraph = null;
let humidityGraph = null;

$(window).on("load", () =>
{
    loadNodeInfo(() =>
    {
        temperatureGraph = loadGraph("temperature-graph");
        $("#temperature-graph-group").css("display", "block");
        humidityGraph = loadGraph("humidity-graph");
        $("#humidity-graph-group").css("display", "block");

        if (sessionIsActive)
            dataTime = moment.utc().millisecond(0).second(0);
        else dataTime = dbTimeToUtc(sessionEndTime);
        loadData();
    });
});


function loadNodeInfo(onSuccess)
{
    loadingCount++;
    let url = "data/get-node-info.php?nodeId={0}&sessionId={1}".format(
        getQueryStringValue("id"), getQueryStringValue("session"));

    $.getJSON(url, (data) =>
    {
        if (data !== false && data !== null)
        {
            sessionIsActive = data["is_active"];
            sessionEndTime = data["end_time"];

            $("#node-location").html(data["location"]);
            $("#node-session").html("Part of the '" + data["session_name"] + "' session");

            let optionsString = "From {0}{1} (reporting every {2} minutes in batches of {3})";

            let startTime = dbTimeToLocal(data["start_time"]).format("DD/MM/YYYY HH:mm");
            let endTime = "";
            if (data["end_time"] !== null)
                endTime = " to " + dbTimeToLocal(data["end_time"]).format("DD/MM/YYYY HH:mm");
            else endTime = ", indefinitely";

            $("#node-options").html(optionsString.format(startTime, endTime, data["interval"],
                data["batch_size"]));

            if (!data["is_active"])
                $("#button-stop").attr("disabled", true);

            $("#node-info-group").css("display", "block");
            loadingCount--;
            onSuccess();
        }
        else
        {
            $("#main").prepend(ERROR_HTML);
            loadingCount--;
        }
        
    }).fail(() =>
    {
        $("#main").prepend(ERROR_HTML);
        loadingCount--;
    });
}

function loadGraph(targetElementId)
{
    let options =
    {
        showPoint: false, lineSmooth: false, height: 400,
        chartPadding: { right: 1, top: 1, left: 0, bottom: 0 },

        axisX:
        {
            type: Chartist.FixedScaleAxis, divisor: 8,
            labelInterpolationFnc: (value) => {
                return moment.unix(value).utc().tz(configTimeZone).format("HH:mm");
            }
        },

        axisY:
        {
            offset: 23, onlyInteger: true,
            labelInterpolationFnc: (value) => { return round(value, 0); }
        }
    };

    let responsiveOptions =
    [
        ["screen and (max-width: 900px)", { height: 300 }],
        ["screen and (max-width: 650px)", { height: 200 }]
    ];

    return new Chartist.Line("#" + targetElementId, null, options, responsiveOptions);
}


function loadData()
{
    $("#time-machine-time").html(
        dataTime.clone().tz(configTimeZone).format("[Data on] DD/MM/YYYY [at] HH:mm"));
    $("#time-machine").css("display", "block");

    loadReports();
    loadGraphData(temperatureGraph, "airt");
    loadGraphData(humidityGraph, "relh");
}

function loadReports()
{
    loadingCount++;
    let url = "data/get-reports.php?nodeId={0}&sessionId={1}&time={2}&amount={3}".format(
        getQueryStringValue("id"), getQueryStringValue("session"),
        dataTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]"), 6);

    $.getJSON(url, (data) =>
    {
        $("#reports").empty();
        if (data !== false)
        {
            if (data !== null)
            {
                let TEMPLATE = `
                    <table>
                        <thead>
                            <tr>
                                <td>Time</td>
                                <td>Temperature</td>
                                <td>Humidity</td>
                                <td>Battery Voltage</td>
                            </tr>
                        </thead>
                        <tbody>{0}</tbody>
                    </table>`;
                
                let rowHtml = "";
                for (let i = 0; i < data.length; i++)
                {
                    rowHtml += "<tr><td>{0}</td><td>{1}</td><td>{2}</td><td>{3}</td></tr>".format(
                        dbTimeToLocal(data[i]["time"]).format("DD/MM/YYYY HH:mm"),
                        data[i]["airt"] === null ? "No Data" : round(data[i]["airt"], 1) + "°C",
                        data[i]["relh"] === null ? "No Data" : round(data[i]["relh"], 1) + "%",
                        data[i]["batv"] === null ? "No Data" : round(data[i]["batv"], 2) + "V");
                }

                $("#reports").append(TEMPLATE.format(rowHtml));
            } else $("#reports").append(NO_DATA_HTML);
        } else $("#reports").append(ERROR_HTML);

        $("#reports-group").css("display", "block");
        loadingCount--;

    }).fail(() =>
    {
        $("#reports").empty();
        $("#reports").append(ERROR_HTML);
        $("#reports-group").css("display", "block");
        loadingCount--;
    });
}

function loadGraphData(graphObject, dataField)
{
    loadingCount++;
    let endTime = moment(dataTime);
    let startTime = moment(endTime).subtract({ hours: 24 });

    let url = "data/get-node-graph.php?nodeId={0}&sessionId={1}&start={2}&end={3}&field={4}"
        .format(getQueryStringValue("id"), getQueryStringValue("session"),
        startTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]"),
        endTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]"), dataField);

    let options = graphObject.options;
    options.axisX.low = startTime.tz(configTimeZone).unix();
    options.axisX.high = endTime.tz(configTimeZone).unix();

    $.getJSON(url, (response) =>
    {
        if (response !== false && response !== null)
            graphObject.update({ series: response }, options);
        else graphObject.update({ series: null }, options);
        loadingCount--;

    }).fail(() =>
    {
        graphObject.update({ series: null }, options);
        loadingCount--;
    });
}


function timeMachineLeft()
{
    if (loadingCount > 0) return;
    dataTime.subtract({ hours: 6 });
    loadData();
}

function timeMachineRight()
{
    if (loadingCount > 0) return;
    dataTime.add({ hours: 6 });
    loadData();
}


function downloadDataClick()
{

}

function stopSessionNodeClick()
{
    if (confirm("This action will stop this sensor node from making any further reports to the session. Are you sure?"))
    {
        let url = "data/set-node-stop.php?sessionId=" +
            getQueryStringValue("session") + "&nodeId=" + getQueryStringValue("id");

        $.getJSON(url, (data) =>
        {
            if (data === true)
                window.location.reload();
            else alert("An error occured while completing the operation.");
        }).fail(() => alert("An error occured while completing the operation."));
    }
}

function deleteSessionNodeClick()
{
    if (confirm("This action will remove this sensor node from the session, and delete all reports that it produced. Are you sure?"))
    {
        let url = "data/del-session-node.php?sessionId=" +
            getQueryStringValue("session") + "&nodeId=" + getQueryStringValue("id");

        $.getJSON(url, (data) =>
        {
            if (data === true)
                window.location.href = "session.html?id=" + getQueryStringValue("session");
            else alert("An error occured while completing the operation.");
        }).fail(() => alert("An error occured while completing the operation."));
    }
}