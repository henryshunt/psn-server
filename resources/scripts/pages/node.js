var graphs = [];

$(window).on("load", () =>
{
    loadNodeInfo();

    loadGraph("temperature_graph", "airt");
    $("#temperature_graph_group").css("display", "block");
    loadGraph("humidity_graph", "relh");
    $("#humidity_graph_group").css("display", "block");
});


function loadNodeInfo()
{
    var url = "data/get-node-info.php?nodeId=" +
        getQueryStringValue("id") + "&sessionId=" + getQueryStringValue("session");

    $.getJSON(url, (data) =>
    {
        if (data !== false)
        {
            if (data !== null)
            {
                $("#node-location").html(data["location"]);
                $("#node-info-group").css("display", "block");
            } else $("#main").prepend(NO_DATA_HTML);
        } else $("#main").prepend(ERROR_HTML);
    }).fail(() => $("#main").prepend(ERROR_HTML));
}


function loadGraph(graphElementId, fields)
{
    var options =
    {
        showPoint: false, lineSmooth: false, height: 400,
        chartPadding: { right: 1, top: 1, left: 0, bottom: 0 },

        axisY: {
            offset: 23, onlyInteger: true,
            labelInterpolationFnc: (value) => { return round(value, 1); }
        },

        axisX: {
            type: Chartist.FixedScaleAxis, divisor: 8,
            labelInterpolationFnc: (value) =>
            { return moment.unix(value).utc().tz(configTimeZone).format("HH:mm"); }
        }
    };

    var responsiveOptions =
    [
        ["screen and (max-width: 900px)", { height: 300 }],
        ["screen and (max-width: 650px)", { height: 200 }]
    ];

    var graph = new Chartist.Line("#" + graphElementId, null, options, responsiveOptions);
    graphs.push({ id: graphElementId, data: { graph: graph, fields: fields }});
    loadGraphData(graph, fields);
}

function loadGraphData(graphObject, fields)
{
    var endTime = moment.utc().tz(configTimeZone);
    var startTime = moment(endTime).subtract({ hours: 24 });

    var url = "data/get-graph.php?nodeId=" + getQueryStringValue("id") +
        "&sessionId=" + getQueryStringValue("session") +
        "&start=" + startTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]") +
        "&end=" + endTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]") + "&fields=" + fields;

    // Draw new graph
    var options = graphObject.options;
    options.axisX.low = startTime.unix();
    options.axisX.high = endTime.unix();

    $.getJSON(url, (response) =>
    {
        if (response !== "1")
            graphObject.update({ series: response }, options);
        else requestError();

    }).fail(requestError = () =>
    { graphObject.update({ series: null }, options); });
}