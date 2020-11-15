window.addEventListener("load", () =>
{
    document.getElementById("new-project-btn").addEventListener("click",
        () => newProjectModal.open()
    );

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
        anchor.href = "project.php?id=" + project["projectId"];
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
        corner.className = "item__corner";
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
        anchor.href = "project.php?id=" + project["projectId"];
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
        corner.className = "item__corner";
        corner.innerText = nodeCount;
        anchor.append(corner);
    }

    document.getElementById(
        "completed-projects-group").classList.remove("titled-group--hidden");
}