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

    // if (getQueryStringValue("id") === null)
    //     showAllErrors();
});

function loadProjectInfoSuccess(data)
{
    editProjectModalData.projectId = parseInt(getQueryStringValue("id"));
    editProjectModalData.name = data["name"];
    editProjectModalData.description = data["description"];
    addNodeModalData.projectId = parseInt(getQueryStringValue("id"));
}


function onDownloadDataClick()
{
    // window.open("data/get-session-download.php?sessionId=" +
    //     getQueryStringValue("id"));
}

function onStopProjectClick()
{
    if (confirm("Are you sure you want to stop the project?"))
    {
        patchReq("api.php/projects/{0}?stop=true".format(getQueryStringValue("id")))
            .then(() => window.location.reload())
            .catch(() => alert("An error occured while stopping the project."));
    }
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