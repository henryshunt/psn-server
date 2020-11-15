let editProjectModalData = { };
let addNodeModalData = { };

window.addEventListener("load", () =>
{
    document.getElementById("edit-project-btn").addEventListener("click",
        () => editProjectModal.open(editProjectModalData)
    );

    document.getElementById(
        "download-data-btn").addEventListener("click", onDownloadDataClick);
    document.getElementById(
        "stop-project-btn").addEventListener("click", onStopProjectClick);
    document.getElementById(
        "delete-project-btn").addEventListener("click", onDeleteProjectClick);

    document.getElementById("add-node-btn").addEventListener("click",
        () => addNodeModal.open(addNodeModalData)
    );


    const showAllErrors = () =>
    {
        document.getElementById("main").insertAdjacentHTML("afterbegin", ERROR_HTML);
        document.getElementById(
            "project-info-group").classList.remove("info-group--hidden");

        document.getElementById("active-nodes").innerHTML = ERROR_HTML;
        document.getElementById(
            "active-nodes-group").classList.remove("titled-group--hidden");

        document.getElementById("completed-nodes").innerHTML = ERROR_HTML;
        document.getElementById(
            "completed-nodes-group").classList.remove("titled-group--hidden");
    };

    if (getQueryStringValue("id") === null)
        showAllErrors();
    
    getJson("api.php/projects/" + getQueryStringValue("id"))
        .then((data) =>
            {
                loadProjectInfoSuccess(data);
                
                let url = "api.php/projects/{0}/nodes?mode=active&report=true";

                getJson(url.format(getQueryStringValue("id")))
                    .then((data) => loadActiveNodesSuccess(data))

                    .catch(() =>
                        {
                            document.getElementById("active-nodes").innerHTML = ERROR_HTML;
                            document.getElementById(
                                "active-nodes-group").classList.remove("titled-group--hidden");
                        }
                    );
                
                url = "api.php/projects/{0}/nodes?mode=completed";
                
                getJson(url.format(getQueryStringValue("id")))
                    .then((data) => loadCompletedNodesSuccess(data))
                    
                    .catch(() =>
                        {
                            document.getElementById("completed-nodes").innerHTML = ERROR_HTML;
                            document.getElementById(
                                "completed-nodes-group").classList.remove("titled-group--hidden");
                        }
                    );
            }
        )
        .catch(showAllErrors);
});

function loadProjectInfoSuccess(data)
{
    editProjectModalData.projectId = parseInt(getQueryStringValue("id"));
    editProjectModalData.name = data["name"];
    editProjectModalData.description = data["description"];
    addNodeModalData.projectId = parseInt(getQueryStringValue("id"));

    document.getElementById("project-name").innerText = data["name"];

    if (data["description"] === null)
        document.getElementById("project-desc").innerText = "No Description Available";
    else document.getElementById("project-desc").innerText = data["description"];

    if (data["isActive"])
    {
        document.getElementById(
            "stop-project-btn").classList.remove("info-group__action--hidden");
    }

    document.getElementById(
        "project-info-group").classList.remove("info-group--hidden");
}

function loadActiveNodesSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("active-nodes").innerHTML = NO_DATA_HTML;
        document.getElementById(
            "active-nodes-group").classList.remove("titled-group--hidden");
        return;
    }

    const reportDataHtml = "<div><p>{0}</p><p>{1}</p></div>";
    
    for (const node of data)
    {
        const item = document.createElement("div");
        item.className = "items-block__item";
        document.getElementById("active-nodes").append(item);

        const anchor = document.createElement("a");
        anchor.className = "item__anchor";
        anchor.href = "node.php?id={0}&project={1}".format(node["nodeId"],
            getQueryStringValue("id"));
        item.append(anchor);

        const nodeLocation = document.createElement("p");
        nodeLocation.className = "item__title";
        nodeLocation.innerText = node["location"];
        anchor.append(nodeLocation);

        var reportTime = "";
        var reportData = "";

        // Display data from the latest report for the node
        if (node["latestReport"] !== null)
        {
            reportTime = "Latest Report on " +
                dbTimeToLocal(node["latestReport"]["time"]).format("DD/MM/YYYY [at] HH:mm");

            if (node["latestReport"]["airt"] !== null)
            {
                reportData += reportDataHtml.format(
                    "Temp.", round(node["latestReport"]["airt"], 1) + "°C");
            }
            else reportData += reportDataHtml.format("Temp.", "None");

            if (node["latestReport"]["relh"] !== null)
            {
                reportData += reportDataHtml.format(
                    "Humid.", round(node["latestReport"]["relh"], 1) + "%");
            }
            else reportData += reportDataHtml.format("Humid.", "None");
        }
        else reportTime = "No Latest Report";

        const nodeReport = document.createElement("p");
        nodeReport.className = "item__sub-title";
        nodeReport.innerText = reportTime;
        anchor.append(nodeReport);

        const nodeData = document.createElement("data");
        nodeData.className = "item__corner item__corner--node";
        nodeData.innerHTML = reportData;
        anchor.append(nodeData);
    }

    document.getElementById(
        "active-nodes-group").classList.remove("titled-group--hidden");
}

function loadCompletedNodesSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("completed-nodes").innerHTML = NO_DATA_HTML;
        document.getElementById(
            "completed-nodes-group").classList.remove("titled-group--hidden");
        return;
    }

    for (const node of data)
    {
        const item = document.createElement("div");
        item.className = "items-block__item items-block__item--thin";
        document.getElementById("completed-nodes").append(item);

        const anchor = document.createElement("a");
        anchor.className = "item__anchor";
        anchor.href = "node.php?id={0}&project={1}".format(node["nodeId"],
            getQueryStringValue("id"));
        item.append(anchor);

        const nodeLocation = document.createElement("p");
        nodeLocation.className = "item__title";
        nodeLocation.innerText = node["location"];
        anchor.append(nodeLocation);
    }

    document.getElementById(
        "completed-nodes-group").classList.remove("titled-group--hidden");
}


function onDownloadDataClick()
{
    // window.open("data/get-session-download.php?sessionId=" +
    //     getQueryStringValue("id"));
}

function onDeleteProjectClick()
{
    if (confirm("Are you sure you want to delete the project? " +
        "This will also delete all reports produced by the sensor nodes inside it."))
    {
        deleteReq("api.php/projects/" + getQueryStringValue("id"))
            .then(() => window.location.href = "index.php")
            .catch(() => alert("An error occured while deleting the project."));
    }
}

function onStopProjectClick()
{
    if (confirm("Are you sure you want to stop the project?"))
    {
        deleteReq("api.php/projects/{0}?stop=true" + this.getQueryStringValue("id"))
            .then(() => window.location.reload())
            .catch(() => alert("An error occured while stopping the project."));
    }
}