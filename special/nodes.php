<div id="new-node-modal" class="modal" style="display: none">
    <div class="modal-header">
        <span>Add a New Sensor Node to the Network</span>
        <button onclick="newNodeModalClose()">
            <i class="material-icons">close</i>
        </button>
    </div>

    <div class="modal-content">
        <p>Enter the MAC address of the sensor node exactly as displayed at the top of the node administrator program.</p>

        <form>
            <input id="new-node-address" type="text" placeholder="Sensor Node MAC Address"/>
        </form>
    </div>

    <div class="modal-footer">
        <button onclick="newNodeModalSave()">Save</button>
        <button onclick="newNodeModalClose()">Cancel</button>
    </div>
</div>


function newNodeModalOpen()
{
    $("#modal-shade").css("display", "block");
    $("#new-node-modal").css("display", "block");

    // Reset form
    $("#new-node-address").val("");
}

function newNodeModalClose()
{
    $("#modal-shade").css("display", "none");
    $("#new-node-modal").css("display", "none");
}

function newNodeModalSave()
{
    if ($("#new-node-address").val() === "")
    {
        alert("You must enter a MAC address.");
        return;
    }

    $.post({
        url: "api.php/nodes?inactive=true",
        data: { "mac_address": $("#new-node-address").val() },
        ContentType: "application/json",

        success: () => newNodeModalClose(),
        error: () => alert("Error while adding the sensor node. Are you sure a node with this address does not already exist?")
    });
}