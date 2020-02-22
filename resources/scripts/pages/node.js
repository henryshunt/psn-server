var graphs = [];

$(window).on("load", () =>
{
    // Load node info
    $.ajax(
    {
        url: "data/get-node-info.php?nodeId=" + getQueryStringValue("id") + "&sessionId=" + getQueryStringValue("session"),
        dataType: "json",

        success: (data) =>
        {
            if (data !== false)
            {
                if (data !== null)
                {
                    const TEMPLATE = `
                        <div class="solid-group-main">
                            <div class="solid-group-left">
                                <span>{0}</span><br><span>Reports every {1} minutes | Uploads in Batches of {2}</span>
                            </div>
                        
                            <div class="solid-group-right">
                                <button>Download All Data</button>
                                <button>Stop Node Reporting Now</button>
                                <button class="last-item" disabled>Delete Node from Session</button>
                            </div>
                        </div>`;

                    $("#node-location").html(data["location"]);
                    // $("#session-description").html(data["description"]);
                    $("#node-info-group").css("display", "block");

                    // $("#main").prepend(TEMPLATE.format(data["location"], data["interval"], data["batch_size"]));
                } else $("#main").prepend(NO_DATA_HTML);
            } else $("#main").prepend(ERROR_HTML);
        },

        error: () =>
        {
            $("#main").prepend(ERROR_HTML);
        }
    });

    loadGraph("temperature_graph", "airt");
    $("#temperature_graph_group").css("display", "block");
    loadGraph("humidity_graph", "relh");
    $("#humidity_graph_group").css("display", "block");
});


function loadGraph(graphElementId, fields)
{
    var options =
    {
        showPoint: false, lineSmooth: false, height: 400, chartPadding: { right: 1, top: 1, left: 0, bottom: 0},

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
        [ "screen and (max-width: 900px)", { height: 300 } ],
        [ "screen and (max-width: 650px)", { height: 200 } ]
    ];

    var graph = new Chartist.Line("#" + graphElementId, null, options, responsiveOptions);
    graphs.push({ id: graphElementId, data: { graph: graph, fields: fields }});
    loadGraphData(graph, fields);
}

function loadGraphData(graphObject, fields)
{
    var endTime = moment.utc().tz(configTimeZone);
    var startTime = moment(endTime).subtract({ hours: 24 });

    var url = "data/get-graph.php?node=" + getQueryStringValue("id") + "&session=" + getQueryStringValue("session") +
        "&start=" + startTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]") + "&end=" + endTime.format("YYYY-MM-DD[T]HH:mm:ss[Z]") + "&fields=" + fields;

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