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
            });
        }
    }

    focusOnDoubleClick (nodeToFocus) {
        const nodesToHide = document.querySelectorAll(".param_list_items:not(.dont_display)");
        const searchBar = document.querySelector(".searchbar > input");

        if (searchBar) {
            const searchLimiter = nodeToFocus.querySelector(".param_list_keys");
            searchBar.value = searchLimiter? ""+searchLimiter.innerText : "";
        }

        for (const hideNode of nodesToHide) {
            hideNode.classList.add("dont_display");
        }

        // later removed here, because it is more performant than reformatting the entire list into an array and splicing this note or having an if condition
        // executed on every turn in the for loop form before
        nodeToFocus.classList.remove("dont_display");

        if (nodeToFocus) {
            nodeToFocus.ondblclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.restoreFocusToNormal(event.currentTarget);
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

        if (nodeToFocus) {
            nodeToFocus.ondblclick = ((event) => {
                event.preventDefault();
                event.stopPropagation();

                this.focusOnDoubleClick(nodeToFocus);
            });
        }

        nodeToFocus.scrollIntoView();
    }
}
