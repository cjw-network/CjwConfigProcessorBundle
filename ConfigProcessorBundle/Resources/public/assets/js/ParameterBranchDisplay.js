class ParameterBranchDisplay {

    parameterToFocus;

    constructor(parameterToFocus) {
        if (parameterToFocus && parameterToFocus.length > 0) {
            this.parameterToFocus = parameterToFocus;
        } else {
            this.parameterToFocus = [];
        }
    }

    setDoubleClickFocusListener () {
        for(const nodeToFocus of this.parameterToFocus) {
            nodeToFocus.ondblclick = ((event) => {
                event.preventDefault();
                event.stopPropagation();

                this.focusOnDoubleClick(nodeToFocus);
            });
        }
    }

    focusOnDoubleClick (nodeToFocus) {
        const nodesToHide = document.querySelectorAll(".param_list_items, .param_list_items:not(.dont_display)");

        for (const hideNode of nodesToHide) {
            if (hideNode !== nodeToFocus) {
                hideNode.classList.add("dont_display");
            }
        }

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
