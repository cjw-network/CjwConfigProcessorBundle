class ParameterDisplay {

    nodeArray;

    cleanUpList() {
        const topNodes = document.querySelectorAll(".param_list > .param_list_items");

        for (const topNodeEntry of topNodes) {
            topNodeEntry.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.getListEntryNodes(event.currentTarget);
            };

            const dontDisplayChildNodes = topNodeEntry.querySelectorAll(".param_list_items, .param_list_values");

            for (const dontDisplayNode of dontDisplayChildNodes) {
                dontDisplayNode.style.display = "none";
            }
        }
    }

    getListEntryNodes(targetNode) {
        if (targetNode && targetNode.children.length > 0) {

            const furtherEntries = targetNode.querySelectorAll(".param_list_items, .param_list_values");

            for (const entry of furtherEntries) {
                entry.style.display = "";

                for (const child of entry.children) {
                    child.style.display = "none";
                }

                let paddingLeft = targetNode.style.paddingLeft.replace("px","");

                entry.style.paddingLeft += `${parseInt(paddingLeft)+10}px`;
                entry.onclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    this.getListEntryNodes(event.currentTarget);
                }
            }
        }
    }
}
