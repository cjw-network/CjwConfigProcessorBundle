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
                this.setAppropriateOnClick(dontDisplayNode);

                dontDisplayNode.style.marginLeft += `15px`;

                dontDisplayNode.classList.add("dont_display");
            }
        }
    }

    getListEntryNodes(targetNode) {
        if (targetNode && targetNode.children.length > 0) {

            for (const entry of targetNode.children) {
                entry.classList.remove("dont_display");

                const childNodes = entry.querySelectorAll(".param_list_items, .param_list_values")

                for (const child of childNodes) {
                    child.classList.add("dont_display");
                }
            }

            targetNode.onclick = (event) => {
                event.stopPropagation();
                event.preventDefault();

                this.closeListEntryNodes(event.currentTarget);
            }
        }
    }

    closeListEntryNodes (targetNode) {
        if (targetNode && targetNode.children.length > 0) {

            const childNodes = targetNode.querySelectorAll(".param_list_items, .param_list_values");

            for (const entry of childNodes) {
                entry.classList.add("dont_display");
                entry.removeEventListener("click", this.getListEntryNodes);
            }

            targetNode.onclick = (event) => {
                event.stopPropagation();
                event.preventDefault();

                this.getListEntryNodes(event.currentTarget);
            }
        }
    }

    setAppropriateOnClick(node) {
        if (node.classList.contains("param_list_items")) {
            node.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.getListEntryNodes(event.currentTarget);
            }
        } else if (node.classList.contains("param_list_values")) {
            node.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();
            }
        }
    }
}
