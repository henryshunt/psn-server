<div class="modal modal--hidden" id="addnode-modal">
    <div class="modal__header">
        <p class="modal__title">Add a Sensor Node to the Project</p>
    </div>

    <div class="modal__content">
        <form id="m-addnode-form">
            <p class="modal__field-title">Sensor Node</p>
            <select class="m-addnode-node" id="m-addnode-node" disabled>
                <option>Select</option>
            </select>

            <p class="modal__field-title">Description of Location</p>
            <input class="m-addnode-location" id="m-addnode-location" type="text" maxlength="128" disabled/>

            <p class="modal__field-title">Reporting Interval</p>
            <select class="m-addnode-interval" id="m-addnode-interval" disabled>
                <option value="1">1 Minute</option>
                <option value="2">2 Minutes</option>
                <option value="5">5 Minutes</option>
                <option value="10">10 Minutes</option>
                <option value="15">15 Minutes</option>
                <option value="20">20 Minutes</option>
                <option value="30">30 Minutes</option>
            </select>

            <p class="modal__field-title">End Reporting At (Leave Empty for No End)</p>
            <input class="m-addnode-end" id="m-addnode-end" type="text" disabled/>

            <p class="modal__field-title">Transmit Reports in Batches Of</p>
            <input class="m-addnode-batch" id="m-addnode-batch" type="number" min="1" max="127" value="1" disabled/>
        </form>
    </div>

    <div class="modal__footer">
        <p class="modal__status" id="m-addnode-status"></p>

        <div class="modal__buttons">
            <button class="modal__submit" id="m-addnode-submit" form="m-addnode-form" disabled>Create</button>
            <button class="modal__cancel" id="m-addnode-cancel" disabled>Cancel</button>
        </div>
    </div>
</div>