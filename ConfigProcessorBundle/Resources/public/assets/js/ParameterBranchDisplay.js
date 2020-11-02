class ParameterBranchDisplay {

    parameterToFocus;

    constructor(parameterToFocus) {
        if (parameterToFocus && parameterToFocus.length > 0) {
            this.parameterToFocus = parameterToFocus;
        } else {
            this.parameterToFocus = [];
        }
    }

    setDoubleClickFocusListener (onclickFunction) {
        for(const nodeToFocus of this.parameterToFocus) {
            nodeToFocus.ondblclick = ((event) => {
                event.preventDefault();
                event.stopPropagation();

                this.focusOnDoubleClick(nodeToFocus);
                this.flipDoubleClickListener(nodeToFocus,false);
            });
        }
    }

    focusOnDoubleClick (nodeToFocus) {
        const nodesToHide = document.querySelectorAll(".param_list_items:not(.dont_display)");
        const searchBar = document.querySelector(".searchbar > input");
        const searchLimiter = nodeToFocus.querySelector(".param_list_keys");

        if (searchBar) {
            searchBar.value = searchLimiter? searchLimiter.getAttribute("key")+":" : "";
        }

        for (const hideNode of nodesToHide) {
            hideNode.classList.add("dont_display");
        }

        // this is removed here, because it is more performant than reformatting the entire list into an array and splicing this note or having an if-condition
        // executed on every turn in the for loop from before
        nodeToFocus.classList.remove("dont_display");

        this.displayEntireBranch(nodeToFocus);

        if (document.querySelector(".second_list")) {
            let testText = `[key="${searchLimiter.getAttribute("key")}"]`;
            const desiredNodes = document.querySelectorAll(`[key="${searchLimiter.getAttribute("key")}"]`);

            if (desiredNodes.length > 1) {
                const desiredNode = (desiredNodes[0].parentElement === nodeToFocus)? desiredNodes[1].parentElement : desiredNodes[0].parentElement;
                desiredNode.classList.remove("dont_display");
                this.flipDoubleClickListener(desiredNode,false);

                this.displayEntireBranch(desiredNode);
            }
        }
    }

    displayEntireBranch (nodeToFocus) {
        if(nodeToFocus) {
            let childNodes = nodeToFocus.querySelectorAll(".param_list_items");
            childNodes = childNodes? Array.from(childNodes) : [];
            childNodes.push(nodeToFocus);

            this.asynchronouslyDisplayEntireBranch(childNodes);
        }
    }

    asynchronouslyDisplayEntireBranch (nodeList) {
        if (nodeList && nodeList.length > 0) {
            const concurrentNodes = nodeList.length > 40? nodeList.splice(0,40) : nodeList.splice(0,nodeList.length);

            for (const node of concurrentNodes) {
                let event = new Event("click");
                node.dispatchEvent(event);
            }

            if (nodeList.length > 0) {
                setTimeout(() => {
                    this.asynchronouslyDisplayEntireBranch(nodeList);
                });
            }
        }
    }

    restoreFocusToNormal (nodeToFocus) {
        const standardNodeList = document.querySelectorAll(".param_list > .param_list_items");
        const searchBar = document.querySelector(".searchbar > input");

        if (searchBar) {
            searchBar.value = "";
        }

        if (standardNodeList) {
            for (const upperLevelNode of standardNodeList) {
                upperLevelNode.classList.remove("dont_display");
            }
        }

        nodeToFocus.scrollIntoView();
    }

    flipDoubleClickListener(nodeToFocus, isActiveThen) {
        if (nodeToFocus && typeof isActiveThen === "boolean") {

            if (!isActiveThen) {
                nodeToFocus.ondblclick = ((event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    this.focusOnDoubleClick(nodeToFocus);
                    this.flipDoubleClickListener(nodeToFocus,true);
                });
            } else {
                nodeToFocus.ondblclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    this.restoreFocusToNormal(event.currentTarget);
                    this.flipDoubleClickListener(nodeToFocus, false);
                }
            }
        }
    }

    setDoubleClickListenerForRemainingNodes() {
        let notTopNodeKeys = document.querySelectorAll(".param_list_keys:not(.top_nodes)");

        if (notTopNodeKeys) {
            for (const key of notTopNodeKeys) {
                if (key.classList.contains("top_nodes")) {
                    key.ondblclick = (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        key.scrollIntoView();
                        key.style.backgroundColor = "#eaA415";
                        setTimeout(() => {
                            key.style.backgroundColor = "";
                        },2000);
                    }
                }
            }
        }
    }
}
