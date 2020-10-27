class ParameterDisplay {

    paramBranchDisplay;

    constructor(paramBranchDisplay) {
        this.paramBranchDisplay = paramBranchDisplay;
    }

    cleanUpList() {
        const topNodes = document.querySelectorAll(".param_list > .param_list_items");

        this.setTopNodesAsynchronously(0,topNodes);

        this.paramBranchDisplay.setDoubleClickFocusListener();
        this.paramBranchDisplay.setDoubleClickListenerForRemainingNodes();
    }

    async setTopNodesAsynchronously (counter, nodeList) {

        if (nodeList && nodeList.length > counter >= 0) {

            do {
                const topNodeEntry = nodeList[counter];

                if (topNodeEntry) {
                    this.setAppropriateOnClick(topNodeEntry);
                    topNodeEntry.classList.remove("dont_display");

                    const dontDisplayChildNodes = topNodeEntry.querySelectorAll(".param_list_items, .param_list_values");

                    setTimeout(() => {
                        this.cleanUpChildNodesAsynchronously(0, dontDisplayChildNodes);
                    });

                    const topKey = topNodeEntry.querySelector(".param_list_keys");

                    if (topKey) {
                        topKey.classList.add("top_nodes");
                    }
                }

                ++counter;
            } while (counter < nodeList.length && (counter % 30 !== 0))

            if (counter < nodeList.length) {
                setTimeout(() => {
                    this.setTopNodesAsynchronously(counter, nodeList);
                });
            }
        }
    }

    cleanUpChildNodesAsynchronously (counter, nodeList)  {
        if (nodeList && nodeList.length > counter >= 0) {
            do {
                const currentNode = nodeList[counter];

                if (currentNode) {
                    this.setAppropriateOnClick(currentNode);
                    currentNode.style.marginLeft += "10px";
                }

                ++counter;
            } while (counter < nodeList.length && (counter % 40 !== 0))

            if (counter < nodeList.length) {
                setTimeout(() => {
                    this.cleanUpChildNodesAsynchronously(counter,nodeList);
                });
            }
        }
    }

    getListEntryNodes(targetNode) {
        if (targetNode && targetNode.children.length > 0) {

            for (const entry of targetNode.children) {
                entry.classList.remove("dont_display");
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

                entry.onclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    this.getListEntryNodes(event.currentTarget);
                };
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
