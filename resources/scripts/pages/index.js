window.addEventListener("load", () =>
{
    document.getElementById("new-project-button").addEventListener("click",
        newProjectModalOpen);
    document.getElementById("newproj-modal-form").addEventListener("submit",
        newProjectModalSubmit);
    document.getElementById("newproj-modal-submit").addEventListener("click",
        newProjectModalSubmit);
    document.getElementById("newproj-modal-cancel").addEventListener("click",
        newProjectModalClose);

    // Load the active projects
    getJson("api.php/projects?mode=active")
        .then((data) => loadActiveProjectsSuccess(data))

        .catch(() =>
            {
                document.getElementById("active-projects").innerHTML = ERROR_HTML;
                document.getElementById(
                    "active-projects-group").classList.remove("titled-group--hidden");
            }
        );
    
    // Load the completed projects
    getJson("api.php/projects?mode=completed")
        .then((data) => loadCompletedProjectsSuccess(data))
        
        .catch(() =>
            {
                document.getElementById("completed-projects").innerHTML = ERROR_HTML;
                document.getElementById(
                    "completed-projects-group").classList.remove("titled-group--hidden");
            }
        );
});


function loadActiveProjectsSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("active-projects").innerHTML = NO_DATA_HTML;
        document.getElementById(
            "active-projects-group").classList.remove("titled-group--hidden");
        return;
    }

    for (const project of data)
    {
        const item = document.createElement("div");
        item.className = "items-block__item"
        document.getElementById("active-projects").append(item);

        const anchor = document.createElement("a");
        anchor.className = "item__anchor";
        anchor.href = "session.php?id=" + project["projectId"];
        item.append(anchor);

        const title = document.createElement("p");
        title.className = "item__title";
        title.innerText = project["name"];
        anchor.append(title);

        let dateRange = "From " + dbTimeToLocal(project["startAt"]).format("DD/MM/YYYY");

        if (project["endAt"] !== null)
            dateRange += " to " + dbTimeToLocal(project["endAt"]).format("DD/MM/YYYY");
        else dateRange += ", Indefinitely";

        if (project["nodeCount"] !== 1)
            var nodeCount = project["nodeCount"] + " Sensor Nodes";
        else var nodeCount = "1 Sensor Node";

        const subTitle = document.createElement("p");
        subTitle.className = "item__sub-title";
        subTitle.innerText = dateRange;
        anchor.append(subTitle);

        const corner = document.createElement("p");
        corner.className = "item__corner item__corner--index";
        corner.innerText = nodeCount;
        anchor.append(corner);
    }

    document.getElementById(
        "active-projects-group").classList.remove("titled-group--hidden");
}

function loadCompletedProjectsSuccess(data)
{
    if (data.length === 0)
    {
        document.getElementById("completed-projects").innerHTML = NO_DATA_HTML;
        document.getElementById(
            "completed-projects-group").classList.remove("titled-group--hidden");
        return;
    }

    for (project of data)
    {
        const item = document.createElement("div");
        item.className = "items-block__item"
        document.getElementById("completed-projects").append(item);

        const anchor = document.createElement("a");
        anchor.className = "item__anchor";
        anchor.href = "session.php?id=" + project["projectId"];
        item.append(anchor);

        const title = document.createElement("p");
        title.className = "item__title";
        title.innerText = project["name"];
        anchor.append(title);

        if (project["nodeCount"] > 0)
        {
            let dateRange = "From " + dbTimeToLocal(project["startAt"]).format("DD/MM/YYYY");
            dateRange += " to " + dbTimeToLocal(project["endAt"]).format("DD/MM/YYYY");

            const subTitle = document.createElement("p");
            subTitle.className = "item__sub-title";
            subTitle.innerText = dateRange;
            anchor.append(subTitle);
        }

        if (project["nodeCount"] !== 1)
            var nodeCount = project["nodeCount"] + " Sensor Nodes";
        else var nodeCount = "1 Sensor Node";

        const corner = document.createElement("p");
        corner.className = "item__corner item__corner--index";
        corner.innerText = nodeCount;
        anchor.append(corner);
    }

    document.getElementById(
        "completed-projects-group").classList.remove("titled-group--hidden");
}


function newProjectModalOpen()
{
    document.getElementById("modal-shade").classList.remove("modal__shade--hidden");
    document.getElementById("newproj-modal").classList.remove("modal--hidden");

    document.getElementById("newproj-modal-name").value = "";
    document.getElementById("newproj-modal-desc").value = "";
    document.getElementById("newproj-modal-status").innerText = "";
    document.getElementById("newproj-modal-name").focus();
}

function newProjectModalClose()
{
    document.getElementById("modal-shade").classList.add("modal__shade--hidden");
    document.getElementById("newproj-modal").classList.add("modal--hidden");
}

function newProjectModalSubmit(event)
{
    // Prevent form submission from reloading the page
    event.preventDefault();

    if (document.getElementById("newproj-modal-name").value.length === 0)
    {
        document.getElementById("newproj-modal-status").innerText =
            "Name cannot be empty";
        return;
    }

    document.getElementById("newproj-modal-name").disabled = true;
    document.getElementById("newproj-modal-desc").disabled = true;
    document.getElementById("newproj-modal-submit").disabled = true;
    document.getElementById("newproj-modal-cancel").disabled = true;
    document.getElementById("newproj-modal-status").innerText = "";

    const reEnableForm = () => 
    {
        document.getElementById("newproj-modal-name").disabled = false;
        document.getElementById("newproj-modal-desc").disabled = false;
        document.getElementById("newproj-modal-submit").disabled = false;
        document.getElementById("newproj-modal-cancel").disabled = false;
    }


    let project = { name: document.getElementById("newproj-modal-name").value };

    if (document.getElementById("newproj-modal-desc").value.length > 0)
        project.description = document.getElementById("newproj-modal-desc").value;

    postJson("api.php/projects", JSON.stringify(project))
        .then((data) => window.location.href = "session.php?id=" + data["projectId"])

        .catch((data) =>
        {
            reEnableForm();

            if (data !== null && "error" in data &&
                data["error"] === "name is not unique within user")
            {
                document.getElementById("newproj-modal-status").innerText =
                    "A project with that name already exists";
                document.getElementById("newproj-modal-name").focus();
            }
            else
            {
                document.getElementById("newproj-modal-status").innerText =
                    "Error creating project";
            }
        });
}