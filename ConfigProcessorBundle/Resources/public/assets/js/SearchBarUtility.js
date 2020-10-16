class SearchBarUtility {

    header;
    mainSection;

    constructor() {
        this.header = document.querySelector(".cjw_header");
        this.mainSection = document.querySelector(".cjw_main_section");
    }

    setUpSearchBar() {
        if (this.header) {
            const searchForm = document.createElement("form");
            const searchField = document.createElement("input");
            searchField.type="text";

            searchForm.appendChild(searchField);

            searchField.addEventListener("input", (event) => {
                event.preventDefault();
                this.reactToSearchInput(event.target.value);
            })

            this.header.appendChild(searchForm);
        }
    }

    reactToSearchInput(searchText) {
        if (searchText.trim().length === 0) {

            return;
        }

        if (this.mainSection) {
            const highlightedNode = document.querySelector(".first_search_result");
            if (highlightedNode) {
                highlightedNode.classList.toggle("first_search_result");
            }

            this.removeRemainingIrrelevantResults(searchText);

            if (searchText.trim().length > 0) {
                const possibleResults = this.mainSection.querySelectorAll(`[key^="${searchText}" i]`);

                if (possibleResults.length > 0) {
                    possibleResults[0].scrollIntoView();
                    possibleResults[0].classList.toggle("first_search_result");
                }

                for (const result of possibleResults) {
                    this.createNodeListToRoot(result);
                }
            }
        }
    }

    createNodeListToRoot (node) {
        // const nodeList = [];

        // if (node.classList.contains("dont_display")) {
        if (node.offsetParent === null || node.classList.contains("dont_display")) {
            const nodeParent = node.parentElement;

            if (nodeParent) {
                if (nodeParent.previousElementSibling) {
                    nodeParent.previousElementSibling.classList.remove("dont_display");
                }

                // nodeList.push(this.createNodeListToRoot(node.parent));
                this.createNodeListToRoot(nodeParent);

                if (!node.classList.contains("param_list_keys")) {
                    let marginLeft = nodeParent.style.marginLeft.replace("px", "");
                    marginLeft = (marginLeft.length < 1) ? 0 : parseInt(marginLeft);

                    node.style.marginLeft += `${marginLeft + 10}px`;
                }
            }

            node.classList.remove("dont_display");
        }

        // return nodeList;
    }

    removeRemainingIrrelevantResults (searchText) {
        const nonRelevantVisibleResults = this.mainSection.querySelectorAll(`div:not(.dont_display):not([key^="${searchText}" i]), [key]`)
        for (const nonRelevantResult of nonRelevantVisibleResults) {
            nonRelevantResult.classList.add("dont_display");
        }
    }

    resetList() {
        this.removeRemainingIrrelevantResults("");
        const rootNodes = document.querySelectorAll(".param_list > .param_list_items");

        for (const node of rootNodes) {
            node.classList.remove("dont_display");

            if (node.children.length > 0) {

            }
        }
    }
}
