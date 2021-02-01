let loadingCount = 0;
let sessionIsActive = false;
let sessionEndTime = null;
let dataTime = null;
let temperatureGraph = null;
let humidityGraph = null;

window.addEventListener("load", () =>
{
    let url = "api.php/projects/{0}/nodes/{1}".format(
        getQueryStringValue("project"), getQueryStringValue("id"));

    getJson(url).then((data) => loadNodeInfoSuccess(data)).catch();

    // loadNodeInfo(() =>
    // {
    //     loadNodeAlarms();
    //     temperatureGraph = initialiseGraph("temperature-graph");
    //     $("#temperature-graph-group").css("display", "block");
    //     humidityGraph = initialiseGraph("humidity-graph");
    //     $("#humidity-graph-group").css("display", "block");

    //     if (sessionIsActive)
    //         dataTime = moment.utc().millisecond(0).second(0);
    //     else dataTime = dbTimeToUtc(sessionEndTime);
    //     loadData();
    // });

    url += "/reports?mode=chart";

    getJson(url + "&columns=airt")
        .then((data) =>
        {
            new Chart(document.getElementById("temperature-graph").getContext("2d"),
            {
                type: "scatter",

                data:
                {
                    datasets:
                    [{
                        borderColor: 'rgb(255, 99, 132)',
                        data: data,
                        showLine: true,
                        pointRadius: 0,
                        pointHitRadius: 30,
                        pointHoverBackgroundColor: "rgba(0,0,0,0)",
                        pointHoverBorderColor: "rgba(0,0,0,0)",
                        borderColor: "rgb(195, 39, 40)",
                        borderWidth: 2
                    }]
                },

                options:
                {
                    legend: { display: false },
                    animation: { duration: 0 },
                    responsive: true,

                    scales:
                    {
                        xAxes: [{ type: "time" }],
                    }
                }
            });
        })

        .catch(() => console.log("error"));

    getJson(url + "&columns=relh")
        .then((data) =>
        {
            new Chart(document.getElementById("humidity-graph").getContext("2d"),
            {
                type: "scatter",

                data:
                {
                    datasets:
                    [{
                        borderColor: 'rgb(255, 99, 132)',
                        data: data,
                        showLine: true,
                        pointRadius: 0,
                        pointHitRadius: 30,
                        pointHoverBackgroundColor: "rgba(0,0,0,0)",
                        pointHoverBorderColor: "rgba(0,0,0,0)",
                        borderColor: "rgb(195, 39, 40)",
                        borderWidth: 2
                    }]
                },

                options:
                {
                    legend: { display: false },
                    animation: { duration: 0 },
                    responsive: true,

                    scales:
                    {
                        xAxes: [{ type: "time" }],
                    }
                }
            });
        })

        .catch(() => console.log("error"));
});


function loadNodeInfoSuccess(data)
{
    if (data["isActive"])
    {
        document.getElementById(
            "stop-node-btn").classList.remove("info-group__action--hidden");
    }

    document.getElementById(
        "node-info-group").classList.remove("info-group--hidden");

    loadReports();
}

function loadNodeAlarms()
{
    let url = "data/get-node-alarms.php?nodeId={0}&sessionId={1}".format(
        getQueryStringValue("id"), getQueryStringValue("session"));

    $.getJSON(url, (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
            {
                const TEMPLATE = `
                    <div class="item item-thin">
                        <a>
                            <span>{0}</span>
                            <br>
                            <span>Safe Value Range: {1} - {2}</span>
                        </a>
                    </div>`;

                let html = "";
                for (let i = 0; i < data.length; i++)
                {
                    let parameter = "";
                    switch (data[i]["parameter"])
                    {
                        case "airt": parameter = "Temperature"; break;
                        case "relh": parameter = "Humidity"; break;
                        case "batv": parameter = "Battery Voltage"; break;
                    }

                    html += TEMPLATE.format(
                        parameter, data[i]["minimum"], data[i]["maximum"]);
                }

                $("#alarms").append(html);
            } else $("#alarms").append(NO_DATA_HTML);
        } else $("#alarms").append(ERROR_HTML);

        $("#alarms-group").css("display", "block");

    }).fail(() =>
    {
        $("#alarms").append(ERROR_HTML);
        $("#alarms-group").css("display", "block");
    });
}

function initialiseGraph(targetElementId)
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

    // Initialise a new line graph with the specified options
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
    let url = "api.php/projects/{0}/nodes/{1}/reports?mode=latest&limit=6".format(
        getQueryStringValue("project"), getQueryStringValue("id"));

    // let url = "data/get-reports.php?nodeId={0}&sessionId={1}&time={2}&amount={3}".format(
    //     getQueryStringValue("id"), getQueryStringValue("session"),
    //     dataTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]"), 6);

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

    // Change the minimum and maximum of the X axis to fir the new data
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
    dataTime.subtract({ hours: 12 });
    loadData();
}

function timeMachineRight()
{
    if (loadingCount > 0) return;
    dataTime.add({ hours: 12 });
    loadData();
}


function downloadDataClick()
{
    window.open("data/get-node-download.php?sessionId=" +
        getQueryStringValue("session") + "&nodeId=" + getQueryStringValue("id"));
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
                window.location.href = "session.php?id=" + getQueryStringValue("session");
            else alert("An error occured while completing the operation.");
        }).fail(() => alert("An error occured while completing the operation."));
    }
}


function newAlarmModalOpen()
{
    $("#modal-shade").css("display", "block");
    $("#new-alarm-modal").css("display", "block");

    // Reset form
    $("#new-alarm-parameter").val("0");
    $("#new-alarm-minimum").val("");
    $("#new-alarm-maximum").val("");
}

function newAlarmModalClose()
{
    $("#modal-shade").css("display", "none");
    $("#new-alarm-modal").css("display", "none");
}

function newAlarmModalSave()
{
    let TEMPLATE = `{"sessionId":{0},"nodeId":{1},"parameter":"{2}","minimum":{3},"maximum":{4}}`;

    let emptyFields = false;
    let parameter = "";

    // Validate the entered minimum and maximum based on the parameter selected
    switch ($("#new-alarm-parameter").val())
    {
        case "0": emptyFields = true; break;
        case "1": // Temperature
        {
            parameter = "airt";
            let minimum = parseFloat($("#new-alarm-minimum").val());
            let maximum = parseFloat($("#new-alarm-maximum").val());

            if (isNaN(minimum) || isNaN(maximum) || maximum <= minimum)
                emptyFields = true;
            break;
        }
        case "2": // Humidity
        {
            parameter = "relh";
            let minimum = parseFloat($("#new-alarm-minimum").val());
            let maximum = parseFloat($("#new-alarm-maximum").val());

            if (!isNaN(minimum) && !isNaN(maximum))
            {
                if (minimum < 0 || maximum > 100 || maximum <= minimum)
                    emptyFields = true;
            } else emptyFields = true;
            break;
        }
        case "3": // Battery voltage
        {
            parameter = "batv";
            let minimum = parseFloat($("#new-alarm-minimum").val());
            let maximum = parseFloat($("#new-alarm-maximum").val());

            if (!isNaN(minimum) && !isNaN(maximum))
            {
                if (minimum < 0 || maximum > 5 || maximum <= minimum)
                    emptyFields = true;
            } else emptyFields = true;
            break;
        }
        default: emptyFields = true; break;
    }

    if (emptyFields === true)
    {
        alert("Cannot submit, one or more fields contain invalid values.");
        return;
    }

    let alarm = TEMPLATE.format(getQueryStringValue("session"), getQueryStringValue("id"),
        parameter, $("#new-alarm-minimum").val(), $("#new-alarm-maximum").val());

    $.post({
        url: "data/add-session-alarm.php",
        data: { "data": alarm },
        ContentType: "application/json",

        success: () => window.location.reload(),
        error: () => alert("Error while creating the alarm")
    });
}