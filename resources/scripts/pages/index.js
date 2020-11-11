window.addEventListener("load", () =>
{
    document.getElementById("new-project-form").addEventListener("submit", newProjectModalSubmit);
    document.getElementById("new-project-modal-cancel").addEventListener("click", newProjectModalClose);
    document.getElementById("new-project-modal-submit").addEventListener("click", newProjectModalSubmit);

    getJson("api.php/projects?mode=active")
        .then((data) => loadActiveProjectsSuccess(data))
        .catch(() => document.getElementById("active-projects").innerHTML = ERROR_HTML);
    
    getJson("api.php/projects?mode=completed")
        .then((data) => loadCompletedProjectsSuccess(data))
        .catch(() => document.getElementById("completed-projects").innerHTML = ERROR_HTML);
});


function loadActiveProjectsSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("active-projects").innerHTML = NO_DATA_HTML;
        return;
    }

    for (const project of data)
    {
        let dateRange = "From " + dbTimeToLocal(project["startAt"]).format("DD/MM/YYYY");

        if (project["endAt"] !== null)
            dateRange += " to " + dbTimeToLocal(project["endAt"]).format("DD/MM/YYYY");
        else dateRange += ", Indefinitely";

        if (project["nodeCount"] !== 1)
            var nodeCount = project["nodeCount"] + " Sensor Nodes";
        else var nodeCount = "1 Sensor Node";


        const item = document.createElement("div");
        item.className = "item"
        document.getElementById("active-projects").append(item);

        const link = document.createElement("a");
        link.href = "session.php?id=" + project["projectId"];
        link.innerHTML = "<span>{0}</span><br><span>{1}</span><span>{2}</span>".format(
            project["name"], dateRange, nodeCount);
        item.append(link);
    }
}

function loadCompletedProjectsSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("completed-projects").innerHTML = NO_DATA_HTML;
        return;
    }

    for (project of data)
    {
        let dateRange = "";
        if (project["startAt"] !== null)
        {
            dateRange += "From " + dbTimeToLocal(project["startAt"]).format("DD/MM/YYYY");
            dateRange += " to " + dbTimeToLocal(project["endAt"]).format("DD/MM/YYYY");
        }

        if (project["nodeCount"] !== 1)
            var nodeCount = project["nodeCount"] + " Sensor Nodes";
        else var nodeCount = "1 Sensor Node";

        
        const item = document.createElement("div");
        item.className = "item item-thin"
        document.getElementById("completed-projects").append(item);

        const link = document.createElement("a");
        link.href = "session.php?id=" + project["projectId"];
        link.innerHTML = "<span>{0}</span><br><span>{1}</span><span>{2}</span>".format(
            project["name"], dateRange, nodeCount);
        item.append(link);
    }
}


function newProjectModalOpen()
{
    document.getElementById("modal-shade").style.display = "block";
    document.getElementById("new-project-modal").style.display = "block";

    document.getElementById("new-project-name").value = "";
    document.getElementById("new-project-description").value = "";
    document.getElementById("new-project-status").innerText = "";
    document.getElementById("new-project-name").focus();
}

function newProjectModalClose()
{
    document.getElementById("modal-shade").style.display = "none";
    document.getElementById("new-project-modal").style.display = "none";
}

function newProjectModalSubmit(event)
{
    event.preventDefault();

    if (document.getElementById("new-project-name").value.length === 0)
    {
        document.getElementById("new-project-status").innerText = "Name cannot be empty";
        return;
    }

    document.getElementById("new-project-name").disabled = true;
    document.getElementById("new-project-description").disabled = true;
    document.getElementById("new-project-modal-submit").disabled = true;
    document.getElementById("new-project-modal-cancel").disabled = true;
    document.getElementById("new-project-status").innerText = "";

    const enableForm = () => 
    {
        document.getElementById("new-project-name").disabled = false;
        document.getElementById("new-project-description").disabled = false;
        document.getElementById("new-project-modal-submit").disabled = false;
        document.getElementById("new-project-modal-cancel").disabled = false;
    }


    let project = { name: document.getElementById("new-project-name").value };

    if (document.getElementById("new-project-description").value.length > 0)
        project.description = document.getElementById("new-project-description").value;

    postJson("api.php/projects", JSON.stringify(project))
        .then((data) => window.location.href = "session.php?id=" + data["projectId"])
        .catch((data) =>
        {
            enableForm();
            if (data !== null && data.error === "name is not unique")
            {
                document.getElementById("new-project-status").innerText =
                    "A project with that name already exists";
            }
            else
            {
                document.getElementById("new-project-status").innerText =
                    "Error creating project";
            }
        });
}