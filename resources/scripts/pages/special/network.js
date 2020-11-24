window.addEventListener("load", () =>
{
    getJson("../api.php/nodes?project=true")
        .then((data) => loadNodesSuccess(data));
});

function loadNodesSuccess(data)
{
    for (const node of data)
    {
        const tr = document.createElement("tr");
        tr.className = "node";

        let name = node["macAddress"];
        // if (node["name"] !== null)
        //     name += " ({0})".format(node["name"]);

        if (node["currentProject"] !== null)
        {
            var location = node["currentProject"]["location"];

            if (node["currentProject"]["latestReport"] !== null)
                var time = node["currentProject"]["latestReport"]["time"];
            else var time = "No Report";
        }
        else
        {
            tr.classList.add("node--inactive");
            var location = "Inactive";
        }

        tr.innerHTML = "<td>{0}</td><td>{1}</td><td>{2}</td>".format(
            name.toUpperCase(), location, time);

        if (node["currentProject"] === null)
            var circleColour = "circle--inactive";
        else circleColour = "circle--active";

        tr.innerHTML += "<td><div class=\"circle {0}\"></div></td>".format(circleColour);

        document.getElementById("nodes-list").append(tr);
    }
}